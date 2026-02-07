<?php
namespace ElementorHtmlCssConverter\Converters\Css\Properties;

use ElementorHtmlCssConverter\Converters\Css\Property_Converter_Base;
use ElementorHtmlCssConverter\Converters\Css\Size_Value_Parser;
use ElementorHtmlCssConverter\Converters\Variables\Variable_Resolver;
use Elementor\Modules\AtomicWidgets\PropTypes\Border_Radius_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Size_Prop_Type;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Border_Radius_Converter extends Property_Converter_Base {

	private const REGEX_WHITESPACE_SPLIT = '/\s+/';
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
		return null;
	}

	private function resolve_size_value( string $value ): ?array {
		$value = trim( $value );

		if ( Variable_Resolver::is_css_variable( $value ) ) {
			return Variable_Resolver::resolve( $value, 'size' );
		}

		if ( $this->is_css_function( $value ) ) {
			return Size_Prop_Type::generate( [
				'size' => $value,
				'unit' => 'custom',
			] );
		}

		if ( $this->is_zero_value( $value ) ) {
			return Size_Prop_Type::generate( [
				'size' => 0.0,
				'unit' => 'px',
			] );
		}

		$parsed = Size_Value_Parser::parse( $value );

		if ( null === $parsed ) {
			return null;
		}

		return Size_Prop_Type::generate( $parsed );
	}

	private function is_zero_value( string $value ): bool {
		return '0' === $value || '0px' === strtolower( $value );
	}

	private function is_css_function( string $value ): bool {
		$value_lower = strtolower( $value );
		return str_starts_with( $value_lower, 'max(' ) ||
			str_starts_with( $value_lower, 'min(' ) ||
			str_starts_with( $value_lower, 'clamp(' ) ||
			str_starts_with( $value_lower, 'calc(' );
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
		$values = $this->split_shorthand_value( trim( $value ) );
		$values = array_filter( $values, fn( $v ) => '' !== trim( $v ) );

		if ( empty( $values ) ) {
			return null;
		}

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

	private function split_shorthand_value( string $value ): array {
		$values = [];
		$current = '';
		$depth = 0;
		$length = strlen( $value );

		for ( $i = 0; $i < $length; $i++ ) {
			$char = $value[ $i ];

			if ( '(' === $char ) {
				$depth++;
				$current .= $char;
			} elseif ( ')' === $char ) {
				$depth--;
				$current .= $char;
			} elseif ( preg_match( '/\s/', $char ) && 0 === $depth ) {
				if ( '' !== trim( $current ) ) {
					$values[] = trim( $current );
					$current = '';
				}
			} else {
				$current .= $char;
			}
		}

		if ( '' !== trim( $current ) ) {
			$values[] = trim( $current );
		}

		return $values;
	}
}

