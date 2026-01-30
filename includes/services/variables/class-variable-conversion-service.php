<?php
/**
 * Variable Conversion Service
 *
 * Service that converts raw CSS variables to Elementor's global variables format.
 *
 * @package ElementorHtmlCssConverter
 */

namespace ElementorHtmlCssConverter\Services\Variables;

use ElementorHtmlCssConverter\Converters\Variables\Variable_Convertor_Registry;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Variable_Conversion_Service
 *
 * Orchestrates the conversion of CSS variables using the convertor registry.
 */
class Variable_Conversion_Service {

	/**
	 * Convert raw variables to Elementor format.
	 *
	 * @param array $variables Array of variables with 'name' and 'value' keys.
	 * @return array Converted variables.
	 */
	public static function convert_to_editor_variables( array $variables ): array {
		$registry  = new Variable_Convertor_Registry();
		$converted = [];

		foreach ( $variables as $variable ) {
			$name  = $variable['name'] ?? null;
			$value = $variable['value'] ?? null;

			if ( ! is_string( $name ) || ! is_string( $value ) ) {
				continue;
			}

			$convertor = $registry->resolve( $name, $value );

			if ( $convertor ) {
				$converted[] = $convertor->convert( $name, $value );
			}
		}

		return $converted;
	}
}
