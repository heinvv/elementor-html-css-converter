<?php
namespace ElementorHtmlCssConverter\Converters\Css;

use ElementorHtmlCssConverter\Converters\Abstracts\Property_Converter_Base;
use Elementor\Modules\AtomicWidgets\PropTypes\Flex_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\Number_Prop_Type;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Flex Grow Converter
 *
 * Converts flex-grow to a Flex_Prop_Type with only flexGrow set.
 * This allows merging with other flex properties.
 */
class Flex_Grow_Converter extends Property_Converter_Base {

	private const SUPPORTED_PROPERTIES = [ 'flex-grow' ];

	protected function get_supported_properties_list(): array {
		return self::SUPPORTED_PROPERTIES;
	}

	/**
	 * Output property is 'flex' to allow merging with flex shorthand.
	 */
	public function get_output_property( string $property ): string {
		return 'flex';
	}

	protected function convert_value( string $property, $value ): ?array {
		$value = trim( $value );

		if ( ! is_numeric( $value ) ) {
			return null;
		}

		$number = (float) $value;

		// flex-grow must be non-negative
		if ( $number < 0 ) {
			return null;
		}

		// Return a Flex_Prop_Type with only flexGrow set
		return Flex_Prop_Type::generate( [
			'flexGrow' => Number_Prop_Type::generate( $number ),
		] );
	}
}
