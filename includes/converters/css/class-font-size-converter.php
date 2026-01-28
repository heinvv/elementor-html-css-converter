<?php
namespace ElementorHtmlCssConverter\Converters\Css;

use ElementorHtmlCssConverter\Abstracts\Property_Converter_Base;
use ElementorHtmlCssConverter\Parsers\Size_Value_Parser;
use Elementor\Modules\AtomicWidgets\PropTypes\Size_Prop_Type;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Font_Size_Converter extends Property_Converter_Base {

	private const SUPPORTED_PROPERTIES = [ 'font-size' ];

	protected function get_supported_properties_list(): array {
		return self::SUPPORTED_PROPERTIES;
	}

	public function convert( string $property, $value ): ?array {
		if ( ! $this->supports( $property ) ) {
			return null;
		}

		if ( ! $this->is_valid_string_value( $value ) ) {
			return null;
		}

		$parsed = $this->parse_size_value( $value );

		if ( null === $parsed ) {
			return null;
		}

		return Size_Prop_Type::generate( $parsed );
	}

	private function is_valid_string_value( $value ): bool {
		return is_string( $value ) && '' !== trim( $value );
	}

	private function parse_size_value( string $value ): ?array {
		return Size_Value_Parser::parse( $value );
	}
}
