<?php
namespace ElementorHtmlCssConverter\Converters\Css\Properties;

use ElementorHtmlCssConverter\Converters\Abstracts\Property_Converter_Base;
use ElementorHtmlCssConverter\Converters\Css\Size_Value_Parser;
use Elementor\Modules\AtomicWidgets\PropTypes\Size_Prop_Type;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Letter_Spacing_Converter extends Property_Converter_Base {

	private const SUPPORTED_PROPERTIES = [ 'letter-spacing' ];

	protected function get_supported_properties_list(): array {
		return self::SUPPORTED_PROPERTIES;
	}

	protected function get_variable_type(): ?string {
		return 'size';
	}

	protected function convert_value( string $property, $value ): ?array {
		if ( $this->is_normal_keyword( $value ) ) {
			return null;
		}

		$size_data = Size_Value_Parser::parse( $value );

		if ( null === $size_data ) {
			return null;
		}

		return Size_Prop_Type::generate( $size_data );
	}

	private function is_normal_keyword( string $value ): bool {
		return 'normal' === strtolower( trim( $value ) );
	}
}

