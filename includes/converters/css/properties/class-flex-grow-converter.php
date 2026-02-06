<?php
namespace ElementorHtmlCssConverter\Converters\Css\Properties;

use ElementorHtmlCssConverter\Converters\Css\Property_Converter_Base;
use Elementor\Modules\AtomicWidgets\PropTypes\Flex_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\Number_Prop_Type;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Flex_Grow_Converter extends Property_Converter_Base {

	private const SUPPORTED_PROPERTIES = [ 'flex-grow' ];

	protected function get_supported_properties_list(): array {
		return self::SUPPORTED_PROPERTIES;
	}

	public function get_output_property( string $property ): string {
		return 'flex';
	}

	protected function convert_value( string $property, $value ): ?array {
		$value = trim( $value );

		if ( ! is_numeric( $value ) ) {
			return null;
		}

		$number = (float) $value;

		if ( $number < 0 ) {
			return null;
		}

		return Flex_Prop_Type::generate( [
			'flexGrow' => Number_Prop_Type::generate( $number ),
		] );
	}
}

