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

class Css_Converter {
	private const PATTERN_CSS_DECLARATION = '/([a-zA-Z0-9-]+)\s*:\s*([^;]+);?/';

	private const PLACEHOLDER_IMAGE_URL = 'none';

	private const BLOCKED_CSS_PROPERTIES = [
		'behavior',
		'-moz-binding',
	];

	private const PATTERN_CSS_EXPRESSION = '/expression\s*\(/i';
	private const PATTERN_CSS_JAVASCRIPT_URL = '/url\s*\(\s*["\']?\s*javascript\s*:/i';
	private const PATTERN_CSS_IMPORT = '/@import\b/i';

	private Converter_Registry $registry;

	public function __construct( Converter_Registry $registry ) {
		$this->registry = $registry;
	}

	public function convert( array $params ): array {
		$css_string = $params['cssString'] ?? '';

		$properties = $this->parse_css_string( $css_string );
		$result     = $this->convert_properties_to_atomic( $properties );

		return $result;
	}

	public function convert_properties( array $properties ): array {
		return $this->convert_properties_to_atomic( $properties );
	}

	private function parse_css_string( string $css_string ): array {
		$css_string = preg_replace( self::PATTERN_CSS_IMPORT, '', $css_string );

		$properties = [];
		$pattern    = self::PATTERN_CSS_DECLARATION;

		if ( preg_match_all( $pattern, $css_string, $matches, PREG_SET_ORDER ) ) {
			foreach ( $matches as $match ) {
				$property = trim( $match[1] );
				$value    = trim( $match[2] );

				if ( $this->is_blocked_css_property( $property ) ) {
					continue;
				}

				$properties[ $property ] = $this->sanitize_css_value( $value );
			}
		}

		return $properties;
	}

	private function is_blocked_css_property( string $property ): bool {
		return in_array( strtolower( $property ), self::BLOCKED_CSS_PROPERTIES, true );
	}

	private function sanitize_css_value( string $value ): string {
		$sanitized = preg_replace( self::PATTERN_CSS_EXPRESSION, '(', $value );
		$sanitized = preg_replace( self::PATTERN_CSS_JAVASCRIPT_URL, 'url(', $sanitized );

		return $sanitized;
	}

	private function convert_properties_to_atomic( array $properties ): array {
		$props       = [];
		$unsupported = [];

		foreach ( $properties as $property => $value ) {
			if ( $this->is_blocked_css_property( $property ) ) {
				continue;
			}

			$value = $this->sanitize_css_value( $value );

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

		if ( isset( $props['background'] ) && $this->is_background_prop( $props['background'] ) ) {
			$props['background'] = $this->remove_placeholder_image_overlays( $props['background'] );
		}

		$result = [
			'props' => $props,
		];

		if ( ! empty( $unsupported ) ) {
			$result['customCss'] = $this->format_custom_css( $unsupported );
		}

		return $result;
	}

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

	private function is_dimensions_prop( array $prop ): bool {
		return isset( $prop['$$type'] ) && 'dimensions' === $prop['$$type'];
	}

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

	private function is_flex_prop( array $prop ): bool {
		return isset( $prop['$$type'] ) && 'flex' === $prop['$$type'];
	}

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

	private function format_custom_css( array $properties ): string {
		if ( empty( $properties ) ) {
			return '';
		}

		$css_parts = [];
		foreach ( $properties as $property => $value ) {
			if ( $this->is_blocked_css_property( $property ) ) {
				continue;
			}

			$sanitized_value = $this->sanitize_css_value( $value );
			$css_parts[] = sprintf( '%s: %s;', $property, $sanitized_value );
		}

		return implode( ' ', $css_parts );
	}

	private function is_background_prop( array $prop ): bool {
		return isset( $prop['$$type'] ) && 'background' === $prop['$$type'];
	}

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

			if ( isset( $new['value']['clip'] ) ) {
				$merged_value['clip'] = $new['value']['clip'];
			}
		}

		return [
			'$$type' => 'background',
			'value'  => $merged_value,
		];
	}

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
			'value'  => array_values( $merged_overlays ),
		];
	}

	private function remove_placeholder_image_overlays( array $background_prop ): array {
		$value = $background_prop['value'] ?? [];

		if ( ! isset( $value['background-overlay'] ) ) {
			return $background_prop;
		}

		$overlay = $value['background-overlay'];
		$overlay_items = $overlay['value'] ?? [];

		if ( ! is_array( $overlay_items ) || empty( $overlay_items ) ) {
			return $background_prop;
		}

		$filtered_items = array_filter( $overlay_items, function( $item ) {
			if ( ! isset( $item['$$type'] ) || 'background-image-overlay' !== $item['$$type'] ) {
				return true;
			}

			return ! $this->is_placeholder_image_overlay( $item );
		} );

		if ( empty( $filtered_items ) ) {
			unset( $value['background-overlay'] );
		} else {
			$value['background-overlay'] = [
				'$$type' => 'background-overlay',
				'value'  => array_values( $filtered_items ),
			];
		}

		return [
			'$$type' => 'background',
			'value'  => $value,
		];
	}

	private function is_placeholder_image_overlay( array $overlay ): bool {
		$overlay_value = $overlay['value'] ?? [];
		$image = $overlay_value['image'] ?? null;

		if ( null === $image || ! is_array( $image ) ) {
			return true;
		}

		$image_value = $image['value'] ?? [];
		$src = $image_value['src'] ?? null;

		if ( null === $src || ! is_array( $src ) ) {
			return true;
		}

		$src_value = $src['value'] ?? [];
		$url = $src_value['url'] ?? null;
		$id = $src_value['id'] ?? null;

		if ( self::PLACEHOLDER_IMAGE_URL === $url && null === $id ) {
			return true;
		}

		return false;
	}

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

	private function get_converter_for_property( string $property ): ?Property_Converter_Interface {
		return $this->registry->resolve( $property );
	}
}
