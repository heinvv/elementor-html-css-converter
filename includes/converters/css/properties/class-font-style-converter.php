<?php
namespace ElementorHtmlCssConverter\Converters\Css\Properties;

use ElementorHtmlCssConverter\Converters\Abstracts\Property_Converter_Base;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Font_Style_Converter extends Property_Converter_Base {

	private const SUPPORTED_PROPERTIES = [ 'font-style' ];

	private const VALID_VALUES = [
		'normal',
		'italic',
		'oblique',
	];

	protected function get_supported_properties_list(): array {
		return self::SUPPORTED_PROPERTIES;
	}

	protected function convert_value( string $property, $value ): ?array {
		$normalized_value = $this->normalize_value( $value );

		if ( null === $normalized_value ) {
			return null;
		}

		return String_Prop_Type::generate( $normalized_value );
	}

	private function normalize_value( string $value ): ?string {
		$value = strtolower( trim( $value ) );

		if ( str_starts_with( $value, 'oblique' ) ) {
			return 'oblique';
		}

		if ( ! in_array( $value, self::VALID_VALUES, true ) ) {
			return null;
		}

		return $value;
	}
}

