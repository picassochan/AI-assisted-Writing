<?php
/**
 * OpenAI-compatible API client.
 *
 * @package AIAW
 */

defined( 'ABSPATH' ) || exit;

class AIAW_API {

	private static $instance = null;

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function get_settings() {
		return get_option( 'aiaw_api_settings', array(
			'api_url'       => 'https://api.openai.com',
			'api_key'       => '',
			'primary_model' => '',
			'backup_model'  => '',
		) );
	}

	private function build_models_url( $api_url ) {
		$url = rtrim( $api_url, '/' );
		if ( preg_match( '#/v1/?$#', $url ) ) {
			return $url . '/models';
		}
		return $url . '/v1/models';
	}

	private function build_chat_url( $api_url ) {
		$url = rtrim( $api_url, '/' );
		if ( preg_match( '#/v1/?$#', $url ) ) {
			return $url . '/chat/completions';
		}
		return $url . '/v1/chat/completions';
	}

	public function test_connection( $api_url = null, $api_key = null ) {
		$settings = $this->get_settings();
		$url = ! empty( $api_url ) ? $api_url : $settings['api_url'];
		$key = ! empty( $api_key ) ? $api_key : $settings['api_key'];

		if ( empty( $key ) ) {
			return new WP_Error( 'no_api_key', __( 'API key is required.', 'ai-assisted-writing' ) );
		}

		$models_url = $this->build_models_url( $url );

		$response = wp_remote_get( $models_url, array(
			'headers' => array(
				'Authorization' => 'Bearer ' . $key,
				'Content-Type'  => 'application/json',
			),
			'timeout' => 15,
		) );

		if ( is_wp_error( $response ) ) {
			return new WP_Error( 'connection_error', $response->get_error_message() );
		}

		$code     = wp_remote_retrieve_response_code( $response );
		$raw_body = wp_remote_retrieve_body( $response );

		if ( 200 !== $code ) {
			$body = json_decode( $raw_body, true );
			$msg  = isset( $body['error']['message'] ) ? $body['error']['message'] : '';
			return new WP_Error( 'api_error', sprintf(
				/* translators: 1: HTTP status code, 2: error message */
				__( 'API returned HTTP %1$d. %2$s', 'ai-assisted-writing' ),
				$code,
				$msg
			) );
		}

		return true;
	}

	public function fetch_models( $api_url = null, $api_key = null ) {
		$settings = $this->get_settings();
		$url = ! empty( $api_url ) ? $api_url : $settings['api_url'];
		$key = ! empty( $api_key ) ? $api_key : $settings['api_key'];

		if ( empty( $key ) ) {
			return new WP_Error( 'no_api_key', __( 'API key is required.', 'ai-assisted-writing' ) );
		}

		$models_url = $this->build_models_url( $url );

		$response = wp_remote_get( $models_url, array(
			'headers' => array(
				'Authorization' => 'Bearer ' . $key,
				'Content-Type'  => 'application/json',
			),
			'timeout' => 15,
		) );

		if ( is_wp_error( $response ) ) {
			return new WP_Error( 'connection_error', $response->get_error_message() );
		}

		$code     = wp_remote_retrieve_response_code( $response );
		$raw_body = wp_remote_retrieve_body( $response );
		$body     = json_decode( $raw_body, true );

		if ( 200 !== $code ) {
			return new WP_Error( 'api_error', sprintf(
				__( 'API returned HTTP %d.', 'ai-assisted-writing' ),
				$code
			) );
		}

		return $this->parse_models( $body );
	}

	private function parse_models( $body ) {
		$models = array();

		// Standard OpenAI format: { "data": [ { "id": "gpt-4", ... } ] }
		if ( isset( $body['data'] ) && is_array( $body['data'] ) ) {
			foreach ( $body['data'] as $model ) {
				if ( isset( $model['id'] ) ) {
					$models[] = array( 'id' => $model['id'], 'name' => $model['id'] );
				}
			}
		}

		// Some APIs nest under "models" key
		if ( empty( $models ) && isset( $body['models'] ) && is_array( $body['models'] ) ) {
			foreach ( $body['models'] as $model ) {
				$id = is_string( $model ) ? $model : ( isset( $model['id'] ) ? $model['id'] : ( isset( $model['name'] ) ? $model['name'] : '' ) );
				if ( $id ) {
					$models[] = array( 'id' => $id, 'name' => $id );
				}
			}
		}

		// Flat array of objects: [ { "id": "..." } ]
		if ( empty( $models ) && is_array( $body ) && ! isset( $body['data'] ) && ! isset( $body['models'] ) ) {
			foreach ( $body as $item ) {
				if ( is_array( $item ) && isset( $item['id'] ) ) {
					$models[] = array( 'id' => $item['id'], 'name' => $item['id'] );
				} elseif ( is_string( $item ) ) {
					$models[] = array( 'id' => $item, 'name' => $item );
				}
			}
		}

		usort( $models, function( $a, $b ) {
			return strcmp( $a['id'], $b['id'] );
		} );

		return $models;
	}

	public function generate( $messages, $model = null, $max_retries = 1 ) {
		$settings = $this->get_settings();
		$url = $settings['api_url'];
		$key = $settings['api_key'];

		if ( empty( $key ) ) {
			return new WP_Error( 'no_api_key', __( 'API key is required.', 'ai-assisted-writing' ) );
		}

		$models_to_try = array();
		if ( ! empty( $model ) ) {
			$models_to_try[] = $model;
		}
		if ( ! empty( $settings['primary_model'] ) && ! in_array( $settings['primary_model'], $models_to_try, true ) ) {
			$models_to_try[] = $settings['primary_model'];
		}
		if ( ! empty( $settings['backup_model'] ) && ! in_array( $settings['backup_model'], $models_to_try, true ) ) {
			$models_to_try[] = $settings['backup_model'];
		}

		if ( empty( $models_to_try ) ) {
			return new WP_Error( 'no_model', __( 'No model configured.', 'ai-assisted-writing' ) );
		}

		$chat_url   = $this->build_chat_url( $url );
		$last_error = null;

		foreach ( $models_to_try as $try_model ) {
			$body = array(
				'model'    => $try_model,
				'messages' => $messages,
			);

			$response = wp_remote_post( $chat_url, array(
				'headers' => array(
					'Authorization' => 'Bearer ' . $key,
					'Content-Type'  => 'application/json',
				),
				'body'    => wp_json_encode( $body ),
				'timeout' => 120,
			) );

			if ( is_wp_error( $response ) ) {
				$last_error = $response;
				continue;
			}

			$code      = wp_remote_retrieve_response_code( $response );
			$resp_body = json_decode( wp_remote_retrieve_body( $response ), true );

			if ( 200 !== $code ) {
				$last_error = new WP_Error( 'api_error', __( 'API request failed. Please check your settings.', 'ai-assisted-writing' ) );
				continue;
			}

			if ( isset( $resp_body['choices'][0]['message']['content'] ) ) {
				return $resp_body['choices'][0]['message']['content'];
			}

			$last_error = new WP_Error( 'empty_response', __( 'Empty response from API.', 'ai-assisted-writing' ) );
		}

		return $last_error ? $last_error : new WP_Error( 'all_failed', __( 'All models failed.', 'ai-assisted-writing' ) );
	}

	/**
	 * Stream chat completion via cURL, forwarding SSE chunks to callback.
	 *
	 * @param array    $messages Chat messages.
	 * @param callable $callback Receives raw SSE lines from API.
	 * @param string   $model    Optional model override.
	 * @return true|WP_Error
	 */
	public function generate_stream( $messages, $callback, $model = null ) {
		$settings = $this->get_settings();
		$url = $settings['api_url'];
		$key = $settings['api_key'];

		if ( empty( $key ) ) {
			return new WP_Error( 'no_api_key', __( 'API key is required.', 'ai-assisted-writing' ) );
		}

		$models_to_try = array();
		if ( ! empty( $model ) ) {
			$models_to_try[] = $model;
		}
		if ( ! empty( $settings['primary_model'] ) && ! in_array( $settings['primary_model'], $models_to_try, true ) ) {
			$models_to_try[] = $settings['primary_model'];
		}
		if ( ! empty( $settings['backup_model'] ) && ! in_array( $settings['backup_model'], $models_to_try, true ) ) {
			$models_to_try[] = $settings['backup_model'];
		}

		if ( empty( $models_to_try ) ) {
			return new WP_Error( 'no_model', __( 'No model configured.', 'ai-assisted-writing' ) );
		}

		$chat_url   = $this->build_chat_url( $url );
		$last_error = null;

		foreach ( $models_to_try as $try_model ) {
			$body = array(
				'model'    => $try_model,
				'messages' => $messages,
				'stream'   => true,
			);

			$result = $this->curl_stream( $chat_url, $key, $body, $callback );
			if ( ! is_wp_error( $result ) ) {
				return true;
			}
			$last_error = $result;
		}

		return $last_error ? $last_error : new WP_Error( 'all_failed', __( 'All models failed.', 'ai-assisted-writing' ) );
	}

	/**
	 * Execute a streaming cURL POST with SSE write callback.
	 *
	 * @param string   $url      API chat URL.
	 * @param string   $key      API key.
	 * @param array    $body     Request body.
	 * @param callable $callback Called with each raw chunk.
	 * @return true|WP_Error
	 */
	private function curl_stream( $url, $key, $body, $callback ) {
		if ( ! function_exists( 'curl_init' ) ) {
			return new WP_Error( 'no_curl', __( 'cURL is required for streaming.', 'ai-assisted-writing' ) );
		}

		$ch = curl_init( $url );
		curl_setopt_array( $ch, array(
			CURLOPT_POST           => true,
			CURLOPT_POSTFIELDS     => wp_json_encode( $body ),
			CURLOPT_HTTPHEADER     => array(
				'Authorization: Bearer ' . $key,
				'Content-Type: application/json',
				'Accept: text/event-stream',
			),
			CURLOPT_TIMEOUT        => 120,
			CURLOPT_WRITEFUNCTION  => function( $ch, $data ) use ( $callback ) {
				call_user_func( $callback, $data );
				return strlen( $data );
			},
			CURLOPT_SSL_VERIFYPEER => true,
		) );

		$result   = curl_exec( $ch );
		$httpcode = curl_getinfo( $ch, CURLINFO_HTTP_CODE );
		$error    = curl_error( $ch );
		curl_close( $ch );

		if ( false === $result ) {
			return new WP_Error( 'curl_error', $error ?: __( 'Streaming request failed.', 'ai-assisted-writing' ) );
		}

		if ( 200 !== $httpcode ) {
			return new WP_Error( 'api_error', sprintf(
				__( 'API returned HTTP %d.', 'ai-assisted-writing' ),
				$httpcode
			) );
		}

		return true;
	}
}
