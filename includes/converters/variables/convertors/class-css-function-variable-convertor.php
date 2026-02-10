<?php
namespace ElementorHtmlCssConverter\Converters\Variables\Convertors;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Css_Function_Variable_Convertor extends Abstract_Variable_Convertor {

	private const CSS_MATH_FUNCTIONS = [ 'calc(', 'min(', 'max(', 'clamp(' ];

	public function supports( string $name, string $value ): bool {
		$value_lower = strtolower( trim( $value ) );

		foreach ( self::CSS_MATH_FUNCTIONS as $function ) {
			if ( str_starts_with( $value_lower, $function ) ) {
				return true;
			}
		}

		return false;
	}

	protected function get_type(): string {
		return 'size-function';
	}

	protected function normalize_value( string $value ): string {
		return trim( $value );
	}
}
