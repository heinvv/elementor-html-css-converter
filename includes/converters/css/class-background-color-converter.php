<?php
namespace ElementorHtmlCssConverter\Converters\Css;

use ElementorHtmlCssConverter\Converters\Abstracts\Property_Converter_Base;
use ElementorHtmlCssConverter\Converters\Parsers\Color_Value_Parser;
use ElementorHtmlCssConverter\Converters\Variables\Variable_Resolver;
use Elementor\Modules\AtomicWidgets\PropTypes\Background_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Background_Overlay_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Background_Gradient_Overlay_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Gradient_Color_Stop_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Color_Stop_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Color_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Image_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Background_Image_Overlay_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\Number_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Background_Color_Converter extends Property_Converter_Base {

	private const SUPPORTED_PROPERTIES = [ 'background-color', 'background', 'background-image' ];
	private const OUTPUT_PROPERTY = 'background';

	protected function get_supported_properties_list(): array {
		return self::SUPPORTED_PROPERTIES;
	}

	public function get_output_property( string $property ): string {
		return self::OUTPUT_PROPERTY;
	}

	public function convert( string $property, $value ): ?array {
		if ( ! $this->supports( $property ) ) {
			return null;
		}

		if ( ! $this->is_valid_string_value( $value ) ) {
			return null;
		}

		$value = trim( $value );

		if ( 'none' === strtolower( $value ) ) {
			return null;
		}

		// Check if value is a CSS variable reference
		if ( Variable_Resolver::is_css_variable( $value ) ) {
			$resolved = Variable_Resolver::resolve( $value, 'color' );

			if ( null !== $resolved ) {
				// Wrap the resolved variable in background prop type structure
				return Background_Prop_Type::generate( [
					'color' => $resolved,
				] );
			}

			// If variable couldn't be resolved, fall through to regular parsing
		}

		// Try to parse as gradient first
		if ( $this->is_gradient_value( $value ) ) {
			return $this->parse_gradient( $value );
		}

		// Try to parse as image URL
		if ( $this->is_image_value( $value ) ) {
			return $this->parse_image( $value );
		}

		// Try to parse as simple color
		$parsed_color = Color_Value_Parser::parse( $value );

		if ( null !== $parsed_color ) {
			return Background_Prop_Type::generate( [
				'color' => Color_Prop_Type::generate( $parsed_color ),
			] );
		}

		return null;
	}

	private function is_valid_string_value( $value ): bool {
		return is_string( $value ) && '' !== trim( $value );
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
		$angle = 180; // Default angle (top to bottom)

		// Extract content inside linear-gradient()
		if ( ! preg_match( '/linear-gradient\s*\(\s*(.+)\s*\)/is', $value, $content_match ) ) {
			return null;
		}

		$content = $content_match[1];

		// Parse angle if present (e.g., "90deg", "to right", etc.)
		$angle = $this->parse_gradient_angle( $content );

		// Remove angle/direction from content to get color stops
		$content = $this->remove_angle_from_gradient_content( $content );

		// Parse color stops
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
		// Extract content inside radial-gradient()
		if ( ! preg_match( '/radial-gradient\s*\(\s*(.+)\s*\)/is', $value, $content_match ) ) {
			return null;
		}

		$content = $content_match[1];

		// Extract position from "at <position>" before parsing stops.
		$position = $this->extract_radial_position( $content );

		// For radial gradients, remove shape/size/position before parsing stops
		// Common patterns: "circle at center", "ellipse", "closest-side", etc.
		$content = preg_replace( '/^(circle|ellipse)?\s*(closest-side|closest-corner|farthest-side|farthest-corner)?\s*(at\s+[^,]+)?\s*,?\s*/i', '', $content );

		$stops = $this->parse_color_stops( $content );

		if ( empty( $stops ) ) {
			return null;
		}

		// Always include positions for radial gradients to match Elementor editor format.
		return [
			'type'      => String_Prop_Type::generate( 'radial' ),
			'angle'     => Number_Prop_Type::generate( 180 ),
			'stops'     => Gradient_Color_Stop_Prop_Type::generate( $stops ),
			'positions' => String_Prop_Type::generate( $position ),
		];
	}

	/**
	 * Extract the position from a radial gradient.
	 *
	 * @param string $content The gradient content.
	 * @return string The position (e.g., "center center", "top left").
	 */
	private function extract_radial_position( string $content ): string {
		// Look for "at <position>" pattern.
		if ( preg_match( '/at\s+([^,]+)/i', $content, $matches ) ) {
			$position = strtolower( trim( $matches[1] ) );

			// Normalize single position to "position position" format.
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

			// Handle two-value positions.
			$parts = preg_split( '/\s+/', $position );
			if ( count( $parts ) >= 2 ) {
				return $parts[0] . ' ' . $parts[1];
			}

			return $position . ' ' . $position;
		}

		// Default position.
		return 'center center';
	}

	private function parse_gradient_angle( string $content ): float {
		// Check for degree angle (e.g., "90deg")
		if ( preg_match( '/^(-?\d+(?:\.\d+)?)\s*deg/i', trim( $content ), $matches ) ) {
			return (float) $matches[1];
		}

		// Check for direction keywords
		$content_lower = strtolower( trim( $content ) );

		if ( str_starts_with( $content_lower, 'to ' ) ) {
			$direction = substr( $content_lower, 3 );
			$direction = trim( explode( ',', $direction )[0] );

			$direction_map = [
				'top'          => 0,
				'right'        => 90,
				'bottom'       => 180,
				'left'         => 270,
				'top right'    => 45,
				'right top'    => 45,
				'bottom right' => 135,
				'right bottom' => 135,
				'bottom left'  => 225,
				'left bottom'  => 225,
				'top left'     => 315,
				'left top'     => 315,
			];

			if ( isset( $direction_map[ $direction ] ) ) {
				return $direction_map[ $direction ];
			}
		}

		// Default: top to bottom
		return 180;
	}

	private function remove_angle_from_gradient_content( string $content ): string {
		// Remove degree angle
		$content = preg_replace( '/^-?\d+(?:\.\d+)?\s*deg\s*,?\s*/i', '', trim( $content ) );

		// Remove direction keywords
		$content = preg_replace( '/^to\s+(?:top|bottom|left|right)(?:\s+(?:top|bottom|left|right))?\s*,?\s*/i', '', $content );

		return trim( $content );
	}

	private function parse_color_stops( string $content ): array {
		$stops = [];

		// Split by comma, but be careful with commas inside color functions
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

		// Extract color and optional position
		$offset = null;

		// Check for percentage at the end
		if ( preg_match( '/\s+(\d+(?:\.\d+)?)\s*%\s*$/', $part, $percent_match ) ) {
			$offset = (float) $percent_match[1];
			$part   = preg_replace( '/\s+\d+(?:\.\d+)?\s*%\s*$/', '', $part );
		}

		// Parse the remaining as color
		$part         = trim( $part );
		$parsed_color = Color_Value_Parser::parse( $part );

		if ( null === $parsed_color ) {
			// Try to match common color patterns directly
			$parsed_color = $this->try_parse_gradient_color( $part );
		}

		if ( null === $parsed_color ) {
			return null;
		}

		// Calculate default offset if not specified
		if ( null === $offset ) {
			if ( 0 === $index ) {
				$offset = 0;
			} elseif ( $index === $total - 1 ) {
				$offset = 100;
			} else {
				// Evenly distribute
				$offset = ( $index / ( $total - 1 ) ) * 100;
			}
		}

		return Color_Stop_Prop_Type::generate( [
			'color'  => Color_Prop_Type::generate( $parsed_color ),
			'offset' => Number_Prop_Type::generate( $offset ),
		] );
	}

	private function try_parse_gradient_color( string $value ): ?string {
		$value = trim( $value );

		// Hex colors (including 8-digit for alpha)
		if ( preg_match( '/^#([a-f0-9]{3}|[a-f0-9]{6}|[a-f0-9]{8})$/i', $value ) ) {
			return strtolower( $value );
		}

		// RGB/RGBA functions
		if ( preg_match( '/^rgba?\s*\([^)]+\)$/i', $value ) ) {
			return $value;
		}

		// HSL/HSLA functions
		if ( preg_match( '/^hsla?\s*\([^)]+\)$/i', $value ) ) {
			return $value;
		}

		// Named colors (common ones)
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
		// Extract URL from url()
		if ( ! preg_match( '/url\s*\(\s*["\']?([^"\')\s]+)["\']?\s*\)/i', $value, $matches ) ) {
			return null;
		}

		$url = $matches[1];

		if ( empty( $url ) ) {
			return null;
		}

		return Background_Prop_Type::generate( [
			'background-overlay' => Background_Overlay_Prop_Type::generate( [
				Background_Image_Overlay_Prop_Type::generate( [
					'image' => Image_Prop_Type::generate( [
						'src' => [
							'$$type' => 'image-src',
							'value'  => [
								'id'  => null,
								'url' => $url,
							],
						],
					] ),
				] ),
			] ),
		] );
	}
}
