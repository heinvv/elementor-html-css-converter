<?php
namespace ElementorHtmlCssConverter\Converters\Css\Properties;

use ElementorHtmlCssConverter\Converters\Abstracts\Property_Converter_Base;
use ElementorHtmlCssConverter\Converters\Css\Color_Value_Parser;
use Elementor\Modules\AtomicWidgets\PropTypes\Background_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Background_Overlay_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Background_Gradient_Overlay_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Gradient_Color_Stop_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Color_Stop_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Color_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Image_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Background_Image_Overlay_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Image_Attachment_Id_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\Number_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Background_Color_Converter extends Property_Converter_Base {

	private const REGEX_LINEAR_GRADIENT_EXTRACTION = '/linear-gradient\s*\(\s*(.+)\s*\)/is';
	private const REGEX_RADIAL_GRADIENT_EXTRACTION = '/radial-gradient\s*\(\s*(.+)\s*\)/is';
	private const REGEX_RADIAL_SHAPE_SIZE_POSITION_REMOVAL = '/^(circle|ellipse)?\s*(closest-side|closest-corner|farthest-side|farthest-corner)?\s*(at\s+[^,]+)?\s*,?\s*/i';
	private const REGEX_RADIAL_POSITION_EXTRACTION = '/at\s+([^,]+)/i';
	private const REGEX_WHITESPACE_SPLIT = '/\s+/';
	private const REGEX_DEGREE_ANGLE_EXTRACTION = '/^(-?\d+(?:\.\d+)?)\s*deg/i';
	private const REGEX_DEGREE_ANGLE_REMOVAL = '/^-?\d+(?:\.\d+)?\s*deg\s*,?\s*/i';
	private const REGEX_DIRECTION_KEYWORD_REMOVAL = '/^to\s+(?:top|bottom|left|right)(?:\s+(?:top|bottom|left|right))?\s*,?\s*/i';
	private const REGEX_COLOR_STOP_PERCENTAGE_EXTRACTION = '/\s+(\d+(?:\.\d+)?)\s*%\s*$/';
	private const REGEX_COLOR_STOP_PERCENTAGE_REMOVAL = '/\s+\d+(?:\.\d+)?\s*%\s*$/';
	private const REGEX_HEX_COLOR_MATCHING = '/^#([a-f0-9]{3}|[a-f0-9]{6}|[a-f0-9]{8})$/i';
	private const REGEX_RGB_RGBA_COLOR_MATCHING = '/^rgba?\s*\([^)]+\)$/i';
	private const REGEX_HSL_HSLA_COLOR_MATCHING = '/^hsla?\s*\([^)]+\)$/i';
	private const REGEX_URL_EXTRACTION = '/url\s*\(\s*["\']?([^"\')\s]+)["\']?\s*\)/i';
	private const SUPPORTED_PROPERTIES = [ 'background-color', 'background', 'background-image' ];
	private const OUTPUT_PROPERTY = 'background';
	private const DEFAULT_GRADIENT_ANGLE = 180;
	private const GRADIENT_ANGLE_TOP = 0;
	private const GRADIENT_ANGLE_RIGHT = 90;
	private const GRADIENT_ANGLE_BOTTOM = 180;
	private const GRADIENT_ANGLE_LEFT = 270;
	private const GRADIENT_ANGLE_TOP_RIGHT = 45;
	private const GRADIENT_ANGLE_BOTTOM_RIGHT = 135;
	private const GRADIENT_ANGLE_BOTTOM_LEFT = 225;
	private const GRADIENT_ANGLE_TOP_LEFT = 315;
	private const COLOR_STOP_OFFSET_START = 0;
	private const COLOR_STOP_OFFSET_END = 100;

	protected function get_supported_properties_list(): array {
		return self::SUPPORTED_PROPERTIES;
	}

	protected function get_variable_type(): ?string {
		return 'color';
	}

	public function get_output_property( string $property ): string {
		return self::OUTPUT_PROPERTY;
	}

	protected function wrap_resolved_variable( array $resolved, string $property ): array {
		return Background_Prop_Type::generate( [
			'color' => $resolved,
		] );
	}

	protected function convert_value( string $property, $value ): ?array {
		$value = trim( $value );

		if ( 'none' === strtolower( $value ) ) {
			return null;
		}

		if ( $this->is_gradient_value( $value ) ) {
			return $this->parse_gradient( $value );
		}

		if ( $this->is_image_value( $value ) ) {
			return $this->parse_image( $value );
		}

		$parsed_color = Color_Value_Parser::parse( $value );

		if ( null !== $parsed_color ) {
			return Background_Prop_Type::generate( [
				'color' => Color_Prop_Type::generate( $parsed_color ),
			] );
		}

		return null;
	}

	private function is_gradient_value( string $value ): bool {
		return false !== strpos( strtolower( $value ), 'gradient(' );
	}

	private function is_image_value( string $value ): bool {
		return false !== strpos( strtolower( $value ), 'url(' );
	}

	private function parse_gradient( string $value ): ?array {
		$value_lower = strtolower( $value );

		if ( false !== strpos( $value_lower, 'linear-gradient' ) ) {
			$gradient_data = $this->parse_linear_gradient( $value );
		} elseif ( false !== strpos( $value_lower, 'radial-gradient' ) ) {
			$gradient_data = $this->parse_radial_gradient( $value );
		} else {
			return null;
		}

		if ( null === $gradient_data ) {
			return null;
		}

		return Background_Prop_Type::generate( [
			'background-overlay' => Background_Overlay_Prop_Type::generate( [
				Background_Gradient_Overlay_Prop_Type::generate( $gradient_data ),
			] ),
		] );
	}

	private function parse_linear_gradient( string $value ): ?array {
		$angle = self::DEFAULT_GRADIENT_ANGLE;

		if ( ! preg_match( self::REGEX_LINEAR_GRADIENT_EXTRACTION, $value, $content_match ) ) {
			return null;
		}

		$content = $content_match[1];

		$angle = $this->parse_gradient_angle( $content );

		$content = $this->remove_angle_from_gradient_content( $content );

		$stops = $this->parse_color_stops( $content );

		if ( empty( $stops ) ) {
			return null;
		}

		return [
			'type'  => String_Prop_Type::generate( 'linear' ),
			'angle' => Number_Prop_Type::generate( $angle ),
			'stops' => Gradient_Color_Stop_Prop_Type::generate( $stops ),
		];
	}

	private function parse_radial_gradient( string $value ): ?array {
		if ( ! preg_match( self::REGEX_RADIAL_GRADIENT_EXTRACTION, $value, $content_match ) ) {
			return null;
		}

		$content = $content_match[1];

		$position = $this->extract_radial_position( $content );

		$content = preg_replace( self::REGEX_RADIAL_SHAPE_SIZE_POSITION_REMOVAL, '', $content );

		$stops = $this->parse_color_stops( $content );

		if ( empty( $stops ) ) {
			return null;
		}

		return [
			'type'      => String_Prop_Type::generate( 'radial' ),
			'angle'     => Number_Prop_Type::generate( self::DEFAULT_GRADIENT_ANGLE ),
			'stops'     => Gradient_Color_Stop_Prop_Type::generate( $stops ),
			'positions' => String_Prop_Type::generate( $position ),
		];
	}

	private function extract_radial_position( string $content ): string {
		if ( preg_match( self::REGEX_RADIAL_POSITION_EXTRACTION, $content, $matches ) ) {
			$position = strtolower( trim( $matches[1] ) );

			if ( 'center' === $position ) {
				return 'center center';
			}
			if ( 'top' === $position ) {
				return 'center top';
			}
			if ( 'bottom' === $position ) {
				return 'center bottom';
			}
			if ( 'left' === $position ) {
				return 'left center';
			}
			if ( 'right' === $position ) {
				return 'right center';
			}

			$parts = preg_split( self::REGEX_WHITESPACE_SPLIT, $position );
			if ( count( $parts ) >= 2 ) {
				return $parts[0] . ' ' . $parts[1];
			}

			return $position . ' ' . $position;
		}

		return 'center center';
	}

	private function parse_gradient_angle( string $content ): float {
		if ( preg_match( self::REGEX_DEGREE_ANGLE_EXTRACTION, trim( $content ), $matches ) ) {
			return (float) $matches[1];
		}

		$content_lower = strtolower( trim( $content ) );

		if ( str_starts_with( $content_lower, 'to ' ) ) {
			$direction = substr( $content_lower, 3 );
			$direction = trim( explode( ',', $direction )[0] );

			$direction_map = [
				'top'          => self::GRADIENT_ANGLE_TOP,
				'right'        => self::GRADIENT_ANGLE_RIGHT,
				'bottom'       => self::GRADIENT_ANGLE_BOTTOM,
				'left'         => self::GRADIENT_ANGLE_LEFT,
				'top right'    => self::GRADIENT_ANGLE_TOP_RIGHT,
				'right top'    => self::GRADIENT_ANGLE_TOP_RIGHT,
				'bottom right' => self::GRADIENT_ANGLE_BOTTOM_RIGHT,
				'right bottom' => self::GRADIENT_ANGLE_BOTTOM_RIGHT,
				'bottom left'  => self::GRADIENT_ANGLE_BOTTOM_LEFT,
				'left bottom'  => self::GRADIENT_ANGLE_BOTTOM_LEFT,
				'top left'     => self::GRADIENT_ANGLE_TOP_LEFT,
				'left top'     => self::GRADIENT_ANGLE_TOP_LEFT,
			];

			if ( isset( $direction_map[ $direction ] ) ) {
				return $direction_map[ $direction ];
			}
		}

		return self::DEFAULT_GRADIENT_ANGLE;
	}

	private function remove_angle_from_gradient_content( string $content ): string {
		$content = preg_replace( self::REGEX_DEGREE_ANGLE_REMOVAL, '', trim( $content ) );

		$content = preg_replace( self::REGEX_DIRECTION_KEYWORD_REMOVAL, '', $content );

		return trim( $content );
	}

	private function parse_color_stops( string $content ): array {
		$stops = [];

		$parts = $this->split_gradient_parts( $content );

		$total_parts = count( $parts );

		foreach ( $parts as $index => $part ) {
			$part = trim( $part );

			if ( empty( $part ) ) {
				continue;
			}

			$stop = $this->parse_single_color_stop( $part, $index, $total_parts );

			if ( null !== $stop ) {
				$stops[] = $stop;
			}
		}

		return $stops;
	}

	private function split_gradient_parts( string $content ): array {
		$parts   = [];
		$current = '';
		$depth   = 0;

		for ( $i = 0; $i < strlen( $content ); $i++ ) {
			$char = $content[ $i ];

			if ( '(' === $char ) {
				$depth++;
			} elseif ( ')' === $char ) {
				$depth--;
			} elseif ( ',' === $char && 0 === $depth ) {
				$parts[] = $current;
				$current = '';
				continue;
			}

			$current .= $char;
		}

		if ( '' !== $current ) {
			$parts[] = $current;
		}

		return $parts;
	}

	private function parse_single_color_stop( string $part, int $index, int $total ): ?array {
		$part = trim( $part );

		$offset = null;

		if ( preg_match( self::REGEX_COLOR_STOP_PERCENTAGE_EXTRACTION, $part, $percent_match ) ) {
			$offset = (float) $percent_match[1];
			$part   = preg_replace( self::REGEX_COLOR_STOP_PERCENTAGE_REMOVAL, '', $part );
		}

		$part         = trim( $part );
		$parsed_color = Color_Value_Parser::parse( $part );

		if ( null === $parsed_color ) {
			$parsed_color = $this->try_parse_gradient_color( $part );
		}

		if ( null === $parsed_color ) {
			return null;
		}

		if ( null === $offset ) {
			if ( 0 === $index ) {
				$offset = self::COLOR_STOP_OFFSET_START;
			} elseif ( $index === $total - 1 ) {
				$offset = self::COLOR_STOP_OFFSET_END;
			} else {
				$offset = ( $index / ( $total - 1 ) ) * self::COLOR_STOP_OFFSET_END;
			}
		}

		return Color_Stop_Prop_Type::generate( [
			'color'  => Color_Prop_Type::generate( $parsed_color ),
			'offset' => Number_Prop_Type::generate( $offset ),
		] );
	}

	private function try_parse_gradient_color( string $value ): ?string {
		$value = trim( $value );

		if ( preg_match( self::REGEX_HEX_COLOR_MATCHING, $value ) ) {
			return strtolower( $value );
		}

		if ( preg_match( self::REGEX_RGB_RGBA_COLOR_MATCHING, $value ) ) {
			return $value;
		}

		if ( preg_match( self::REGEX_HSL_HSLA_COLOR_MATCHING, $value ) ) {
			return $value;
		}

		$named_colors = [
			'transparent', 'black', 'white', 'red', 'green', 'blue', 'yellow',
			'cyan', 'magenta', 'gray', 'grey', 'orange', 'pink', 'purple',
			'brown', 'navy', 'teal', 'olive', 'maroon', 'aqua', 'fuchsia',
			'lime', 'silver', 'gold', 'coral', 'salmon', 'tomato', 'crimson',
		];

		if ( in_array( strtolower( $value ), $named_colors, true ) ) {
			return strtolower( $value );
		}

		return null;
	}

	private function parse_image( string $value ): ?array {
		if ( ! preg_match( self::REGEX_URL_EXTRACTION, $value, $matches ) ) {
			return null;
		}

		$url = $matches[1];

		if ( empty( $url ) ) {
			return null;
		}

		$image_src_value = [
			'id'  => null,
			'url' => $url,
		];

		if ( function_exists( 'attachment_url_to_postid' ) ) {
			$local_id = attachment_url_to_postid( $url );
			if ( $local_id > 0 ) {
				$image_src_value['id'] = Image_Attachment_Id_Prop_Type::generate( $local_id );
				$image_src_value['url'] = null;
			}
		}

		return Background_Prop_Type::generate( [
			'background-overlay' => Background_Overlay_Prop_Type::generate( [
				Background_Image_Overlay_Prop_Type::generate( [
					'image' => Image_Prop_Type::generate( [
						'src' => [
							'$$type' => 'image-src',
							'value'  => $image_src_value,
						],
					] ),
				] ),
			] ),
		] );
	}
}

