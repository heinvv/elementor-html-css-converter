<?php
/**
 * Media Query Parser
 *
 * Parses CSS @media blocks and extracts breakpoint information.
 *
 * @package ElementorHtmlCssConverter
 */

namespace ElementorHtmlCssConverter\Converters\Css;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Media_Query_Parser
 *
 * Parses @media queries from CSS and extracts breakpoint conditions.
 */
class Media_Query_Parser {

	private const REGEX_MEDIA_QUERY = '/@media\s+(?:[^{]*?\()?\s*(?:max-width|min-width)\s*:\s*(\d+)px\s*\)?\s*\{([^{}]*(?:\{[^{}]*\}[^{}]*)*)\}/s';

	/**
	 * Parse media queries from CSS.
	 *
	 * @param string $css CSS content.
	 * @return array Array of parsed media queries with breakpoint info and CSS content.
	 *               Format: [['width' => 768, 'direction' => 'max', 'css' => '...'], ...]
	 */
	public function parse_media_queries( string $css ): array {
		$media_queries = [];

		if ( empty( trim( $css ) ) ) {
			return $media_queries;
		}

		if ( preg_match_all( self::REGEX_MEDIA_QUERY, $css, $matches, PREG_SET_ORDER ) ) {
			foreach ( $matches as $match ) {
				$full_match = $match[0];
				$width      = (int) $match[1];
				$css_content = $match[2];

				$direction = $this->extract_direction( $full_match );

				$media_queries[] = [
					'width'     => $width,
					'direction' => $direction,
					'css'       => trim( $css_content ),
				];
			}
		}

		return $media_queries;
	}

	/**
	 * Extract desktop CSS (CSS without @media blocks).
	 *
	 * @param string $css CSS content.
	 * @return string CSS without media query blocks.
	 */
	public function extract_desktop_css( string $css ): string {
		$desktop_css = preg_replace( self::REGEX_MEDIA_QUERY, '', $css );
		return trim( $desktop_css );
	}

	/**
	 * Extract direction (max or min) from media query string.
	 *
	 * @param string $media_query Media query string.
	 * @return string 'max' or 'min'.
	 */
	private function extract_direction( string $media_query ): string {
		if ( strpos( $media_query, 'min-width' ) !== false ) {
			return 'min';
		}

		return 'max';
	}
}
