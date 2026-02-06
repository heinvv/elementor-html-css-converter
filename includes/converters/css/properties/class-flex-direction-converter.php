<?php
namespace ElementorHtmlCssConverter\Converters\Css\Properties;

use ElementorHtmlCssConverter\Converters\Abstracts\Property_Converter_Base;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Flex_Direction_Converter extends Property_Converter_Base {

	private const SUPPORTED_PROPERTIES = [ 'flex-direction' ];

	private const ALLOWED_VALUES = [
		'row',
		'row-reverse',
		'column',
		'column-reverse',
	];

	protected function get_supported_properties_list(): array {
		return self::SUPPORTED_PROPERTIES;
	}

	protected function convert_value( string $property, $value ): ?array {
		$normalized = $this->normalize_value( $value );

		if ( ! $this->is_allowed_value( $normalized ) ) {
			return null;
		}

		return String_Prop_Type::generate( $normalized );
	}

	private function normalize_value( string $value ): string {
		return strtolower( trim( $value ) );
	}

	private function is_allowed_value( string $value ): bool {
		return in_array( $value, self::ALLOWED_VALUES, true );
	}
}

