<?php
/**
 * ID Style Extractor
 *
 * Extracts CSS rules from <style> tags that use ID selectors only.
 *
 * @package ElementorHtmlCssConverter
 */

namespace ElementorHtmlCssConverter\Converters\Html;

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
	 * Parsed ID rules cache.
	 *
	 * @var array
	 */
	private array $id_rules = [];

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
	 * Parse CSS and return ID selector rules only.
	 *
	 * Only processes #id { ... } selectors, ignores all others
	 * (class selectors, tag selectors, attribute selectors, etc.).
	 *
	 * @param string $css Raw CSS content.
	 * @return array Map of element IDs to their CSS declarations.
	 *               Example: ['container' => ['display' => 'flex', 'gap' => '20px']]
	 */
	public function parse_id_rules( string $css ): array {
		$this->id_rules = [];

		if ( empty( trim( $css ) ) ) {
			return $this->id_rules;
		}

		$css = preg_replace( self::REGEX_CSS_COMMENT_REMOVAL, '', $css );

		if ( preg_match_all( self::REGEX_ID_SELECTOR_PATTERN, $css, $matches, PREG_SET_ORDER ) ) {
			foreach ( $matches as $match ) {
				$id           = $match[1];
				$declarations = $match[2];

				$parsed_declarations = $this->parse_declarations( $declarations );

				if ( ! empty( $parsed_declarations ) ) {
					if ( isset( $this->id_rules[ $id ] ) ) {
						$this->id_rules[ $id ] = array_merge(
							$this->id_rules[ $id ],
							$parsed_declarations
						);
					} else {
						$this->id_rules[ $id ] = $parsed_declarations;
					}
				}
			}
		}

		return $this->id_rules;
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
	 * Get styles for a specific element ID.
	 *
	 * @param string $id       The element ID (without the # prefix).
	 * @param array  $id_rules The parsed ID rules from parse_id_rules().
	 * @return array CSS property-value pairs for the element, or empty array if not found.
	 */
	public function get_styles_for_id( string $id, array $id_rules ): array {
		if ( empty( $id ) ) {
			return [];
		}

		return $id_rules[ $id ] ?? [];
	}

	/**
	 * Extract and parse all ID styles from a DOM document.
	 *
	 * Convenience method that combines extract_style_tags() and parse_id_rules().
	 *
	 * @param \DOMDocument $dom The DOM document.
	 * @return array Map of element IDs to their CSS declarations.
	 */
	public function extract_all_id_styles( \DOMDocument $dom ): array {
		$css = $this->extract_style_tags( $dom );
		return $this->parse_id_rules( $css );
	}
}

