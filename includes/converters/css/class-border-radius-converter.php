<?php
namespace ElementorHtmlCssConverter\Converters\Css;

use ElementorHtmlCssConverter\Abstracts\Property_Converter_Base;
use ElementorHtmlCssConverter\Parsers\Size_Value_Parser;
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

	public function convert( string $property, $value ): ?array {
		if ( ! $this->supports( $property ) ) {
			return null;
		}

		if ( ! $this->is_valid_string_value( $value ) ) {
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

	private function is_valid_string_value( $value ): bool {
		return is_string( $value ) && '' !== trim( $value );
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

		$size_value = Size_Value_Parser::parse( $value );

		if ( null === $size_value ) {
			return null;
		}

		return [
			'start-start' => 'start-start' === $logical_corner ? $this->create_size_prop( $size_value ) : null,
			'start-end' => 'start-end' === $logical_corner ? $this->create_size_prop( $size_value ) : null,
			'end-end' => 'end-end' === $logical_corner ? $this->create_size_prop( $size_value ) : null,
			'end-start' => 'end-start' === $logical_corner ? $this->create_size_prop( $size_value ) : null,
		];
	}

	private function parse_shorthand_border_radius( string $value ): ?array {
		$values = preg_split( '/\s+/', trim( $value ) );
		$values = array_filter( $values );

		if ( empty( $values ) ) {
			return null;
		}

		$parsed_values = array_map( [ Size_Value_Parser::class, 'parse' ], $values );

		if ( in_array( null, $parsed_values, true ) ) {
			return null;
		}

		return $this->map_shorthand_values_to_corners( $parsed_values );
	}

	private function map_shorthand_values_to_corners( array $parsed_values ): ?array {
		$count = count( $parsed_values );

		switch ( $count ) {
			case 1:
				$size_prop = $this->create_size_prop( $parsed_values[0] );
				return [
					'start-start' => $size_prop,
					'start-end' => $size_prop,
					'end-end' => $size_prop,
					'end-start' => $size_prop,
				];

			case 2:
				$tl_br = $this->create_size_prop( $parsed_values[0] );
				$tr_bl = $this->create_size_prop( $parsed_values[1] );
				return [
					'start-start' => $tl_br,
					'start-end' => $tr_bl,
					'end-end' => $tl_br,
					'end-start' => $tr_bl,
				];

			case 3:
				return [
					'start-start' => $this->create_size_prop( $parsed_values[0] ),
					'start-end' => $this->create_size_prop( $parsed_values[1] ),
					'end-end' => $this->create_size_prop( $parsed_values[2] ),
					'end-start' => $this->create_size_prop( $parsed_values[1] ),
				];

			case 4:
				return [
					'start-start' => $this->create_size_prop( $parsed_values[0] ),
					'start-end' => $this->create_size_prop( $parsed_values[1] ),
					'end-end' => $this->create_size_prop( $parsed_values[2] ),
					'end-start' => $this->create_size_prop( $parsed_values[3] ),
				];

			default:
				return null;
		}
	}

	private function create_size_prop( ?array $size_value ): array {
		if ( null === $size_value ) {
			return $this->create_zero_size();
		}

		return Size_Prop_Type::generate( $size_value );
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
