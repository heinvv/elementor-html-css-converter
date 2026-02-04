<?php
namespace ElementorHtmlCssConverter\Converters\Css;

use ElementorHtmlCssConverter\Converters\Abstracts\Property_Converter_Base;
use ElementorHtmlCssConverter\Converters\Css\Size_Value_Parser;
use ElementorHtmlCssConverter\Converters\Css\Color_Value_Parser;
use ElementorHtmlCssConverter\Converters\Variables\Variable_Resolver;
use Elementor\Modules\AtomicWidgets\PropTypes\Color_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Size_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Border Shorthand Converter
 *
 * Expands border shorthand properties into separate border-width, border-style, and border-color properties.
 * This matches how Elementor's atomic widgets handle borders - as separate properties rather than a combined type.
 *
 * Input: "border: 1px solid red"
 * Output: [
 *   'border-width' => Size_Prop_Type,
 *   'border-style' => String_Prop_Type,
 *   'border-color' => Color_Prop_Type
 * ]
 */
class Border_Converter extends Property_Converter_Base {

	private const SUPPORTED_PROPERTIES = [
		'border',
		'border-top',
		'border-right',
		'border-bottom',
		'border-left',
		'border-block-start',
		'border-block-end',
		'border-inline-start',
		'border-inline-end',
	];

	private const VALID_STYLES = [
		'none', 'hidden', 'dotted', 'dashed', 'solid',
		'double', 'groove', 'ridge', 'inset', 'outset',
	];

	private const WIDTH_KEYWORDS = [
		'thin'   => [ 'size' => 1, 'unit' => 'px' ],
		'medium' => [ 'size' => 3, 'unit' => 'px' ],
		'thick'  => [ 'size' => 5, 'unit' => 'px' ],
	];

	private const NAMED_COLORS = [
		'transparent', 'black', 'white', 'red', 'green', 'blue', 'yellow',
		'cyan', 'magenta', 'gray', 'grey', 'orange', 'purple', 'pink',
		'brown', 'navy', 'teal', 'lime', 'olive', 'maroon', 'silver',
		'aqua', 'fuchsia', 'gold', 'coral', 'crimson', 'currentcolor',
	];

	protected function get_supported_properties_list(): array {
		return self::SUPPORTED_PROPERTIES;
	}

	/**
	 * Convert border shorthand to multiple properties.
	 *
	 * Returns an associative array with expanded properties:
	 * - border-width
	 * - border-style
	 * - border-color
	 *
	 * @param string $property The CSS property name.
	 * @param mixed  $value    The CSS property value.
	 * @return array|null Array of expanded properties or null if invalid.
	 */
	public function convert( string $property, $value ): ?array {
		if ( ! $this->supports( $property ) ) {
			return null;
		}

		if ( ! is_string( $value ) || '' === trim( $value ) ) {
			return null;
		}

		$parsed = $this->parse_border_shorthand( trim( $value ) );

		if ( null === $parsed ) {
			return null;
		}

		return $this->build_expanded_properties( $parsed );
	}

	protected function convert_value( string $property, $value ): ?array {
		// Not used - convert() handles everything for shorthand properties.
		return null;
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

	private function parse_border_shorthand( string $value ): ?array {
		// Handle 'none' keyword
		if ( 'none' === strtolower( $value ) ) {
			return [
				'width' => Size_Prop_Type::generate( [ 'size' => 0, 'unit' => 'px' ] ),
				'style' => 'none',
				'color' => null,
			];
		}

		$parts = $this->tokenize_border_value( $value );

		$width = null;
		$style = null;
		$color = null;

		foreach ( $parts as $part ) {
			$lower_part = strtolower( $part );

			// Check for style
			if ( in_array( $lower_part, self::VALID_STYLES, true ) ) {
				$style = $lower_part;
				continue;
			}

			// Check for width keyword
			if ( isset( self::WIDTH_KEYWORDS[ $lower_part ] ) ) {
				$width = Size_Prop_Type::generate( self::WIDTH_KEYWORDS[ $lower_part ] );
				continue;
			}

			// Check for CSS variable (could be width or color - try size first)
			if ( Variable_Resolver::is_css_variable( $part ) ) {
				// Try to resolve as size first (for width)
				if ( null === $width ) {
					$resolved_size = Variable_Resolver::resolve( $part, 'size' );
					if ( null !== $resolved_size ) {
						$width = $resolved_size;
						continue;
					}
				}

				// Try to resolve as color
				if ( null === $color ) {
					$resolved_color = Variable_Resolver::resolve( $part, 'color' );
					if ( null !== $resolved_color ) {
						$color = $resolved_color;
						continue;
					}
				}
				continue;
			}

			// Check for size value (width)
			$size_value = Size_Value_Parser::parse( $part );
			if ( null !== $size_value && null === $width ) {
				$width = Size_Prop_Type::generate( $size_value );
				continue;
			}

			// Check for color
			if ( $this->is_color_value( $part ) ) {
				$color = $this->resolve_color_value( $part );
				continue;
			}
		}

		// Border shorthand requires at least a style to be valid
		if ( null === $style ) {
			return null;
		}

		return [
			'width' => $width ?? Size_Prop_Type::generate( [ 'size' => 3, 'unit' => 'px' ] ), // Default: medium
			'style' => $style,
			'color' => $color,
		];
	}

	private function tokenize_border_value( string $value ): array {
		$parts = [];
		$current_part = '';
		$paren_depth = 0;

		$chars = str_split( $value );

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

		if ( str_starts_with( $value, '#' ) ) {
			return true;
		}

		if ( str_starts_with( strtolower( $value ), 'rgb' ) || str_starts_with( strtolower( $value ), 'hsl' ) ) {
			return true;
		}

		return in_array( strtolower( $value ), self::NAMED_COLORS, true );
	}

	/**
	 * Build expanded properties from parsed border values.
	 *
	 * @param array $parsed The parsed border values.
	 * @return array Associative array of expanded properties.
	 */
	private function build_expanded_properties( array $parsed ): array {
		$result = [];

		// Add width
		$result['border-width'] = $parsed['width'];

		// Add style
		$result['border-style'] = String_Prop_Type::generate( $parsed['style'] );

		// Add color if present
		if ( null !== $parsed['color'] ) {
			$result['border-color'] = $parsed['color'];
		}

		return $result;
	}
}
