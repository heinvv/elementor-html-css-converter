<?php
/**
 * Variable Extractor
 *
 * Extracts CSS variable declarations from raw CSS.
 *
 * @package ElementorHtmlCssConverter
 */

namespace ElementorHtmlCssConverter\Converters\Variables;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Variable_Extractor
 *
 * Extracts CSS variables from raw declarations without selector wrappers.
 */
class Variable_Extractor {

	private const REGEX_CSS_COMMENT_REMOVAL = '/\/\*.*?\*\//s';
	private const REGEX_CSS_VARIABLE_DECLARATION = '/(--[a-zA-Z0-9_-]+)\s*:\s*([^;]+);/';

	/**
	 * Extract variables from raw CSS.
	 *
	 * Accepts raw variable declarations like:
	 * --primary-color: #ff0000;
	 * --font-size: 16px;
	 *
	 * @param string $css Raw CSS variable declarations.
	 * @return array Array of variables with 'name' and 'value' keys.
	 */
	public function extract_from_css( string $css ): array {
		$variables = [];

		$css = preg_replace( self::REGEX_CSS_COMMENT_REMOVAL, '', $css );

		if ( preg_match_all( self::REGEX_CSS_VARIABLE_DECLARATION, $css, $matches, PREG_SET_ORDER ) ) {
			foreach ( $matches as $match ) {
				$name  = trim( $match[1] );
				$value = trim( $match[2] );

				if ( ! empty( $name ) && ! empty( $value ) ) {
					$variables[] = [
						'name'  => $name,
						'value' => $value,
					];
				}
			}
		}

		return $variables;
	}
}

