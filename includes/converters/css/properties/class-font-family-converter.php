<?php
namespace ElementorHtmlCssConverter\Converters\Css\Properties;

use ElementorHtmlCssConverter\Converters\Css\Property_Converter_Base;
use ElementorHtmlCssConverter\Converters\Variables\Unsupported_Font_Variable_Service;
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

	public function convert( string $property, $value, array $context = [] ): ?array {
		if ( ! $this->supports( $property ) ) {
			return null;
		}

		if ( ! is_string( $value ) || '' === trim( $value ) ) {
			return null;
		}

		$variable_fallback = $context['variable_fallback'] ?? [];
		if ( \ElementorHtmlCssConverter\Converters\Variables\Variable_Resolver::is_css_variable( $value ) ) {
			$variable_type = $this->get_variable_type();
			if ( null !== $variable_type ) {
				$resolved = \ElementorHtmlCssConverter\Converters\Variables\Variable_Resolver::resolve( $value, $variable_type );
				if ( null !== $resolved ) {
					return $this->wrap_resolved_variable( $resolved, $property );
				}
			}
			$substituted = \ElementorHtmlCssConverter\Converters\Variables\Variable_Fallback_Substitutor::substitute_in_value( $value, $variable_fallback );
			if ( $substituted !== $value ) {
				return $this->convert_value( $property, $substituted, $context );
			}
			return $this->convert_value( $property, $value, $context );
		}

		return $this->convert_value( $property, $value, $context );
	}

	protected function convert_value( string $property, $value, array $context = [] ): ?array {
		if ( $this->is_css_keyword_to_skip( $value ) ) {
			return null;
		}

		$parsed = $this->parse_font_family_value( $value );

		if ( empty( $parsed['fonts'] ) ) {
			return null;
		}

		$primary_font = $this->extract_primary_font( $parsed );

		if ( $this->is_generic_font_family( $primary_font ) || $this->is_registered_font( $primary_font ) ) {
			return String_Prop_Type::generate( $parsed['full_value'] );
		}

		$repository = $context['variable_repository'] ?? null;
		if ( null !== $repository && class_exists( '\Elementor\Plugin' ) ) {
			$service = new Unsupported_Font_Variable_Service();
			$result = $service->get_or_create_variable( $parsed['full_value'], $repository );
			if ( null !== $result ) {
				$collector = $context['unsupported_fonts_collector'] ?? null;
				if ( is_array( $collector ) ) {
					$collector[] = [
						'font'     => $result['font'],
						'variable' => '--' . $result['label'],
					];
				}
				return [
					'$$type' => 'global-font-variable',
					'value'  => $result['id'],
				];
			}
		}

		return null;
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

	private function is_registered_font( string $font_name ) {
		$normalized = $this->normalize_font_name( $font_name );
		return \Elementor\Fonts::get_font_type( $normalized );
	}

	protected function get_variable_type(): ?string {
		return 'font';
	}
}

