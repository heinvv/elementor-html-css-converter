<?php
/**
 * Classes REST API
 *
 * REST API endpoint for importing CSS classes into Elementor Global Classes.
 *
 * @package ElementorHtmlCssConverter
 */

namespace ElementorHtmlCssConverter\Converters\Classes;

use ElementorHtmlCssConverter\Converters\Classes\Converter_Registry;
use ElementorHtmlCssConverter\Converters\Classes\Atomic_To_Css_Converter;
use Elementor\Modules\GlobalClasses\Global_Classes_Repository;
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
					'resolutions' => [
						'type'    => 'object',
						'default' => [],
					],
					'rename_map'  => [
						'type'    => 'object',
						'default' => [],
					],
				],
			]
		);

		register_rest_route(
			'html-css-converter/v1',
			'/export-classes',
			[
				'methods'             => 'GET',
				'callback'            => [ $this, 'export_classes' ],
				'permission_callback' => [ $this, 'check_permissions' ],
				'args'                => [
					'context' => [
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
		$resolutions = $request->get_param( 'resolutions' ) ?? [];
		$rename_map  = $request->get_param( 'rename_map' ) ?? [];

		$validation_error = $this->validate_css_or_url_provided( $css, $url );
		if ( $validation_error ) {
			return $validation_error;
		}

		$css_result = $this->fetch_css_from_url_if_provided( $url, $css );
		if ( $css_result instanceof WP_REST_Response ) {
			return $css_result;
		}
		$css = $css_result;

		$empty_css_error = $this->validate_css_not_empty( $css );
		if ( $empty_css_error ) {
			return $empty_css_error;
		}

		$css = $this->remove_utf8_bom( $css );

		return $this->process_and_register_classes( $css, $update_mode, $context, $resolutions, $rename_map );
	}

	/**
	 * Handle the export classes request.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response Response object.
	 */
	public function export_classes( WP_REST_Request $request ): WP_REST_Response {
		$context = $request->get_param( 'context' ) ?? 'frontend';

		if ( ! class_exists( '\Elementor\Plugin' ) || ! class_exists( '\Elementor\Modules\GlobalClasses\Global_Classes_Repository' ) ) {
			return new WP_REST_Response(
				[
					'error' => 'Global Classes not available',
					'code'  => 'not_available',
				],
				500
			);
		}

		try {
			$repository = Global_Classes_Repository::make()->context( $context );
			$all_classes = $repository->all();
			$items = $all_classes->get_items()->all();
			$order = $all_classes->get_order()->all();
		} catch ( \Exception $e ) {
			return new WP_REST_Response(
				[
					'error'   => 'Failed to read classes',
					'code'    => 'read_error',
					'details' => $e->getMessage(),
				],
				500
			);
		}

		if ( empty( $items ) ) {
			return new WP_REST_Response(
				[
					'success'     => true,
					'css'         => '',
					'total_classes' => 0,
				],
				200
			);
		}

		$ordered_items = [];
		foreach ( $order as $id ) {
			if ( isset( $items[ $id ] ) ) {
				$ordered_items[] = $items[ $id ];
			}
		}

		foreach ( $items as $id => $item ) {
			if ( ! in_array( $id, $order, true ) ) {
				$ordered_items[] = $item;
			}
		}

		$converter = new Atomic_To_Css_Converter();
		$css = $converter->convert_classes_to_css( $ordered_items );

		return new WP_REST_Response(
			[
				'success'       => true,
				'css'           => $css,
				'total_classes' => count( $items ),
			],
			200
		);
	}

	/**
	 * Validate that either css or url is provided.
	 *
	 * @param string|null $css CSS string.
	 * @param string|null $url URL string.
	 * @return WP_REST_Response|null Error response if validation fails, null otherwise.
	 */
	private function validate_css_or_url_provided( ?string $css, ?string $url ): ?WP_REST_Response {
		if ( empty( $css ) && empty( $url ) ) {
			return new WP_REST_Response(
				[
					'error' => 'Missing css or url',
					'code'  => 'invalid_request',
				],
				400
			);
		}

		return null;
	}

	/**
	 * Fetch CSS from URL if provided.
	 *
	 * @param string|null $url URL to fetch from.
	 * @param string|null $css Existing CSS string.
	 * @return string|WP_REST_Response CSS content or error response.
	 */
	private function fetch_css_from_url_if_provided( ?string $url, ?string $css ) {
		if ( empty( $url ) ) {
			return $css;
		}

		$fetch_result = $this->fetch_css_from_url( $url );

		if ( $fetch_result instanceof WP_REST_Response ) {
			return $fetch_result;
		}

		return $fetch_result;
	}

	/**
	 * Validate CSS is not empty.
	 *
	 * @param string|null $css CSS string.
	 * @return WP_REST_Response|null Error response if CSS is empty, null otherwise.
	 */
	private function validate_css_not_empty( ?string $css ): ?WP_REST_Response {
		if ( empty( $css ) ) {
			return new WP_REST_Response(
				[
					'error' => 'Empty CSS',
					'code'  => 'empty_css',
				],
				422
			);
		}

		return null;
	}

	/**
	 * Process CSS and register classes with Elementor.
	 *
	 * @param string $css         CSS content.
	 * @param string $update_mode Update mode: 'create_new' or 'update'.
	 * @param string $context     Context: 'frontend' or 'preview'.
	 * @return WP_REST_Response Response with results.
	 */
	private function process_and_register_classes( string $css, string $update_mode, string $context, array $resolutions = [], array $rename_map = [] ): WP_REST_Response {
		$extractor         = new Class_Extractor();
		$matcher           = new \ElementorHtmlCssConverter\Converters\Css\Breakpoint_Matcher();
		$extracted_classes = $extractor->extract_from_css( $css, $matcher );

		if ( empty( $extracted_classes ) ) {
			return new WP_REST_Response(
				[
					'error' => 'No classes found in CSS',
					'code'  => 'no_classes',
				],
				422
			);
		}

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

		$registration_service = new Class_Registration_Service();

		try {
			$result = $registration_service->register_with_elementor(
				$converted_classes,
				$update_mode,
				$context,
				$resolutions,
				$rename_map
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
