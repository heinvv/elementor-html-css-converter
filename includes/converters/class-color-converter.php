<?php
/**
 * Color Converter Class
 *
 * @package ElementorHtmlCssConverter
 */

namespace ElementorHtmlCssConverter\Converters;

use ElementorHtmlCssConverter\Abstracts\Property_Converter_Base;
use ElementorHtmlCssConverter\PropTypes\Color_Prop_Type;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class Color_Converter
 *
 * Converts CSS color properties to Elementor atomic widget format.
 * Supports hex, rgb, rgba, hsl, hsla, named colors, and transparent.
 */
class Color_Converter extends Property_Converter_Base {
	/**
	 * List of CSS properties this converter handles.
	 */
	private const SUPPORTED_PROPERTIES = [ 'color' ];

	/**
	 * Pattern to match simple color names.
	 */
	private const PATTERN_SIMPLE_COLOR_NAME = '/^[a-zA-Z0-9-]+$/';

	/**
	 * Get the list of supported properties.
	 *
	 * @return array List of supported property names.
	 */
	protected function get_supported_properties_list(): array {
		return self::SUPPORTED_PROPERTIES;
	}

	/**
	 * Convert a color property to atomic format.
	 *
	 * @param string $property The CSS property name.
	 * @param mixed  $value    The CSS property value.
	 * @return array|null The atomic format array or null if conversion fails.
	 */
	public function convert( string $property, $value ): ?array {
		if ( ! $this->supports( $property ) ) {
			return null;
		}

		if ( ! is_string( $value ) || empty( trim( $value ) ) ) {
			return null;
		}

		$normalized = $this->normalize_color_value( $value );
		if ( null === $normalized ) {
			return null;
		}

		return Color_Prop_Type::generate( $normalized );
	}

	/**
	 * Normalize a color value for conversion.
	 *
	 * @param string $value The raw color value.
	 * @return string|null The normalized value or null if invalid.
	 */
	private function normalize_color_value( string $value ): ?string {
		$value = trim( $value );

		if ( empty( $value ) ) {
			return null;
		}

		if ( 'none' === $value ) {
			return null;
		}

		if ( 'transparent' === $value ) {
			return 'rgba(0,0,0,0)';
		}

		if ( $this->is_valid_color_format( $value ) ) {
			return $value;
		}

		return null;
	}

	/**
	 * Check if a value is a valid color format.
	 *
	 * @param string $value The color value to check.
	 * @return bool True if the format is valid.
	 */
	private function is_valid_color_format( string $value ): bool {
		// CSS variables are not supported.
		if ( $this->is_css_variable( $value ) ) {
			return false;
		}

		// Hex colors (#fff or #ffffff).
		if ( str_starts_with( $value, '#' ) && ( strlen( $value ) === 4 || strlen( $value ) === 7 ) ) {
			return ctype_xdigit( substr( $value, 1 ) );
		}

		// RGB/RGBA and HSL/HSLA functions.
		if ( str_starts_with( $value, 'rgb' ) || str_starts_with( $value, 'hsl' ) ) {
			return true;
		}

		// Named colors (red, blue, etc.).
		return $this->is_simple_color_name( $value );
	}

	/**
	 * Check if a value is a CSS variable.
	 *
	 * @param string $value The value to check.
	 * @return bool True if it's a CSS variable.
	 */
	private function is_css_variable( string $value ): bool {
		return str_starts_with( $value, 'var(' );
	}

	/**
	 * Check if a value is a simple color name.
	 *
	 * @param string $value The value to check.
	 * @return bool True if it's a valid simple color name.
	 */
	private function is_simple_color_name( string $value ): bool {
		return preg_match( self::PATTERN_SIMPLE_COLOR_NAME, $value ) === 1;
	}
}
