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

if ( ! function_exists( 'str_starts_with' ) ) {
	function str_starts_with( string $haystack, string $needle ): bool {
		return 0 === strncmp( $haystack, $needle, strlen( $needle ) );
	}
}

if ( ! function_exists( 'str_ends_with' ) ) {
	function str_ends_with( string $haystack, string $needle ): bool {
		return '' === $needle || ( strlen( $needle ) <= strlen( $haystack ) && substr_compare( $haystack, $needle, -strlen( $needle ) ) === 0 );
	}
}

if ( ! function_exists( 'wp_kses' ) ) {
	function wp_kses( $string, $allowed_html ) {
		$allowed = '';
		foreach ( array_keys( $allowed_html ) as $tag ) {
			$allowed .= '<' . $tag . '>';
		}
		return strip_tags( $string, $allowed );
	}
}

require_once $plugin_root . '/includes/autoloader.php';
ElementorHtmlCssConverter\Autoloader::register();
