<?php
namespace ElementorHtmlCssConverter\Converters\Css;

use ElementorHtmlCssConverter\Converters\Css\Property_Converter_Base;
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

			if ( $this->process_border_part_style_and_width_keyword( $lower_part, $style, $width ) ) {
				continue;
			}

			if ( Variable_Resolver::is_css_variable( $part ) ) {
				if ( null === $width ) {
					$resolved_size = Variable_Resolver::resolve( $part, 'size' );
					if ( null !== $resolved_size ) {
						$width = $resolved_size;
						continue;
					}
				}

				if ( null === $color ) {
					$resolved_color = Variable_Resolver::resolve( $part, 'color' );
					if ( null !== $resolved_color ) {
						$color = $resolved_color;
						continue;
					}
				}
				continue;
			}

			if ( $this->process_border_part_size_value( $part, $width ) ) {
				continue;
			}

			if ( $this->process_border_part_color_value( $part, $color ) ) {
				continue;
			}
		}

		if ( ! $this->validate_border_style_required( $style ) ) {
			return null;
		}

		return [
			'width' => $this->get_border_width_with_default( $width ),
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
			} elseif ( ' ' === $char && 0 === $paren_depth && '' !== trim( $current_part ) ) {
				$parts[] = trim( $current_part );
				$current_part = '';
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

		$this->add_border_width_to_result( $result, $parsed['width'] );
		$this->add_border_style_to_result( $result, $parsed['style'] );
		$this->add_border_color_to_result_if_present( $result, $parsed['color'] );

		return $result;
	}

	/**
	 * Add border width to result array.
	 *
	 * @param array $result Result array (by reference).
	 * @param array $width  Width value.
	 * @return void
	 */
	private function add_border_width_to_result( array &$result, array $width ): void {
		$result['border-width'] = $width;
	}

	/**
	 * Add border style to result array.
	 *
	 * @param array  $result Result array (by reference).
	 * @param string $style  Style value.
	 * @return void
	 */
	private function add_border_style_to_result( array &$result, string $style ): void {
		$result['border-style'] = String_Prop_Type::generate( $style );
	}

	/**
	 * Add border color to result array if present.
	 *
	 * @param array      $result Result array (by reference).
	 * @param array|null $color  Color value or null.
	 * @return void
	 */
	private function add_border_color_to_result_if_present( array &$result, ?array $color ): void {
		if ( null !== $color ) {
			$result['border-color'] = $color;
		}
	}

	/**
	 * Process border part for style or width keyword.
	 *
	 * @param string   $lower_part Lowercase part to check.
	 * @param string|null $style     Style variable to set if found (by reference).
	 * @param array|null $width      Width variable to set if found (by reference).
	 * @return bool True if part was processed (style or width keyword found), false otherwise.
	 */
	private function process_border_part_style_and_width_keyword( string $lower_part, ?string &$style, ?array &$width ): bool {
		if ( in_array( $lower_part, self::VALID_STYLES, true ) ) {
			$style = $lower_part;
			return true;
		}

		if ( isset( self::WIDTH_KEYWORDS[ $lower_part ] ) ) {
			$width = Size_Prop_Type::generate( self::WIDTH_KEYWORDS[ $lower_part ] );
			return true;
		}

		return false;
	}

	/**
	 * Process border part for size value (width).
	 *
	 * @param string     $part  Part to check.
	 * @param array|null $width Width variable to set if found (by reference).
	 * @return bool True if part was processed (size value found and width was null), false otherwise.
	 */
	private function process_border_part_size_value( string $part, ?array &$width ): bool {
		$size_value = Size_Value_Parser::parse( $part );
		if ( null !== $size_value && null === $width ) {
			$width = Size_Prop_Type::generate( $size_value );
			return true;
		}

		return false;
	}

	/**
	 * Process border part for color value.
	 *
	 * @param string     $part  Part to check.
	 * @param array|null $color Color variable to set if found (by reference).
	 * @return bool True if part was processed (color value found), false otherwise.
	 */
	private function process_border_part_color_value( string $part, ?array &$color ): bool {
		if ( $this->is_color_value( $part ) ) {
			$color = $this->resolve_color_value( $part );
			return true;
		}

		return false;
	}

	/**
	 * Validate that border style is required and not null.
	 *
	 * @param string|null $style Style to validate.
	 * @return bool True if style is valid (not null), false otherwise.
	 */
	private function validate_border_style_required( ?string $style ): bool {
		return null !== $style;
	}

	/**
	 * Get border width with default medium value if not set.
	 *
	 * @param array|null $width Width value or null.
	 * @return array Width value or default medium width.
	 */
	private function get_border_width_with_default( ?array $width ): array {
		return $width ?? Size_Prop_Type::generate( [ 'size' => 3, 'unit' => 'px' ] );
	}
}
