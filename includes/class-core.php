<?php
/**
 * Plugin lifecycle and initialization.
 *
 * @package AIAW
 */

defined( 'ABSPATH' ) || exit;

class AIAW_Core {

	private static $instance = null;

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'init', array( $this, 'init' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );

		register_activation_hook( AIAW_PLUGIN_PATH . 'wp-ai-writer.php', array( $this, 'activate' ) );
		register_deactivation_hook( AIAW_PLUGIN_PATH . 'wp-ai-writer.php', array( $this, 'deactivate' ) );
	}

	public function init() {
		AIAW_i18n::get_instance();
		AIAW_Settings::get_instance();
		AIAW_Generator::get_instance();
	}

	public function activate() {
		// Migrate from old option keys before creating defaults
		$this->migrate_old_options();

		$defaults = array(
			'api_url'       => 'https://api.openai.com',
			'api_key'       => '',
			'primary_model' => '',
			'backup_model'  => '',
		);
		if ( false === get_option( 'aiaw_api_settings' ) ) {
			add_option( 'aiaw_api_settings', $defaults );
		}
		if ( false === get_option( 'aiaw_templates' ) ) {
			add_option( 'aiaw_templates', array() );
		}

		flush_rewrite_rules();
	}

	public function deactivate() {
		flush_rewrite_rules();
	}

	private function migrate_old_options() {
		$old_settings = get_option( 'wpaiwriter_api_settings' );
		if ( false !== $old_settings && false === get_option( 'aiaw_api_settings' ) ) {
			add_option( 'aiaw_api_settings', $old_settings );
			delete_option( 'wpaiwriter_api_settings' );
		}

		$old_templates = get_option( 'wpaiwriter_templates' );
		if ( false !== $old_templates && false === get_option( 'aiaw_templates' ) ) {
			add_option( 'aiaw_templates', $old_templates );
			delete_option( 'wpaiwriter_templates' );
		}
	}

	public function enqueue_admin_assets( $hook ) {
		$screen_id = get_current_screen()->id;
		$plugin_screens = array(
			'toplevel_page_ai-assisted-writing',
			'ai-assisted-writing_page_ai-assisted-writing-writing',
		);

		if ( ! in_array( $screen_id, $plugin_screens, true ) ) {
			return;
		}

		wp_enqueue_style(
			'aiaw-admin',
			AIAW_PLUGIN_URL . 'admin/css/admin.css',
			array(),
			AIAW_VERSION
		);

		wp_enqueue_script(
			'aiaw-admin',
			AIAW_PLUGIN_URL . 'admin/js/admin.js',
			array( 'jquery' ),
			AIAW_VERSION,
			true
		);

		wp_localize_script( 'aiaw-admin', 'aiaw', array(
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'nonce'    => wp_create_nonce( 'aiaw_nonce' ),
			'strings'  => array(
				'testing'         => __( 'Testing connection...', 'ai-assisted-writing' ),
				'success'         => __( 'Connection successful!', 'ai-assisted-writing' ),
				'failed'          => __( 'Connection failed.', 'ai-assisted-writing' ),
				'fetching'        => __( 'Fetching models...', 'ai-assisted-writing' ),
				'fetch_ok'        => __( 'models found.', 'ai-assisted-writing' ),
				'fetch_empty'     => __( 'No models found. You can select "Custom" to enter model ID manually.', 'ai-assisted-writing' ),
				'generating'      => __( 'Generating...', 'ai-assisted-writing' ),
				'gen_outline'     => __( 'Generating outline...', 'ai-assisted-writing' ),
				'expanding'       => __( 'Expanding section...', 'ai-assisted-writing' ),
				'saving'          => __( 'Saving...', 'ai-assisted-writing' ),
				'saved'           => __( 'Article saved!', 'ai-assisted-writing' ),
				'select_cat'      => __( 'Please select a category.', 'ai-assisted-writing' ),
				'select_topic'    => __( 'Please select a topic.', 'ai-assisted-writing' ),
				'confirm_del_cat' => __( 'Delete this category and all its topics?', 'ai-assisted-writing' ),
				'confirm_del_topic' => __( 'Delete this topic?', 'ai-assisted-writing' ),
				'no_sections'     => __( 'No H2 sections found in the outline.', 'ai-assisted-writing' ),
				'gen_content_first' => __( 'Generate content first.', 'ai-assisted-writing' ),
				'title_required'  => __( 'Title and content are required.', 'ai-assisted-writing' ),
				'req_failed'      => __( 'Request failed.', 'ai-assisted-writing' ),
				'save_err'        => __( 'Error saving template.', 'ai-assisted-writing' ),
				'del_err'         => __( 'Error deleting.', 'ai-assisted-writing' ),
				'tags_err'        => __( 'Failed to generate tags.', 'ai-assisted-writing' ),
			),
		) );
	}
}
