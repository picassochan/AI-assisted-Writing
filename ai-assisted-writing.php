<?php
/**
 * Plugin Name: AI-assisted Writing
 * Plugin URI:  https://github.com/picassochan/AI-assisted-Writing
 * Description: AI-powered article generation for WordPress using OpenAI-compatible APIs.
 * Version:     1.1.4
 * Author:      Picasso Chan
 * Author URI:  https://github.com/picassochan/AI-assisted-Writing
 * License:     GPL-2.0+
 * Text Domain: ai-assisted-writing
 * Domain Path: /languages
 *
 * @package AIAW
 */

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/plugin-update-checker/plugin-update-checker.php';
use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

$updateChecker = PucFactory::buildUpdateChecker(
    'https://github.com/picassochan/AI-assisted-Writing',
    __FILE__,
    'ai-assisted-writing'
);
$updateChecker->getVcsApi()->enableReleaseAssets();

define( 'AIAW_VERSION', '1.1.4' );
define( 'AIAW_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'AIAW_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'AIAW_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

require_once AIAW_PLUGIN_PATH . 'includes/class-i18n.php';
require_once AIAW_PLUGIN_PATH . 'includes/class-api.php';
require_once AIAW_PLUGIN_PATH . 'includes/class-template.php';
require_once AIAW_PLUGIN_PATH . 'includes/class-generator.php';
require_once AIAW_PLUGIN_PATH . 'includes/class-settings.php';
require_once AIAW_PLUGIN_PATH . 'includes/class-core.php';

AIAW_Core::get_instance();
