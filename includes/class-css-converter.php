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
	 * Convert an array of CSS properties to atomic format.
	 *
	 * This is the public entry point for converting pre-parsed CSS properties.
	 * Used by Atomic_Data_Parser to avoid duplicating conversion logic.
	 *
	 * @param array $properties Associative array of property => value.
	 * @return array Atomic props (without 'props' wrapper).
	 */
	public function convert_properties( array $properties ): array {
		$result = $this->convert_properties_to_atomic( $properties );
		return $result['props'] ?? [];
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
					// Check if this is a multi-property return (shorthand expansion)
					// Multi-property returns don't have $$type at the root level
					if ( $this->is_multi_property_result( $converted ) ) {
						// Merge each expanded property
						foreach ( $converted as $expanded_property => $expanded_value ) {
							$props[ $expanded_property ] = $this->merge_props( $props[ $expanded_property ] ?? null, $expanded_value );
						}
					} else {
						// Single property return
						$output_property = $converter->get_output_property( $property );
						$props[ $output_property ] = $this->merge_props( $props[ $output_property ] ?? null, $converted );
					}
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
	 * Check if a converted result is a multi-property return (shorthand expansion).
	 *
	 * Multi-property returns are associative arrays where keys are property names
	 * and values are prop type arrays (with $$type key).
	 * Single property returns have $$type at the root level.
	 *
	 * @param array $converted The converted result.
	 * @return bool True if this is a multi-property result.
	 */
	private function is_multi_property_result( array $converted ): bool {
		// If it has $$type at root, it's a single property
		if ( isset( $converted['$$type'] ) ) {
			return false;
		}

		// Check if all values are prop type arrays (have $$type)
		foreach ( $converted as $key => $value ) {
			if ( ! is_array( $value ) || ! isset( $value['$$type'] ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Merge two props, handling dimensions and flex merging.
	 *
	 * @param array|null $existing Existing prop value or null.
	 * @param array      $new      New prop value to merge.
	 * @return array The merged prop value.
	 */
	private function merge_props( ?array $existing, array $new ): array {
		if ( null === $existing ) {
			return $new;
		}

		// Check if both are dimensions type (have $$type = 'dimensions')
		if ( $this->is_dimensions_prop( $existing ) && $this->is_dimensions_prop( $new ) ) {
			return $this->merge_dimensions( $existing, $new );
		}

		// Check if both are flex type (have $$type = 'flex')
		if ( $this->is_flex_prop( $existing ) && $this->is_flex_prop( $new ) ) {
			return $this->merge_flex( $existing, $new );
		}

		// For other props, new value overwrites
		return $new;
	}

	/**
	 * Check if a prop is a dimensions type.
	 *
	 * @param array $prop The prop to check.
	 * @return bool True if it's a dimensions prop.
	 */
	private function is_dimensions_prop( array $prop ): bool {
		return isset( $prop['$$type'] ) && 'dimensions' === $prop['$$type'];
	}

	/**
	 * Merge two dimensions props, combining their dimension values.
	 *
	 * @param array $existing Existing dimensions prop.
	 * @param array $new      New dimensions prop to merge.
	 * @return array The merged dimensions prop.
	 */
	private function merge_dimensions( array $existing, array $new ): array {
		$merged_value = $existing['value'] ?? [];

		// Merge new dimension values into existing
		if ( isset( $new['value'] ) && is_array( $new['value'] ) ) {
			foreach ( $new['value'] as $dimension => $size_prop ) {
				$merged_value[ $dimension ] = $size_prop;
			}
		}

		return [
			'$$type' => 'dimensions',
			'value'  => $merged_value,
		];
	}

	/**
	 * Check if a prop is a flex type.
	 *
	 * @param array $prop The prop to check.
	 * @return bool True if it's a flex prop.
	 */
	private function is_flex_prop( array $prop ): bool {
		return isset( $prop['$$type'] ) && 'flex' === $prop['$$type'];
	}

	/**
	 * Merge two flex props, combining their component values.
	 *
	 * New values override existing values for the same component.
	 *
	 * @param array $existing Existing flex prop.
	 * @param array $new      New flex prop to merge.
	 * @return array The merged flex prop.
	 */
	private function merge_flex( array $existing, array $new ): array {
		$merged_value = $existing['value'] ?? [];

		// Merge new flex component values into existing
		if ( isset( $new['value'] ) && is_array( $new['value'] ) ) {
			foreach ( $new['value'] as $component => $component_value ) {
				$merged_value[ $component ] = $component_value;
			}
		}

		return [
			'$$type' => 'flex',
			'value'  => $merged_value,
		];
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
