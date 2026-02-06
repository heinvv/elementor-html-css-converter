<?php
/**
 * Class Conversion Service
 *
 * Service that converts extracted CSS classes to Elementor's atomic format.
 *
 * @package ElementorHtmlCssConverter
 */

namespace ElementorHtmlCssConverter\Converters\Classes;

use ElementorHtmlCssConverter\Converters\Css\Css_Converter;
use ElementorHtmlCssConverter\Converters\Classes\Converter_Registry;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Class_Conversion_Service
 *
 * Converts extracted CSS class properties to Elementor atomic format.
 */
class Class_Conversion_Service {

	/**
	 * The CSS converter.
	 *
	 * @var Css_Converter
	 */
	private Css_Converter $css_converter;

	/**
	 * Constructor.
	 *
	 * @param Converter_Registry $registry The converter registry.
	 */
	public function __construct( Converter_Registry $registry ) {
		$this->css_converter = new Css_Converter( $registry );
	}

	/**
	 * Convert breakpoint-aware classes to atomic format.
	 *
	 * @param array $breakpoint_classes Array of breakpoint-aware class definitions.
	 *                                  Format: ['class-name' => ['desktop' => [...], 'tablet' => [...]]]
	 * @return array Converted classes with atomic props per breakpoint.
	 */
	public function convert_to_atomic( array $breakpoint_classes ): array {
		$converted = [];

		foreach ( $breakpoint_classes as $class_name => $breakpoint_data ) {
			$breakpoint_props = [];

			foreach ( $breakpoint_data as $breakpoint => $class_data ) {
				if ( 'desktop' === $breakpoint || 'tablet' === $breakpoint || 'mobile' === $breakpoint || 
					 'mobile_extra' === $breakpoint || 'tablet_extra' === $breakpoint || 
					 'laptop' === $breakpoint || 'widescreen' === $breakpoint ) {
					$properties = $class_data['properties'] ?? [];

					if ( empty( $properties ) ) {
						continue;
					}

					$css_string = $this->build_css_string( $properties );
					$result     = $this->css_converter->convert( [ 'cssString' => $css_string ] );

					$breakpoint_props[ $breakpoint ] = [
						'atomic_props' => $result['props'] ?? [],
						'custom_css'   => $result['customCss'] ?? null,
					];
				}
			}

			if ( ! empty( $breakpoint_props ) ) {
				$converted[ $class_name ] = [
					'label'             => $class_name,
					'breakpoint_props'   => $breakpoint_props,
					'original_selector' => '.' . $class_name,
				];
			}
		}

		return $converted;
	}

	/**
	 * Build CSS string from properties array.
	 *
	 * @param array $properties Associative array of property => value.
	 * @return string CSS declaration string.
	 */
	private function build_css_string( array $properties ): string {
		$css_parts = [];

		foreach ( $properties as $property => $value ) {
			$css_parts[] = sprintf( '%s: %s;', $property, $value );
		}

		return implode( ' ', $css_parts );
	}

	/**
	 * Get conversion statistics.
	 *
	 * @param array $converted Converted classes from convert_to_atomic().
	 * @return array Statistics about the conversion.
	 */
	public function get_conversion_stats( array $converted ): array {
		$stats = [
			'total_classes'         => count( $converted ),
			'with_atomic_props'     => 0,
			'with_custom_css'       => 0,
			'empty_conversions'     => 0,
			'total_atomic_props'    => 0,
		];

		foreach ( $converted as $class_data ) {
			$atomic_props = $class_data['atomic_props'] ?? [];
			$custom_css   = $class_data['custom_css'] ?? null;

			if ( ! empty( $atomic_props ) ) {
				++$stats['with_atomic_props'];
				$stats['total_atomic_props'] += count( $atomic_props );
			}

			if ( ! empty( $custom_css ) ) {
				++$stats['with_custom_css'];
			}

			if ( empty( $atomic_props ) && empty( $custom_css ) ) {
				++$stats['empty_conversions'];
			}
		}

		return $stats;
	}
}

