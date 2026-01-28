<?php
namespace ElementorHtmlCssConverter\Converters\Css;

use ElementorHtmlCssConverter\Abstracts\Property_Converter_Base;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Align_Items_Converter extends Property_Converter_Base {

	private const SUPPORTED_PROPERTIES = [ 'align-items' ];

	private const ALLOWED_VALUES = [
		'flex-start',
		'flex-end',
		'center',
		'baseline',
		'stretch',
		'start',
		'end',
		'self-start',
		'self-end',
		'normal',
	];

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

		$normalized = $this->normalize_value( $value );

		if ( ! $this->is_allowed_value( $normalized ) ) {
			return null;
		}

		return String_Prop_Type::generate( $normalized );
	}

	private function is_valid_string_value( $value ): bool {
		return is_string( $value ) && '' !== trim( $value );
	}

	private function normalize_value( string $value ): string {
		return strtolower( trim( $value ) );
	}

	private function is_allowed_value( string $value ): bool {
		return in_array( $value, self::ALLOWED_VALUES, true );
	}
}
