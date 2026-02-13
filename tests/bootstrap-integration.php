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

$wp_core_dir = getenv( 'WP_CORE_DIR' )
	? rtrim( getenv( 'WP_CORE_DIR' ), '/' )
	: ( dirname( $_tests_dir ) . '/wordpress' );
if ( ! is_dir( $wp_core_dir ) ) {
	$wp_core_dir = '/tmp/wordpress';
}

define( 'EHCC_TESTS', true );
define( 'ELEMENTOR_TESTS', true );

$elementor_plugin_path = 'elementor/elementor.php';
$converter_plugin_path = 'elementor-html-css-converter/elementor-html-css-converter.php';

$active_plugins = [ $elementor_plugin_path, $converter_plugin_path ];

$GLOBALS['wp_tests_options'] = [
	'active_plugins' => $active_plugins,
	'template'       => 'twentytwentyone',
	'stylesheet'    => 'twentytwentyone',
];

require_once $_tests_dir . '/includes/functions.php';

tests_add_filter( 'muplugins_loaded', function () use ( $wp_core_dir, $plugin_root ) {
	$elementor_file = getenv( 'WP_TESTS_ELEMENTOR_DIR' )
		?: $wp_core_dir . '/wp-content/plugins/elementor/elementor.php';
	
	if ( file_exists( $elementor_file ) ) {
		require $elementor_file;
	}
	
	require $plugin_root . '/elementor-html-css-converter.php';
} );

tests_add_filter( 'shutdown', 'drop_tables', 999999 );

require $_tests_dir . '/includes/bootstrap.php';

remove_action( 'admin_init', '_maybe_update_themes' );
remove_action( 'admin_init', '_maybe_update_core' );
remove_action( 'admin_init', '_maybe_update_plugins' );
remove_action( 'wp_print_styles', 'print_emoji_styles' );

if ( ! defined( 'WP_ADMIN' ) ) {
	define( 'WP_ADMIN', true );
}

add_action( 'elementor/experiments/feature-registered', function ( $experiments_manager, $experimental_data ) {
	$exclude = [];
	if ( ! $experimental_data['mutable'] || in_array( $experimental_data['name'], $exclude, true ) ) {
		return;
	}
	$experiments_manager->set_feature_default_state( $experimental_data['name'], $experiments_manager::STATE_ACTIVE );
}, 10, 2 );

\Elementor\Plugin::instance();

do_action( 'plugins_loaded' );
do_action( 'init' );
do_action( 'wp_loaded' );

