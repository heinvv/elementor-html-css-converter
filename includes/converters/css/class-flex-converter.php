<?php
namespace ElementorHtmlCssConverter\Converters\Css;

use ElementorHtmlCssConverter\Converters\Css\Property_Converter_Base;
use ElementorHtmlCssConverter\Converters\Css\Size_Value_Parser;
use Elementor\Modules\AtomicWidgets\PropTypes\Flex_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\Number_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Size_Prop_Type;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Flex Shorthand Converter
 *
 * Converts flex shorthand property to Elementor's Flex_Prop_Type.
 * The Flex_Transformer in Elementor will render this as a CSS flex shorthand.
 *
 * Input: "flex: 1 0 auto"
 * Output: Flex_Prop_Type with flexGrow, flexShrink, flexBasis
 */
class Flex_Converter extends Property_Converter_Base {

	private const SUPPORTED_PROPERTIES = [ 'flex' ];

	protected function get_supported_properties_list(): array {
		return self::SUPPORTED_PROPERTIES;
	}

	protected function convert_value( string $property, $value ): ?array {
		// Not used - convert() handles everything for shorthand properties.
		return null;
	}

	public function convert( string $property, $value ): ?array {
		if ( ! $this->supports( $property ) ) {
			return null;
		}

		if ( ! $this->is_valid_string_value( $value ) ) {
			return null;
		}

		$value = strtolower( trim( $value ) );

		// Handle keyword values
		if ( 'none' === $value ) {
			// flex: none = flex: 0 0 auto
			return $this->generate_flex( 0, 0, [ 'size' => 'auto', 'unit' => 'custom' ] );
		}

		if ( 'auto' === $value ) {
			// flex: auto = flex: 1 1 auto
			return $this->generate_flex( 1, 1, [ 'size' => 'auto', 'unit' => 'custom' ] );
		}

		if ( 'initial' === $value ) {
			// flex: initial = flex: 0 1 auto
			return $this->generate_flex( 0, 1, [ 'size' => 'auto', 'unit' => 'custom' ] );
		}

		// Parse shorthand values
		$parts = preg_split( '/\s+/', $value );
		$count = count( $parts );

		if ( 0 === $count || $count > 3 ) {
			return null;
		}

		return $this->parse_flex_shorthand( $parts );
	}

	private function is_valid_string_value( $value ): bool {
		return is_string( $value ) && '' !== trim( $value );
	}

	private function parse_flex_shorthand( array $parts ): ?array {
		$grow   = 0;
		$shrink = 1;
		$basis  = [ 'size' => 'auto', 'unit' => 'custom' ];

		$count = count( $parts );

		if ( 1 === $count ) {
			if ( is_numeric( $parts[0] ) ) {
				$grow   = (float) $parts[0];
				$shrink = 1;
				$basis  = [ 'size' => 0, 'unit' => 'px' ];
			} else {
				$parsed_basis = $this->parse_basis( $parts[0] );
				if ( null === $parsed_basis ) {
					return null;
				}
				$basis = $parsed_basis;
			}
		}

		if ( 2 === $count ) {
			if ( ! is_numeric( $parts[0] ) ) {
				return null;
			}
			$grow = (float) $parts[0];

			if ( is_numeric( $parts[1] ) ) {
				$shrink = (float) $parts[1];
				$basis  = [ 'size' => 0, 'unit' => 'px' ];
			} else {
				$parsed_basis = $this->parse_basis( $parts[1] );
				if ( null === $parsed_basis ) {
					return null;
				}
				$basis  = $parsed_basis;
				$shrink = 1;
			}
		}

		if ( 3 === $count ) {
			if ( ! is_numeric( $parts[0] ) || ! is_numeric( $parts[1] ) ) {
				return null;
			}
			$grow   = (float) $parts[0];
			$shrink = (float) $parts[1];

			$parsed_basis = $this->parse_basis( $parts[2] );
			if ( null === $parsed_basis ) {
				return null;
			}
			$basis = $parsed_basis;
		}

		// Validate non-negative values
		if ( $grow < 0 || $shrink < 0 ) {
			return null;
		}

		return $this->generate_flex( $grow, $shrink, $basis );
	}

	private function parse_basis( string $value ): ?array {
		$value = strtolower( trim( $value ) );

		// Keyword values
		$keywords = [
			'auto'        => [ 'size' => 'auto', 'unit' => 'custom' ],
			'content'     => [ 'size' => 'content', 'unit' => 'custom' ],
			'fit-content' => [ 'size' => 'fit-content', 'unit' => 'custom' ],
			'max-content' => [ 'size' => 'max-content', 'unit' => 'custom' ],
			'min-content' => [ 'size' => 'min-content', 'unit' => 'custom' ],
		];

		if ( isset( $keywords[ $value ] ) ) {
			return $keywords[ $value ];
		}

		// Try to parse as size
		return Size_Value_Parser::parse( $value );
	}

	/**
	 * Generate a Flex_Prop_Type from parsed values.
	 *
	 * @param float $grow   The flex-grow value.
	 * @param float $shrink The flex-shrink value.
	 * @param array $basis  The flex-basis value.
	 * @return array The Flex_Prop_Type array.
	 */
	private function generate_flex( float $grow, float $shrink, array $basis ): array {
		return Flex_Prop_Type::generate( [
			'flexGrow'   => Number_Prop_Type::generate( $grow ),
			'flexShrink' => Number_Prop_Type::generate( $shrink ),
			'flexBasis'  => Size_Prop_Type::generate( $basis ),
		] );
	}
}
