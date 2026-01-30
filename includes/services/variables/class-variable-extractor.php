<?php
/**
 * Variable Extractor
 *
 * Extracts CSS variable declarations from raw CSS.
 *
 * @package ElementorHtmlCssConverter
 */

namespace ElementorHtmlCssConverter\Services\Variables;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Variable_Extractor
 *
 * Extracts CSS variables from raw declarations without selector wrappers.
 */
class Variable_Extractor {

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

		// Remove CSS comments
		$css = preg_replace( '/\/\*.*?\*\//s', '', $css );

		// Pattern: --variable-name: value;
		// Matches CSS custom property declarations
		$pattern = '/(--[a-zA-Z0-9_-]+)\s*:\s*([^;]+);/';

		if ( preg_match_all( $pattern, $css, $matches, PREG_SET_ORDER ) ) {
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
