<?php
namespace ElementorHtmlCssConverter\Converters\Css;

use ElementorHtmlCssConverter\Converters\Abstracts\Property_Converter_Base;
use ElementorHtmlCssConverter\Converters\Parsers\Size_Value_Parser;
use Elementor\Modules\AtomicWidgets\PropTypes\Dimensions_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Size_Prop_Type;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Padding_Converter extends Property_Converter_Base {

	private const SUPPORTED_PROPERTIES = [
		'padding',
		'padding-top',
		'padding-right',
		'padding-bottom',
		'padding-left',
		'padding-block-start',
		'padding-block-end',
		'padding-inline-start',
		'padding-inline-end',
		'padding-block',
		'padding-inline',
	];

	private const OUTPUT_PROPERTY = 'padding';

	protected function get_supported_properties_list(): array {
		return self::SUPPORTED_PROPERTIES;
	}

	public function get_output_property( string $property ): string {
		return self::OUTPUT_PROPERTY;
	}

	public function convert( string $property, $value ): ?array {
		if ( ! $this->supports( $property ) ) {
			return null;
		}

		if ( ! $this->is_valid_string_value( $value ) ) {
			return null;
		}

		$dimensions_data = $this->parse_padding_property( $property, (string) $value );

		if ( null === $dimensions_data ) {
			return null;
		}

		return Dimensions_Prop_Type::generate( $dimensions_data );
	}

	private function is_valid_string_value( $value ): bool {
		return is_string( $value ) && '' !== trim( $value );
	}

	private function parse_padding_property( string $property, string $value ): ?array {
		switch ( $property ) {
			case 'padding':
				return $this->parse_shorthand_to_logical_properties( $value );

			case 'padding-top':
			case 'padding-block-start':
				return $this->create_single_dimension( 'block-start', $value );

			case 'padding-right':
			case 'padding-inline-end':
				return $this->create_single_dimension( 'inline-end', $value );

			case 'padding-bottom':
			case 'padding-block-end':
				return $this->create_single_dimension( 'block-end', $value );

			case 'padding-left':
			case 'padding-inline-start':
				return $this->create_single_dimension( 'inline-start', $value );

			case 'padding-block':
				return $this->parse_logical_shorthand( $value, 'block' );

			case 'padding-inline':
				return $this->parse_logical_shorthand( $value, 'inline' );

			default:
				return null;
		}
	}

	private function create_single_dimension( string $logical_side, string $value ): ?array {
		$parsed = Size_Value_Parser::parse( $value );

		if ( null === $parsed ) {
			return null;
		}

		return [
			$logical_side => Size_Prop_Type::generate( $parsed ),
		];
	}

	private function parse_shorthand_to_logical_properties( string $value ): ?array {
		$values = preg_split( '/\s+/', trim( $value ) );
		$count  = count( $values );

		switch ( $count ) {
			case 1:
				$parsed = Size_Value_Parser::parse( $values[0] );
				if ( null === $parsed ) {
					return null;
				}
				$size_prop = Size_Prop_Type::generate( $parsed );
				return [
					'block-start'  => $size_prop,
					'inline-end'   => $size_prop,
					'block-end'    => $size_prop,
					'inline-start' => $size_prop,
				];

			case 2:
				$vertical   = Size_Value_Parser::parse( $values[0] );
				$horizontal = Size_Value_Parser::parse( $values[1] );
				if ( null === $vertical || null === $horizontal ) {
					return null;
				}
				return [
					'block-start'  => Size_Prop_Type::generate( $vertical ),
					'inline-end'   => Size_Prop_Type::generate( $horizontal ),
					'block-end'    => Size_Prop_Type::generate( $vertical ),
					'inline-start' => Size_Prop_Type::generate( $horizontal ),
				];

			case 3:
				$top        = Size_Value_Parser::parse( $values[0] );
				$horizontal = Size_Value_Parser::parse( $values[1] );
				$bottom     = Size_Value_Parser::parse( $values[2] );
				if ( null === $top || null === $horizontal || null === $bottom ) {
					return null;
				}
				return [
					'block-start'  => Size_Prop_Type::generate( $top ),
					'inline-end'   => Size_Prop_Type::generate( $horizontal ),
					'block-end'    => Size_Prop_Type::generate( $bottom ),
					'inline-start' => Size_Prop_Type::generate( $horizontal ),
				];

			case 4:
				$block_start  = Size_Value_Parser::parse( $values[0] );
				$inline_end   = Size_Value_Parser::parse( $values[1] );
				$block_end    = Size_Value_Parser::parse( $values[2] );
				$inline_start = Size_Value_Parser::parse( $values[3] );
				if ( null === $block_start || null === $inline_end || null === $block_end || null === $inline_start ) {
					return null;
				}
				return [
					'block-start'  => Size_Prop_Type::generate( $block_start ),
					'inline-end'   => Size_Prop_Type::generate( $inline_end ),
					'block-end'    => Size_Prop_Type::generate( $block_end ),
					'inline-start' => Size_Prop_Type::generate( $inline_start ),
				];

			default:
				return null;
		}
	}

	private function parse_logical_shorthand( string $value, string $axis ): ?array {
		$values = preg_split( '/\s+/', trim( $value ) );
		$count  = count( $values );

		if ( 1 === $count ) {
			$parsed = Size_Value_Parser::parse( $values[0] );
			if ( null === $parsed ) {
				return null;
			}
			$size_prop = Size_Prop_Type::generate( $parsed );
			return [
				$axis . '-start' => $size_prop,
				$axis . '-end'   => $size_prop,
			];
		}

		if ( 2 === $count ) {
			$start = Size_Value_Parser::parse( $values[0] );
			$end   = Size_Value_Parser::parse( $values[1] );
			if ( null === $start || null === $end ) {
				return null;
			}
			return [
				$axis . '-start' => Size_Prop_Type::generate( $start ),
				$axis . '-end'   => Size_Prop_Type::generate( $end ),
			];
		}

		return null;
	}
}
