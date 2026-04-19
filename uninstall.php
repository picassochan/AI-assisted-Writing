<?php
/**
 * Clean up plugin data on uninstall.
 *
 * @package AIAW
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'aiaw_api_settings' );
delete_option( 'aiaw_templates' );