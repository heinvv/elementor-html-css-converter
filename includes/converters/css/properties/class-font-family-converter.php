<?php
namespace ElementorHtmlCssConverter\Converters\Css\Properties;

use ElementorHtmlCssConverter\Converters\Abstracts\Property_Converter_Base;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Font_Family_Converter extends Property_Converter_Base {

	private const SUPPORTED_PROPERTIES = [ 'font-family' ];

	private const CSS_KEYWORDS = [
		'inherit',
		'initial',
		'unset',
		'revert',
	];

	private const GENERIC_FONT_FAMILIES = [
		'serif',
		'sans-serif',
		'monospace',
		'cursive',
		'fantasy',
	];

	protected function get_supported_properties_list(): array {
		return self::SUPPORTED_PROPERTIES;
	}

	protected function convert_value( string $property, $value ): ?array {
		if ( $this->is_css_keyword_to_skip( $value ) ) {
			return null;
		}

		$parsed = $this->parse_font_family_value( $value );

		if ( empty( $parsed['fonts'] ) ) {
			return null;
		}

		return String_Prop_Type::generate( $parsed['full_value'] );
	}

	private function parse_font_family_value( string $value ): array {
		$value = trim( $value );

		if ( '' === $value ) {
			return [ 'fonts' => [], 'full_value' => '' ];
		}

		$fonts = [];
		$current_font = '';
		$in_quotes = false;
		$quote_char = '';
		$length = strlen( $value );

		for ( $i = 0; $i < $length; $i++ ) {
			$char = $value[ $i ];

			if ( ( '"' === $char || "'" === $char ) && ( $i === 0 || '\\' !== $value[ $i - 1 ] ) ) {
				if ( ! $in_quotes ) {
					$in_quotes = true;
					$quote_char = $char;
				} elseif ( $char === $quote_char ) {
					$in_quotes = false;
					$quote_char = '';
				} else {
					$current_font .= $char;
				}
			} elseif ( ',' === $char && ! $in_quotes ) {
				$trimmed_font = trim( $current_font );
				if ( '' !== $trimmed_font ) {
					$fonts[] = $trimmed_font;
				}
				$current_font = '';
			} else {
				$current_font .= $char;
			}
		}

		$trimmed_font = trim( $current_font );
		if ( '' !== $trimmed_font ) {
			$fonts[] = $trimmed_font;
		}

		$normalized_value = trim( $value );

		return [
			'fonts' => $fonts,
			'full_value' => $normalized_value,
		];
	}

	private function extract_primary_font( array $parsed ): string {
		if ( empty( $parsed['fonts'] ) ) {
			return '';
		}

		return $this->normalize_font_name( $parsed['fonts'][0] );
	}

	private function normalize_font_name( string $font ): string {
		$font = trim( $font );

		if ( ( str_starts_with( $font, '"' ) && str_ends_with( $font, '"' ) ) ||
			 ( str_starts_with( $font, "'" ) && str_ends_with( $font, "'" ) ) ) {
			$font = substr( $font, 1, -1 );
		}

		$font = trim( $font );
		$font = preg_replace( '/\s+/', ' ', $font );

		return $font;
	}

	private function is_css_keyword_to_skip( string $value ): bool {
		$normalized = strtolower( trim( $value ) );
		return in_array( $normalized, self::CSS_KEYWORDS, true );
	}

	private function is_generic_font_family( string $font ): bool {
		$normalized = strtolower( trim( $font ) );
		return in_array( $normalized, self::GENERIC_FONT_FAMILIES, true );
	}

	private function is_registered_font( string $font_name ): bool|string {
		$normalized = $this->normalize_font_name( $font_name );
		return \Elementor\Fonts::get_font_type( $normalized );
	}
}

