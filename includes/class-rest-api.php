<?php
/**
 * REST API Class
 *
 * @package ElementorHtmlCssConverter
 */

namespace ElementorHtmlCssConverter;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class Rest_Api
 *
 * Handles REST API endpoint registration and request handling.
 */
class Rest_Api {
	/**
	 * REST API namespace.
	 */
	private const REST_NAMESPACE = 'html-css-converter/v1';

	/**
	 * REST API route.
	 */
	private const ROUTE = '/css-to-atomic';

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
	}

	/**
	 * Register REST API hooks.
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		add_action( 'rest_api_init', [ $this, 'register_routes' ] );
	}

	/**
	 * Register REST API routes.
	 *
	 * @return void
	 */
	public function register_routes(): void {
		register_rest_route(
			self::REST_NAMESPACE,
			self::ROUTE,
			[
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'handle_request' ],
				'permission_callback' => [ $this, 'check_permissions' ],
				'args'                => [
					'cssString' => [
						'type'              => 'string',
						'required'          => true,
						'sanitize_callback' => 'sanitize_textarea_field',
					],
				],
			]
		);
	}

	/**
	 * Check if user has permission to use the endpoint.
	 *
	 * WARNING: This endpoint is currently OPEN FOR TESTING PURPOSES ONLY.
	 * TODO: Before production, change this back to:
	 *       return current_user_can( 'edit_posts' );
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
	 * Handle the CSS to atomic conversion request.
	 *
	 * @param \WP_REST_Request $request The REST request.
	 * @return \WP_REST_Response The REST response.
	 */
	public function handle_request( \WP_REST_Request $request ): \WP_REST_Response {
		$params = $request->get_json_params();

		$converter = new Css_Converter( $this->registry );
		$result    = $converter->convert( $params );

		return rest_ensure_response( $result );
	}
}
