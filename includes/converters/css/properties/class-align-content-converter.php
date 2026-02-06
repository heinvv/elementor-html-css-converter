<?php
namespace ElementorHtmlCssConverter\Converters\Css\Properties;

use ElementorHtmlCssConverter\Converters\Css\Property_Converter_Base;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Align_Content_Converter extends Property_Converter_Base {

	private const SUPPORTED_PROPERTIES = [ 'align-content' ];

	private const ALLOWED_VALUES = [
		'flex-start',
		'flex-end',
		'center',
		'space-between',
		'space-around',
		'space-evenly',
		'stretch',
		'start',
		'end',
		'baseline',
		'normal',
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

