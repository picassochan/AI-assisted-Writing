<?php
/**
 * Internationalization loader.
 *
 * @package AIAW
 */

defined( 'ABSPATH' ) || exit;

class AIAW_i18n {

	private static $instance = null;

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );
	}

	public function load_textdomain() {
		load_plugin_textdomain(
			'ai-assisted-writing',
			false,
			dirname( AIAW_PLUGIN_BASENAME ) . '/languages/'
		);
	}
}
