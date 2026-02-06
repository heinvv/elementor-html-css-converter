<?php
namespace ElementorHtmlCssConverter\Converters\Css\Properties;

use ElementorHtmlCssConverter\Converters\Css\Property_Converter_Base;
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
	private const REGEX_WHITESPACE_SPLIT = '/\s+/';

	protected function get_supported_properties_list(): array {
		return self::SUPPORTED_PROPERTIES;
	}

	protected function convert_value( string $property, $value ): ?array {
		$decoration_value = $this->parse_text_decoration_value( $value );

		if ( null === $decoration_value ) {
			return null;
		}

		return String_Prop_Type::generate( $decoration_value );
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
		$parts = preg_split( self::REGEX_WHITESPACE_SPLIT, strtolower( $value ) );

		foreach ( $parts as $part ) {
			if ( in_array( $part, self::VALID_DECORATION_LINES, true ) ) {
				return $part;
			}
		}

		return strtolower( $value );
	}
}

