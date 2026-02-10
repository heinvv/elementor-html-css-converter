<?php
/**
 * Line Height Variable Convertor
 *
 * Converts unitless line-height values to em format using name heuristics.
 *
 * @package ElementorHtmlCssConverter
 */

namespace ElementorHtmlCssConverter\Converters\Variables\Convertors;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Line_Height_Variable_Convertor
 *
 * Handles unitless decimal values when the variable name contains "line-height" or "lineheight".
 * Converts to em (e.g., 1.5 -> 1.5em).
 */
class Line_Height_Variable_Convertor extends Abstract_Variable_Convertor {

	private const UNITLESS_NUMBER_PATTERN = '/^(\d*\.)?\d+$/';

	private const NAME_KEYWORD_PATTERN = '/line-?height/i';

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
			&& 1 === preg_match( self::UNITLESS_NUMBER_PATTERN, trim( $value ) );
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
	 * Normalize unitless number to em value.
	 *
	 * @param string $value Raw value.
	 * @return string Normalized em value.
	 */
	protected function normalize_value( string $value ): string {
		$float = (float) trim( $value );

		if ( $float == (int) $float ) {
			return (string) (int) $float . 'em';
		}

		return $float . 'em';
	}
}
