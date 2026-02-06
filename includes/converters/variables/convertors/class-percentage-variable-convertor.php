<?php
/**
 * Percentage Variable Convertor
 *
 * Converts percentage variables to Elementor format.
 *
 * @package ElementorHtmlCssConverter
 */

namespace ElementorHtmlCssConverter\Converters\Variables\Convertors;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Percentage_Variable_Convertor
 *
 * Handles percentage values (e.g., 50%, 100%).
 */
class Percentage_Variable_Convertor extends Abstract_Variable_Convertor {

	/**
	 * Regex pattern for percentage values.
	 */
	private const PERCENTAGE_PATTERN = '/^([+-]?(?:\d*\.)?\d+)%$/';

	/**
	 * Check if this convertor supports the value.
	 *
	 * @param string $name  Variable name.
	 * @param string $value Variable value.
	 * @return bool
	 */
	public function supports( string $name, string $value ): bool {
		return 1 === preg_match( self::PERCENTAGE_PATTERN, trim( $value ) );
	}

	/**
	 * Get type identifier.
	 *
	 * @return string
	 */
	protected function get_type(): string {
		return 'size-percentage';
	}

	/**
	 * Normalize percentage value.
	 *
	 * @param string $value Raw value.
	 * @return string Normalized value.
	 */
	protected function normalize_value( string $value ): string {
		$trimmed = trim( $value );

		if ( 1 === preg_match( self::PERCENTAGE_PATTERN, $trimmed, $matches ) ) {
			$number = $matches[1];
			$float  = (float) $number;

			if ( $float === (int) $float ) {
				return (string) (int) $float . '%';
			}

			return $float . '%';
		}

		return $trimmed;
	}
}

