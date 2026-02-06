<?php
/**
 * ID Style Extractor
 *
 * Extracts CSS rules from <style> tags that use ID selectors only.
 *
 * @package ElementorHtmlCssConverter
 */

namespace ElementorHtmlCssConverter\Converters\Html;

use ElementorHtmlCssConverter\Converters\Css\Media_Query_Parser;
use ElementorHtmlCssConverter\Converters\Css\Breakpoint_Matcher;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Id_Style_Extractor
 *
 * Extracts CSS from <style> tags and parses only #id selector rules.
 * Ignores class selectors, tag selectors, and inline styles.
 */
class Id_Style_Extractor {

	private const REGEX_CSS_COMMENT_REMOVAL = '/\/\*.*?\*\//s';
	private const REGEX_ID_SELECTOR_PATTERN = '/#([a-zA-Z][a-zA-Z0-9_-]*)\s*\{([^}]*)\}/s';
	private const REGEX_IMPORTANT_FLAG_REMOVAL = '/\s*!important\s*$/i';

	/**
	 * Extract CSS content from all <style> tags in the DOM.
	 *
	 * @param \DOMDocument $dom The DOM document.
	 * @return string Combined CSS content from all style tags.
	 */
	public function extract_style_tags( \DOMDocument $dom ): string {
		$css_content = '';
		$style_tags  = $dom->getElementsByTagName( 'style' );

		foreach ( $style_tags as $style_tag ) {
			$css_content .= $style_tag->textContent . "\n";
		}

		return trim( $css_content );
	}

	/**
	 * Parse ID rules from CSS block (helper for breakpoint parsing).
	 *
	 * @param string $css_block CSS block content.
	 * @return array Map of element IDs to their CSS declarations.
	 */
	private function parse_id_rules_from_block( string $css_block ): array {
		$id_rules = [];

		if ( empty( trim( $css_block ) ) ) {
			return $id_rules;
		}

		$css_block = preg_replace( self::REGEX_CSS_COMMENT_REMOVAL, '', $css_block );

		if ( preg_match_all( self::REGEX_ID_SELECTOR_PATTERN, $css_block, $matches, PREG_SET_ORDER ) ) {
			foreach ( $matches as $match ) {
				$id           = $match[1];
				$declarations = $match[2];

				$parsed_declarations = $this->parse_declarations( $declarations );

				if ( ! empty( $parsed_declarations ) ) {
					if ( isset( $id_rules[ $id ] ) ) {
						$id_rules[ $id ] = array_merge(
							$id_rules[ $id ],
							$parsed_declarations
						);
					} else {
						$id_rules[ $id ] = $parsed_declarations;
					}
				}
			}
		}

		return $id_rules;
	}

	/**
	 * Parse CSS declarations string into property-value pairs.
	 *
	 * @param string $declarations CSS declarations (e.g., "color: red; font-size: 16px").
	 * @return array Property-value pairs.
	 */
	private function parse_declarations( string $declarations ): array {
		$styles = [];
		$parts  = explode( ';', $declarations );

		foreach ( $parts as $part ) {
			$part = trim( $part );
			if ( empty( $part ) ) {
				continue;
			}

			$colon_pos = strpos( $part, ':' );
			if ( false === $colon_pos ) {
				continue;
			}

			$property = trim( substr( $part, 0, $colon_pos ) );
			$value    = trim( substr( $part, $colon_pos + 1 ) );

			if ( ! empty( $property ) && '' !== $value ) {
				$value = preg_replace( self::REGEX_IMPORTANT_FLAG_REMOVAL, '', $value );
				$value = trim( $value );

				$styles[ $property ] = $value;
			}
		}

		return $styles;
	}

	/**
	 * Parse ID rules with breakpoint support.
	 *
	 * Extracts ID rules per breakpoint from CSS with media queries.
	 *
	 * @param string            $css    Raw CSS content.
	 * @param Breakpoint_Matcher $matcher Breakpoint matcher instance.
	 * @return array Map of element IDs to breakpoint-specific CSS declarations.
	 *               Format: ['id' => ['desktop' => [...], 'tablet' => [...], 'mobile' => [...]]]
	 */
	public function parse_id_rules( string $css, Breakpoint_Matcher $matcher ): array {
		$breakpoint_rules = [];

		if ( empty( trim( $css ) ) ) {
			return $breakpoint_rules;
		}

		$css = preg_replace( self::REGEX_CSS_COMMENT_REMOVAL, '', $css );

		$media_parser = new Media_Query_Parser();
		$media_queries = $media_parser->parse_media_queries( $css );
		$desktop_css = $media_parser->extract_desktop_css( $css );

		$desktop_rules = $this->parse_id_rules_from_block( $desktop_css );

		foreach ( $desktop_rules as $id => $styles ) {
			if ( ! isset( $breakpoint_rules[ $id ] ) ) {
				$breakpoint_rules[ $id ] = [];
			}
			$breakpoint_rules[ $id ]['desktop'] = $styles;
		}

		foreach ( $media_queries as $media_query ) {
			$width     = $media_query['width'];
			$direction = $media_query['direction'];
			$css_block = $media_query['css'];

			$elementor_breakpoint = $matcher->match_css_to_elementor_breakpoint( $width, $direction );

			if ( null === $elementor_breakpoint ) {
				continue;
			}

			$responsive_rules = $this->parse_id_rules_from_block( $css_block );

			foreach ( $responsive_rules as $id => $styles ) {
				if ( ! isset( $breakpoint_rules[ $id ] ) ) {
					$breakpoint_rules[ $id ] = [];
				}

				if ( ! isset( $breakpoint_rules[ $id ]['desktop'] ) ) {
					$breakpoint_rules[ $id ]['desktop'] = [];
				}

				if ( ! isset( $breakpoint_rules[ $id ][ $elementor_breakpoint ] ) ) {
					$breakpoint_rules[ $id ][ $elementor_breakpoint ] = [];
				}

				$breakpoint_rules[ $id ][ $elementor_breakpoint ] = array_merge(
					$breakpoint_rules[ $id ][ $elementor_breakpoint ],
					$styles
				);
			}
		}

		return $breakpoint_rules;
	}
}

