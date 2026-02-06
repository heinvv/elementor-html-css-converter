<?php
/**
 * SVG Security Bypass Handler
 *
 * Handles security permission checks for SVG imports in unauthenticated REST API contexts.
 * This allows the HTML/CSS converter to work without requiring user authentication
 * while still respecting Elementor's security settings.
 *
 * @package ElementorHtmlCssConverter
 */

namespace ElementorHtmlCssConverter\Converters\Images;

use Elementor\Core\Files\Uploads_Manager;
use Elementor\Core\Files\File_Types\Svg;
use Elementor\User;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Svg_Security_Bypass_Handler
 *
 * Handles security bypasses for SVG imports in specific contexts (e.g., unauthenticated REST API).
 */
class Svg_Security_Bypass_Handler {

	/**
	 * Check if current request is a REST API request.
	 *
	 * @return bool True if REST API request.
	 */
	public function is_rest_api_request(): bool {
		return ( defined( 'REST_REQUEST' ) && REST_REQUEST )
			|| ( defined( 'WP_CLI' ) && WP_CLI )
			|| ( isset( $_SERVER['REQUEST_URI'] ) && strpos( $_SERVER['REQUEST_URI'], '/wp-json/' ) !== false );
	}

	/**
	 * Check if current request is unauthenticated.
	 *
	 * @return bool True if unauthenticated.
	 */
	public function is_unauthenticated(): bool {
		return 0 === get_current_user_id();
	}

	/**
	 * Check if unfiltered uploads are enabled, with bypass for unauthenticated REST API.
	 *
	 * For unauthenticated REST API requests, checks the Elementor option directly
	 * instead of requiring user context.
	 *
	 * @return bool True if enabled (with bypass applied if applicable).
	 */
	public function are_unfiltered_uploads_enabled(): bool {
		$enabled = Uploads_Manager::are_unfiltered_uploads_enabled();

		if ( ! $enabled && $this->is_rest_api_request() && $this->is_unauthenticated() ) {
			$option_value = get_option( 'elementor_unfiltered_files_upload', '' );
			$sanitizer_can_run = Svg::file_sanitizer_can_run();

			if ( ( '1' === $option_value || true === $option_value ) && $sanitizer_can_run ) {
				return true;
			}
		}

		return $enabled;
	}

	/**
	 * Check if user can upload JSON, with bypass for unauthenticated REST API.
	 *
	 * For unauthenticated REST API requests with unfiltered uploads enabled,
	 * allows the operation to proceed.
	 *
	 * @return bool True if allowed (with bypass applied if applicable).
	 */
	public function can_upload_json(): bool {
		$can_upload = User::is_current_user_can_upload_json();

		if ( ! $can_upload && $this->is_rest_api_request() && $this->is_unauthenticated() ) {
			$unfiltered_enabled = $this->are_unfiltered_uploads_enabled();
			if ( $unfiltered_enabled ) {
				return true;
			}
		}

		return $can_upload;
	}

	/**
	 * Check if SVG mime type should be registered, with bypass for unauthenticated REST API.
	 *
	 * For unauthenticated REST API requests, allows SVG mime type registration
	 * even without user permissions.
	 *
	 * @return bool True if should register.
	 */
	public function should_register_svg_mime_type(): bool {
		$can_upload_json = User::is_current_user_can_upload_json();

		if ( $can_upload_json ) {
			return true;
		}

		if ( $this->is_rest_api_request() && $this->is_unauthenticated() ) {
			return true;
		}

		return false;
	}

	/**
	 * Check if SVG mime type warning should be suppressed.
	 *
	 * Suppresses warnings when filter is registered for unauthenticated REST API requests,
	 * as the filter will be applied during actual upload.
	 *
	 * @param bool $has_filter Whether the SVG mime type filter is registered.
	 * @return bool True if warning should be suppressed.
	 */
	public function should_suppress_mime_type_warning( bool $has_filter ): bool {
		return $has_filter && $this->is_rest_api_request() && $this->is_unauthenticated();
	}
}
