<?php
/**
 * Class Extractor
 *
 * Extracts CSS class definitions from raw CSS.
 *
 * @package ElementorHtmlCssConverter
 */

namespace ElementorHtmlCssConverter\Converters\Classes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Class_Extractor
 *
 * Parses CSS and extracts class definitions with their properties.
 */
class Class_Extractor {

	/**
	 * Elementor internal class prefixes to skip.
	 *
	 * @var array
	 */
	private const ELEMENTOR_PREFIXES = [ 'e-con-', 'elementor-', 'e-' ];

	/**
	 * Maximum class name length.
	 *
	 * @var int
	 */
	private const MAX_CLASS_NAME_LENGTH = 50;

	/**
	 * Extract class definitions from CSS.
	 *
	 * @param string $css Raw CSS containing class definitions.
	 * @return array Array of class definitions with 'selector' and 'properties' keys.
	 */
	public function extract_from_css( string $css ): array {
		$classes = [];

		// Remove CSS comments.
		$css = preg_replace( '/\/\*.*?\*\//s', '', $css );

		// Remove @media queries (MVP: desktop only).
		$css = preg_replace( '/@media[^{]+\{([^{}]*\{[^}]*\})*[^}]*\}/s', '', $css );

		// Remove @keyframes, @font-face, and other at-rules.
		$css = preg_replace( '/@[a-z-]+[^{]*\{([^{}]*\{[^}]*\})*[^}]*\}/s', '', $css );

		// Pattern to match CSS rules: selector { properties }
		$pattern = '/([^{]+)\{([^}]+)\}/s';

		if ( preg_match_all( $pattern, $css, $matches, PREG_SET_ORDER ) ) {
			foreach ( $matches as $match ) {
				$selector   = trim( $match[1] );
				$properties = trim( $match[2] );

				// Process selector to extract class names.
				$class_name = $this->extract_class_name( $selector );

				if ( null === $class_name ) {
					continue;
				}

				// Parse properties.
				$parsed_properties = $this->parse_properties( $properties );

				if ( empty( $parsed_properties ) ) {
					continue;
				}

				// Merge if class already exists (same class in multiple rules).
				if ( isset( $classes[ $class_name ] ) ) {
					$classes[ $class_name ]['properties'] = array_merge(
						$classes[ $class_name ]['properties'],
						$parsed_properties
					);
				} else {
					$classes[ $class_name ] = [
						'selector'   => $selector,
						'properties' => $parsed_properties,
					];
				}
			}
		}

		return $classes;
	}

	/**
	 * Extract a single class name from a selector.
	 *
	 * @param string $selector CSS selector.
	 * @return string|null Class name or null if not extractable.
	 */
	private function extract_class_name( string $selector ): ?string {
		$selector = trim( $selector );

		// Skip empty selectors.
		if ( empty( $selector ) ) {
			return null;
		}

		// Skip pseudo-classes like .btn:hover (MVP).
		if ( strpos( $selector, ':' ) !== false ) {
			return null;
		}

		// Skip ID selectors.
		if ( strpos( $selector, '#' ) !== false ) {
			return null;
		}

		// Skip attribute selectors.
		if ( strpos( $selector, '[' ) !== false ) {
			return null;
		}

		// Check if selector starts with a class.
		if ( ! preg_match( '/^\.([a-zA-Z_-][a-zA-Z0-9_-]*)/', $selector, $match ) ) {
			return null;
		}

		$class_name = $match[1];

		// Skip compound selectors (.class1.class2).
		if ( preg_match( '/^\.[a-zA-Z_-][a-zA-Z0-9_-]*\./', $selector ) ) {
			return null;
		}

		// Skip descendant/child selectors (.parent .child).
		if ( preg_match( '/^\.[a-zA-Z_-][a-zA-Z0-9_-]*\s+/', $selector ) ) {
			return null;
		}

		// Skip sibling selectors.
		if ( preg_match( '/^\.[a-zA-Z_-][a-zA-Z0-9_-]*\s*[>+~]/', $selector ) ) {
			return null;
		}

		// Skip Elementor internal classes.
		if ( $this->is_elementor_class( $class_name ) ) {
			return null;
		}

		// Skip class names that are too long.
		if ( strlen( $class_name ) > self::MAX_CLASS_NAME_LENGTH ) {
			return null;
		}

		return $class_name;
	}

	/**
	 * Check if a class name is an Elementor internal class.
	 *
	 * @param string $class_name Class name to check.
	 * @return bool True if it's an Elementor internal class.
	 */
	private function is_elementor_class( string $class_name ): bool {
		foreach ( self::ELEMENTOR_PREFIXES as $prefix ) {
			if ( 0 === strpos( $class_name, $prefix ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Parse CSS properties from a declaration block.
	 *
	 * @param string $block CSS declaration block content.
	 * @return array Associative array of property => value.
	 */
	private function parse_properties( string $block ): array {
		$properties = [];

		// Pattern: property: value;
		$pattern = '/([a-zA-Z-]+)\s*:\s*([^;]+);?/';

		if ( preg_match_all( $pattern, $block, $matches, PREG_SET_ORDER ) ) {
			foreach ( $matches as $match ) {
				$property = trim( $match[1] );
				$value    = trim( $match[2] );

				// Strip !important flag.
				$value = preg_replace( '/\s*!important\s*$/i', '', $value );

				if ( ! empty( $property ) && ! empty( $value ) ) {
					// Later declarations override earlier ones.
					$properties[ $property ] = $value;
				}
			}
		}

		return $properties;
	}

	/**
	 * Get extraction statistics.
	 *
	 * @param string $css Raw CSS.
	 * @return array Statistics about what was found and skipped.
	 */
	public function get_extraction_stats( string $css ): array {
		$stats = [
			'total_rules'     => 0,
			'class_selectors' => 0,
			'id_selectors'    => 0,
			'element_selectors' => 0,
			'compound_selectors' => 0,
			'pseudo_selectors' => 0,
			'elementor_classes' => 0,
			'extracted'       => 0,
		];

		// Remove comments.
		$css = preg_replace( '/\/\*.*?\*\//s', '', $css );

		// Remove @rules.
		$css = preg_replace( '/@[a-z-]+[^{]*\{([^{}]*\{[^}]*\})*[^}]*\}/s', '', $css );

		$pattern = '/([^{]+)\{([^}]+)\}/s';

		if ( preg_match_all( $pattern, $css, $matches, PREG_SET_ORDER ) ) {
			foreach ( $matches as $match ) {
				$selector = trim( $match[1] );
				++$stats['total_rules'];

				if ( strpos( $selector, '#' ) !== false ) {
					++$stats['id_selectors'];
				} elseif ( strpos( $selector, ':' ) !== false ) {
					++$stats['pseudo_selectors'];
				} elseif ( preg_match( '/^\.[a-zA-Z_-][a-zA-Z0-9_-]*\./', $selector ) ) {
					++$stats['compound_selectors'];
				} elseif ( preg_match( '/^\.([a-zA-Z_-][a-zA-Z0-9_-]*)$/', $selector, $m ) ) {
					++$stats['class_selectors'];
					if ( $this->is_elementor_class( $m[1] ) ) {
						++$stats['elementor_classes'];
					} else {
						++$stats['extracted'];
					}
				} elseif ( preg_match( '/^[a-zA-Z]/', $selector ) ) {
					++$stats['element_selectors'];
				}
			}
		}

		return $stats;
	}
}
