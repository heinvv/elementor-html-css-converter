<?php

$plugin_root = dirname( __DIR__ );
$composer_autoload = $plugin_root . '/vendor/autoload.php';

if ( ! file_exists( $composer_autoload ) ) {
	die( 'Run composer install before running tests.' );
}

require $composer_autoload;

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', '/tmp/' );
}

if ( ! defined( 'EHCC_PATH' ) ) {
	define( 'EHCC_PATH', $plugin_root . '/' );
}

if ( ! function_exists( 'wp_rand' ) ) {
	function wp_rand( int $min = 0, ?int $max = null ): int {
		return null === $max ? mt_rand() : mt_rand( $min, $max );
	}
}

require_once $plugin_root . '/includes/autoloader.php';
ElementorHtmlCssConverter\Autoloader::register();
