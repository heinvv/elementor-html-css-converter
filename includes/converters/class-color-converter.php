<?php
/**
 * Color Converter Class
 *
 * @package ElementorHtmlCssConverter
 */

namespace ElementorHtmlCssConverter\Converters;

use ElementorHtmlCssConverter\Abstracts\Property_Converter_Base;
use Elementor\Modules\AtomicWidgets\PropTypes\Color_Prop_Type;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Color_Converter extends Property_Converter_Base {

	private const SUPPORTED_PROPERTIES = [ 'color' ];
	private const PATTERN_NAMED_COLOR = '/^[a-zA-Z0-9-]+$/';
	private const TRANSPARENT_RGBA = 'rgba(0,0,0,0)';

	protected function get_supported_properties_list(): array {
		return self::SUPPORTED_PROPERTIES;
	}

	public function convert( string $property, $value ): ?array {
		if ( ! $this->supports( $property ) ) {
			return null;
		}

		if ( ! $this->is_valid_string_value( $value ) ) {
			return null;
		}

		$normalized = $this->normalize_color_value( $value );

		if ( null === $normalized ) {
			return null;
		}

		return Color_Prop_Type::generate( $normalized );
	}

	private function is_valid_string_value( $value ): bool {
		return is_string( $value ) && ! empty( trim( $value ) );
	}

	private function normalize_color_value( string $value ): ?string {
		$value = trim( $value );

		if ( $this->is_empty_or_none( $value ) ) {
			return null;
		}

		if ( $this->is_transparent( $value ) ) {
			return self::TRANSPARENT_RGBA;
		}

		if ( $this->is_supported_color_format( $value ) ) {
			return $value;
		}

		return null;
	}

	private function is_empty_or_none( string $value ): bool {
		return empty( $value ) || 'none' === $value;
	}

	private function is_transparent( string $value ): bool {
		return 'transparent' === $value;
	}

	private function is_supported_color_format( string $value ): bool {
		if ( $this->is_css_variable( $value ) ) {
			return false;
		}

		return $this->is_hex_color( $value )
			|| $this->is_rgb_or_hsl_function( $value )
			|| $this->is_named_color( $value );
	}

	private function is_css_variable( string $value ): bool {
		return str_starts_with( $value, 'var(' );
	}

	private function is_hex_color( string $value ): bool {
		$is_hex_format = str_starts_with( $value, '#' )
			&& ( strlen( $value ) === 4 || strlen( $value ) === 7 );

		return $is_hex_format && ctype_xdigit( substr( $value, 1 ) );
	}

	private function is_rgb_or_hsl_function( string $value ): bool {
		return str_starts_with( $value, 'rgb' ) || str_starts_with( $value, 'hsl' );
	}

	private function is_named_color( string $value ): bool {
		return preg_match( self::PATTERN_NAMED_COLOR, $value ) === 1;
	}
}
