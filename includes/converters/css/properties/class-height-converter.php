<?php
namespace ElementorHtmlCssConverter\Converters\Css\Properties;

use ElementorHtmlCssConverter\Converters\Css\Property_Converter_Base;
use ElementorHtmlCssConverter\Converters\Css\Size_Value_Parser;
use Elementor\Modules\AtomicWidgets\PropTypes\Size_Prop_Type;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Height_Converter extends Property_Converter_Base {

	private const SUPPORTED_PROPERTIES = [
		'height',
		'min-height',
		'max-height',
	];

	private const KEYWORD_VALUES = [
		'auto'        => [ 'size' => 'auto', 'unit' => 'custom' ],
		'fit-content' => [ 'size' => 'fit-content', 'unit' => 'custom' ],
		'max-content' => [ 'size' => 'max-content', 'unit' => 'custom' ],
		'min-content' => [ 'size' => 'min-content', 'unit' => 'custom' ],
	];

	private const UNSUPPORTED_KEYWORDS = [ 'inherit', 'initial', 'unset', 'revert', 'revert-layer' ];

	protected function get_supported_properties_list(): array {
		return self::SUPPORTED_PROPERTIES;
	}

	protected function get_variable_type(): ?string {
		return 'size';
	}

	protected function convert_value( string $property, $value ): ?array {
		$value_trimmed = trim( $value );
		$value_lower = strtolower( $value_trimmed );

		if ( $this->is_unsupported_keyword( $value_lower ) ) {
			return null;
		}

		if ( $this->is_keyword_value( $value_lower ) ) {
			return Size_Prop_Type::generate( self::KEYWORD_VALUES[ $value_lower ] );
		}

		if ( $this->is_css_function( $value_lower ) ) {
			return Size_Prop_Type::generate( [
				'size' => $value_trimmed,
				'unit' => 'custom',
			] );
		}

		$parsed = Size_Value_Parser::parse( $value_trimmed );

		if ( null === $parsed ) {
			return null;
		}

		return Size_Prop_Type::generate( $parsed );
	}

	private function is_keyword_value( string $value ): bool {
		return isset( self::KEYWORD_VALUES[ $value ] );
	}

	private function is_unsupported_keyword( string $value ): bool {
		return in_array( $value, self::UNSUPPORTED_KEYWORDS, true );
	}

	private function is_css_function( string $value ): bool {
		return str_starts_with( $value, 'max(' ) ||
			str_starts_with( $value, 'min(' ) ||
			str_starts_with( $value, 'clamp(' ) ||
			str_starts_with( $value, 'calc(' );
	}
}

