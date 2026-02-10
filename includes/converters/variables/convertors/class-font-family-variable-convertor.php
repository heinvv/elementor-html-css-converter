<?php
namespace ElementorHtmlCssConverter\Converters\Variables\Convertors;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Font_Family_Variable_Convertor extends Abstract_Variable_Convertor {

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

	public function supports( string $name, string $value ): bool {
		$trimmed = trim( $value );

		if ( '' === $trimmed ) {
			return false;
		}

		if ( $this->is_css_keyword( $trimmed ) ) {
			return false;
		}

		return '' !== $this->resolve_usable_font( $trimmed );
	}

	protected function get_type(): string {
		return 'font-family';
	}

	protected function normalize_value( string $value ): string {
		$resolved = $this->resolve_usable_font( $value );

		if ( '' === $resolved ) {
			return trim( $value );
		}

		return $resolved;
	}

	private function resolve_usable_font( string $value ): string {
		$fonts = $this->parse_font_list( $value );

		$generic_fallback = '';

		foreach ( $fonts as $font ) {
			$normalized = $this->strip_quotes_and_normalize( $font );

			if ( $this->is_generic_font_family( $normalized ) ) {
				if ( '' === $generic_fallback ) {
					$generic_fallback = $normalized;
				}
				continue;
			}

			if ( $this->is_registered_elementor_font( $normalized ) ) {
				return $normalized;
			}
		}

		return $generic_fallback;
	}

	private function parse_font_list( string $value ): array {
		$value = trim( $value );

		if ( '' === $value ) {
			return [];
		}

		$fonts = [];
		$current_font = '';
		$in_quotes = false;
		$quote_char = '';
		$length = strlen( $value );

		for ( $i = 0; $i < $length; $i++ ) {
			$char = $value[ $i ];

			if ( ( '"' === $char || "'" === $char ) && ( 0 === $i || '\\' !== $value[ $i - 1 ] ) ) {
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

		return $fonts;
	}

	private function strip_quotes_and_normalize( string $font ): string {
		$font = trim( $font );

		if ( ( str_starts_with( $font, '"' ) && str_ends_with( $font, '"' ) ) ||
			 ( str_starts_with( $font, "'" ) && str_ends_with( $font, "'" ) ) ) {
			$font = substr( $font, 1, -1 );
		}

		$font = trim( $font );
		$font = preg_replace( '/\s+/', ' ', $font );

		return $font;
	}

	private function is_css_keyword( string $value ): bool {
		return in_array( strtolower( trim( $value ) ), self::CSS_KEYWORDS, true );
	}

	private function is_generic_font_family( string $value ): bool {
		return in_array( strtolower( trim( $value ) ), self::GENERIC_FONT_FAMILIES, true );
	}

	private function is_registered_elementor_font( string $value ): bool {
		if ( ! class_exists( '\Elementor\Fonts' ) ) {
			return false;
		}

		$normalized = $this->strip_quotes_and_normalize( $value );

		return (bool) \Elementor\Fonts::get_font_type( $normalized );
	}

	public function is_supported_font( string $font_name ): bool {
		if ( $this->is_generic_font_family( $font_name ) ) {
			return true;
		}

		if ( ! class_exists( '\Elementor\Fonts' ) ) {
			return false;
		}

		return (bool) \Elementor\Fonts::get_font_type( $font_name );
	}
}
