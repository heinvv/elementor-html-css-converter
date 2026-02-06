<?php
/**
 * Breakpoint Matcher
 *
 * Maps CSS media query breakpoints to Elementor breakpoint names.
 *
 * @package ElementorHtmlCssConverter
 */

namespace ElementorHtmlCssConverter\Converters\Css;

use Elementor\Plugin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Breakpoint_Matcher
 *
 * Matches CSS breakpoint widths to Elementor breakpoint system.
 */
class Breakpoint_Matcher {

	private const TOLERANCE_THRESHOLD = 200;

	/**
	 * Match CSS breakpoint width to Elementor breakpoint name.
	 *
	 * @param int    $width     CSS breakpoint width in pixels.
	 * @param string $direction 'max' or 'min'.
	 * @return string|null Elementor breakpoint name or null if no match.
	 */
	public function match_css_to_elementor_breakpoint( int $width, string $direction = 'max' ): ?string {
		$breakpoints_config = $this->get_breakpoints_config();

		if ( empty( $breakpoints_config ) ) {
			return null;
		}

		$matching_breakpoints = $this->filter_by_direction( $breakpoints_config, $direction );

		if ( empty( $matching_breakpoints ) ) {
			return null;
		}

		$exact_match = $this->find_exact_match( $matching_breakpoints, $width );

		if ( $exact_match ) {
			return $exact_match;
		}

		$closest_match = $this->find_closest_match( $matching_breakpoints, $width );

		if ( $closest_match ) {
			return $closest_match;
		}

		return null;
	}

	/**
	 * Get Elementor breakpoints configuration.
	 *
	 * @return array Breakpoints config array.
	 */
	public function get_breakpoints_config(): array {
		if ( ! class_exists( '\Elementor\Plugin' ) ) {
			return [];
		}

		if ( ! isset( Plugin::$instance ) || ! Plugin::$instance ) {
			return [];
		}

		if ( ! isset( Plugin::$instance->breakpoints ) ) {
			return [];
		}

		try {
			return Plugin::$instance->breakpoints->get_breakpoints_config();
		} catch ( \Exception $e ) {
			return [];
		}
	}

	/**
	 * Filter breakpoints by direction and enabled status.
	 *
	 * @param array  $breakpoints_config Breakpoints config.
	 * @param string $direction          'max' or 'min'.
	 * @return array Filtered breakpoints.
	 */
	private function filter_by_direction( array $breakpoints_config, string $direction ): array {
		$filtered = [];

		foreach ( $breakpoints_config as $name => $config ) {
			$config_direction = $config['direction'] ?? 'max';
			$is_enabled       = $config['is_enabled'] ?? false;

			if ( $config_direction === $direction && $is_enabled ) {
				$filtered[ $name ] = $config;
			}
		}

		return $filtered;
	}

	/**
	 * Find exact match for width.
	 *
	 * @param array $breakpoints Filtered breakpoints.
	 * @param int   $width       CSS width.
	 * @return string|null Breakpoint name or null.
	 */
	private function find_exact_match( array $breakpoints, int $width ): ?string {
		foreach ( $breakpoints as $name => $config ) {
			$config_width = $config['value'] ?? 0;

			if ( $config_width === $width ) {
				return $name;
			}
		}

		return null;
	}

	/**
	 * Find closest match for width.
	 *
	 * @param array $breakpoints Filtered breakpoints.
	 * @param int   $width       CSS width.
	 * @return string|null Breakpoint name or null if difference too large.
	 */
	private function find_closest_match( array $breakpoints, int $width ): ?string {
		$closest_name  = null;
		$closest_diff  = null;

		foreach ( $breakpoints as $name => $config ) {
			$config_width = $config['value'] ?? 0;
			$diff         = abs( $config_width - $width );

			if ( $diff > self::TOLERANCE_THRESHOLD ) {
				continue;
			}

			if ( null === $closest_diff || $diff < $closest_diff ) {
				$closest_diff = $diff;
				$closest_name = $name;
			}
		}

		return $closest_name;
	}
}
