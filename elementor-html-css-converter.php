<?php
/**
 * Plugin Name: Elementor HTML CSS Converter
 * Plugin URI: https://github.com/your-username/elementor-html-css-converter
 * Description: Converts CSS properties to Elementor atomic widget format via REST API.
 * Version: 0.0.1
 * Author: Hein van Vlastuin
 * Author URI: https://github.com/heinvv
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Requires PHP: 7.4
 * Requires at least: 6.0
 * Requires Plugins: elementor
 * Text Domain: elementor-html-css-converter
 * Domain Path: /languages
 *
 * @package ElementorHtmlCssConverter
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Plugin version.
define( 'EHCC_VERSION', '0.0.1' );

// Plugin file.
define( 'EHCC_FILE', __FILE__ );

// Plugin path.
define( 'EHCC_PATH', plugin_dir_path( __FILE__ ) );

// Plugin base name.
define( 'EHCC_PLUGIN_BASE', plugin_basename( __FILE__ ) );

// Minimum Elementor version.
define( 'EHCC_MINIMUM_ELEMENTOR_VERSION', '3.34.0' );

// Minimum PHP version.
define( 'EHCC_MINIMUM_PHP_VERSION', '7.4' );

/**
 * PHP 7.4 polyfill for str_starts_with function.
 */
if ( ! function_exists( 'str_starts_with' ) ) {
	/**
	 * Check if a string starts with a given substring.
	 *
	 * @param string $haystack The string to search in.
	 * @param string $needle   The substring to search for.
	 * @return bool True if haystack starts with needle.
	 */
	function str_starts_with( string $haystack, string $needle ): bool {
		return 0 === strncmp( $haystack, $needle, strlen( $needle ) );
	}
}

/**
 * Load the plugin after plugins are loaded.
 *
 * @return void
 */
function ehcc_load() {
	// Check for Elementor.
	if ( ! did_action( 'elementor/loaded' ) ) {
		add_action( 'admin_notices', 'ehcc_admin_notice_missing_elementor' );
		add_action( 'admin_init', 'ehcc_deactivate_self' );
		return;
	}

	// Check Elementor version.
	if ( ! version_compare( ELEMENTOR_VERSION, EHCC_MINIMUM_ELEMENTOR_VERSION, '>=' ) ) {
		add_action( 'admin_notices', 'ehcc_admin_notice_minimum_elementor_version' );
		return;
	}

	// Load plugin files.
	ehcc_load_files();

	// Initialize the plugin.
	\ElementorHtmlCssConverter\Plugin::instance();
}
add_action( 'plugins_loaded', 'ehcc_load', 11 );

/**
 * Load all plugin files in the correct order.
 *
 * @return void
 */
function ehcc_load_files() {
	// Interfaces.
	require_once EHCC_PATH . 'includes/interfaces/interface-property-converter.php';
	require_once EHCC_PATH . 'includes/interfaces/interface-widget-style-applicator.php';

	// Abstracts.
	require_once EHCC_PATH . 'includes/abstracts/class-property-converter-base.php';

	// Core classes.
	require_once EHCC_PATH . 'includes/class-converter-registry.php';
	require_once EHCC_PATH . 'includes/class-style-definition-builder.php';
	require_once EHCC_PATH . 'includes/class-elementor-document-service.php';

	// Parsers.
	require_once EHCC_PATH . 'includes/parsers/class-size-value-parser.php';
	require_once EHCC_PATH . 'includes/parsers/class-color-value-parser.php';

	// Converters.
	require_once EHCC_PATH . 'includes/converters/class-color-converter.php';
	require_once EHCC_PATH . 'includes/converters/class-background-color-converter.php';
	require_once EHCC_PATH . 'includes/converters/class-font-size-converter.php';
	require_once EHCC_PATH . 'includes/converters/class-width-converter.php';
	require_once EHCC_PATH . 'includes/converters/class-height-converter.php';
	require_once EHCC_PATH . 'includes/converters/class-padding-converter.php';
	require_once EHCC_PATH . 'includes/converters/class-margin-converter.php';
	require_once EHCC_PATH . 'includes/converters/class-display-converter.php';

	// Conversion services.
	require_once EHCC_PATH . 'includes/class-css-converter.php';
	require_once EHCC_PATH . 'includes/class-widget-style-applicator.php';

	// API and plugin.
	require_once EHCC_PATH . 'includes/class-rest-api.php';
	require_once EHCC_PATH . 'includes/class-plugin.php';
}

/**
 * Admin notice for missing Elementor.
 *
 * @return void
 */
function ehcc_admin_notice_missing_elementor() {
	if ( ! current_user_can( 'activate_plugins' ) ) {
		return;
	}

	$message = sprintf(
		/* translators: 1: Plugin name 2: Elementor */
		esc_html__( '"%1$s" requires "%2$s" to be installed and activated.', 'elementor-html-css-converter' ),
		'<strong>Elementor HTML CSS Converter</strong>',
		'<strong>Elementor</strong>'
	);

	printf( '<div class="notice notice-warning is-dismissible"><p>%s</p></div>', $message );
}

/**
 * Admin notice for minimum Elementor version.
 *
 * @return void
 */
function ehcc_admin_notice_minimum_elementor_version() {
	if ( ! current_user_can( 'activate_plugins' ) ) {
		return;
	}

	$message = sprintf(
		/* translators: 1: Plugin name 2: Elementor 3: Required version */
		esc_html__( '"%1$s" requires "%2$s" version %3$s or higher.', 'elementor-html-css-converter' ),
		'<strong>Elementor HTML CSS Converter</strong>',
		'<strong>Elementor</strong>',
		EHCC_MINIMUM_ELEMENTOR_VERSION
	);

	printf( '<div class="notice notice-warning is-dismissible"><p>%s</p></div>', $message );
}

/**
 * Deactivate the plugin.
 *
 * @return void
 */
function ehcc_deactivate_self() {
	deactivate_plugins( EHCC_PLUGIN_BASE );

	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( isset( $_GET['activate'] ) ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		unset( $_GET['activate'] );
	}
}
