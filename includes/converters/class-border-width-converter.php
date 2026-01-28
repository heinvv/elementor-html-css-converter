<?php
namespace ElementorHtmlCssConverter\Converters;

use ElementorHtmlCssConverter\Abstracts\Property_Converter_Base;
use ElementorHtmlCssConverter\Parsers\Size_Value_Parser;
use Elementor\Modules\AtomicWidgets\PropTypes\Size_Prop_Type;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Border_Width_Converter extends Property_Converter_Base {

	private const SUPPORTED_PROPERTIES = [
		'border-width',
		'border-top-width',
		'border-right-width',
		'border-bottom-width',
		'border-left-width',
		'border-block-start-width',
		'border-block-end-width',
		'border-inline-start-width',
		'border-inline-end-width',
	];

	/**
	 * Maps physical border-width properties to logical properties.
	 */
	private const PHYSICAL_TO_LOGICAL_MAPPING = [
		'border-top-width'    => 'border-block-start-width',
		'border-bottom-width' => 'border-block-end-width',
		'border-left-width'   => 'border-inline-start-width',
		'border-right-width'  => 'border-inline-end-width',
	];

	/**
	 * Keyword values for border-width.
	 */
	private const KEYWORD_VALUES = [
		'thin'   => [ 'size' => 1, 'unit' => 'px' ],
		'medium' => [ 'size' => 3, 'unit' => 'px' ],
		'thick'  => [ 'size' => 5, 'unit' => 'px' ],
	];

	protected function get_supported_properties_list(): array {
		return self::SUPPORTED_PROPERTIES;
	}

	public function get_output_property( string $property ): string {
		// Map physical to logical properties
		if ( isset( self::PHYSICAL_TO_LOGICAL_MAPPING[ $property ] ) ) {
			return self::PHYSICAL_TO_LOGICAL_MAPPING[ $property ];
		}

		return $property;
	}

	public function convert( string $property, $value ): ?array {
		if ( ! $this->supports( $property ) ) {
			return null;
		}

		if ( ! $this->is_valid_string_value( $value ) ) {
			return null;
		}

		$normalized_value = strtolower( trim( $value ) );

		// Handle keyword values
		if ( isset( self::KEYWORD_VALUES[ $normalized_value ] ) ) {
			return Size_Prop_Type::generate( self::KEYWORD_VALUES[ $normalized_value ] );
		}

		$size_value = Size_Value_Parser::parse( $value );

		if ( null === $size_value ) {
			return null;
		}

		return Size_Prop_Type::generate( $size_value );
	}

	private function is_valid_string_value( $value ): bool {
		return is_string( $value ) && '' !== trim( $value );
	}
}
