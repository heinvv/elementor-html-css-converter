<?php
namespace ElementorHtmlCssConverter\Converters\Classes;

use ElementorHtmlCssConverter\Converters\Css\Breakpoint_Matcher;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Atomic_To_Css_Converter {

	private const DIMENSION_SIDES = [
		'block-start'  => 'block-start',
		'block-end'    => 'block-end',
		'inline-start' => 'inline-start',
		'inline-end'   => 'inline-end',
	];

	private const BREAKPOINT_ORDER = [ 'desktop', 'tablet', 'mobile', 'mobile_extra', 'tablet_extra', 'laptop', 'widescreen' ];

	public function convert_classes_to_css( array $classes ): string {
		$desktop_rules = [];
		$responsive_rules = [];

		foreach ( $classes as $class_config ) {
			$label    = $class_config['label'] ?? '';
			$variants = $class_config['variants'] ?? [];

			if ( empty( $label ) || empty( $variants ) ) {
				continue;
			}

			$selector = '.' . $this->sanitize_class_name( $label );

			foreach ( $variants as $variant ) {
				$meta       = $variant['meta'] ?? [];
				$props      = $variant['props'] ?? [];
				$custom_css = $variant['custom_css'] ?? [];
				$breakpoint = $meta['breakpoint'] ?? 'desktop';
				$state      = $meta['state'] ?? null;

				$css_properties = $this->convert_props_to_css( $props );
				$raw_custom_css = $this->decode_custom_css( $custom_css );

				if ( ! empty( $raw_custom_css ) ) {
					$custom_lines = $this->extract_properties_from_raw_css( $raw_custom_css );
					$css_properties = array_merge( $css_properties, $custom_lines );
				}

				if ( empty( $css_properties ) ) {
					continue;
				}

				$full_selector = $state ? $selector . ':' . $state : $selector;
				$rule = $this->format_rule( $full_selector, $css_properties );

				if ( 'desktop' === $breakpoint ) {
					$desktop_rules[] = $rule;
				} else {
					if ( ! isset( $responsive_rules[ $breakpoint ] ) ) {
						$responsive_rules[ $breakpoint ] = [];
					}
					$responsive_rules[ $breakpoint ][] = $rule;
				}
			}
		}

		return $this->assemble_css( $desktop_rules, $responsive_rules );
	}

	private function convert_props_to_css( array $props ): array {
		$css_properties = [];

		foreach ( $props as $prop_name => $prop_value ) {
			if ( ! is_array( $prop_value ) || ! isset( $prop_value['$$type'] ) ) {
				continue;
			}

			$converted = $this->convert_single_prop( $prop_name, $prop_value );

			foreach ( $converted as $css_property => $css_value ) {
				$css_properties[ $css_property ] = $css_value;
			}
		}

		return $css_properties;
	}

	private function convert_single_prop( string $prop_name, array $prop_value ): array {
		$type = $prop_value['$$type'] ?? '';
		$value = $prop_value['value'] ?? null;

		switch ( $type ) {
			case 'size':
				return $this->convert_size_prop( $prop_name, $value );

			case 'string':
				return [ $prop_name => (string) $value ];

			case 'number':
				return [ $prop_name => (string) $value ];

			case 'color':
				return $this->convert_color_prop( $prop_name, $value );

			case 'dimensions':
				return $this->convert_dimensions_prop( $prop_name, $value );

			case 'background':
				return $this->convert_background_prop( $value );

			case 'flex':
				return $this->convert_flex_prop( $value );

			case 'border':
				return $this->convert_border_prop( $value );

			case 'box-shadow':
				return $this->convert_box_shadow_prop( $value );

			case 'text-shadow':
				return $this->convert_text_shadow_prop( $value );

			case 'linked-dimensions':
				return $this->convert_dimensions_prop( $prop_name, $value );

			default:
				return [];
		}
	}

	private function convert_size_prop( string $prop_name, $value ): array {
		$css_value = $this->size_to_css( $value );

		if ( null === $css_value ) {
			return [];
		}

		return [ $prop_name => $css_value ];
	}

	private function size_to_css( $value ): ?string {
		if ( ! is_array( $value ) ) {
			return null;
		}

		if ( isset( $value['$$type'] ) && 'global-variable-ref' === $value['$$type'] ) {
			$ref_value = $value['value'] ?? [];
			$var_label = $ref_value['label'] ?? '';
			if ( ! empty( $var_label ) ) {
				return 'var(--' . $var_label . ')';
			}
			return null;
		}

		$size = $value['size'] ?? null;
		$unit = $value['unit'] ?? 'px';

		if ( null === $size ) {
			return null;
		}

		if ( 'custom' === $unit ) {
			return (string) $size;
		}

		return $size . $unit;
	}

	private function convert_color_prop( string $prop_name, $value ): array {
		$css_value = $this->color_to_css( $value );

		if ( null === $css_value ) {
			return [];
		}

		return [ $prop_name => $css_value ];
	}

	private function color_to_css( $value ): ?string {
		if ( is_string( $value ) ) {
			return $value;
		}

		if ( ! is_array( $value ) ) {
			return null;
		}

		if ( isset( $value['$$type'] ) && 'global-variable-ref' === $value['$$type'] ) {
			$ref_value = $value['value'] ?? [];
			$var_label = $ref_value['label'] ?? '';
			if ( ! empty( $var_label ) ) {
				return 'var(--' . $var_label . ')';
			}
			return null;
		}

		$color = $value['color'] ?? null;
		if ( is_string( $color ) ) {
			return $color;
		}

		if ( is_array( $color ) && isset( $color['$$type'] ) ) {
			return $this->color_to_css( $color['value'] ?? $color );
		}

		return null;
	}

	private function convert_dimensions_prop( string $prop_name, $value ): array {
		if ( ! is_array( $value ) ) {
			return [];
		}

		$css_properties = [];

		foreach ( self::DIMENSION_SIDES as $side_key => $css_suffix ) {
			if ( ! isset( $value[ $side_key ] ) ) {
				continue;
			}

			$side_value = $value[ $side_key ];

			if ( is_array( $side_value ) && isset( $side_value['$$type'] ) && 'size' === $side_value['$$type'] ) {
				$css_value = $this->size_to_css( $side_value['value'] ?? [] );
			} else {
				$css_value = $this->size_to_css( $side_value );
			}

			if ( null !== $css_value ) {
				$css_properties[ $prop_name . '-' . $css_suffix ] = $css_value;
			}
		}

		return $css_properties;
	}

	private function convert_background_prop( $value ): array {
		if ( ! is_array( $value ) ) {
			return [];
		}

		$css_properties = [];

		if ( isset( $value['color'] ) ) {
			$color_value = $value['color'];

			if ( is_array( $color_value ) && isset( $color_value['$$type'] ) && 'color' === $color_value['$$type'] ) {
				$css_color = $this->color_to_css( $color_value['value'] ?? null );
			} else {
				$css_color = $this->color_to_css( $color_value );
			}

			if ( null !== $css_color ) {
				$css_properties['background-color'] = $css_color;
			}
		}

		if ( isset( $value['background-overlay'] ) ) {
			$overlay = $value['background-overlay'];
			$overlay_value = $overlay['value'] ?? [];

			if ( is_array( $overlay_value ) ) {
				foreach ( $overlay_value as $overlay_item ) {
					$overlay_type = $overlay_item['$$type'] ?? '';

					if ( 'background-gradient-overlay' === $overlay_type ) {
						$gradient_css = $this->convert_gradient_to_css( $overlay_item['value'] ?? [] );
						if ( null !== $gradient_css ) {
							$css_properties['background-image'] = $gradient_css;
						}
					}

					if ( 'background-image-overlay' === $overlay_type ) {
						$image_css = $this->convert_image_overlay_to_css( $overlay_item['value'] ?? [] );
						if ( ! empty( $image_css ) ) {
							$css_properties = array_merge( $css_properties, $image_css );
						}
					}
				}
			}
		}

		return $css_properties;
	}

	private function convert_gradient_to_css( array $gradient_data ): ?string {
		$type_data = $gradient_data['type'] ?? [];
		$type = $type_data['value'] ?? 'linear';

		$stops_data = $gradient_data['stops'] ?? [];
		$stops_value = $stops_data['value'] ?? [];

		if ( empty( $stops_value ) ) {
			return null;
		}

		$stop_parts = [];
		foreach ( $stops_value as $stop ) {
			$stop_value = $stop['value'] ?? [];
			$color_data = $stop_value['color'] ?? [];
			$offset_data = $stop_value['offset'] ?? [];

			$color = $this->color_to_css( $color_data['value'] ?? null );
			$offset = $offset_data['value'] ?? null;

			if ( null === $color ) {
				continue;
			}

			$stop_css = $color;
			if ( null !== $offset ) {
				$stop_css .= ' ' . $offset . '%';
			}

			$stop_parts[] = $stop_css;
		}

		if ( empty( $stop_parts ) ) {
			return null;
		}

		if ( 'radial' === $type ) {
			$position = $gradient_data['positions'] ?? [];
			$position_value = $position['value'] ?? 'center center';
			return 'radial-gradient(circle at ' . $position_value . ', ' . implode( ', ', $stop_parts ) . ')';
		}

		$angle_data = $gradient_data['angle'] ?? [];
		$angle = $angle_data['value'] ?? 180;
		return 'linear-gradient(' . $angle . 'deg, ' . implode( ', ', $stop_parts ) . ')';
	}

	private function convert_image_overlay_to_css( array $overlay_data ): array {
		$css = [];

		$image = $overlay_data['image'] ?? [];
		$image_value = $image['value'] ?? [];
		$src = $image_value['src'] ?? [];
		$src_value = $src['value'] ?? [];
		$url = $src_value['url'] ?? null;

		if ( null === $url || 'none' === $url ) {
			return $css;
		}

		$css['background-image'] = 'url(' . $url . ')';

		$repeat = $overlay_data['repeat'] ?? [];
		if ( ! empty( $repeat ) ) {
			$repeat_value = $repeat['value'] ?? null;
			if ( null !== $repeat_value ) {
				$css['background-repeat'] = (string) $repeat_value;
			}
		}

		$size = $overlay_data['size'] ?? [];
		if ( ! empty( $size ) ) {
			$size_value = $size['value'] ?? null;
			if ( null !== $size_value ) {
				$css['background-size'] = (string) $size_value;
			}
		}

		$position = $overlay_data['position'] ?? [];
		if ( ! empty( $position ) ) {
			$position_value = $position['value'] ?? null;
			if ( null !== $position_value ) {
				$css['background-position'] = (string) $position_value;
			}
		}

		return $css;
	}

	private function convert_flex_prop( $value ): array {
		if ( ! is_array( $value ) ) {
			return [];
		}

		$css = [];

		$component_map = [
			'direction'      => 'flex-direction',
			'wrap'           => 'flex-wrap',
			'grow'           => 'flex-grow',
			'shrink'         => 'flex-shrink',
			'basis'          => 'flex-basis',
			'align-items'    => 'align-items',
			'align-content'  => 'align-content',
			'align-self'     => 'align-self',
			'justify-content' => 'justify-content',
		];

		foreach ( $value as $component => $component_value ) {
			if ( ! is_array( $component_value ) || ! isset( $component_value['$$type'] ) ) {
				continue;
			}

			$css_property = $component_map[ $component ] ?? $component;
			$css_value = $component_value['value'] ?? null;

			if ( null !== $css_value ) {
				$css[ $css_property ] = (string) $css_value;
			}
		}

		return $css;
	}

	private function convert_border_prop( $value ): array {
		if ( ! is_array( $value ) ) {
			return [];
		}

		$css = [];

		$sides = [ 'top', 'right', 'bottom', 'left' ];

		foreach ( $sides as $side ) {
			if ( ! isset( $value[ $side ] ) ) {
				continue;
			}

			$side_data = $value[ $side ];
			if ( ! is_array( $side_data ) ) {
				continue;
			}

			$width = $side_data['width'] ?? null;
			$style = $side_data['style'] ?? null;
			$color = $side_data['color'] ?? null;

			$parts = [];

			if ( is_array( $width ) && isset( $width['$$type'] ) ) {
				$width_css = $this->size_to_css( $width['value'] ?? [] );
				if ( null !== $width_css ) {
					$parts[] = $width_css;
				}
			}

			if ( is_array( $style ) && isset( $style['value'] ) ) {
				$parts[] = (string) $style['value'];
			}

			if ( null !== $color ) {
				$color_css = $this->color_to_css( is_array( $color ) && isset( $color['value'] ) ? $color['value'] : $color );
				if ( null !== $color_css ) {
					$parts[] = $color_css;
				}
			}

			if ( ! empty( $parts ) ) {
				$css[ 'border-' . $side ] = implode( ' ', $parts );
			}
		}

		return $css;
	}

	private function convert_box_shadow_prop( $value ): array {
		if ( ! is_array( $value ) || empty( $value ) ) {
			return [];
		}

		$shadows = is_array( $value ) && isset( $value[0] ) ? $value : [ $value ];
		$shadow_parts = [];

		foreach ( $shadows as $shadow ) {
			$shadow_css = $this->single_shadow_to_css( $shadow );
			if ( null !== $shadow_css ) {
				$shadow_parts[] = $shadow_css;
			}
		}

		if ( empty( $shadow_parts ) ) {
			return [];
		}

		return [ 'box-shadow' => implode( ', ', $shadow_parts ) ];
	}

	private function single_shadow_to_css( array $shadow ): ?string {
		$h_offset = $this->extract_size_from_shadow_component( $shadow['horizontal'] ?? null );
		$v_offset = $this->extract_size_from_shadow_component( $shadow['vertical'] ?? null );
		$blur     = $this->extract_size_from_shadow_component( $shadow['blur'] ?? null );
		$spread   = $this->extract_size_from_shadow_component( $shadow['spread'] ?? null );
		$color    = $this->color_to_css( $shadow['color']['value'] ?? $shadow['color'] ?? null );

		$parts = [];
		$parts[] = $h_offset ?? '0px';
		$parts[] = $v_offset ?? '0px';

		if ( null !== $blur ) {
			$parts[] = $blur;
		}
		if ( null !== $spread ) {
			$parts[] = $spread;
		}
		if ( null !== $color ) {
			$parts[] = $color;
		}

		return implode( ' ', $parts );
	}

	private function extract_size_from_shadow_component( $component ): ?string {
		if ( null === $component ) {
			return null;
		}

		if ( is_array( $component ) && isset( $component['$$type'] ) && 'size' === $component['$$type'] ) {
			return $this->size_to_css( $component['value'] ?? [] );
		}

		if ( is_array( $component ) && isset( $component['value'] ) ) {
			return $this->size_to_css( $component['value'] );
		}

		return null;
	}

	private function convert_text_shadow_prop( $value ): array {
		$result = $this->convert_box_shadow_prop( $value );

		if ( isset( $result['box-shadow'] ) ) {
			return [ 'text-shadow' => $result['box-shadow'] ];
		}

		return [];
	}

	private function decode_custom_css( $custom_css ): string {
		if ( empty( $custom_css ) ) {
			return '';
		}

		$raw = $custom_css['raw'] ?? '';

		if ( empty( $raw ) ) {
			return '';
		}

		if ( class_exists( '\Elementor\Utils' ) && method_exists( '\Elementor\Utils', 'decode_string' ) ) {
			$decoded = \Elementor\Utils::decode_string( $raw );
			if ( ! empty( $decoded ) ) {
				return $decoded;
			}
		}

		$decoded = base64_decode( $raw, true ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode

		if ( false !== $decoded && mb_check_encoding( $decoded, 'UTF-8' ) ) {
			return $decoded;
		}

		return $raw;
	}

	private function extract_properties_from_raw_css( string $raw_css ): array {
		$properties = [];
		$pattern = '/([a-zA-Z-]+)\s*:\s*([^;]+);?/';

		if ( preg_match_all( $pattern, $raw_css, $matches, PREG_SET_ORDER ) ) {
			foreach ( $matches as $match ) {
				$prop  = trim( $match[1] );
				$value = trim( $match[2] );
				$properties[ $prop ] = $value;
			}
		}

		return $properties;
	}

	private function format_rule( string $selector, array $properties ): string {
		$lines = [];

		foreach ( $properties as $property => $value ) {
			$lines[] = "\t" . $property . ': ' . $value . ';';
		}

		return $selector . " {\n" . implode( "\n", $lines ) . "\n}";
	}

	private function assemble_css( array $desktop_rules, array $responsive_rules ): string {
		$parts = [];

		if ( ! empty( $desktop_rules ) ) {
			$parts[] = implode( "\n\n", $desktop_rules );
		}

		$breakpoint_widths = $this->get_breakpoint_widths();

		foreach ( self::BREAKPOINT_ORDER as $breakpoint ) {
			if ( 'desktop' === $breakpoint || ! isset( $responsive_rules[ $breakpoint ] ) ) {
				continue;
			}

			$width = $breakpoint_widths[ $breakpoint ] ?? null;

			if ( null === $width ) {
				continue;
			}

			$rules_css = implode( "\n\n", $responsive_rules[ $breakpoint ] );
			$indented = $this->indent_rules( $rules_css );

			$parts[] = '@media (max-width: ' . $width . 'px) {' . "\n" . $indented . "\n" . '}';
		}

		if ( empty( $parts ) ) {
			return '';
		}

		return implode( "\n\n", $parts ) . "\n";
	}

	private function indent_rules( string $rules_css ): string {
		$lines = explode( "\n", $rules_css );
		$indented = array_map( function ( $line ) {
			return "\t" . $line;
		}, $lines );

		return implode( "\n", $indented );
	}

	private function get_breakpoint_widths(): array {
		$matcher = new Breakpoint_Matcher();
		$config = $matcher->get_breakpoints_config();

		$widths = [];

		foreach ( $config as $name => $breakpoint_config ) {
			$is_enabled = $breakpoint_config['is_enabled'] ?? false;
			if ( ! $is_enabled ) {
				continue;
			}

			$value = $breakpoint_config['value'] ?? null;
			if ( null !== $value ) {
				$widths[ $name ] = (int) $value;
			}
		}

		if ( empty( $widths ) ) {
			$widths = [
				'tablet' => 1024,
				'mobile' => 767,
			];
		}

		return $widths;
	}

	private function sanitize_class_name( string $label ): string {
		return preg_replace( '/[^a-zA-Z0-9_-]/', '-', $label );
	}
}
