<?php
/**
 * Import REST API
 *
 * REST API endpoints for triggering GitHub import workflow and receiving results.
 *
 * @package ElementorHtmlCssConverter
 */

namespace ElementorHtmlCssConverter\Converters\Import;

use WP_REST_Request;
use WP_REST_Response;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Import_Rest_API {

	private const NAMESPACE = 'html-css-converter/v1';
	private const RESULTS_STORAGE_OPTION = 'ehcc_import_results';
	private const REQUEST_TOKEN_EXPIRY = 3600;
	private const GITHUB_REPO = 'heinvv/elementor-playwright-scraper';
	private const VERCEL_ENDPOINT = 'https://elementor-scraper-connect.vercel.app/api/trigger';

	public function __construct() {
		add_action( 'rest_api_init', [ $this, 'register_routes' ] );
	}

	public function register_routes() {
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
					'elementor_base_url'   => [
						'type'              => 'string',
						'required'          => false,
						'default'           => '',
						'sanitize_callback' => 'esc_url_raw',
					],
					'wordpress_website_url' => [
						'type'              => 'string',
						'required'          => false,
						'sanitize_callback' => 'esc_url_raw',
						'validate_callback' => [ $this, 'validate_url' ],
						'default'           => '',
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
	}

	public function validate_url( $param ): bool {
		return filter_var( $param, FILTER_VALIDATE_URL ) !== false;
	}

	public function trigger_import( WP_REST_Request $request ): WP_REST_Response {
		$github_repo = self::GITHUB_REPO;

		$url                  = $request->get_param( 'url' );
		$selectors            = $request->get_param( 'selectors' );
		$timeout              = $request->get_param( 'timeout' );
		$elementor_base_url   = $request->get_param( 'elementor_base_url' );
		$wordpress_website_url = $request->get_param( 'wordpress_website_url' );
		$post_id              = $request->get_param( 'post_id' );
		$job_id               = 'wp-' . time() . '-' . wp_generate_password( 8, false );
		$request_token        = wp_generate_password( 32, false );

		if ( empty( $wordpress_website_url ) ) {
			$wordpress_website_url = home_url();
		}

		if ( defined( 'EHCC_WEBHOOK_BASE_URL' ) && ! empty( constant( 'EHCC_WEBHOOK_BASE_URL' ) ) ) {
			$wordpress_website_url = constant( 'EHCC_WEBHOOK_BASE_URL' );
		}

		if ( empty( $elementor_base_url ) ) {
			$elementor_base_url = home_url();
		}

		$wordpress_website_url = trailingslashit( $wordpress_website_url );
		$webhook_url = $wordpress_website_url . 'wp-json/' . self::NAMESPACE . '/import-results';

		set_transient(
			'ehcc_import_token_' . $job_id,
			$request_token,
			self::REQUEST_TOKEN_EXPIRY
		);

		$payload = [
			'event_type'    => 'run-scrape',
			'client_payload' => [
				'url'                => $url,
				'selectors'          => $selectors,
				'timeout'            => $timeout,
				'elementor_base_url' => $elementor_base_url,
				'webhook_url'        => $webhook_url,
				'job_id'             => $job_id,
				'request_token'      => $request_token,
				'post_id'            => $post_id,
			],
		];

		$vercel_response = wp_remote_post(
			self::VERCEL_ENDPOINT,
			[
				'headers' => [
					'Content-Type' => 'application/json',
				],
				'body'    => wp_json_encode( $payload ),
				'timeout' => 30,
			]
		);

		if ( is_wp_error( $vercel_response ) ) {
			return new WP_REST_Response(
				[
					'success' => false,
					'message' => 'Failed to trigger GitHub workflow: ' . $vercel_response->get_error_message(),
					'job_id'  => $job_id,
				],
				500
			);
		}

		$response_code = wp_remote_retrieve_response_code( $vercel_response );
		$response_body = wp_remote_retrieve_body( $vercel_response );
		$response_data = json_decode( $response_body, true );

		if ( $response_code !== 200 ) {
			$error_message = $response_data['message'] ?? 'Unknown error from Vercel endpoint';
			return new WP_REST_Response(
				[
					'success' => false,
					'message' => 'Vercel endpoint error: ' . $error_message,
					'job_id'  => $job_id,
					'vercel_response' => $response_data,
				],
				$response_code
			);
		}

		return new WP_REST_Response(
			[
				'success'     => true,
				'message'     => $response_data['message'] ?? 'GitHub Actions workflow triggered',
				'job_id'      => $job_id,
				'github_repo' => $github_repo,
				'webhook_url' => $webhook_url,
				'actions_url' => $response_data['actions_url'] ?? '',
			],
			200
		);
	}

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

		$results = get_option( self::RESULTS_STORAGE_OPTION, [] );
		if ( ! is_array( $results ) ) {
			$results = [];
		}

		$results[ $job_id ] = [
			'data'      => $data,
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

		return new WP_REST_Response(
			[
				'success' => true,
				'status'  => 'complete',
				'job_id'  => $job_id,
				'data'    => $results[ $job_id ]['data'],
				'received' => $results[ $job_id ]['received'],
			],
			200
		);
	}
}
