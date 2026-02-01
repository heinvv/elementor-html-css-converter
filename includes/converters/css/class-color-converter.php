<?php
namespace ElementorHtmlCssConverter\Converters\Css;

use ElementorHtmlCssConverter\Converters\Abstracts\Property_Converter_Base;
use ElementorHtmlCssConverter\Converters\Parsers\Color_Value_Parser;
use ElementorHtmlCssConverter\Converters\Variables\Variable_Resolver;
use Elementor\Modules\AtomicWidgets\PropTypes\Color_Prop_Type;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Color_Converter extends Property_Converter_Base {

	private const SUPPORTED_PROPERTIES = [ 'color' ];

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

		// Check if value is a CSS variable reference
		if ( Variable_Resolver::is_css_variable( $value ) ) {
			$resolved = Variable_Resolver::resolve( $value, 'color' );

			if ( null !== $resolved ) {
				return $resolved;
			}

			// If variable couldn't be resolved, fall through to regular parsing
			// which will handle var() as a pass-through value
		}

		$parsed_color = Color_Value_Parser::parse( $value );

		if ( null === $parsed_color ) {
			return null;
		}

		return Color_Prop_Type::generate( $parsed_color );
	}

	private function is_valid_string_value( $value ): bool {
		return is_string( $value ) && '' !== trim( $value );
	}
}
