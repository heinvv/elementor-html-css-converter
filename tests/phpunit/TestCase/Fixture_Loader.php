<?php

namespace ElementorHtmlCssConverter\Tests\TestCase;

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', '/tmp/' );
}

class Fixture_Loader {

	private static string $fixtures_path;

	public static function set_fixtures_path( string $path ): void {
		self::$fixtures_path = rtrim( $path, '/' );
	}

	public static function load_json( string $relative_path ): array {
		$base = self::$fixtures_path ?? dirname( __DIR__ ) . '/fixtures';
		$path = $base . '/' . ltrim( $relative_path, '/' );
		if ( ! file_exists( $path ) ) {
			throw new \RuntimeException( "Fixture not found: $path" );
		}
		$json = file_get_contents( $path );
		$data = json_decode( $json, true );
		if ( json_last_error() !== JSON_ERROR_NONE ) {
			throw new \RuntimeException( 'Invalid JSON in fixture: ' . $path );
		}
		return $data;
	}

	public static function get_fixtures_path(): string {
		return self::$fixtures_path ?? dirname( __DIR__ ) . '/fixtures';
	}

}
