<?php
/**
 * Class Extractor
 *
 * Extracts CSS class definitions from raw CSS.
 *
 * @package ElementorHtmlCssConverter
 */

namespace ElementorHtmlCssConverter\Converters\Classes;

use ElementorHtmlCssConverter\Converters\Css\Media_Query_Parser;
use ElementorHtmlCssConverter\Converters\Css\Breakpoint_Matcher;

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
	private const REGEX_CSS_COMMENT_REMOVAL = '/\/\*.*?\*\//s';
	private const REGEX_MEDIA_QUERY_REMOVAL = '/@media[^{]+\{([^{}]*\{[^}]*\})*[^}]*\}/s';
	private const REGEX_AT_RULE_REMOVAL = '/@[a-z-]+[^{]*\{([^{}]*\{[^}]*\})*[^}]*\}/s';
	private const REGEX_CSS_RULE_PATTERN = '/([^{]+)\{([^}]+)\}/s';
	private const REGEX_CLASS_SELECTOR_START = '/^\.([a-zA-Z_-][a-zA-Z0-9_-]*)/';
	private const REGEX_COMPOUND_CLASS_SELECTOR = '/^\.[a-zA-Z_-][a-zA-Z0-9_-]*\./';
	private const REGEX_DESCENDANT_SELECTOR = '/^\.[a-zA-Z_-][a-zA-Z0-9_-]*\s+/';
	private const REGEX_SIBLING_SELECTOR = '/^\.[a-zA-Z_-][a-zA-Z0-9_-]*\s*[>+~]/';
	private const REGEX_SINGLE_CLASS_SELECTOR = '/^\.([a-zA-Z_-][a-zA-Z0-9_-]*)$/';
	private const REGEX_ELEMENT_SELECTOR_START = '/^[a-zA-Z]/';
	private const REGEX_CSS_PROPERTY_PATTERN = '/([a-zA-Z-]+)\s*:\s*([^;]+);?/';
	private const REGEX_IMPORTANT_FLAG_REMOVAL = '/\s*!important\s*$/i';

	/**
	 * Extract class definitions from CSS with breakpoint support.
	 *
	 * @param string            $css     Raw CSS containing class definitions.
	 * @param Breakpoint_Matcher $matcher Breakpoint matcher instance.
	 * @return array Array of class definitions per breakpoint.
	 *               Format: ['class-name' => ['desktop' => [...], 'tablet' => [...], 'mobile' => [...]]]
	 */
	public function extract_from_css( string $css, Breakpoint_Matcher $matcher ): array {
		$breakpoint_classes = [];

		if ( empty( trim( $css ) ) ) {
			return $breakpoint_classes;
		}

		$css = preg_replace( self::REGEX_CSS_COMMENT_REMOVAL, '', $css );

		$media_parser = new Media_Query_Parser();
		$media_queries = $media_parser->parse_media_queries( $css );
		$desktop_css = $media_parser->extract_desktop_css( $css );

		$desktop_css = preg_replace( self::REGEX_AT_RULE_REMOVAL, '', $desktop_css );

		$desktop_classes = $this->extract_classes_from_css_block( $desktop_css );

		foreach ( $desktop_classes as $class_name => $class_data ) {
			if ( ! isset( $breakpoint_classes[ $class_name ] ) ) {
				$breakpoint_classes[ $class_name ] = [];
			}
			$breakpoint_classes[ $class_name ]['desktop'] = $class_data;
		}

		foreach ( $media_queries as $media_query ) {
			$width     = $media_query['width'];
			$direction = $media_query['direction'];
			$css_block = $media_query['css'];

			$elementor_breakpoint = $matcher->match_css_to_elementor_breakpoint( $width, $direction );

			if ( null === $elementor_breakpoint ) {
				continue;
			}

			$responsive_classes = $this->extract_classes_from_css_block( $css_block );

			foreach ( $responsive_classes as $class_name => $class_data ) {
				if ( ! isset( $breakpoint_classes[ $class_name ] ) ) {
					$breakpoint_classes[ $class_name ] = [];
				}

				if ( ! isset( $breakpoint_classes[ $class_name ]['desktop'] ) ) {
					$breakpoint_classes[ $class_name ]['desktop'] = [
						'selector'   => '.' . $class_name,
						'properties' => [],
					];
				}

				if ( ! isset( $breakpoint_classes[ $class_name ][ $elementor_breakpoint ] ) ) {
					$breakpoint_classes[ $class_name ][ $elementor_breakpoint ] = [
						'selector'   => '.' . $class_name,
						'properties' => [],
					];
				}

				$breakpoint_classes[ $class_name ][ $elementor_breakpoint ]['properties'] = array_merge(
					$breakpoint_classes[ $class_name ][ $elementor_breakpoint ]['properties'],
					$class_data['properties']
				);
			}
		}

		return $breakpoint_classes;
	}

	/**
	 * Extract classes from a CSS block (without media queries).
	 *
	 * @param string $css_block CSS block content.
	 * @return array Extracted classes.
	 */
	private function extract_classes_from_css_block( string $css_block ): array {
		$classes = [];

		if ( preg_match_all( self::REGEX_CSS_RULE_PATTERN, $css_block, $matches, PREG_SET_ORDER ) ) {
			foreach ( $matches as $match ) {
				$selector   = trim( $match[1] );
				$properties = trim( $match[2] );

				$class_name = $this->extract_class_name( $selector );

				if ( null === $class_name ) {
					continue;
				}

				$parsed_properties = $this->parse_properties( $properties );

				if ( empty( $parsed_properties ) ) {
					continue;
				}

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

		if ( empty( $selector ) ) {
			return null;
		}

		if ( strpos( $selector, ':' ) !== false ) {
			return null;
		}

		if ( strpos( $selector, '#' ) !== false ) {
			return null;
		}

		if ( strpos( $selector, '[' ) !== false ) {
			return null;
		}

		if ( ! preg_match( self::REGEX_CLASS_SELECTOR_START, $selector, $match ) ) {
			return null;
		}

		$class_name = $match[1];

		if ( preg_match( self::REGEX_COMPOUND_CLASS_SELECTOR, $selector ) ) {
			return null;
		}

		if ( preg_match( self::REGEX_DESCENDANT_SELECTOR, $selector ) ) {
			return null;
		}

		if ( preg_match( self::REGEX_SIBLING_SELECTOR, $selector ) ) {
			return null;
		}

		if ( $this->is_elementor_class( $class_name ) ) {
			return null;
		}

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

		if ( preg_match_all( self::REGEX_CSS_PROPERTY_PATTERN, $block, $matches, PREG_SET_ORDER ) ) {
			foreach ( $matches as $match ) {
				$property = trim( $match[1] );
				$value    = trim( $match[2] );

				$value = preg_replace( self::REGEX_IMPORTANT_FLAG_REMOVAL, '', $value );

				if ( ! empty( $property ) && ! empty( $value ) ) {
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

		$css = preg_replace( self::REGEX_CSS_COMMENT_REMOVAL, '', $css );

		$css = preg_replace( self::REGEX_AT_RULE_REMOVAL, '', $css );

		if ( preg_match_all( self::REGEX_CSS_RULE_PATTERN, $css, $matches, PREG_SET_ORDER ) ) {
			foreach ( $matches as $match ) {
				$selector = trim( $match[1] );
				++$stats['total_rules'];

				if ( strpos( $selector, '#' ) !== false ) {
					++$stats['id_selectors'];
				} elseif ( strpos( $selector, ':' ) !== false ) {
					++$stats['pseudo_selectors'];
				} elseif ( preg_match( self::REGEX_COMPOUND_CLASS_SELECTOR, $selector ) ) {
					++$stats['compound_selectors'];
				} elseif ( preg_match( self::REGEX_SINGLE_CLASS_SELECTOR, $selector, $m ) ) {
					++$stats['class_selectors'];
					if ( $this->is_elementor_class( $m[1] ) ) {
						++$stats['elementor_classes'];
					} else {
						++$stats['extracted'];
					}
				} elseif ( preg_match( self::REGEX_ELEMENT_SELECTOR_START, $selector ) ) {
					++$stats['element_selectors'];
				}
			}
		}

		return $stats;
	}
}

