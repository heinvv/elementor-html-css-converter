<?php
/**
 * CSS Converter Class
 *
 * @package ElementorHtmlCssConverter
 */

namespace ElementorHtmlCssConverter\Converters\Css;

use ElementorHtmlCssConverter\Converters\Css\Property_Converter_Interface;
use ElementorHtmlCssConverter\Converters\Classes\Converter_Registry;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
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
			if ( null === $converter ) {
				$unsupported[ $property ] = $value;
				continue;
			}

			$converted = $converter->convert( $property, $value );
			if ( null === $converted ) {
				$unsupported[ $property ] = $value;
				continue;
			}

			if ( $this->is_multi_property_result( $converted ) ) {
				foreach ( $converted as $expanded_property => $expanded_value ) {
					$props[ $expanded_property ] = $this->merge_props( $props[ $expanded_property ] ?? null, $expanded_value );
				}
			} else {
				$output_property = $converter->get_output_property( $property );
				$props[ $output_property ] = $this->merge_props( $props[ $output_property ] ?? null, $converted );
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
		if ( isset( $converted['$$type'] ) ) {
			return false;
		}

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

		if ( $this->is_dimensions_prop( $existing ) && $this->is_dimensions_prop( $new ) ) {
			return $this->merge_dimensions( $existing, $new );
		}

		if ( $this->is_flex_prop( $existing ) && $this->is_flex_prop( $new ) ) {
			return $this->merge_flex( $existing, $new );
		}

		if ( $this->is_background_prop( $existing ) && $this->is_background_prop( $new ) ) {
			return $this->merge_background( $existing, $new );
		}

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
	 * Check if a prop is a background type.
	 *
	 * @param array $prop The prop to check.
	 * @return bool True if it's a background prop.
	 */
	private function is_background_prop( array $prop ): bool {
		return isset( $prop['$$type'] ) && 'background' === $prop['$$type'];
	}

	/**
	 * Merge two background props, combining their values.
	 *
	 * Merges color and background-overlay properties.
	 *
	 * @param array $existing Existing background prop.
	 * @param array $new      New background prop to merge.
	 * @return array The merged background prop.
	 */
	private function merge_background( array $existing, array $new ): array {
		$merged_value = $existing['value'] ?? [];

		if ( isset( $new['value'] ) && is_array( $new['value'] ) ) {
			if ( isset( $new['value']['color'] ) ) {
				$merged_value['color'] = $new['value']['color'];
			}

			if ( isset( $new['value']['background-overlay'] ) ) {
				if ( isset( $merged_value['background-overlay'] ) ) {
					$merged_value['background-overlay'] = $this->merge_background_overlay(
						$merged_value['background-overlay'],
						$new['value']['background-overlay']
					);
				} else {
					$merged_value['background-overlay'] = $new['value']['background-overlay'];
				}
			}
		}

		return [
			'$$type' => 'background',
			'value'  => $merged_value,
		];
	}

	/**
	 * Merge two background overlay props.
	 *
	 * Combines overlay arrays, merging image overlays when possible.
	 *
	 * @param array $existing Existing overlay prop.
	 * @param array $new      New overlay prop to merge.
	 * @return array The merged overlay prop.
	 */
	private function merge_background_overlay( array $existing, array $new ): array {
		$existing_value = $existing['value'] ?? [];
		$new_value = $new['value'] ?? [];

		if ( empty( $existing_value ) ) {
			return $new;
		}

		if ( empty( $new_value ) ) {
			return $existing;
		}

		$merged_overlays = $existing_value;

		foreach ( $new_value as $overlay ) {
			if ( isset( $overlay['$$type'] ) && 'background-image-overlay' === $overlay['$$type'] ) {
				$overlay_value = $overlay['value'] ?? [];
				$image_value = $overlay_value['image'] ?? null;

				$merged_into_existing = false;
				foreach ( $merged_overlays as $index => $existing_overlay ) {
					if ( isset( $existing_overlay['$$type'] ) && 'background-image-overlay' === $existing_overlay['$$type'] ) {
						$existing_overlay_value = $existing_overlay['value'] ?? [];
						$existing_image_value = $existing_overlay_value['image'] ?? null;

						if ( $this->images_match( $image_value, $existing_image_value ) ) {
							$merged_overlays[ $index ] = $this->merge_image_overlay( $existing_overlay, $overlay );
							$merged_into_existing = true;
							break;
						}
					}
				}

				if ( ! $merged_into_existing ) {
					$merged_overlays[] = $overlay;
				}
			} else {
				$merged_overlays[] = $overlay;
			}
		}

		return [
			'$$type' => 'background-overlay',
			'value'  => $merged_overlays,
		];
	}

	/**
	 * Check if two image values match.
	 *
	 * @param array|null $image1 First image value.
	 * @param array|null $image2 Second image value.
	 * @return bool True if images match.
	 */
	private function images_match( ?array $image1, ?array $image2 ): bool {
		if ( null === $image1 || null === $image2 ) {
			return false;
		}

		$src1 = $image1['value']['src'] ?? null;
		$src2 = $image2['value']['src'] ?? null;

		if ( null === $src1 || null === $src2 ) {
			return false;
		}

		$url1 = $src1['value']['url'] ?? $src1['value']['id'] ?? null;
		$url2 = $src2['value']['url'] ?? $src2['value']['id'] ?? null;

		return $url1 === $url2;
	}

	/**
	 * Merge two image overlay props.
	 *
	 * Merges repeat, size, position, and attachment properties.
	 *
	 * @param array $existing Existing image overlay.
	 * @param array $new      New image overlay to merge.
	 * @return array The merged image overlay.
	 */
	private function merge_image_overlay( array $existing, array $new ): array {
		$existing_value = $existing['value'] ?? [];
		$new_value = $new['value'] ?? [];

		$merged_value = $existing_value;

		if ( isset( $new_value['repeat'] ) ) {
			$merged_value['repeat'] = $new_value['repeat'];
		}

		if ( isset( $new_value['size'] ) ) {
			$merged_value['size'] = $new_value['size'];
		}

		if ( isset( $new_value['position'] ) ) {
			$merged_value['position'] = $new_value['position'];
		}

		if ( isset( $new_value['attachment'] ) ) {
			$merged_value['attachment'] = $new_value['attachment'];
		}

		return [
			'$$type' => 'background-image-overlay',
			'value'  => $merged_value,
		];
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
