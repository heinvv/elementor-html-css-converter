<?php
namespace ElementorHtmlCssConverter\Converters\Css\Properties;

use ElementorHtmlCssConverter\Converters\Css\Property_Converter_Base;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\Number_Prop_Type;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Order_Converter extends Property_Converter_Base {

	private const SUPPORTED_PROPERTIES = [ 'order' ];

	protected function get_supported_properties_list(): array {
		return self::SUPPORTED_PROPERTIES;
	}

	protected function convert_value( string $property, $value ): ?array {
		$value = trim( $value );

		if ( ! is_numeric( $value ) ) {
			return null;
		}

		$number = (int) $value;

		return Number_Prop_Type::generate( $number );
	}
}

