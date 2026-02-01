<?php
namespace ElementorHtmlCssConverter\Converters\Parsers;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Color_Value_Parser {

	private const PATTERN_NAMED_COLOR = '/^[a-zA-Z0-9-]+$/';
	private const TRANSPARENT_RGBA = 'rgba(0,0,0,0)';

	public static function parse( string $value ): ?string {
		$value = trim( $value );

		if ( self::is_empty_or_none( $value ) ) {
			return null;
		}

		if ( self::is_transparent( $value ) ) {
			return self::TRANSPARENT_RGBA;
		}

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
			return true; // ✅ Accept var() references
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

	private static function is_rgb_or_hsl_function( string $value ): bool {
		return str_starts_with( $value, 'rgb' ) || str_starts_with( $value, 'hsl' );
	}

	private static function is_named_color( string $value ): bool {
		return 1 === preg_match( self::PATTERN_NAMED_COLOR, $value );
	}
}
