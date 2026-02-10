<?php
/**
 * Opacity Variable Convertor
 *
 * Converts unitless opacity values (0-1) to percentage format using name heuristics.
 *
 * @package ElementorHtmlCssConverter
 */

namespace ElementorHtmlCssConverter\Converters\Variables\Convertors;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Opacity_Variable_Convertor
 *
 * Handles unitless decimal values (0-1) when the variable name contains "opacity".
 * Converts to percentage (e.g., 0.75 -> 75%).
 */
class Opacity_Variable_Convertor extends Abstract_Variable_Convertor {

	private const UNITLESS_ZERO_TO_ONE_PATTERN = '/^(?:0(?:\.\d+)?|1(?:\.0+)?)$/';

	private const NAME_KEYWORD_PATTERN = '/opacity/i';

	/**
	 * Check if this convertor supports the variable.
	 *
	 * @param string $name  Variable name.
	 * @param string $value Variable value.
	 * @return bool
	 */
	public function supports( string $name, string $value ): bool {
		$stripped_name = ltrim( $name, '-' );

		return 1 === preg_match( self::NAME_KEYWORD_PATTERN, $stripped_name )
			&& 1 === preg_match( self::UNITLESS_ZERO_TO_ONE_PATTERN, trim( $value ) );
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
	 * Normalize unitless decimal to percentage.
	 *
	 * @param string $value Raw value.
	 * @return string Normalized percentage value.
	 */
	protected function normalize_value( string $value ): string {
		$float      = (float) trim( $value );
		$percentage = round( $float * 100, 10 );

		if ( $percentage == (int) $percentage ) {
			return (string) (int) $percentage . '%';
		}

		return $percentage . '%';
	}
}
