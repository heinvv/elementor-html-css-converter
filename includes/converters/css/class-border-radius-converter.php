<?php
namespace ElementorHtmlCssConverter\Converters\Css;

use ElementorHtmlCssConverter\Converters\Abstracts\Property_Converter_Base;
use ElementorHtmlCssConverter\Converters\Parsers\Size_Value_Parser;
use ElementorHtmlCssConverter\Converters\Variables\Variable_Resolver;
use Elementor\Modules\AtomicWidgets\PropTypes\Border_Radius_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Size_Prop_Type;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Border_Radius_Converter extends Property_Converter_Base {

	private const SUPPORTED_PROPERTIES = [
		'border-radius',
		'border-top-left-radius',
		'border-top-right-radius',
		'border-bottom-left-radius',
		'border-bottom-right-radius',
		'border-start-start-radius',
		'border-start-end-radius',
		'border-end-start-radius',
		'border-end-end-radius',
	];

	private const LOGICAL_TO_PHYSICAL_MAPPING = [
		'border-start-start-radius' => 'border-top-left-radius',
		'border-start-end-radius' => 'border-top-right-radius',
		'border-end-start-radius' => 'border-bottom-left-radius',
		'border-end-end-radius' => 'border-bottom-right-radius',
	];

	private const PHYSICAL_TO_LOGICAL_CORNER = [
		'border-top-left-radius' => 'start-start',
		'border-top-right-radius' => 'start-end',
		'border-bottom-right-radius' => 'end-end',
		'border-bottom-left-radius' => 'end-start',
	];

	protected function get_supported_properties_list(): array {
		return self::SUPPORTED_PROPERTIES;
	}

	public function get_output_property( string $property ): string {
		return 'border-radius';
	}

	/**
	 * Override convert to handle shorthand properties with multiple values.
	 * Each value needs individual variable resolution.
	 */
	public function convert( string $property, $value ): ?array {
		if ( ! $this->supports( $property ) ) {
			return null;
		}

		if ( ! is_string( $value ) || '' === trim( $value ) ) {
			return null;
		}

		$value = trim( $value );

		if ( $this->contains_elliptical_syntax( $value ) ) {
			return null;
		}

		if ( 'border-radius' === $property ) {
			$border_radius_value = $this->parse_shorthand_border_radius( $value );
		} else {
			$border_radius_value = $this->convert_individual_corner( $property, $value );
		}

		if ( null === $border_radius_value ) {
			return null;
		}

		return Border_Radius_Prop_Type::generate( $border_radius_value );
	}

	protected function convert_value( string $property, $value ): ?array {
		// Not used - convert() handles everything for shorthand properties.
		return null;
	}

	/**
	 * Resolve a single size value, handling CSS variables.
	 */
	private function resolve_size_value( string $value ): ?array {
		$value = trim( $value );

		if ( Variable_Resolver::is_css_variable( $value ) ) {
			return Variable_Resolver::resolve( $value, 'size' );
		}

		$parsed = Size_Value_Parser::parse( $value );

		if ( null === $parsed ) {
			return null;
		}

		return Size_Prop_Type::generate( $parsed );
	}

	private function contains_elliptical_syntax( string $value ): bool {
		return str_contains( $value, '/' );
	}

	private function convert_individual_corner( string $property, string $value ): ?array {
		$physical_property = $this->map_logical_to_physical( $property );
		$logical_corner = self::PHYSICAL_TO_LOGICAL_CORNER[ $physical_property ] ?? null;

		if ( null === $logical_corner ) {
			return null;
		}

		$resolved = $this->resolve_size_value( $value );

		if ( null === $resolved ) {
			return null;
		}

		return [
			'start-start' => 'start-start' === $logical_corner ? $resolved : null,
			'start-end' => 'start-end' === $logical_corner ? $resolved : null,
			'end-end' => 'end-end' === $logical_corner ? $resolved : null,
			'end-start' => 'end-start' === $logical_corner ? $resolved : null,
		];
	}

	private function parse_shorthand_border_radius( string $value ): ?array {
		$values = preg_split( '/\s+/', trim( $value ) );
		$values = array_filter( $values );

		if ( empty( $values ) ) {
			return null;
		}

		// Resolve each value (handles CSS variables)
		$resolved_values = [];
		foreach ( $values as $val ) {
			$resolved = $this->resolve_size_value( $val );
			if ( null === $resolved ) {
				return null;
			}
			$resolved_values[] = $resolved;
		}

		return $this->map_shorthand_values_to_corners( $resolved_values );
	}

	private function map_shorthand_values_to_corners( array $resolved_values ): ?array {
		$count = count( $resolved_values );

		switch ( $count ) {
			case 1:
				return [
					'start-start' => $resolved_values[0],
					'start-end' => $resolved_values[0],
					'end-end' => $resolved_values[0],
					'end-start' => $resolved_values[0],
				];

			case 2:
				return [
					'start-start' => $resolved_values[0],
					'start-end' => $resolved_values[1],
					'end-end' => $resolved_values[0],
					'end-start' => $resolved_values[1],
				];

			case 3:
				return [
					'start-start' => $resolved_values[0],
					'start-end' => $resolved_values[1],
					'end-end' => $resolved_values[2],
					'end-start' => $resolved_values[1],
				];

			case 4:
				return [
					'start-start' => $resolved_values[0],
					'start-end' => $resolved_values[1],
					'end-end' => $resolved_values[2],
					'end-start' => $resolved_values[3],
				];

			default:
				return null;
		}
	}

	private function create_zero_size(): array {
		return Size_Prop_Type::generate( [
			'size' => 0.0,
			'unit' => 'px',
		] );
	}

	private function map_logical_to_physical( string $property ): string {
		return self::LOGICAL_TO_PHYSICAL_MAPPING[ $property ] ?? $property;
	}
}
