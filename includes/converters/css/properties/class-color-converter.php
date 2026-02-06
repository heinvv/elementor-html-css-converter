<?php
namespace ElementorHtmlCssConverter\Converters\Css\Properties;

use ElementorHtmlCssConverter\Converters\Css\Property_Converter_Base;
use ElementorHtmlCssConverter\Converters\Css\Color_Value_Parser;
use Elementor\Modules\AtomicWidgets\PropTypes\Color_Prop_Type;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Color_Converter extends Property_Converter_Base {

	private const SUPPORTED_PROPERTIES = [ 'color' ];

	protected function get_supported_properties_list(): array {
		return self::SUPPORTED_PROPERTIES;
	}

	protected function get_variable_type(): ?string {
		return 'color';
	}

	protected function convert_value( string $property, $value ): ?array {
		$parsed_color = Color_Value_Parser::parse( $value );

		if ( null === $parsed_color ) {
			return null;
		}

		return Color_Prop_Type::generate( $parsed_color );
	}
}

