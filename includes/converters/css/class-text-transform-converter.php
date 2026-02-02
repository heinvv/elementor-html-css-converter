<?php
namespace ElementorHtmlCssConverter\Converters\Css;

use ElementorHtmlCssConverter\Converters\Abstracts\Property_Converter_Base;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Text_Transform_Converter extends Property_Converter_Base {

	private const SUPPORTED_PROPERTIES = [ 'text-transform' ];

	private const VALID_VALUES = [
		'none',
		'capitalize',
		'uppercase',
		'lowercase',
	];

	protected function get_supported_properties_list(): array {
		return self::SUPPORTED_PROPERTIES;
	}

	protected function convert_value( string $property, $value ): ?array {
		$normalized_value = strtolower( trim( $value ) );

		if ( ! in_array( $normalized_value, self::VALID_VALUES, true ) ) {
			return null;
		}

		return String_Prop_Type::generate( $normalized_value );
	}
}
