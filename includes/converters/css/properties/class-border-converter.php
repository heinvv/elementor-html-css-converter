<?php
namespace ElementorHtmlCssConverter\Converters\Css\Properties;

use ElementorHtmlCssConverter\Converters\Css\Property_Converter_Base;
use ElementorHtmlCssConverter\Converters\Css\Size_Value_Parser;
use ElementorHtmlCssConverter\Converters\Css\Color_Value_Parser;
use ElementorHtmlCssConverter\Converters\Css\Css_Named_Colors;
use ElementorHtmlCssConverter\Converters\Variables\Variable_Resolver;
use Elementor\Modules\AtomicWidgets\PropTypes\Color_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Size_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

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

	private const BORDER_WIDTH_THIN = 1;
	private const BORDER_WIDTH_MEDIUM = 3;
	private const BORDER_WIDTH_THICK = 5;
	private const DEFAULT_BORDER_WIDTH = 3;
	private const ZERO_SIZE = 0;

	private const WIDTH_KEYWORDS = [
		'thin'   => [ 'size' => self::BORDER_WIDTH_THIN, 'unit' => 'px' ],
		'medium' => [ 'size' => self::BORDER_WIDTH_MEDIUM, 'unit' => 'px' ],
		'thick'  => [ 'size' => self::BORDER_WIDTH_THICK, 'unit' => 'px' ],
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

		$parsed = $this->parse_border_shorthand( trim( $value ) );

		if ( null === $parsed ) {
			return null;
		}

		return $this->build_expanded_properties( $parsed );
	}

	protected function convert_value( string $property, $value ): ?array {
		return null;
	}

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
		$value_lower = strtolower( trim( $value ) );

		if ( 'none' === $value_lower ) {
			return [
				'width' => Size_Prop_Type::generate( [ 'size' => self::ZERO_SIZE, 'unit' => 'px' ] ),
				'style' => 'none',
				'color' => null,
			];
		}

		if ( '0' === $value_lower ) {
			return [
				'width' => Size_Prop_Type::generate( [ 'size' => self::ZERO_SIZE, 'unit' => 'px' ] ),
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

			if ( in_array( $lower_part, self::VALID_STYLES, true ) ) {
				$style = $lower_part;
				continue;
			}

			if ( isset( self::WIDTH_KEYWORDS[ $lower_part ] ) ) {
				$width = Size_Prop_Type::generate( self::WIDTH_KEYWORDS[ $lower_part ] );
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

			$size_value = Size_Value_Parser::parse( $part );
			if ( null !== $size_value && null === $width ) {
				$width = Size_Prop_Type::generate( $size_value );
				continue;
			}

			if ( $this->is_color_value( $part ) ) {
				$color = $this->resolve_color_value( $part );
				continue;
			}
		}

		if ( null === $style ) {
			return null;
		}

		return [
			'width' => $width ?? Size_Prop_Type::generate( [ 'size' => self::DEFAULT_BORDER_WIDTH, 'unit' => 'px' ] ),
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

		return Css_Named_Colors::is_named_color( $value );
	}

	private function build_expanded_properties( array $parsed ): array {
		$result = [];

		$result['border-width'] = $parsed['width'];

		$result['border-style'] = String_Prop_Type::generate( $parsed['style'] );

		if ( null !== $parsed['color'] ) {
			$result['border-color'] = $parsed['color'];
		}

		return $result;
	}
}

