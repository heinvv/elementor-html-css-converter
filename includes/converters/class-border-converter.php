<?php
namespace ElementorHtmlCssConverter\Converters;

use ElementorHtmlCssConverter\Abstracts\Property_Converter_Base;
use ElementorHtmlCssConverter\Parsers\Size_Value_Parser;
use ElementorHtmlCssConverter\Parsers\Color_Value_Parser;
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

		if ( ! $this->is_valid_string_value( $value ) ) {
			return null;
		}

		$parsed = $this->parse_border_shorthand( trim( $value ) );

		if ( null === $parsed ) {
			return null;
		}

		return $this->build_expanded_properties( $parsed );
	}

	private function is_valid_string_value( $value ): bool {
		return is_string( $value ) && '' !== trim( $value );
	}

	private function parse_border_shorthand( string $value ): ?array {
		// Handle 'none' keyword
		if ( 'none' === strtolower( $value ) ) {
			return [
				'width' => [ 'size' => 0, 'unit' => 'px' ],
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
				$width = self::WIDTH_KEYWORDS[ $lower_part ];
				continue;
			}

			// Check for size value (width)
			$size_value = Size_Value_Parser::parse( $part );
			if ( null !== $size_value && null === $width ) {
				$width = $size_value;
				continue;
			}

			// Check for color
			if ( $this->is_color_value( $part ) ) {
				$color = Color_Value_Parser::parse( $part );
				continue;
			}
		}

		// Border shorthand requires at least a style to be valid
		if ( null === $style ) {
			return null;
		}

		return [
			'width' => $width ?? [ 'size' => 3, 'unit' => 'px' ], // Default: medium
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
		$result['border-width'] = Size_Prop_Type::generate( $parsed['width'] );

		// Add style
		$result['border-style'] = String_Prop_Type::generate( $parsed['style'] );

		// Add color if present
		if ( null !== $parsed['color'] ) {
			$result['border-color'] = Color_Prop_Type::generate( $parsed['color'] );
		}

		return $result;
	}
}
