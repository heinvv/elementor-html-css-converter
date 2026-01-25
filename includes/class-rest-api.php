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
	 * REST API route for CSS to atomic conversion.
	 */
	private const ROUTE_CSS_TO_ATOMIC = '/css-to-atomic';

	/**
	 * REST API route for applying styles to existing widget.
	 */
	private const ROUTE_APPLY_STYLES = '/apply-styles-to-widget';

	/**
	 * REST API route for creating post with widget.
	 */
	private const ROUTE_CREATE_POST = '/create-post-with-widget';

	/**
	 * REST API route for adding widget to post.
	 */
	private const ROUTE_ADD_WIDGET = '/add-widget-to-post';

	/**
	 * The converter registry.
	 *
	 * @var Converter_Registry
	 */
	private Converter_Registry $registry;

	/**
	 * The widget style applicator.
	 *
	 * @var Widget_Style_Applicator
	 */
	private Widget_Style_Applicator $widget_style_applicator;

	/**
	 * Constructor.
	 *
	 * @param Converter_Registry      $registry                The converter registry.
	 * @param Widget_Style_Applicator $widget_style_applicator The widget style applicator.
	 */
	public function __construct(
		Converter_Registry $registry,
		Widget_Style_Applicator $widget_style_applicator
	) {
		$this->registry                = $registry;
		$this->widget_style_applicator = $widget_style_applicator;
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
		$this->register_css_to_atomic_route();
		$this->register_apply_styles_route();
		$this->register_create_post_route();
		$this->register_add_widget_route();
	}

	/**
	 * Register the CSS to atomic conversion route.
	 *
	 * @return void
	 */
	private function register_css_to_atomic_route(): void {
		register_rest_route(
			self::REST_NAMESPACE,
			self::ROUTE_CSS_TO_ATOMIC,
			[
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'handle_css_to_atomic_request' ],
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
	 * Register the apply styles to widget route.
	 *
	 * @return void
	 */
	private function register_apply_styles_route(): void {
		register_rest_route(
			self::REST_NAMESPACE,
			self::ROUTE_APPLY_STYLES,
			[
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'handle_apply_styles_request' ],
				'permission_callback' => [ $this, 'check_permissions' ],
				'args'                => [
					'postId'    => [
						'type'              => 'integer',
						'required'          => true,
						'sanitize_callback' => 'absint',
					],
					'widgetId'  => [
						'type'              => 'string',
						'required'          => true,
						'sanitize_callback' => 'sanitize_text_field',
					],
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
	 * Register the create post with widget route.
	 *
	 * @return void
	 */
	private function register_create_post_route(): void {
		register_rest_route(
			self::REST_NAMESPACE,
			self::ROUTE_CREATE_POST,
			[
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'handle_create_post_request' ],
				'permission_callback' => [ $this, 'check_permissions' ],
				'args'                => [
					'postTitle'      => [
						'type'              => 'string',
						'required'          => true,
						'sanitize_callback' => 'sanitize_text_field',
					],
					'postStatus'     => [
						'type'              => 'string',
						'default'           => 'draft',
						'sanitize_callback' => 'sanitize_text_field',
					],
					'widgetType'     => [
						'type'              => 'string',
						'required'          => true,
						'sanitize_callback' => 'sanitize_text_field',
					],
					'widgetSettings' => [
						'type'    => 'object',
						'default' => [],
					],
					'cssString'      => [
						'type'              => 'string',
						'default'           => '',
						'sanitize_callback' => 'sanitize_textarea_field',
					],
				],
			]
		);
	}

	/**
	 * Register the add widget to post route.
	 *
	 * @return void
	 */
	private function register_add_widget_route(): void {
		register_rest_route(
			self::REST_NAMESPACE,
			self::ROUTE_ADD_WIDGET,
			[
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'handle_add_widget_request' ],
				'permission_callback' => [ $this, 'check_permissions' ],
				'args'                => [
					'postId'         => [
						'type'              => 'integer',
						'required'          => true,
						'sanitize_callback' => 'absint',
					],
					'widgetType'     => [
						'type'              => 'string',
						'required'          => true,
						'sanitize_callback' => 'sanitize_text_field',
					],
					'widgetSettings' => [
						'type'    => 'object',
						'default' => [],
					],
					'cssString'      => [
						'type'              => 'string',
						'default'           => '',
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
	public function handle_css_to_atomic_request( \WP_REST_Request $request ): \WP_REST_Response {
		$params = $request->get_json_params();

		$converter = new Css_Converter( $this->registry );
		$result    = $converter->convert( $params );

		return rest_ensure_response( $result );
	}

	/**
	 * Handle the apply styles to widget request.
	 *
	 * @param \WP_REST_Request $request The REST request.
	 * @return \WP_REST_Response The REST response.
	 */
	public function handle_apply_styles_request( \WP_REST_Request $request ): \WP_REST_Response {
		$params = $request->get_json_params();

		$post_id    = (int) ( $params['postId'] ?? 0 );
		$widget_id  = $params['widgetId'] ?? '';
		$css_string = $params['cssString'] ?? '';

		$result = $this->widget_style_applicator->apply_to_existing( $post_id, $widget_id, $css_string );

		return rest_ensure_response( $result );
	}

	/**
	 * Handle the create post with widget request.
	 *
	 * @param \WP_REST_Request $request The REST request.
	 * @return \WP_REST_Response The REST response.
	 */
	public function handle_create_post_request( \WP_REST_Request $request ): \WP_REST_Response {
		$params = $request->get_json_params();

		$title           = $params['postTitle'] ?? 'Untitled';
		$status          = $params['postStatus'] ?? 'draft';
		$widget_type     = $params['widgetType'] ?? 'e-heading';
		$widget_settings = $params['widgetSettings'] ?? [];
		$css_string      = $params['cssString'] ?? '';

		$result = $this->widget_style_applicator->create_post_with_widget(
			$title,
			$status,
			$widget_type,
			$widget_settings,
			$css_string
		);

		return rest_ensure_response( $result );
	}

	/**
	 * Handle the add widget to post request.
	 *
	 * @param \WP_REST_Request $request The REST request.
	 * @return \WP_REST_Response The REST response.
	 */
	public function handle_add_widget_request( \WP_REST_Request $request ): \WP_REST_Response {
		$params = $request->get_json_params();

		$post_id         = (int) ( $params['postId'] ?? 0 );
		$widget_type     = $params['widgetType'] ?? 'e-heading';
		$widget_settings = $params['widgetSettings'] ?? [];
		$css_string      = $params['cssString'] ?? '';

		$result = $this->widget_style_applicator->add_widget_to_post(
			$post_id,
			$widget_type,
			$widget_settings,
			$css_string
		);

		return rest_ensure_response( $result );
	}
}
