<?php
namespace ElementorHtmlCssConverter\Converters\Css\Properties;

use ElementorHtmlCssConverter\Converters\Css\Property_Converter_Base;
use ElementorHtmlCssConverter\Converters\Css\Size_Value_Parser;
use Elementor\Modules\AtomicWidgets\PropTypes\Size_Prop_Type;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Word_Spacing_Converter extends Property_Converter_Base {

	private const SUPPORTED_PROPERTIES = [ 'word-spacing' ];

	protected function get_supported_properties_list(): array {
		return self::SUPPORTED_PROPERTIES;
	}

	protected function get_variable_type(): ?string {
		return 'size';
	}

	protected function convert_value( string $property, $value ): ?array {
		$normalized_value = trim( $value );

		if ( 'normal' === strtolower( $normalized_value ) ) {
			return null;
		}

		$size_value = Size_Value_Parser::parse( $normalized_value );

		if ( null === $size_value ) {
			return null;
		}

		return Size_Prop_Type::generate( $size_value );
	}
}

