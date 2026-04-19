<?php
/**
 * AI article generation logic.
 *
 * @package AIAW
 */

defined( 'ABSPATH' ) || exit;

class AIAW_Generator {

	private static $instance = null;

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'wp_ajax_aiaw_generate', array( $this, 'ajax_generate' ) );
		add_action( 'wp_ajax_aiaw_generate_stream', array( $this, 'ajax_generate_stream' ) );
		add_action( 'wp_ajax_aiaw_create_post', array( $this, 'ajax_create_post' ) );
	}

	private function verify_request() {
		check_ajax_referer( 'aiaw_nonce', 'nonce' );
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'ai-assisted-writing' ) ) );
		}
	}

	public function ajax_generate() {
		$this->verify_request();

		$title       = sanitize_text_field( wp_unslash( $_POST['title'] ) );
		$description = sanitize_textarea_field( wp_unslash( $_POST['description'] ) );
		$template_id = sanitize_text_field( wp_unslash( $_POST['template_id'] ) );
		$category_id = absint( $_POST['category_id'] );

		if ( empty( $title ) ) {
			wp_send_json_error( array( 'message' => __( 'Please enter a title.', 'ai-assisted-writing' ) ) );
		}

		$template = $this->find_template( $template_id, $category_id );

		$prompt   = $this->build_prompt( $title, $description, $template );
		$messages = $this->build_messages( $prompt );

		$result = AIAW_API::get_instance()->generate( $messages );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		$data = $this->parse_response( $result );

		wp_send_json_success( array(
			'title'       => $data['title'],
			'content'     => $data['content'],
			'tags'        => $data['tags'],
			'category_id' => $category_id,
		) );
	}

	/**
	 * Stream article generation using SSE.
	 */
	public function ajax_generate_stream() {
		check_ajax_referer( 'aiaw_nonce', 'nonce' );
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'ai-assisted-writing' ) ) );
		}

		$title       = sanitize_text_field( wp_unslash( $_POST['title'] ) );
		$description = sanitize_textarea_field( wp_unslash( $_POST['description'] ) );
		$template_id = sanitize_text_field( wp_unslash( $_POST['template_id'] ) );
		$category_id = absint( $_POST['category_id'] );

		if ( empty( $title ) ) {
			wp_send_json_error( array( 'message' => __( 'Please enter a title.', 'ai-assisted-writing' ) ) );
		}

		$template = $this->find_template( $template_id, $category_id );

		$prompt   = $this->build_stream_prompt( $title, $description, $template );
		$messages = $this->build_stream_messages( $prompt );

		// Set SSE headers.
		header( 'Content-Type: text/event-stream' );
		header( 'Cache-Control: no-cache' );
		header( 'Connection: keep-alive' );
		header( 'X-Accel-Buffering: no' );

		// Disable all output buffering.
		while ( ob_get_level() ) {
			ob_end_clean();
		}

		$buffer = '';

		$callback = function( $raw ) use ( &$buffer ) {
			$buffer .= $raw;

			// Process complete lines.
			while ( false !== ( $pos = strpos( $buffer, "\n" ) ) ) {
				$line   = substr( $buffer, 0, $pos );
				$buffer = substr( $buffer, $pos + 1 );
				$line   = trim( $line );

				if ( '' === $line ) {
					continue;
				}
				if ( 0 !== strpos( $line, 'data: ' ) ) {
					continue;
				}

				$payload = substr( $line, 6 );

				if ( '[DONE]' === $payload ) {
					$this->send_sse( array( 'type' => 'done' ) );
					continue;
				}

				$json = json_decode( $payload, true );
				if ( ! $json || ! isset( $json['choices'][0]['delta'] ) ) {
					continue;
				}

				$delta = $json['choices'][0]['delta'];
				if ( isset( $delta['content'] ) ) {
					$this->send_sse( array(
						'type'    => 'content',
						'content' => $delta['content'],
					) );
				}
			}
		};

		$result = AIAW_API::get_instance()->generate_stream( $messages, $callback );

		if ( is_wp_error( $result ) ) {
			$this->send_sse( array(
				'type'    => 'error',
				'message' => $result->get_error_message(),
			) );
		}

		exit;
	}

	/**
	 * Send a single SSE event and flush.
	 */
	private function send_sse( $data ) {
		echo 'data: ' . wp_json_encode( $data ) . "\n\n";
		if ( ob_get_level() ) {
			ob_flush();
		}
		flush();
	}

	/**
	 * Find a template by ID within the given category.
	 */
	private function find_template( $template_id, $category_id ) {
		if ( empty( $template_id ) ) {
			return null;
		}
		$tpl = AIAW_Template::get_instance();
		foreach ( $tpl->get_all() as $cat ) {
			if ( absint( $cat['wp_category_id'] ) === $category_id ) {
				foreach ( $cat['topics'] as $topic ) {
					if ( $topic['id'] === $template_id ) {
						return $topic;
					}
				}
			}
		}
		return null;
	}

	private function parse_response( $result ) {
		// Try to parse as JSON first
		$decoded = json_decode( $result, true );
		if ( is_array( $decoded ) && isset( $decoded['content'] ) ) {
			return array(
				'title'   => $decoded['title'] ?? '',
				'content' => $decoded['content'],
				'tags'    => $decoded['tags'] ?? array(),
			);
		}

		// Fallback: extract title from first H1, rest as content
		$title   = '';
		$content = $result;

		if ( preg_match( '/^#\s+(.+)$/m', $result, $matches ) ) {
			$title   = $matches[1];
			$content = preg_replace( '/^#\s+.+$/m', '', $result, 1 );
			$content = trim( $content );
		}

		return array(
			'title'   => $title,
			'content' => $content,
			'tags'    => array(),
		);
	}

	public function ajax_create_post() {
		$this->verify_request();

		$title       = sanitize_text_field( wp_unslash( $_POST['title'] ) );
		$content     = wp_kses_post( wp_unslash( $_POST['content'] ) );
		$category_id = absint( $_POST['category_id'] );
		$tags        = isset( $_POST['tags'] ) ? array_map( 'sanitize_text_field', (array) wp_unslash( $_POST['tags'] ) ) : array();
		$status      = sanitize_text_field( wp_unslash( $_POST['status'] ) );

		if ( ! in_array( $status, array( 'draft', 'publish' ), true ) ) {
			$status = 'draft';
		}

		$post_data = array(
			'post_title'   => $title,
			'post_content' => $content,
			'post_status'  => $status,
			'post_type'    => 'post',
		);

		if ( $category_id > 0 ) {
			$post_data['post_category'] = array( $category_id );
		}

		if ( ! empty( $tags ) ) {
			$post_data['tags_input'] = $tags;
		}

		$post_id = wp_insert_post( $post_data, true );

		if ( is_wp_error( $post_id ) ) {
			wp_send_json_error( array( 'message' => $post_id->get_error_message() ) );
		}

		wp_send_json_success( array(
			'post_id'  => $post_id,
			'edit_url' => get_edit_post_link( $post_id, 'raw' ),
		) );
	}

	private function build_prompt( $title, $description, $template ) {
		$template_part = '';
		if ( $template && ! empty( $template['prompt'] ) ) {
			$template_part = sprintf(
				"\n\n%s",
				$template['prompt']
			);
		}

		$description_part = '';
		if ( ! empty( $description ) ) {
			$description_part = sprintf(
				"\n\n%s:\n%s",
				__( 'User provided description/outline', 'ai-assisted-writing' ),
				$description
			);
		}

		return sprintf(
			/* translators: %s: article title */
			__( 'Write a complete, well-structured article with the title: "%1$s"%2$s%3$s

Return your response as a JSON object with this exact structure:
{
  "title": "Your suggested title (can refine the original)",
  "content": "Full article content in markdown format (do NOT include the title as H1)",
  "tags": ["tag1", "tag2", "tag3"]
}

Requirements:
- Use proper headings (H2 for sections, H3 for subsections)
- Include an introduction and conclusion
- Write in a professional yet engaging tone
- Aim for 800-1500 words
- Use markdown formatting
- Suggest 3-5 relevant tags
- Only return the JSON object, nothing else', 'ai-assisted-writing' ),
			$title,
			$template_part,
			$description_part
		);
	}

	private function build_stream_prompt( $title, $description, $template ) {
		$template_part = '';
		if ( $template && ! empty( $template['prompt'] ) ) {
			$template_part = "\n\n" . $template['prompt'];
		}

		$description_part = '';
		if ( ! empty( $description ) ) {
			$description_part = sprintf(
				"\n\n%s:\n%s",
				__( 'User provided description/outline', 'ai-assisted-writing' ),
				$description
			);
		}

		return sprintf(
			/* translators: %s: article title */
			__( 'Write a complete, well-structured article with the title: "%1$s"%2$s%3$s

Requirements:
- Start with the title as a markdown H1 heading
- Use proper headings (H2 for sections, H3 for subsections)
- Include an introduction and conclusion
- Write in a professional yet engaging tone
- Aim for 800-1500 words
- Use markdown formatting', 'ai-assisted-writing' ),
			$title,
			$template_part,
			$description_part
		);
	}

	private function build_messages( $prompt ) {
		return array(
			array(
				'role'    => 'system',
				'content' => __( 'You are a professional content writer for a WordPress blog. Write clear, engaging, and SEO-friendly content. Always respond in the same language as the user\'s request. You must respond with valid JSON only.', 'ai-assisted-writing' ),
			),
			array(
				'role'    => 'user',
				'content' => $prompt,
			),
		);
	}

	private function build_stream_messages( $prompt ) {
		return array(
			array(
				'role'    => 'system',
				'content' => __( 'You are a professional content writer for a WordPress blog. Write clear, engaging, and SEO-friendly content. Always respond in the same language as the user\'s request. Output markdown directly.', 'ai-assisted-writing' ),
			),
			array(
				'role'    => 'user',
				'content' => $prompt,
			),
		);
	}
}
