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
 * PHP 7.4 polyfill for str_contains function.
 */
if ( ! function_exists( 'str_contains' ) ) {
	/**
	 * Check if a string contains a given substring.
	 *
	 * @param string $haystack The string to search in.
	 * @param string $needle   The substring to search for.
	 * @return bool True if haystack contains needle.
	 */
	function str_contains( string $haystack, string $needle ): bool {
		return '' === $needle || false !== strpos( $haystack, $needle );
	}
}

// Register PSR-4 autoloader.
require_once EHCC_PATH . 'includes/autoloader.php';
ElementorHtmlCssConverter\Autoloader::register();

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

	// Initialize the plugin.
	\ElementorHtmlCssConverter\Plugin::instance();
}
add_action( 'plugins_loaded', 'ehcc_load', 11 );

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
