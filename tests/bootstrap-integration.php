<?php

$plugin_root = dirname( __DIR__ );
$composer_autoload = $plugin_root . '/vendor/autoload.php';

if ( ! file_exists( $composer_autoload ) ) {
	die( 'Run composer install before running tests.' );
}

require $composer_autoload;

$_tests_dir = getenv( 'WP_TESTS_DIR' );
if ( empty( $_tests_dir ) || ! is_dir( $_tests_dir ) ) {
	die( 'Run bin/install-wp-tests-local.sh first. Set WP_TESTS_DIR if using custom path.' );
}

define( 'EHCC_TESTS', true );

$active_plugins = [
	'elementor/elementor.php',
	'elementor-html-css-converter/elementor-html-css-converter.php',
];

$GLOBALS['wp_tests_options'] = [
	'active_plugins' => $active_plugins,
	'template'       => 'twentytwentyone',
	'stylesheet'    => 'twentytwentyone',
];

require_once $_tests_dir . '/includes/functions.php';

tests_add_filter( 'shutdown', 'drop_tables', 999999 );

require $_tests_dir . '/includes/bootstrap.php';

remove_action( 'admin_init', '_maybe_update_themes' );
remove_action( 'admin_init', '_maybe_update_core' );
remove_action( 'admin_init', '_maybe_update_plugins' );
remove_action( 'wp_print_styles', 'print_emoji_styles' );

if ( ! defined( 'WP_ADMIN' ) ) {
	define( 'WP_ADMIN', true );
}

do_action( 'plugins_loaded' );

$elementor_instance = class_exists( '\Elementor\Plugin' )
	? \Elementor\Plugin::$instance
	: null;

do_action( 'init' );
do_action( 'wp_loaded' );
do_action( 'rest_api_init' );
