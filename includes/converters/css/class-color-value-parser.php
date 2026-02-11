<?php
namespace ElementorHtmlCssConverter\Converters\Css;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Color_Value_Parser {

	private const TRANSPARENT_RGBA = 'rgba(0,0,0,0)';

	public static function parse( string $value ): ?string {
		$value = trim( $value );

		if ( self::is_empty_or_none( $value ) ) {
			return null;
		}

		if ( self::is_transparent( $value ) ) {
			return self::TRANSPARENT_RGBA;
		}

		$value = self::normalize_modern_rgb_syntax( $value );

		if ( self::is_supported_color_format( $value ) ) {
			return $value;
		}

		return null;
	}

	public static function is_valid_color( string $value ): bool {
		return null !== self::parse( $value );
	}

	private static function is_empty_or_none( string $value ): bool {
		return '' === $value || 'none' === $value;
	}

	private static function is_transparent( string $value ): bool {
		return 'transparent' === strtolower( $value );
	}

	private static function is_supported_color_format( string $value ): bool {
		if ( self::is_css_variable( $value ) ) {
			return true;
		}

		return self::is_hex_color( $value )
			|| self::is_rgb_or_hsl_function( $value )
			|| self::is_named_color( $value );
	}

	private static function is_css_variable( string $value ): bool {
		return str_starts_with( $value, 'var(' );
	}

	private static function is_hex_color( string $value ): bool {
		if ( ! str_starts_with( $value, '#' ) ) {
			return false;
		}

		$hex_length = strlen( $value );
		$is_valid_length = 4 === $hex_length || 7 === $hex_length;

		return $is_valid_length && ctype_xdigit( substr( $value, 1 ) );
	}

	private static function normalize_modern_rgb_syntax( string $value ): string {
		$rgb_space_slash = '/^rgb\(\s*(\d+)\s+(\d+)\s+(\d+)(?:\s*\/\s*([\d.]+))?\s*\)$/i';

		if ( preg_match( $rgb_space_slash, $value, $matches ) ) {
			$r = (int) $matches[1];
			$g = (int) $matches[2];
			$b = (int) $matches[3];
			$a = isset( $matches[4] ) ? (float) $matches[4] : 1.0;

			return sprintf( 'rgba(%d, %d, %d, %s)', $r, $g, $b, $a );
		}

		return $value;
	}

	private static function is_rgb_or_hsl_function( string $value ): bool {
		return str_starts_with( $value, 'rgb' ) || str_starts_with( $value, 'hsl' );
	}

	private static function is_named_color( string $value ): bool {
		return Css_Named_Colors::is_named_color( $value );
	}
}
