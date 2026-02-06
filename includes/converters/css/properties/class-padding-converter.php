<?php
namespace ElementorHtmlCssConverter\Converters\Css\Properties;

use ElementorHtmlCssConverter\Converters\Abstracts\Property_Converter_Base;
use ElementorHtmlCssConverter\Converters\Css\Size_Value_Parser;
use ElementorHtmlCssConverter\Converters\Variables\Variable_Resolver;
use Elementor\Modules\AtomicWidgets\PropTypes\Dimensions_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Size_Prop_Type;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Padding_Converter extends Property_Converter_Base {

	private const REGEX_WHITESPACE_SPLIT = '/\s+/';
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

		if ( ! is_string( $value ) || '' === trim( $value ) ) {
			return null;
		}

		$dimensions_data = $this->parse_padding_property( $property, (string) $value );

		if ( null === $dimensions_data ) {
			return null;
		}

		return Dimensions_Prop_Type::generate( $dimensions_data );
	}

	protected function convert_value( string $property, $value ): ?array {
		return null;
	}

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
		$resolved = $this->resolve_size_value( $value );

		if ( null === $resolved ) {
			return null;
		}

		return [
			$logical_side => $resolved,
		];
	}

	private function parse_shorthand_to_logical_properties( string $value ): ?array {
		$values = preg_split( self::REGEX_WHITESPACE_SPLIT, trim( $value ) );
		$count  = count( $values );

		switch ( $count ) {
			case 1:
				$size_prop = $this->resolve_size_value( $values[0] );
				if ( null === $size_prop ) {
					return null;
				}
				return [
					'block-start'  => $size_prop,
					'inline-end'   => $size_prop,
					'block-end'    => $size_prop,
					'inline-start' => $size_prop,
				];

			case 2:
				$vertical   = $this->resolve_size_value( $values[0] );
				$horizontal = $this->resolve_size_value( $values[1] );
				if ( null === $vertical || null === $horizontal ) {
					return null;
				}
				return [
					'block-start'  => $vertical,
					'inline-end'   => $horizontal,
					'block-end'    => $vertical,
					'inline-start' => $horizontal,
				];

			case 3:
				$top        = $this->resolve_size_value( $values[0] );
				$horizontal = $this->resolve_size_value( $values[1] );
				$bottom     = $this->resolve_size_value( $values[2] );
				if ( null === $top || null === $horizontal || null === $bottom ) {
					return null;
				}
				return [
					'block-start'  => $top,
					'inline-end'   => $horizontal,
					'block-end'    => $bottom,
					'inline-start' => $horizontal,
				];

			case 4:
				$block_start  = $this->resolve_size_value( $values[0] );
				$inline_end   = $this->resolve_size_value( $values[1] );
				$block_end    = $this->resolve_size_value( $values[2] );
				$inline_start = $this->resolve_size_value( $values[3] );
				if ( null === $block_start || null === $inline_end || null === $block_end || null === $inline_start ) {
					return null;
				}
				return [
					'block-start'  => $block_start,
					'inline-end'   => $inline_end,
					'block-end'    => $block_end,
					'inline-start' => $inline_start,
				];

			default:
				return null;
		}
	}

	private function parse_logical_shorthand( string $value, string $axis ): ?array {
		$values = preg_split( self::REGEX_WHITESPACE_SPLIT, trim( $value ) );
		$count  = count( $values );

		if ( 1 === $count ) {
			$size_prop = $this->resolve_size_value( $values[0] );
			if ( null !== $size_prop ) {
				return [
					$axis . '-start' => $size_prop,
					$axis . '-end'   => $size_prop,
				];
			}
			return null;
		}

		if ( 2 === $count ) {
			$start = $this->resolve_size_value( $values[0] );
			$end   = $this->resolve_size_value( $values[1] );
			if ( null !== $start && null !== $end ) {
				return [
					$axis . '-start' => $start,
					$axis . '-end'   => $end,
				];
			}
			return null;
		}

		return null;
	}
}

