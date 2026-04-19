<?php
/**
 * Admin settings page registration and rendering.
 *
 * @package AIAW
 */

defined( 'ABSPATH' ) || exit;

class AIAW_Settings {

	private static $instance = null;

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'admin_menu', array( $this, 'register_menus' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );

		add_action( 'wp_ajax_aiaw_test_api', array( $this, 'ajax_test_api' ) );
		add_action( 'wp_ajax_aiaw_fetch_models', array( $this, 'ajax_fetch_models' ) );
		add_action( 'wp_ajax_aiaw_save_template', array( $this, 'ajax_save_template' ) );
		add_action( 'wp_ajax_aiaw_delete_template', array( $this, 'ajax_delete_template' ) );
		add_action( 'wp_ajax_aiaw_get_templates', array( $this, 'ajax_get_templates' ) );
	}

	public function register_menus() {
		add_menu_page(
			__( 'AI-assisted Writing', 'ai-assisted-writing' ),
			__( 'AI-assisted Writing', 'ai-assisted-writing' ),
			'manage_options',
			'ai-assisted-writing',
			array( $this, 'render_settings_page' ),
			'dashicons-edit-large',
			30
		);

		add_submenu_page(
			'ai-assisted-writing',
			__( 'Settings', 'ai-assisted-writing' ),
			__( 'Settings', 'ai-assisted-writing' ),
			'manage_options',
			'ai-assisted-writing',
			array( $this, 'render_settings_page' )
		);

		add_submenu_page(
			'ai-assisted-writing',
			__( 'Writing Assistant', 'ai-assisted-writing' ),
			__( 'Writing Assistant', 'ai-assisted-writing' ),
			'edit_posts',
			'ai-assisted-writing-writing',
			array( $this, 'render_writing_page' )
		);
	}

	public function register_settings() {
		register_setting( 'aiaw_settings_group', 'aiaw_api_settings', array(
			'sanitize_callback' => array( $this, 'sanitize_api_settings' ),
		) );
	}

	public function sanitize_api_settings( $input ) {
		$clean = array();
		$clean['api_url']       = esc_url_raw( $input['api_url'] ?? '' );
		$clean['api_key']       = sanitize_text_field( $input['api_key'] ?? '' );
		$clean['primary_model'] = sanitize_text_field( $input['primary_model'] ?? '' );
		$clean['backup_model']  = sanitize_text_field( $input['backup_model'] ?? '' );
		$clean['debug_mode']   = ! empty( $input['debug_mode'] ) ? 1 : 0;
		return $clean;
	}

	public function ajax_test_api() {
		check_ajax_referer( 'aiaw_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'ai-assisted-writing' ) ) );
		}

		$api_url = isset( $_POST['api_url'] ) ? esc_url_raw( wp_unslash( $_POST['api_url'] ) ) : '';
		$api_key = isset( $_POST['api_key'] ) ? sanitize_text_field( wp_unslash( $_POST['api_key'] ) ) : '';

		$result = AIAW_API::get_instance()->test_connection( $api_url, $api_key );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		wp_send_json_success( array( 'message' => __( 'Connection successful!', 'ai-assisted-writing' ) ) );
	}

	public function ajax_fetch_models() {
		check_ajax_referer( 'aiaw_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'ai-assisted-writing' ) ) );
		}

		$api_url = isset( $_POST['api_url'] ) ? esc_url_raw( wp_unslash( $_POST['api_url'] ) ) : '';
		$api_key = isset( $_POST['api_key'] ) ? sanitize_text_field( wp_unslash( $_POST['api_key'] ) ) : '';

		$result = AIAW_API::get_instance()->fetch_models( $api_url, $api_key );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		wp_send_json_success( array( 'models' => $result ) );
	}

	public function ajax_save_template() {
		check_ajax_referer( 'aiaw_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'ai-assisted-writing' ) ) );
		}

		$action_type = sanitize_text_field( wp_unslash( $_POST['template_action'] ) );
		$tpl    = AIAW_Template::get_instance();
		$result = false;

		switch ( $action_type ) {
			case 'add_category':
				$result = $tpl->add_category(
					sanitize_text_field( wp_unslash( $_POST['name'] ) ),
					absint( $_POST['wp_category_id'] )
				);
				break;
			case 'update_category':
				$result = $tpl->update_category(
					sanitize_text_field( wp_unslash( $_POST['id'] ) ),
					sanitize_text_field( wp_unslash( $_POST['name'] ) ),
					absint( $_POST['wp_category_id'] )
				);
				break;
			case 'add_topic':
				$result = $tpl->add_topic(
					sanitize_text_field( wp_unslash( $_POST['category_id'] ) ),
					sanitize_text_field( wp_unslash( $_POST['name'] ) ),
					wp_kses_post( wp_unslash( $_POST['prompt'] ) )
				);
				break;
			case 'update_topic':
				$result = $tpl->update_topic(
					sanitize_text_field( wp_unslash( $_POST['category_id'] ) ),
					sanitize_text_field( wp_unslash( $_POST['id'] ) ),
					sanitize_text_field( wp_unslash( $_POST['name'] ) ),
					wp_kses_post( wp_unslash( $_POST['prompt'] ) )
				);
				break;
		}

		if ( $result ) {
			wp_send_json_success( array( 'templates' => $tpl->get_all() ) );
		}

		wp_send_json_error( array( 'message' => __( 'Failed to save template.', 'ai-assisted-writing' ) ) );
	}

	public function ajax_delete_template() {
		check_ajax_referer( 'aiaw_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'ai-assisted-writing' ) ) );
		}

		$action_type = sanitize_text_field( wp_unslash( $_POST['template_action'] ) );
		$tpl    = AIAW_Template::get_instance();
		$result = false;

		switch ( $action_type ) {
			case 'delete_category':
				$result = $tpl->delete_category( sanitize_text_field( wp_unslash( $_POST['id'] ) ) );
				break;
			case 'delete_topic':
				$result = $tpl->delete_topic(
					sanitize_text_field( wp_unslash( $_POST['category_id'] ) ),
					sanitize_text_field( wp_unslash( $_POST['id'] ) )
				);
				break;
		}

		if ( $result ) {
			wp_send_json_success( array( 'templates' => $tpl->get_all() ) );
		}

		wp_send_json_error( array( 'message' => __( 'Failed to delete.', 'ai-assisted-writing' ) ) );
	}

	public function ajax_get_templates() {
		check_ajax_referer( 'aiaw_nonce', 'nonce' );
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'ai-assisted-writing' ) ) );
		}

		$category_id = absint( $_POST['category_id'] );
		$all         = AIAW_Template::get_instance()->get_all();
		$topics      = array();

		foreach ( $all as $cat ) {
			if ( absint( $cat['wp_category_id'] ) === $category_id && ! empty( $cat['topics'] ) ) {
				foreach ( $cat['topics'] as $t ) {
					$topics[] = array( 'id' => $t['id'], 'name' => $t['name'] );
				}
			}
		}

		wp_send_json_success( array( 'topics' => $topics ) );
	}

	public function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$settings      = get_option( 'aiaw_api_settings', array() );
		$templates     = AIAW_Template::get_instance()->get_all();
		$categories    = get_categories( array( 'hide_empty' => false ) );
		$active_tab    = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : 'api';
		$saved_primary = $settings['primary_model'] ?? '';
		$saved_backup  = $settings['backup_model'] ?? '';

		echo '<div class="wrap">';
		echo '<h1>' . esc_html__( 'AI-assisted Writing Settings', 'ai-assisted-writing' ) . '</h1>';

		echo '<nav class="nav-tab-wrapper">';
		echo '<a href="' . esc_url( admin_url( 'admin.php?page=ai-assisted-writing&tab=api' ) ) . '" class="nav-tab' . ( $active_tab === 'api' ? ' nav-tab-active' : '' ) . '">' . esc_html__( 'API Settings', 'ai-assisted-writing' ) . '</a>';
		echo '<a href="' . esc_url( admin_url( 'admin.php?page=ai-assisted-writing&tab=templates' ) ) . '" class="nav-tab' . ( $active_tab === 'templates' ? ' nav-tab-active' : '' ) . '">' . esc_html__( 'AI Templates', 'ai-assisted-writing' ) . '</a>';
		echo '</nav>';

		if ( $active_tab === 'api' ) {
			include AIAW_PLUGIN_PATH . 'admin/views/settings-api.php';
		} else {
			include AIAW_PLUGIN_PATH . 'admin/views/settings-template.php';
		}

		echo '</div>';
	}

	public function render_writing_page() {
		if ( ! current_user_can( 'edit_posts' ) ) {
			return;
		}

		$templates   = AIAW_Template::get_instance()->get_all();
		$categories  = get_categories( array( 'hide_empty' => false ) );
		$settings    = get_option( 'aiaw_api_settings', array() );
		$has_api_key = ! empty( $settings['api_key'] );

		include AIAW_PLUGIN_PATH . 'admin/views/writing-assistant.php';
	}
}
