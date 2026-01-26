<?php
namespace ElementorHtmlCssConverter\Converters;

use ElementorHtmlCssConverter\Abstracts\Property_Converter_Base;
use ElementorHtmlCssConverter\Parsers\Size_Value_Parser;
use Elementor\Modules\AtomicWidgets\PropTypes\Size_Prop_Type;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Width_Converter extends Property_Converter_Base {

	private const SUPPORTED_PROPERTIES = [
		'width',
		'min-width',
		'max-width',
	];

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

		if ( $this->is_keyword_value( $value ) ) {
			return null;
		}

		$parsed = Size_Value_Parser::parse( $value );

		if ( null === $parsed ) {
			return null;
		}

		return Size_Prop_Type::generate( $parsed );
	}

	private function is_valid_string_value( $value ): bool {
		return is_string( $value ) && '' !== trim( $value );
	}

	private function is_keyword_value( string $value ): bool {
		$keywords = [ 'auto', 'fit-content', 'max-content', 'min-content', 'inherit', 'initial', 'unset' ];

		return in_array( strtolower( trim( $value ) ), $keywords, true );
	}
}
