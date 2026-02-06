<?php
namespace ElementorHtmlCssConverter\Converters\Css\Properties;

use ElementorHtmlCssConverter\Converters\Css\Property_Converter_Base;
use ElementorHtmlCssConverter\Converters\Css\Size_Value_Parser;
use Elementor\Modules\AtomicWidgets\PropTypes\Size_Prop_Type;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Width_Converter extends Property_Converter_Base {

	private const SUPPORTED_PROPERTIES = [
		'width',
		'min-width',
		'max-width',
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
		$value = strtolower( trim( $value ) );

		if ( $this->is_unsupported_keyword( $value ) ) {
			return null;
		}

		if ( $this->is_keyword_value( $value ) ) {
			return Size_Prop_Type::generate( self::KEYWORD_VALUES[ $value ] );
		}

		if ( $this->is_calc_value( $value ) ) {
			return Size_Prop_Type::generate( [
				'size' => $value,
				'unit' => 'custom',
			] );
		}

		$parsed = Size_Value_Parser::parse( $value );

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

	private function is_calc_value( string $value ): bool {
		return str_starts_with( $value, 'calc(' );
	}
}

