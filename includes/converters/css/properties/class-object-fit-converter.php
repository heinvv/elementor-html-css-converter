<?php
namespace ElementorHtmlCssConverter\Converters\Css\Properties;

use ElementorHtmlCssConverter\Converters\Css\Property_Converter_Base;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Object_Fit_Converter extends Property_Converter_Base {

	private const SUPPORTED_PROPERTIES = [ 'object-fit' ];

	private const ALLOWED_VALUES = [
		'fill',
		'cover',
		'contain',
		'none',
		'scale-down',
	];

	protected function get_supported_properties_list(): array {
		return self::SUPPORTED_PROPERTIES;
	}

	protected function convert_value( string $property, $value ): ?array {
		$normalized = strtolower( trim( $value ) );

		if ( ! in_array( $normalized, self::ALLOWED_VALUES, true ) ) {
			return null;
		}

		return String_Prop_Type::generate( $normalized );
	}
}
