<?php
/**
 * Length Size Viewport Variable Convertor
 *
 * Converts length/size/viewport unit variables to Elementor format.
 *
 * @package ElementorHtmlCssConverter
 */

namespace ElementorHtmlCssConverter\Converters\Variables\Convertors;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Length_Size_Viewport_Variable_Convertor
 *
 * Handles values with units: px, pt, em, rem, vh, vw, etc.
 */
class Length_Size_Viewport_Variable_Convertor extends Abstract_Variable_Convertor {

	/**
	 * Regex pattern for length values.
	 */
	private const LENGTH_PATTERN = '/^([+-]?(?:\d*\.)?\d+)(px|pt|em|rem|ch|vh|vw|svh|svw|vmin|vmax)$/i';

	/**
	 * Check if this convertor supports the value.
	 *
	 * @param string $name  Variable name.
	 * @param string $value Variable value.
	 * @return bool
	 */
	public function supports( string $name, string $value ): bool {
		return 1 === preg_match( self::LENGTH_PATTERN, trim( $value ) );
	}

	/**
	 * Get type identifier.
	 *
	 * @return string
	 */
	protected function get_type(): string {
		return 'size-length-viewport';
	}

	/**
	 * Normalize length value.
	 *
	 * @param string $value Raw value.
	 * @return string Normalized value.
	 */
	protected function normalize_value( string $value ): string {
		return $this->normalize_length( $value );
	}

	/**
	 * Normalize length.
	 *
	 * Removes unnecessary decimal places (e.g., 16.0px → 16px).
	 *
	 * @param string $length Length value.
	 * @return string Normalized length.
	 */
	private function normalize_length( string $length ): string {
		$trimmed = trim( $length );

		if ( 1 === preg_match( self::LENGTH_PATTERN, $trimmed, $matches ) ) {
			$number = $matches[1];
			$unit   = strtolower( $matches[2] );

			// Normalize the number to avoid unnecessary decimal places
			$normalized_number = $this->normalize_number( $number );

			return $normalized_number . $unit;
		}

		return $trimmed;
	}

	/**
	 * Normalize number.
	 *
	 * Removes unnecessary decimals (16.0 → 16).
	 *
	 * @param string $number Number string.
	 * @return string Normalized number.
	 */
	private function normalize_number( string $number ): string {
		$float = (float) $number;

		// If it's a whole number, return as integer
		if ( $float === (int) $float ) {
			return (string) (int) $float;
		}

		// Otherwise return the float with reasonable precision
		return (string) $float;
	}
}
