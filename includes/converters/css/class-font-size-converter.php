<?php
namespace ElementorHtmlCssConverter\Converters\Css;

use ElementorHtmlCssConverter\Converters\Abstracts\Property_Converter_Base;
use ElementorHtmlCssConverter\Converters\Parsers\Size_Value_Parser;
use ElementorHtmlCssConverter\Converters\Variables\Variable_Resolver;
use Elementor\Modules\AtomicWidgets\PropTypes\Size_Prop_Type;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Font_Size_Converter extends Property_Converter_Base {

	private const SUPPORTED_PROPERTIES = [ 'font-size' ];

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

		// Check for CSS variable references and resolve to Elementor variable IDs.
		if ( Variable_Resolver::is_css_variable( $value ) ) {
			$resolved = Variable_Resolver::resolve( $value, 'size' );

			if ( null !== $resolved ) {
				return $resolved;
			}

			// Variable not found in Elementor, return null (don't pass through raw var()).
			return null;
		}

		$parsed = $this->parse_size_value( $value );

		if ( null === $parsed ) {
			return null;
		}

		return Size_Prop_Type::generate( $parsed );
	}

	private function is_valid_string_value( $value ): bool {
		return is_string( $value ) && '' !== trim( $value );
	}

	private function parse_size_value( string $value ): ?array {
		return Size_Value_Parser::parse( $value );
	}
}
