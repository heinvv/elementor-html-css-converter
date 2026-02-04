<?php
namespace ElementorHtmlCssConverter\Converters\Css;

use ElementorHtmlCssConverter\Converters\Abstracts\Property_Converter_Base;
use ElementorHtmlCssConverter\Converters\Css\Size_Value_Parser;
use ElementorHtmlCssConverter\Converters\Variables\Variable_Resolver;
use Elementor\Modules\AtomicWidgets\PropTypes\Layout_Direction_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Size_Prop_Type;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Gap_Converter extends Property_Converter_Base {

	private const SUPPORTED_PROPERTIES = [
		'gap',
		'row-gap',
		'column-gap',
	];

	private const OUTPUT_PROPERTY = 'gap';

	protected function get_supported_properties_list(): array {
		return self::SUPPORTED_PROPERTIES;
	}

	public function get_output_property( string $property ): string {
		return self::OUTPUT_PROPERTY;
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

		switch ( $property ) {
			case 'gap':
				return $this->parse_gap_shorthand( $value );

			case 'row-gap':
				return $this->parse_row_gap( $value );

			case 'column-gap':
				return $this->parse_column_gap( $value );

			default:
				return null;
		}
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

	private function parse_gap_shorthand( string $value ): ?array {
		$parts = preg_split( '/\s+/', trim( $value ) );
		$count = count( $parts );

		if ( 0 === $count || $count > 2 ) {
			return null;
		}

		$row_gap = $this->resolve_size_value( $parts[0] );

		if ( null === $row_gap ) {
			return null;
		}

		// If only one value, use same for both row and column
		if ( 1 === $count ) {
			$column_gap = $row_gap;
		} else {
			$column_gap = $this->resolve_size_value( $parts[1] );

			if ( null === $column_gap ) {
				return null;
			}
		}

		return Layout_Direction_Prop_Type::generate( [
			'row'    => $row_gap,
			'column' => $column_gap,
		] );
	}

	private function parse_row_gap( string $value ): ?array {
		$resolved = $this->resolve_size_value( $value );

		if ( null === $resolved ) {
			return null;
		}

		return Layout_Direction_Prop_Type::generate( [
			'row' => $resolved,
		] );
	}

	private function parse_column_gap( string $value ): ?array {
		$resolved = $this->resolve_size_value( $value );

		if ( null === $resolved ) {
			return null;
		}

		return Layout_Direction_Prop_Type::generate( [
			'column' => $resolved,
		] );
	}
}
