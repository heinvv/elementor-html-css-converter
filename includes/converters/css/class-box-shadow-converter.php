<?php
namespace ElementorHtmlCssConverter\Converters\Css;

use ElementorHtmlCssConverter\Converters\Abstracts\Property_Converter_Base;
use ElementorHtmlCssConverter\Converters\Parsers\Size_Value_Parser;
use ElementorHtmlCssConverter\Converters\Parsers\Color_Value_Parser;
use ElementorHtmlCssConverter\Converters\Variables\Variable_Resolver;
use Elementor\Modules\AtomicWidgets\PropTypes\Box_Shadow_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Shadow_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Color_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Size_Prop_Type;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Box_Shadow_Converter extends Property_Converter_Base {

	private const SUPPORTED_PROPERTIES = [ 'box-shadow' ];

	private const NAMED_COLORS = [
		'transparent', 'black', 'white', 'red', 'green', 'blue', 'yellow',
		'cyan', 'magenta', 'gray', 'grey', 'orange', 'purple', 'pink',
		'brown', 'navy', 'teal', 'lime', 'olive', 'maroon', 'silver',
		'aqua', 'fuchsia', 'gold', 'coral', 'crimson',
	];

	protected function get_supported_properties_list(): array {
		return self::SUPPORTED_PROPERTIES;
	}

	/**
	 * Override convert to handle complex shadow parsing with CSS variables.
	 */
	public function convert( string $property, $value ): ?array {
		if ( ! $this->supports( $property ) ) {
			return null;
		}

		if ( ! is_string( $value ) || '' === trim( $value ) ) {
			return null;
		}

		$shadows_data = $this->parse_box_shadow_value( $value );

		if ( null === $shadows_data ) {
			return null;
		}

		return Box_Shadow_Prop_Type::generate( $shadows_data );
	}

	protected function convert_value( string $property, $value ): ?array {
		// Not used - convert() handles everything for this property.
		return null;
	}

	/**
	 * Resolve a color value, handling CSS variables.
	 */
	private function resolve_color_value( string $value ): ?array {
		$value = trim( $value );

		if ( Variable_Resolver::is_css_variable( $value ) ) {
			return Variable_Resolver::resolve( $value, 'color' );
		}

		$parsed = Color_Value_Parser::parse( $value );

		if ( null === $parsed ) {
			return null;
		}

		return Color_Prop_Type::generate( $parsed );
	}

	/**
	 * Resolve a size value, handling CSS variables.
	 */
	private function resolve_size_value( string $value ): ?array {
		$value = trim( $value );

		if ( Variable_Resolver::is_css_variable( $value ) ) {
			return Variable_Resolver::resolve( $value, 'size' );
		}

		$parsed = Size_Value_Parser::parse( $value );

		if ( null === $parsed ) {
			return null;
		}

		return Size_Prop_Type::generate( $parsed );
	}

	private function parse_box_shadow_value( string $value ): ?array {
		$value = trim( $value );

		if ( 'none' === strtolower( $value ) ) {
			return [];
		}

		$shadows = $this->split_multiple_shadows( $value );
		$parsed_shadows = [];

		foreach ( $shadows as $shadow ) {
			$parsed_shadow = $this->parse_single_shadow( $shadow );

			if ( null === $parsed_shadow ) {
				return null;
			}

			$parsed_shadows[] = $parsed_shadow;
		}

		return $parsed_shadows;
	}

	private function split_multiple_shadows( string $value ): array {
		$shadows = [];
		$current_shadow = '';
		$paren_depth = 0;

		$chars = str_split( $value );

		for ( $i = 0; $i < count( $chars ); $i++ ) {
			$char = $chars[ $i ];

			if ( '(' === $char ) {
				++$paren_depth;
			} elseif ( ')' === $char ) {
				--$paren_depth;
			} elseif ( ',' === $char && 0 === $paren_depth ) {
				$shadows[] = trim( $current_shadow );
				$current_shadow = '';
				continue;
			}

			$current_shadow .= $char;
		}

		if ( '' !== trim( $current_shadow ) ) {
			$shadows[] = trim( $current_shadow );
		}

		return $shadows;
	}

	private function parse_single_shadow( string $shadow ): ?array {
		$shadow = trim( $shadow );

		if ( empty( $shadow ) ) {
			return null;
		}

		$is_inset = $this->extract_inset_keyword( $shadow );

		if ( $is_inset ) {
			$shadow = $this->remove_inset_keyword( $shadow );
		}

		$parts = $this->tokenize_shadow_parts( $shadow );

		if ( count( $parts ) < 2 ) {
			return null;
		}

		$size_values = [];
		$color_value = null;

		foreach ( $parts as $part ) {
			if ( $this->is_color_value( $part ) ) {
				$color_value = $part;
			} elseif ( $this->is_size_value( $part ) ) {
				$size_values[] = $part;
			}
		}

		if ( count( $size_values ) < 2 ) {
			return null;
		}

		return $this->build_shadow_prop_type( $size_values, $color_value, $is_inset );
	}

	private function extract_inset_keyword( string $shadow ): bool {
		return str_starts_with( strtolower( $shadow ), 'inset ' )
			|| str_contains( strtolower( $shadow ), ' inset' );
	}

	private function remove_inset_keyword( string $shadow ): string {
		$shadow = preg_replace( '/\binset\b/i', '', $shadow );
		return trim( preg_replace( '/\s+/', ' ', $shadow ) );
	}

	private function tokenize_shadow_parts( string $shadow ): array {
		$parts = [];
		$current_part = '';
		$paren_depth = 0;

		$chars = str_split( $shadow );

		for ( $i = 0; $i < count( $chars ); $i++ ) {
			$char = $chars[ $i ];

			if ( '(' === $char ) {
				++$paren_depth;
			} elseif ( ')' === $char ) {
				--$paren_depth;
			} elseif ( ' ' === $char && 0 === $paren_depth ) {
				if ( '' !== trim( $current_part ) ) {
					$parts[] = trim( $current_part );
					$current_part = '';
				}
				continue;
			}

			$current_part .= $char;
		}

		if ( '' !== trim( $current_part ) ) {
			$parts[] = trim( $current_part );
		}

		return $parts;
	}

	private function is_color_value( string $value ): bool {
		$value = trim( $value );

		// CSS variable could be a color
		if ( Variable_Resolver::is_css_variable( $value ) ) {
			return true;
		}

		if ( str_starts_with( $value, '#' ) ) {
			return true;
		}

		if ( str_starts_with( strtolower( $value ), 'rgb' ) || str_starts_with( strtolower( $value ), 'hsl' ) ) {
			return true;
		}

		return in_array( strtolower( $value ), self::NAMED_COLORS, true );
	}

	private function is_size_value( string $value ): bool {
		$value = trim( $value );

		// CSS variable could be a size
		if ( Variable_Resolver::is_css_variable( $value ) ) {
			return true;
		}

		if ( '0' === $value ) {
			return true;
		}

		$pattern = '/^-?(?:\d+(?:\.\d+)?|\.\d+)(px|em|rem|%|vw|vh)$/';
		return (bool) preg_match( $pattern, $value );
	}

	private function build_shadow_prop_type( array $size_values, ?string $color_value, bool $is_inset ): array {
		$h_offset = $this->resolve_size_value( $size_values[0] );
		$v_offset = $this->resolve_size_value( $size_values[1] );

		if ( null === $h_offset || null === $v_offset ) {
			return null;
		}

		$blur = isset( $size_values[2] ) ? $this->resolve_size_value( $size_values[2] ) : Size_Prop_Type::generate( $this->create_zero_size() );
		$spread = isset( $size_values[3] ) ? $this->resolve_size_value( $size_values[3] ) : Size_Prop_Type::generate( $this->create_zero_size() );

		// Resolve color (could be a CSS variable)
		$color = null;
		if ( null !== $color_value ) {
			$color = $this->resolve_color_value( $color_value );
		}
		if ( null === $color ) {
			$color = Color_Prop_Type::generate( 'rgba(0, 0, 0, 0.5)' );
		}

		$shadow_data = [
			'hOffset' => $h_offset,
			'vOffset' => $v_offset,
			'blur' => $blur ?? Size_Prop_Type::generate( $this->create_zero_size() ),
			'spread' => $spread ?? Size_Prop_Type::generate( $this->create_zero_size() ),
			'color' => $color,
		];

		if ( $is_inset ) {
			$shadow_data['position'] = 'inset';
		}

		return Shadow_Prop_Type::generate( $shadow_data );
	}

	private function create_zero_size(): array {
		return [
			'size' => 0.0,
			'unit' => 'px',
		];
	}
}
