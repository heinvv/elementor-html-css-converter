<?php
namespace ElementorHtmlCssConverter\Converters;

use ElementorHtmlCssConverter\Abstracts\Property_Converter_Base;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Text_Align_Converter extends Property_Converter_Base {

	private const SUPPORTED_PROPERTIES = [ 'text-align' ];

	private const ALLOWED_VALUES = [ 'start', 'center', 'end', 'justify' ];

	private const VALUE_MAPPING = [
		'left' => 'start',
		'right' => 'end',
		'center' => 'center',
		'justify' => 'justify',
		'start' => 'start',
		'end' => 'end',
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

		$mapped_value = $this->map_text_align_value( $value );

		if ( null === $mapped_value ) {
			return null;
		}

		return String_Prop_Type::generate( $mapped_value );
	}

	private function is_valid_string_value( $value ): bool {
		return is_string( $value ) && '' !== trim( $value );
	}

	private function map_text_align_value( string $value ): ?string {
		$value = strtolower( trim( $value ) );

		$mapped_value = self::VALUE_MAPPING[ $value ] ?? null;

		if ( null === $mapped_value || ! in_array( $mapped_value, self::ALLOWED_VALUES, true ) ) {
			return null;
		}

		return $mapped_value;
	}
}
