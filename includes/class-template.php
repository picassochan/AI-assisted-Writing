<?php
/**
 * Template CRUD management.
 *
 * @package AIAW
 */

defined( 'ABSPATH' ) || exit;

class AIAW_Template {

	private static $instance = null;

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function get_all() {
		return get_option( 'aiaw_templates', array() );
	}

	public function save_all( $templates ) {
		$templates = $this->sanitize_templates( $templates );
		return update_option( 'aiaw_templates', $templates );
	}

	public function get_category( $cat_id ) {
		$templates = $this->get_all();
		foreach ( $templates as $cat ) {
			if ( $cat['id'] === $cat_id ) {
				return $cat;
			}
		}
		return null;
	}

	public function get_topic( $cat_id, $topic_id ) {
		$cat = $this->get_category( $cat_id );
		if ( ! $cat ) {
			return null;
		}
		foreach ( $cat['topics'] as $topic ) {
			if ( $topic['id'] === $topic_id ) {
				return $topic;
			}
		}
		return null;
	}

	public function add_category( $name, $wp_category_id ) {
		$templates   = $this->get_all();
		$templates[] = array(
			'id'             => $this->generate_id( 'cat' ),
			'name'           => sanitize_text_field( $name ),
			'wp_category_id' => absint( $wp_category_id ),
			'topics'         => array(),
		);
		return $this->save_all( $templates );
	}

	public function update_category( $cat_id, $name, $wp_category_id ) {
		$templates = $this->get_all();
		foreach ( $templates as &$cat ) {
			if ( $cat['id'] === $cat_id ) {
				$cat['name']           = sanitize_text_field( $name );
				$cat['wp_category_id'] = absint( $wp_category_id );
				break;
			}
		}
		unset( $cat );
		return $this->save_all( $templates );
	}

	public function delete_category( $cat_id ) {
		$templates = $this->get_all();
		$templates = array_filter( $templates, function( $cat ) use ( $cat_id ) {
			return $cat['id'] !== $cat_id;
		} );
		return $this->save_all( array_values( $templates ) );
	}

	public function add_topic( $cat_id, $name, $prompt ) {
		$templates = $this->get_all();
		foreach ( $templates as &$cat ) {
			if ( $cat['id'] === $cat_id ) {
				$cat['topics'][] = array(
					'id'     => $this->generate_id( 'topic' ),
					'name'   => sanitize_text_field( $name ),
					'prompt' => wp_kses_post( $prompt ),
				);
				break;
			}
		}
		unset( $cat );
		return $this->save_all( $templates );
	}

	public function update_topic( $cat_id, $topic_id, $name, $prompt ) {
		$templates = $this->get_all();
		foreach ( $templates as &$cat ) {
			if ( $cat['id'] === $cat_id ) {
				foreach ( $cat['topics'] as &$topic ) {
					if ( $topic['id'] === $topic_id ) {
						$topic['name']   = sanitize_text_field( $name );
						$topic['prompt'] = wp_kses_post( $prompt );
						break;
					}
				}
				unset( $topic );
				break;
			}
		}
		unset( $cat );
		return $this->save_all( $templates );
	}

	public function delete_topic( $cat_id, $topic_id ) {
		$templates = $this->get_all();
		foreach ( $templates as &$cat ) {
			if ( $cat['id'] === $cat_id ) {
				$cat['topics'] = array_filter( $cat['topics'], function( $topic ) use ( $topic_id ) {
					return $topic['id'] !== $topic_id;
				} );
				$cat['topics'] = array_values( $cat['topics'] );
				break;
			}
		}
		unset( $cat );
		return $this->save_all( $templates );
	}

	private function generate_id( $prefix ) {
		return $prefix . '_' . uniqid();
	}

	private function sanitize_templates( $templates ) {
		if ( ! is_array( $templates ) ) {
			return array();
		}
		foreach ( $templates as &$cat ) {
			$cat['id']             = sanitize_text_field( $cat['id'] );
			$cat['name']           = sanitize_text_field( $cat['name'] );
			$cat['wp_category_id'] = absint( $cat['wp_category_id'] );
			foreach ( $cat['topics'] as &$topic ) {
				$topic['id']     = sanitize_text_field( $topic['id'] );
				$topic['name']   = sanitize_text_field( $topic['name'] );
				$topic['prompt'] = wp_kses_post( $topic['prompt'] );
			}
			unset( $topic );
		}
		unset( $cat );
		return $templates;
	}
}
