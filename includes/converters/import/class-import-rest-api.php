<?php
/**
 * Import REST API
 *
 * REST API endpoints for triggering GitHub import workflow and receiving results.
 *
 * @package ElementorHtmlCssConverter
 */

namespace ElementorHtmlCssConverter\Converters\Import;

use ElementorHtmlCssConverter\Converters\Css\Breakpoint_Matcher;
use WP_REST_Request;
use WP_REST_Response;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Import_Rest_API {

	private const NAMESPACE = 'html-css-converter/v1';
	private const RESULTS_STORAGE_OPTION = 'ehcc_import_results';
	private const DEFAULT_SCRAPER_ENDPOINT = 'https://playwright-scraper-542363463421.europe-west1.run.app';
	private const SCRAPER_REQUEST_TIMEOUT = 600;

	public function __construct() {
		add_action( 'rest_api_init', [ $this, 'register_routes' ] );
	}

	public function register_routes() {
		register_rest_route(
			self::NAMESPACE,
			'/breakpoints',
			[
				'methods'             => 'GET',
				'callback'            => [ $this, 'get_breakpoints' ],
				'permission_callback' => '__return_true',
				'args'                => [],
			]
		);

		register_rest_route(
			self::NAMESPACE,
			'/trigger-import',
			[
				'methods'             => 'POST',
				'callback'            => [ $this, 'trigger_import' ],
				'permission_callback' => '__return_true',
				'args'                => [
					'url'                  => [
						'type'              => 'string',
						'required'          => true,
						'sanitize_callback' => 'esc_url_raw',
						'validate_callback' => [ $this, 'validate_url' ],
					],
					'selectors'            => [
						'type'              => 'string',
						'required'          => true,
						'sanitize_callback' => 'sanitize_text_field',
					],
					'timeout'              => [
						'type'              => 'string',
						'required'          => false,
						'default'           => '60',
						'sanitize_callback' => 'sanitize_text_field',
					],
					'post_id'              => [
						'type'              => 'integer',
						'required'          => false,
						'sanitize_callback' => 'absint',
						'default'           => 0,
					],
				],
			]
		);

		register_rest_route(
			self::NAMESPACE,
			'/import-results',
			[
				'methods'             => 'POST',
				'callback'            => [ $this, 'receive_results' ],
				'permission_callback' => '__return_true',
				'args'                => [],
			]
		);

		register_rest_route(
			self::NAMESPACE,
			'/import-results/(?P<job_id>[a-zA-Z0-9_-]+)',
			[
				'methods'             => 'GET',
				'callback'            => [ $this, 'get_results' ],
				'permission_callback' => '__return_true',
				'args'                => [
					'job_id' => [
						'type'              => 'string',
						'required'          => true,
						'sanitize_callback' => 'sanitize_text_field',
					],
				],
			]
		);

		register_rest_route(
			self::NAMESPACE,
			'/template/(?P<id>\d+)',
			[
				'methods'             => 'DELETE',
				'callback'            => [ $this, 'delete_template' ],
				'permission_callback' => '__return_true',
				'args'                => [
					'id' => [
						'type'              => 'integer',
						'required'          => true,
						'sanitize_callback' => 'absint',
					],
				],
			]
		);
	}

	public function validate_url( $param ): bool {
		return filter_var( $param, FILTER_VALIDATE_URL ) !== false;
	}

	public function get_breakpoints( WP_REST_Request $request ): WP_REST_Response {
		$breakpoints = $this->get_breakpoint_config_for_scraper();
		return new WP_REST_Response( [ 'breakpoints' => $breakpoints ], 200 );
	}

	private function get_breakpoint_config_for_scraper(): array {
		$matcher   = new Breakpoint_Matcher();
		$config    = $matcher->get_breakpoints_config();
		$breakpoints = [];

		foreach ( $config as $name => $bp_config ) {
			if ( ! isset( $bp_config['is_enabled'] ) || ! $bp_config['is_enabled'] ) {
				continue;
			}

			$width = $bp_config['value'] ?? 0;
			if ( ( $bp_config['direction'] ?? 'max' ) === 'max' && $width > 0 ) {
				$breakpoints[] = [
					'name'      => $name,
					'width'     => $width,
					'direction' => 'max',
				];
			}
		}

		usort( $breakpoints, fn( $a, $b ) => $b['width'] <=> $a['width'] );

		return $breakpoints;
	}

	public function trigger_import( WP_REST_Request $request ): WP_REST_Response {
		try {
			$url       = $request->get_param( 'url' );
			$selectors = $request->get_param( 'selectors' );
			$timeout   = $request->get_param( 'timeout' );
			$job_id    = 'wp-' . time() . '-' . wp_generate_password( 8, false );
			$site_url  = trailingslashit( home_url() );

			$payload = [
				'event_type'     => 'run-scrape',
				'client_payload' => [
					'url'        => $url,
					'selectors'  => $selectors,
					'timeout'    => $timeout,
					'job_id'     => $job_id,
					'breakpoints' => $this->get_breakpoint_config_for_scraper(),
				],
			];

			$scraper_response = wp_remote_post(
				self::DEFAULT_SCRAPER_ENDPOINT,
				[
					'headers'   => [
						'Content-Type' => 'application/json',
					],
					'body'      => wp_json_encode( $payload ),
					'timeout'   => self::SCRAPER_REQUEST_TIMEOUT,
					'sslverify' => apply_filters( 'ehcc_scraper_ssl_verify', true ),
				]
			);

			if ( is_wp_error( $scraper_response ) ) {
				return new WP_REST_Response(
					[
						'success' => false,
						'message' => 'Failed to trigger scraper: ' . $scraper_response->get_error_message(),
						'job_id'  => $job_id,
					],
					500
				);
			}

			$response_code = wp_remote_retrieve_response_code( $scraper_response );
			$response_body = wp_remote_retrieve_body( $scraper_response );
			$response_data = json_decode( $response_body, true );

			if ( $response_code !== 200 ) {
				$error_message = $response_data['error'] ?? $response_data['message'] ?? 'Unknown error from scraper endpoint';
				return new WP_REST_Response(
					[
						'success'          => false,
						'message'          => 'Scraper error: ' . $error_message,
						'job_id'           => $job_id,
						'scraper_endpoint' => rtrim( self::DEFAULT_SCRAPER_ENDPOINT, '/' ),
						'scraper_response' => $response_data,
					],
					200
				);
			}

			return new WP_REST_Response(
				[
					'success'          => true,
					'message'          => $response_data['message'] ?? 'Scraper triggered',
					'job_id'           => $job_id,
					'scraper_endpoint' => rtrim( self::DEFAULT_SCRAPER_ENDPOINT, '/' ),
				],
				200
			);
		} catch ( \Throwable $e ) {
			return new WP_REST_Response(
				[
					'success' => false,
					'message'  => 'Import error: ' . $e->getMessage(),
					'job_id'   => '',
				],
				500
			);
		}
	}

	/**
	 * @deprecated Use polling of Cloud Run GET /results/:jobId instead. Kept for backwards compatibility.
	 */
	public function receive_results( WP_REST_Request $request ): WP_REST_Response {
		$body = $request->get_body();
		$data = json_decode( $body, true );

		if ( json_last_error() !== JSON_ERROR_NONE ) {
			return new WP_REST_Response(
				[
					'success' => false,
					'message' => 'Invalid JSON payload',
				],
				400
			);
		}

		$job_id = $data['job_id'] ?? '';
		$provided_token = $data['request_token'] ?? '';

		if ( empty( $job_id ) || empty( $provided_token ) ) {
			return new WP_REST_Response(
				[
					'success' => false,
					'message' => 'Missing job_id or request_token in payload',
				],
				400
			);
		}

		$stored_token = get_transient( 'ehcc_import_token_' . $job_id );

		if ( false === $stored_token ) {
			return new WP_REST_Response(
				[
					'success' => false,
					'message' => 'Request token not found or expired',
				],
				401
			);
		}

		if ( ! hash_equals( $stored_token, $provided_token ) ) {
			return new WP_REST_Response(
				[
					'success' => false,
					'message' => 'Request token mismatch',
				],
				401
			);
		}

		delete_transient( 'ehcc_import_token_' . $job_id );

		$serialized_payload = wp_json_encode( $data );
		if ( preg_match( '/<\s*script/i', $serialized_payload ) ) {
			return new WP_REST_Response(
				[
					'success' => false,
					'message' => 'Payload rejected: contains script reference',
				],
				400
			);
		}

		$sanitized_data = [
			'status'  => sanitize_text_field( $data['status'] ?? '' ),
			'error'   => sanitize_text_field( $data['error'] ?? '' ),
			'results' => [
				'converter' => [
					'template_id' => absint( $data['results']['converter']['template_id'] ?? 0 ),
				],
			],
		];

		$results = get_option( self::RESULTS_STORAGE_OPTION, [] );
		if ( ! is_array( $results ) ) {
			$results = [];
		}

		$results[ $job_id ] = [
			'data'      => $sanitized_data,
			'received'  => current_time( 'mysql' ),
			'timestamp' => time(),
		];

		update_option( self::RESULTS_STORAGE_OPTION, $results );

		return new WP_REST_Response(
			[
				'success' => true,
				'message' => 'Results received',
				'job_id'  => $job_id,
			],
			200
		);
	}

	/**
	 * @deprecated Use polling of Cloud Run GET /results/:jobId instead. Kept for backwards compatibility.
	 */
	public function get_results( WP_REST_Request $request ): WP_REST_Response {
		$job_id = $request->get_param( 'job_id' );

		$results = get_option( self::RESULTS_STORAGE_OPTION, [] );
		if ( ! is_array( $results ) || ! isset( $results[ $job_id ] ) ) {
			return new WP_REST_Response(
				[
					'success' => false,
					'status'  => 'pending',
					'job_id'  => $job_id,
				],
				200
			);
		}

		$response_data = [
			'success'  => true,
			'status'   => 'complete',
			'job_id'   => $job_id,
			'data'     => $results[ $job_id ]['data'],
			'received' => $results[ $job_id ]['received'],
		];

		unset( $results[ $job_id ] );
		update_option( self::RESULTS_STORAGE_OPTION, $results );

		return new WP_REST_Response( $response_data, 200 );
	}

	public function delete_template( WP_REST_Request $request ): WP_REST_Response {
		$template_id = $request->get_param( 'id' );

		$post = get_post( $template_id );

		if ( ! $post ) {
			return new WP_REST_Response(
				[
					'success' => false,
					'message' => 'Template not found',
				],
				404
			);
		}

		if ( 'elementor_library' !== $post->post_type ) {
			return new WP_REST_Response(
				[
					'success' => false,
					'message' => 'Post is not an Elementor template',
				],
				400
			);
		}

		$deleted = wp_delete_post( $template_id, true );

		if ( false === $deleted ) {
			return new WP_REST_Response(
				[
					'success' => false,
					'message' => 'Failed to delete template',
				],
				500
			);
		}

		return new WP_REST_Response(
			[
				'success' => true,
				'message' => 'Template deleted',
			],
			200
		);
	}
}
