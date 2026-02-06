<?php
namespace ElementorHtmlCssConverter\Converters\Css\Properties;

use ElementorHtmlCssConverter\Converters\Css\Property_Converter_Base;
use ElementorHtmlCssConverter\Converters\Css\Size_Value_Parser;
use Elementor\Modules\AtomicWidgets\PropTypes\Flex_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\Number_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Size_Prop_Type;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Flex_Converter extends Property_Converter_Base {

	private const SUPPORTED_PROPERTIES = [ 'flex' ];
	private const FLEX_GROW_DEFAULT = 0;
	private const FLEX_SHRINK_DEFAULT = 1;
	private const FLEX_BASIS_ZERO_SIZE = 0;
	private const FLEX_NONE_GROW = 0;
	private const FLEX_NONE_SHRINK = 0;
	private const FLEX_AUTO_GROW = 1;
	private const FLEX_AUTO_SHRINK = 1;
	private const FLEX_INITIAL_GROW = 0;
	private const FLEX_INITIAL_SHRINK = 1;
	private const REGEX_WHITESPACE_SPLIT = '/\s+/';

	protected function get_supported_properties_list(): array {
		return self::SUPPORTED_PROPERTIES;
	}

	protected function convert_value( string $property, $value ): ?array {
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

		if ( 'none' === $value ) {
			return $this->generate_flex( self::FLEX_NONE_GROW, self::FLEX_NONE_SHRINK, [ 'size' => 'auto', 'unit' => 'custom' ] );
		}

		if ( 'auto' === $value ) {
			return $this->generate_flex( self::FLEX_AUTO_GROW, self::FLEX_AUTO_SHRINK, [ 'size' => 'auto', 'unit' => 'custom' ] );
		}

		if ( 'initial' === $value ) {
			return $this->generate_flex( self::FLEX_INITIAL_GROW, self::FLEX_INITIAL_SHRINK, [ 'size' => 'auto', 'unit' => 'custom' ] );
		}

		$parts = preg_split( self::REGEX_WHITESPACE_SPLIT, $value );
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
		$grow   = self::FLEX_GROW_DEFAULT;
		$shrink = self::FLEX_SHRINK_DEFAULT;
		$basis  = [ 'size' => 'auto', 'unit' => 'custom' ];

		$count = count( $parts );

		if ( 1 === $count ) {
			if ( is_numeric( $parts[0] ) ) {
				$grow   = (float) $parts[0];
				$shrink = self::FLEX_SHRINK_DEFAULT;
				$basis  = [ 'size' => self::FLEX_BASIS_ZERO_SIZE, 'unit' => 'px' ];
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
				$basis  = [ 'size' => self::FLEX_BASIS_ZERO_SIZE, 'unit' => 'px' ];
			} else {
				$parsed_basis = $this->parse_basis( $parts[1] );
				if ( null === $parsed_basis ) {
					return null;
				}
				$basis  = $parsed_basis;
				$shrink = self::FLEX_SHRINK_DEFAULT;
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

		if ( $grow < 0 || $shrink < 0 ) {
			return null;
		}

		return $this->generate_flex( $grow, $shrink, $basis );
	}

	private function parse_basis( string $value ): ?array {
		$value = strtolower( trim( $value ) );

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

		return Size_Value_Parser::parse( $value );
	}

	private function generate_flex( float $grow, float $shrink, array $basis ): array {
		return Flex_Prop_Type::generate( [
			'flexGrow'   => Number_Prop_Type::generate( $grow ),
			'flexShrink' => Number_Prop_Type::generate( $shrink ),
			'flexBasis'  => Size_Prop_Type::generate( $basis ),
		] );
	}
}

