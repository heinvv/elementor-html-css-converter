<?php
namespace ElementorHtmlCssConverter\Converters\Variables\Convertors;

use ElementorHtmlCssConverter\Converters\Css\Css_Named_Colors;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Color_Named_Variable_Convertor extends Abstract_Variable_Convertor {

	public function supports( string $name, string $value ): bool {
		return Css_Named_Colors::is_named_color( $value );
	}

	protected function get_type(): string {
		return 'color-named';
	}

	protected function normalize_value( string $value ): string {
		return Css_Named_Colors::to_hex( $value ) ?? $value;
	}
}
