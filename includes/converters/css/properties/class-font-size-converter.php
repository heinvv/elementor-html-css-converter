<?php
namespace ElementorHtmlCssConverter\Converters\Css\Properties;

use ElementorHtmlCssConverter\Converters\Css\Property_Converter_Base;
use ElementorHtmlCssConverter\Converters\Css\Size_Value_Parser;
use Elementor\Modules\AtomicWidgets\PropTypes\Size_Prop_Type;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Font_Size_Converter extends Property_Converter_Base {

	private const SUPPORTED_PROPERTIES = [ 'font-size' ];

	protected function get_supported_properties_list(): array {
		return self::SUPPORTED_PROPERTIES;
	}

	protected function get_variable_type(): ?string {
		return 'size';
	}

	protected function convert_value( string $property, $value ): ?array {
		$value = trim( $value );

		if ( $this->is_css_function( $value ) ) {
			return Size_Prop_Type::generate( [
				'size' => $value,
				'unit' => 'custom',
			] );
		}

		$parsed = Size_Value_Parser::parse( $value );

		if ( null === $parsed ) {
			return null;
		}

		return Size_Prop_Type::generate( $parsed );
	}

	private function is_css_function( string $value ): bool {
		$value_lower = strtolower( $value );
		return str_starts_with( $value_lower, 'max(' ) ||
			str_starts_with( $value_lower, 'min(' ) ||
			str_starts_with( $value_lower, 'clamp(' ) ||
			str_starts_with( $value_lower, 'calc(' );
	}
}

