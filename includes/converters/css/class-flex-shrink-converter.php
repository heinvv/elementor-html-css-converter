<?php
namespace ElementorHtmlCssConverter\Converters\Css;

use ElementorHtmlCssConverter\Abstracts\Property_Converter_Base;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\Number_Prop_Type;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Flex_Shrink_Converter extends Property_Converter_Base {

	private const SUPPORTED_PROPERTIES = [ 'flex-shrink' ];

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

		$number = (float) $value;

		// flex-shrink must be non-negative
		if ( $number < 0 ) {
			return null;
		}

		return Number_Prop_Type::generate( $number );
	}

	private function is_valid_string_value( $value ): bool {
		return is_string( $value ) && '' !== trim( $value );
	}
}
