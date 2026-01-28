<?php
namespace ElementorHtmlCssConverter\Converters\Css;

use ElementorHtmlCssConverter\Abstracts\Property_Converter_Base;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Text_Decoration_Converter extends Property_Converter_Base {

	private const SUPPORTED_PROPERTIES = [ 'text-decoration' ];

	private const VALID_DECORATION_LINES = [
		'none',
		'underline',
		'overline',
		'line-through',
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

		$decoration_value = $this->parse_text_decoration_value( $value );

		if ( null === $decoration_value ) {
			return null;
		}

		return String_Prop_Type::generate( $decoration_value );
	}

	private function is_valid_string_value( $value ): bool {
		return is_string( $value ) && '' !== trim( $value );
	}

	private function parse_text_decoration_value( string $value ): ?string {
		$value = trim( $value );

		$decoration_line = $this->extract_decoration_line( $value );

		if ( ! in_array( $decoration_line, self::VALID_DECORATION_LINES, true ) ) {
			return null;
		}

		return $decoration_line;
	}

	private function extract_decoration_line( string $value ): ?string {
		$parts = preg_split( '/\s+/', strtolower( $value ) );

		foreach ( $parts as $part ) {
			if ( in_array( $part, self::VALID_DECORATION_LINES, true ) ) {
				return $part;
			}
		}

		return strtolower( $value );
	}
}
