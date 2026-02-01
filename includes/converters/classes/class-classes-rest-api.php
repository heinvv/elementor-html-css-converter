<?php
/**
 * Classes REST API
 *
 * REST API endpoint for importing CSS classes into Elementor Global Classes.
 *
 * @package ElementorHtmlCssConverter
 */

namespace ElementorHtmlCssConverter\Converters\Classes;

use ElementorHtmlCssConverter\Converters\Core\Converter_Registry;
use WP_REST_Request;
use WP_REST_Response;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Classes_Rest_API
 *
 * Handles the /import-classes REST endpoint.
 */
class Classes_Rest_API {

	/**
	 * The converter registry.
	 *
	 * @var Converter_Registry
	 */
	private Converter_Registry $registry;

	/**
	 * Constructor.
	 *
	 * @param Converter_Registry $registry The converter registry.
	 */
	public function __construct( Converter_Registry $registry ) {
		$this->registry = $registry;
		add_action( 'rest_api_init', [ $this, 'register_route' ] );
	}

	/**
	 * Register the REST API route.
	 *
	 * @return void
	 */
	public function register_route(): void {
		register_rest_route(
			'html-css-converter/v1',
			'/import-classes',
			[
				'methods'             => 'POST',
				'callback'            => [ $this, 'import_classes' ],
				'permission_callback' => [ $this, 'check_permissions' ],
				'args'                => [
					'css'         => [
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_textarea_field',
					],
					'url'         => [
						'type'              => 'string',
						'sanitize_callback' => 'esc_url_raw',
					],
					'update_mode' => [
						'type'              => 'string',
						'default'           => 'create_new',
						'enum'              => [ 'create_new', 'update' ],
						'sanitize_callback' => 'sanitize_text_field',
					],
					'context'     => [
						'type'              => 'string',
						'default'           => 'frontend',
						'enum'              => [ 'frontend', 'preview' ],
						'sanitize_callback' => 'sanitize_text_field',
					],
				],
			]
		);
	}

	/**
	 * Check permissions for the endpoint.
	 *
	 * @return bool True if user has permission.
	 */
	public function check_permissions(): bool {
		// TESTING ONLY - Allow public access without authentication.
		// Remove this line and uncomment the next for production:
		return true;
		// return current_user_can( 'edit_posts' );
	}

	/**
	 * Handle the import classes request.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response Response object.
	 */
	public function import_classes( WP_REST_Request $request ): WP_REST_Response {
		$url         = $request->get_param( 'url' );
		$css         = $request->get_param( 'css' );
		$update_mode = $request->get_param( 'update_mode' ) ?? 'create_new';
		$context     = $request->get_param( 'context' ) ?? 'frontend';

		// Validate that either css or url is provided.
		if ( empty( $css ) && empty( $url ) ) {
			return new WP_REST_Response(
				[
					'error' => 'Missing css or url',
					'code'  => 'invalid_request',
				],
				400
			);
		}

		// Fetch from URL if provided.
		if ( ! empty( $url ) ) {
			$fetch_result = $this->fetch_css_from_url( $url );

			if ( $fetch_result instanceof WP_REST_Response ) {
				return $fetch_result;
			}

			$css = $fetch_result;
		}

		// Validate CSS is not empty.
		if ( empty( $css ) ) {
			return new WP_REST_Response(
				[
					'error' => 'Empty CSS',
					'code'  => 'empty_css',
				],
				422
			);
		}

		// Remove UTF-8 BOM if present.
		$css = $this->remove_utf8_bom( $css );

		// Step 1: Extract classes from CSS.
		$extractor         = new Class_Extractor();
		$extracted_classes = $extractor->extract_from_css( $css );

		if ( empty( $extracted_classes ) ) {
			return new WP_REST_Response(
				[
					'error' => 'No classes found in CSS',
					'code'  => 'no_classes',
				],
				422
			);
		}

		// Step 2: Convert to atomic format.
		$conversion_service = new Class_Conversion_Service( $this->registry );
		$converted_classes  = $conversion_service->convert_to_atomic( $extracted_classes );

		if ( empty( $converted_classes ) ) {
			return new WP_REST_Response(
				[
					'error' => 'No classes could be converted',
					'code'  => 'conversion_failed',
				],
				422
			);
		}

		// Step 3: Register with Elementor Global Classes.
		$registration_service = new Class_Registration_Service();

		try {
			$result = $registration_service->register_with_elementor(
				$converted_classes,
				$update_mode,
				$context
			);
		} catch ( \Exception $e ) {
			return new WP_REST_Response(
				[
					'error'   => 'Failed to register classes',
					'code'    => 'registration_error',
					'details' => $e->getMessage(),
				],
				500
			);
		}

		if ( ! $result['success'] ) {
			return new WP_REST_Response(
				[
					'error'   => $result['error'] ?? 'Registration failed',
					'code'    => 'registration_error',
					'classes' => $result['classes'] ?? [],
				],
				500
			);
		}

		// Build response.
		return new WP_REST_Response(
			[
				'success'    => true,
				'classes'    => $result['classes'],
				'statistics' => [
					'detected'   => count( $extracted_classes ),
					'converted'  => count( $converted_classes ),
					'registered' => $result['registered'],
					'skipped'    => $result['skipped'],
					'updated'    => $result['updated'],
				],
				'overflow'   => $result['overflow'],
			],
			200
		);
	}

	/**
	 * Fetch CSS from URL.
	 *
	 * @param string $url URL to fetch from.
	 * @return string|WP_REST_Response CSS content or error response.
	 */
	private function fetch_css_from_url( string $url ) {
		$response = wp_remote_get(
			$url,
			[
				'timeout' => 30,
			]
		);

		if ( is_wp_error( $response ) ) {
			return new WP_REST_Response(
				[
					'error'   => 'Fetch failed',
					'code'    => 'fetch_error',
					'details' => $response->get_error_message(),
				],
				502
			);
		}

		$code = wp_remote_retrieve_response_code( $response );
		if ( 200 !== (int) $code ) {
			return new WP_REST_Response(
				[
					'error'   => 'Fetch failed',
					'code'    => 'http_error',
					'details' => 'HTTP ' . $code,
				],
				502
			);
		}

		return wp_remote_retrieve_body( $response );
	}

	/**
	 * Remove UTF-8 BOM from string.
	 *
	 * @param string $css CSS string.
	 * @return string CSS without BOM.
	 */
	private function remove_utf8_bom( string $css ): string {
		if ( 0 === strpos( $css, "\xEF\xBB\xBF" ) ) {
			return substr( $css, 3 );
		}

		return $css;
	}
}
