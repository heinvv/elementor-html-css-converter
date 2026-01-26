<?php
namespace ElementorHtmlCssConverter\Converters;

use ElementorHtmlCssConverter\Abstracts\Property_Converter_Base;
use ElementorHtmlCssConverter\Parsers\Color_Value_Parser;
use Elementor\Modules\AtomicWidgets\PropTypes\Background_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Color_Prop_Type;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Background_Color_Converter extends Property_Converter_Base {

	private const SUPPORTED_PROPERTIES = [ 'background-color' ];
	private const OUTPUT_PROPERTY = 'background';

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

		$parsed_color = Color_Value_Parser::parse( $value );

		if ( null === $parsed_color ) {
			return null;
		}

		return Background_Prop_Type::generate( [
			'color' => Color_Prop_Type::generate( $parsed_color ),
		] );
	}

	private function is_valid_string_value( $value ): bool {
		return is_string( $value ) && '' !== trim( $value );
	}
}
