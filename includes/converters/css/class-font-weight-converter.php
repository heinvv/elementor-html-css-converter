<?php
namespace ElementorHtmlCssConverter\Converters\Css;

use ElementorHtmlCssConverter\Converters\Abstracts\Property_Converter_Base;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Font_Weight_Converter extends Property_Converter_Base {

	private const SUPPORTED_PROPERTIES = [ 'font-weight' ];

	private const VALID_KEYWORD_VALUES = [
		'100', '200', '300', '400', '500', '600', '700', '800', '900',
		'normal', 'bold', 'bolder', 'lighter',
	];

	private const KEYWORD_TO_NUMERIC_MAPPING = [
		'thin' => '100',
		'extra-light' => '200',
		'ultra-light' => '200',
		'light' => '300',
		'regular' => '400',
		'normal' => '400',
		'medium' => '500',
		'semi-bold' => '600',
		'demi-bold' => '600',
		'bold' => '700',
		'extra-bold' => '800',
		'ultra-bold' => '800',
		'black' => '900',
		'heavy' => '900',
	];

	protected function get_supported_properties_list(): array {
		return self::SUPPORTED_PROPERTIES;
	}

	protected function convert_value( string $property, $value ): ?array {
		if ( $this->is_css_keyword_to_skip( $value ) ) {
			return null;
		}

		$normalized_value = $this->normalize_font_weight_value( $value );

		if ( null === $normalized_value ) {
			return null;
		}

		return String_Prop_Type::generate( $normalized_value );
	}

	private function is_css_keyword_to_skip( string $value ): bool {
		$skip_keywords = [ 'inherit', 'initial', 'unset', 'revert' ];
		return in_array( strtolower( trim( $value ) ), $skip_keywords, true );
	}

	private function normalize_font_weight_value( string $value ): ?string {
		$value = strtolower( trim( $value ) );

		if ( in_array( $value, self::VALID_KEYWORD_VALUES, true ) ) {
			return $value;
		}

		if ( isset( self::KEYWORD_TO_NUMERIC_MAPPING[ $value ] ) ) {
			return self::KEYWORD_TO_NUMERIC_MAPPING[ $value ];
		}

		if ( is_numeric( $value ) ) {
			return $this->normalize_numeric_weight( (int) $value );
		}

		return null;
	}

	private function normalize_numeric_weight( int $value ): ?string {
		if ( $value >= 100 && $value <= 900 && $value % 100 === 0 ) {
			return (string) $value;
		}

		if ( $value < 100 ) {
			return '100';
		}

		if ( $value > 900 ) {
			return '900';
		}

		$rounded = (int) ( round( $value / 100 ) * 100 );
		return (string) max( 100, min( 900, $rounded ) );
	}
}
