<?php
namespace ElementorHtmlCssConverter\Converters\Css;

use ElementorHtmlCssConverter\Converters\Abstracts\Property_Converter_Base;
use ElementorHtmlCssConverter\Converters\Css\Color_Value_Parser;
use Elementor\Modules\AtomicWidgets\PropTypes\Color_Prop_Type;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Border_Color_Converter extends Property_Converter_Base {

	private const SUPPORTED_PROPERTIES = [
		'border-color',
		'border-top-color',
		'border-right-color',
		'border-bottom-color',
		'border-left-color',
		'border-block-start-color',
		'border-block-end-color',
		'border-inline-start-color',
		'border-inline-end-color',
	];

	/**
	 * Maps physical border-color properties to logical properties.
	 */
	private const PHYSICAL_TO_LOGICAL_MAPPING = [
		'border-top-color'    => 'border-block-start-color',
		'border-bottom-color' => 'border-block-end-color',
		'border-left-color'   => 'border-inline-start-color',
		'border-right-color'  => 'border-inline-end-color',
	];

	protected function get_supported_properties_list(): array {
		return self::SUPPORTED_PROPERTIES;
	}

	protected function get_variable_type(): ?string {
		return 'color';
	}

	public function get_output_property( string $property ): string {
		// Map physical to logical properties
		if ( isset( self::PHYSICAL_TO_LOGICAL_MAPPING[ $property ] ) ) {
			return self::PHYSICAL_TO_LOGICAL_MAPPING[ $property ];
		}

		return $property;
	}

	protected function convert_value( string $property, $value ): ?array {
		$parsed_color = Color_Value_Parser::parse( trim( $value ) );

		if ( null === $parsed_color ) {
			return null;
		}

		return Color_Prop_Type::generate( $parsed_color );
	}
}
