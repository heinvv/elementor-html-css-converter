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

		if ( $this->is_class_name_too_long( $class_name ) ) {
			return null;
		}

		return $class_name;
	}

	/**
	 * Check if a class name exceeds the maximum allowed length.
	 *
	 * @param string $class_name Class name to check.
	 * @return bool True if the class name is too long.
	 */
	private function is_class_name_too_long( string $class_name ): bool {
		return strlen( $class_name ) > self::MAX_CLASS_NAME_LENGTH;
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
}

