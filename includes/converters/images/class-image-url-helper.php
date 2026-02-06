<?php
/**
 * Image URL Helper
 *
 * Utility methods for detecting and handling image URLs.
 *
 * @package ElementorHtmlCssConverter
 */

namespace ElementorHtmlCssConverter\Converters\Images;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Image_Url_Helper
 *
 * Provides utility methods for working with image URLs.
 */
class Image_Url_Helper {

	/**
	 * Check if a URL is external (not from the current WordPress site).
	 *
	 * @param string $url The URL to check.
	 * @return bool True if external, false if local.
	 */
	public static function is_external_url( string $url ): bool {
		if ( empty( $url ) ) {
			return false;
		}

		$parsed_url = wp_parse_url( $url );

		if ( ! isset( $parsed_url['host'] ) ) {
			return false;
		}

		$site_url = wp_parse_url( get_site_url() );
		$home_url = wp_parse_url( get_home_url() );

		$site_host = $site_url['host'] ?? '';
		$home_host = $home_url['host'] ?? '';

		$url_host = $parsed_url['host'];

		return $url_host !== $site_host && $url_host !== $home_host;
	}

	/**
	 * Check if a URL is already a local WordPress attachment.
	 *
	 * @param string $url The URL to check.
	 * @return int|null Attachment ID if found, null otherwise.
	 */
	public static function get_local_attachment_id( string $url ): ?int {
		if ( empty( $url ) ) {
			return null;
		}

		if ( ! function_exists( 'attachment_url_to_postid' ) ) {
			return null;
		}

		$attachment_id = attachment_url_to_postid( $url );
		return $attachment_id > 0 ? $attachment_id : null;
	}

	/**
	 * Normalize image URL by removing query parameters and fragments.
	 *
	 * @param string $url The URL to normalize.
	 * @return string Normalized URL.
	 */
	public static function normalize_url( string $url ): string {
		$parsed = wp_parse_url( $url );

		if ( ! $parsed ) {
			return $url;
		}

		$normalized = '';

		if ( isset( $parsed['scheme'] ) ) {
			$normalized .= $parsed['scheme'] . '://';
		}

		if ( isset( $parsed['host'] ) ) {
			$normalized .= $parsed['host'];
		}

		if ( isset( $parsed['port'] ) ) {
			$normalized .= ':' . $parsed['port'];
		}

		if ( isset( $parsed['path'] ) ) {
			$normalized .= $parsed['path'];
		}

		return $normalized;
	}

}
