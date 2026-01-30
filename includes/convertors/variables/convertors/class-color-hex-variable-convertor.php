<?php
/**
 * Color Hex Variable Convertor
 *
 * Converts hexadecimal color variables to Elementor format.
 *
 * @package ElementorHtmlCssConverter
 */

namespace ElementorHtmlCssConverter\Convertors\Variables\Convertors;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Color_Hex_Variable_Convertor
 *
 * Handles hex color values: #RGB, #RRGGBB, #RRGGBBAA
 */
class Color_Hex_Variable_Convertor extends Abstract_Variable_Convertor {

	/**
	 * Regex patterns for hex colors.
	 */
	private const HEX3_PATTERN  = '/^#([A-Fa-f0-9]{3})$/';
	private const HEX6_PATTERN  = '/^#([A-Fa-f0-9]{6})$/';
	private const HEXA_PATTERN = '/^#([A-Fa-f0-9]{8})$/';

	/**
	 * Check if this convertor supports the value.
	 *
	 * @param string $name  Variable name.
	 * @param string $value Variable value.
	 * @return bool
	 */
	public function supports( string $name, string $value ): bool {
		return 1 === preg_match( self::HEX3_PATTERN, $value )
			|| 1 === preg_match( self::HEX6_PATTERN, $value )
			|| 1 === preg_match( self::HEXA_PATTERN, $value );
	}

	/**
	 * Get type identifier.
	 *
	 * @return string
	 */
	protected function get_type(): string {
		return 'color-hex';
	}

	/**
	 * Normalize hex color value.
	 *
	 * @param string $value Raw value.
	 * @return string Normalized value.
	 */
	protected function normalize_value( string $value ): string {
		return $this->normalize_hex( $value );
	}

	/**
	 * Normalize hex color.
	 *
	 * Expands 3-char hex to 6-char and lowercases.
	 *
	 * @param string $hex Hex color.
	 * @return string Normalized hex.
	 */
	private function normalize_hex( string $hex ): string {
		$lower = strtolower( $hex );

		// HEXA format (8 chars) - return as-is
		if ( 1 === preg_match( self::HEXA_PATTERN, $lower ) ) {
			return $lower;
		}

		// HEX6 format (6 chars) - return as-is
		if ( 1 === preg_match( self::HEX6_PATTERN, $lower ) ) {
			return $lower;
		}

		// HEX3 format (3 chars) - expand to 6 chars
		$digits = substr( $lower, 1 );
		return '#' . $digits[0] . $digits[0] . $digits[1] . $digits[1] . $digits[2] . $digits[2];
	}
}
