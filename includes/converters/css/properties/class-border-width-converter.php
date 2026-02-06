<?php
namespace ElementorHtmlCssConverter\Converters\Css\Properties;

use ElementorHtmlCssConverter\Converters\Css\Property_Converter_Base;
use ElementorHtmlCssConverter\Converters\Css\Size_Value_Parser;
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

	private const PHYSICAL_TO_LOGICAL_MAPPING = [
		'border-top-width'    => 'border-block-start-width',
		'border-bottom-width' => 'border-block-end-width',
		'border-left-width'   => 'border-inline-start-width',
		'border-right-width'  => 'border-inline-end-width',
	];

	private const BORDER_WIDTH_THIN = 1;
	private const BORDER_WIDTH_MEDIUM = 3;
	private const BORDER_WIDTH_THICK = 5;

	private const KEYWORD_VALUES = [
		'thin'   => [ 'size' => self::BORDER_WIDTH_THIN, 'unit' => 'px' ],
		'medium' => [ 'size' => self::BORDER_WIDTH_MEDIUM, 'unit' => 'px' ],
		'thick'  => [ 'size' => self::BORDER_WIDTH_THICK, 'unit' => 'px' ],
	];

	protected function get_supported_properties_list(): array {
		return self::SUPPORTED_PROPERTIES;
	}

	protected function get_variable_type(): ?string {
		return 'size';
	}

	public function get_output_property( string $property ): string {
		if ( isset( self::PHYSICAL_TO_LOGICAL_MAPPING[ $property ] ) ) {
			return self::PHYSICAL_TO_LOGICAL_MAPPING[ $property ];
		}

		return $property;
	}

	protected function convert_value( string $property, $value ): ?array {
		$normalized_value = strtolower( trim( $value ) );

		if ( isset( self::KEYWORD_VALUES[ $normalized_value ] ) ) {
			return Size_Prop_Type::generate( self::KEYWORD_VALUES[ $normalized_value ] );
		}

		$size_value = Size_Value_Parser::parse( $value );

		if ( null === $size_value ) {
			return null;
		}

		return Size_Prop_Type::generate( $size_value );
	}
}

