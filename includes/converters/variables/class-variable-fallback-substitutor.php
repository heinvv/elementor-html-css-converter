<?php
/**
 * Variable Fallback Substitutor
 *
 * Substitutes unresolved var() references with literal values from a fallback map
 * when Variable_Resolver cannot find the variable in Elementor's repository.
 *
 * @package ElementorHtmlCssConverter
 */

namespace ElementorHtmlCssConverter\Converters\Variables;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Variable_Fallback_Substitutor {

	private const REGEX_VAR_REFERENCE = '/var\s*\(\s*(--[a-zA-Z0-9_-]+)\s*(?:,\s*([^)]*))?\)/';

	public static function build_fallback_map_from_css( string $css ): array {
		$extractor = new Variable_Extractor();
		$variables = $extractor->extract_from_css( $css );
		$map      = [];

		foreach ( $variables as $variable ) {
			$name  = $variable['name'] ?? null;
			$value = $variable['value'] ?? null;

			if ( is_string( $name ) && is_string( $value ) && '' !== trim( $name ) && '' !== trim( $value ) ) {
				$map[ $name ] = trim( $value );
			}
		}

		return $map;
	}

	public static function substitute_in_value( string $value, array $fallback_map ): string {
		if ( empty( $fallback_map ) ) {
			return $value;
		}

		return preg_replace_callback(
			self::REGEX_VAR_REFERENCE,
			function ( array $matches ) use ( $fallback_map ): string {
				$var_name = $matches[1];

				if ( isset( $fallback_map[ $var_name ] ) ) {
					return $fallback_map[ $var_name ];
				}

				return $matches[0];
			},
			$value
		);
	}
}
