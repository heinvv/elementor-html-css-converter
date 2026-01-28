<?php
namespace ElementorHtmlCssConverter\Converters;

use ElementorHtmlCssConverter\Abstracts\Property_Converter_Base;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\Number_Prop_Type;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Order_Converter extends Property_Converter_Base {

	private const SUPPORTED_PROPERTIES = [ 'order' ];

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

		$value = trim( $value );

		if ( ! is_numeric( $value ) ) {
			return null;
		}

		// Order must be an integer
		$number = (int) $value;

		return Number_Prop_Type::generate( $number );
	}

	private function is_valid_string_value( $value ): bool {
		return is_string( $value ) && '' !== trim( $value );
	}
}
