<?php
/**
 * CSS Converter Class
 *
 * @package ElementorHtmlCssConverter
 */

namespace ElementorHtmlCssConverter;

use ElementorHtmlCssConverter\Interfaces\Property_Converter_Interface;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class Css_Converter
 *
 * Parses CSS strings and converts supported properties to Elementor atomic format.
 */
class Css_Converter {
	/**
	 * Regex pattern to match CSS declarations.
	 */
	private const PATTERN_CSS_DECLARATION = '/([a-zA-Z0-9-]+)\s*:\s*([^;]+);?/';

	/**
	 * The converter registry.
	 *
	 * @var Converter_Registry
	 */
	private Converter_Registry $registry;

	/**
	 * Constructor.
	 *
	 * @param Converter_Registry $registry The converter registry.
	 */
	public function __construct( Converter_Registry $registry ) {
		$this->registry = $registry;
	}

	/**
	 * Convert CSS string to atomic format.
	 *
	 * @param array $params Parameters containing 'cssString' key.
	 * @return array Result with 'props' and optionally 'customCss'.
	 */
	public function convert( array $params ): array {
		$css_string = $params['cssString'] ?? '';

		$properties = $this->parse_css_string( $css_string );
		$result     = $this->convert_properties_to_atomic( $properties );

		return $result;
	}

	/**
	 * Parse a CSS string into property-value pairs.
	 *
	 * @param string $css_string The CSS string to parse.
	 * @return array Associative array of property => value.
	 */
	private function parse_css_string( string $css_string ): array {
		$properties = [];
		$pattern    = self::PATTERN_CSS_DECLARATION;

		if ( preg_match_all( $pattern, $css_string, $matches, PREG_SET_ORDER ) ) {
			foreach ( $matches as $match ) {
				$property              = trim( $match[1] );
				$value                 = trim( $match[2] );
				$properties[ $property ] = $value;
			}
		}

		return $properties;
	}

	/**
	 * Convert parsed properties to atomic format.
	 *
	 * @param array $properties Associative array of property => value.
	 * @return array Result with 'props' and optionally 'customCss'.
	 */
	private function convert_properties_to_atomic( array $properties ): array {
		$props       = [];
		$unsupported = [];

		foreach ( $properties as $property => $value ) {
			$converter = $this->get_converter_for_property( $property );
			if ( null !== $converter ) {
				$converted = $converter->convert( $property, $value );
				if ( null !== $converted ) {
					$props[ $property ] = $converted;
				} else {
					$unsupported[ $property ] = $value;
				}
			} else {
				$unsupported[ $property ] = $value;
			}
		}

		$result = [
			'props' => $props,
		];

		if ( ! empty( $unsupported ) ) {
			$result['customCss'] = $this->format_custom_css( $unsupported );
		}

		return $result;
	}

	/**
	 * Format unsupported properties back into CSS string.
	 *
	 * @param array $properties Associative array of property => value.
	 * @return string CSS string.
	 */
	private function format_custom_css( array $properties ): string {
		$css_parts = [];
		foreach ( $properties as $property => $value ) {
			$css_parts[] = sprintf( '%s: %s;', $property, $value );
		}
		return implode( ' ', $css_parts );
	}

	/**
	 * Get the converter for a given property.
	 *
	 * @param string $property The CSS property name.
	 * @return Property_Converter_Interface|null The converter or null.
	 */
	private function get_converter_for_property( string $property ): ?Property_Converter_Interface {
		return $this->registry->resolve( $property );
	}
}
