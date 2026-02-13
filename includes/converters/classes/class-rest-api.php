<?php
/**
 * REST API Class
 *
 * @package ElementorHtmlCssConverter
 */

namespace ElementorHtmlCssConverter\Converters\Classes;

use ElementorHtmlCssConverter\Converters\Css\Widget_Style_Applicator;
use ElementorHtmlCssConverter\Converters\Html\Html_Converter;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
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
	 * REST API route for HTML to widgets conversion.
	 */
	private const ROUTE_CONVERT_HTML = '/convert-html';

	/**
	 * The converter registry.
	 *
	 * @var Converter_Registry
	 */
	private Converter_Registry $registry;

	/**
	 * The HTML converter.
	 *
	 * @var Html_Converter
	 */
	private Html_Converter $html_converter;

	/**
	 * The widget style applicator.
	 *
	 * @var Widget_Style_Applicator
	 */
	private Widget_Style_Applicator $widget_style_applicator;

	/**
	 * The Elementor document service.
	 *
	 * @var Elementor_Document_Service
	 */
	private Elementor_Document_Service $document_service;

	/**
	 * Constructor.
	 *
	 * @param Converter_Registry         $registry                The converter registry.
	 * @param Widget_Style_Applicator    $widget_style_applicator The widget style applicator.
	 * @param Html_Converter             $html_converter          The HTML converter.
	 * @param Elementor_Document_Service $document_service        The document service.
	 */
	public function __construct(
		Converter_Registry $registry,
		Widget_Style_Applicator $widget_style_applicator,
		Html_Converter $html_converter,
		Elementor_Document_Service $document_service
	) {
		$this->registry                = $registry;
		$this->widget_style_applicator = $widget_style_applicator;
		$this->html_converter          = $html_converter;
		$this->document_service        = $document_service;
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
		$this->register_convert_html_route();
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
						'type'              => 'object',
						'default'           => [],
						'sanitize_callback' => [ $this, 'sanitize_widget_settings' ],
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
						'type'              => 'object',
						'default'           => [],
						'sanitize_callback' => [ $this, 'sanitize_widget_settings' ],
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
		return true;
	}

	/**
	 * Sanitize HTML content while preserving style tags and SVG elements.
	 *
	 * wp_kses_post strips <style> tags and SVG elements, but we need them for
	 * ID-based CSS extraction and inline SVG import.
	 *
	 * @param string $html HTML content.
	 * @return string Sanitized HTML.
	 */
	public function sanitize_html_with_styles( string $html ): string {
		$allowed_html = wp_kses_allowed_html( 'post' );
		$allowed_html['style'] = [];
		
		$allowed_html['svg'] = [
			'xmlns' => true,
			'viewbox' => true,
			'viewBox' => true,
			'width' => true,
			'height' => true,
			'class' => true,
			'id' => true,
			'style' => true,
			'preserveaspectratio' => true,
			'preserveAspectRatio' => true,
			'fill' => true,
			'stroke' => true,
			'xml:space' => true,
		];
		
		$allowed_html['path'] = [
			'd' => true,
			'fill' => true,
			'stroke' => true,
			'stroke-width' => true,
			'stroke-linecap' => true,
			'stroke-linejoin' => true,
			'class' => true,
			'id' => true,
			'style' => true,
			'transform' => true,
		];
		
		$allowed_html['circle'] = [
			'cx' => true,
			'cy' => true,
			'r' => true,
			'fill' => true,
			'stroke' => true,
			'class' => true,
			'id' => true,
			'style' => true,
		];
		
		$allowed_html['rect'] = [
			'x' => true,
			'y' => true,
			'width' => true,
			'height' => true,
			'rx' => true,
			'ry' => true,
			'fill' => true,
			'stroke' => true,
			'class' => true,
			'id' => true,
			'style' => true,
		];
		
		$allowed_html['line'] = [
			'x1' => true,
			'y1' => true,
			'x2' => true,
			'y2' => true,
			'stroke' => true,
			'stroke-width' => true,
			'class' => true,
			'id' => true,
			'style' => true,
		];
		
		$allowed_html['polyline'] = [
			'points' => true,
			'fill' => true,
			'stroke' => true,
			'stroke-width' => true,
			'class' => true,
			'id' => true,
			'style' => true,
		];
		
		$allowed_html['polygon'] = [
			'points' => true,
			'fill' => true,
			'stroke' => true,
			'stroke-width' => true,
			'class' => true,
			'id' => true,
			'style' => true,
		];
		
		$allowed_html['g'] = [
			'fill' => true,
			'stroke' => true,
			'transform' => true,
			'class' => true,
			'id' => true,
			'style' => true,
		];
		
		$allowed_html['text'] = [
			'x' => true,
			'y' => true,
			'fill' => true,
			'font-family' => true,
			'font-size' => true,
			'font-weight' => true,
			'text-anchor' => true,
			'class' => true,
			'id' => true,
			'style' => true,
		];
		
		$allowed_html['defs'] = [];
		$allowed_html['use'] = [
			'href' => true,
			'x' => true,
			'y' => true,
			'width' => true,
			'height' => true,
			'class' => true,
			'id' => true,
		];
		
		$allowed_html['clipPath'] = [
			'id' => true,
		];
		
		$allowed_html['mask'] = [
			'id' => true,
		];
		
		$allowed_html['linearGradient'] = [
			'id' => true,
			'x1' => true,
			'y1' => true,
			'x2' => true,
			'y2' => true,
			'gradientUnits' => true,
		];
		
		$allowed_html['radialGradient'] = [
			'id' => true,
			'cx' => true,
			'cy' => true,
			'r' => true,
			'gradientUnits' => true,
		];
		
		$allowed_html['stop'] = [
			'offset' => true,
			'stop-color' => true,
			'stop-opacity' => true,
		];

		return wp_kses( $html, $allowed_html );
	}

	/**
	 * Recursively sanitize widget settings by stripping all HTML tags from string values.
	 *
	 * @param mixed $settings The widget settings value (object, array, or scalar).
	 * @return mixed The sanitized settings.
	 */
	public function sanitize_widget_settings( $settings ) {
		if ( is_string( $settings ) ) {
			return wp_strip_all_tags( $settings );
		}

		if ( ! is_array( $settings ) ) {
			return $settings;
		}

		$sanitized = [];

		foreach ( $settings as $key => $value ) {
			$sanitized_key = is_string( $key ) ? wp_strip_all_tags( $key ) : $key;
			$sanitized[ $sanitized_key ] = $this->sanitize_widget_settings( $value );
		}

		return $sanitized;
	}

	/**
	 * Handle the CSS to atomic conversion request.
	 *
	 * @param \WP_REST_Request $request The REST request.
	 * @return \WP_REST_Response The REST response.
	 */
	public function handle_css_to_atomic_request( \WP_REST_Request $request ): \WP_REST_Response {
		$params     = $request->get_json_params() ?: $request->get_params();
		$css_string = $params['cssString'] ?? '';

		$converter = new \ElementorHtmlCssConverter\Converters\Css\Css_Converter( $this->registry );
		$result    = $converter->convert( [ 'cssString' => $css_string ] );

		$response = [
			'success'   => true,
			'props'     => $result['props'] ?? [],
			'customCss' => $result['customCss'] ?? '',
		];

		return rest_ensure_response( $response );
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

	/**
	 * Register the HTML to widgets conversion route.
	 *
	 * @return void
	 */
	private function register_convert_html_route(): void {
		register_rest_route(
			self::REST_NAMESPACE,
			self::ROUTE_CONVERT_HTML,
			[
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'handle_convert_html_request' ],
				'permission_callback' => [ $this, 'check_permissions' ],
				'args'                => [
					'html'             => [
						'type'              => 'string',
						'required'          => true,
						'sanitize_callback' => [ $this, 'sanitize_html_with_styles' ],
					],
					'options'          => [
						'type'    => 'object',
						'default' => [],
					],
					'css_variables'    => [
						'type'              => 'string',
						'required'          => false,
						'sanitize_callback' => 'sanitize_textarea_field',
						'description'       => 'Raw CSS variable declarations (e.g., "--primary-color: #ff0000;")',
					],
					'import_variables' => [
						'type'              => 'boolean',
						'required'          => false,
						'default'           => true,
						'sanitize_callback' => 'rest_sanitize_boolean',
						'description'       => 'Extract variables from <style> tags in HTML',
					],
					'import_classes'   => [
						'type'              => 'boolean',
						'required'          => false,
						'default'           => true,
						'sanitize_callback' => 'rest_sanitize_boolean',
						'description'       => 'Create global classes from .class selectors in <style> tags',
					],
					'import_images'    => [
						'type'              => 'boolean',
						'required'          => false,
						'default'           => true,
						'sanitize_callback' => 'rest_sanitize_boolean',
						'description'       => 'Import external images from <img> tags and background-image CSS into WordPress media library',
					],
					'update_mode'      => [
						'type'              => 'string',
						'required'          => false,
						'default'           => 'create_new',
						'enum'              => [ 'create_new', 'update' ],
						'sanitize_callback' => 'sanitize_text_field',
					],
					'postId'           => [
						'type'              => 'integer',
						'required'          => false,
						'sanitize_callback' => 'absint',
					],
					'widgetId'         => [
						'type'              => 'string',
						'required'          => false,
						'sanitize_callback' => 'sanitize_text_field',
					],
					'postTitle'        => [
						'type'              => 'string',
						'required'          => false,
						'default'           => 'Converted HTML',
						'sanitize_callback' => 'sanitize_text_field',
					],
					'postStatus'       => [
						'type'              => 'string',
						'required'          => false,
						'default'           => 'draft',
						'sanitize_callback' => 'sanitize_text_field',
					],
					'save_as_template' => [
						'type'              => 'boolean',
						'required'          => false,
						'default'           => false,
						'sanitize_callback' => 'rest_sanitize_boolean',
						'description'       => 'Save converted widgets as an Elementor template instead of creating/updating a post',
					],
				],
			]
		);
	}

	/**
	 * Handle the HTML to widgets conversion request.
	 *
	 * When postId is provided, widgets are inserted into that post.
	 * When widgetId is also provided, widgets are inserted into that specific container.
	 * When postId is NOT provided, a new Elementor page is auto-created.
	 *
	 * @param \WP_REST_Request $request The REST request.
	 * @return \WP_REST_Response The REST response.
	 */
	public function handle_convert_html_request( \WP_REST_Request $request ): \WP_REST_Response {
		$html             = $request->get_param( 'html' ) ?? '';
		$options          = $request->get_param( 'options' ) ?? [];
		$css_variables    = $request->get_param( 'css_variables' ) ?? '';
		$import_variables = $request->get_param( 'import_variables' ) ?? true;
		$import_classes   = $request->get_param( 'import_classes' ) ?? true;
		$import_images    = $request->get_param( 'import_images' ) ?? true;
		$update_mode      = $request->get_param( 'update_mode' ) ?? 'create_new';
		$post_id           = (int) ( $request->get_param( 'postId' ) ?? 0 );
		$widget_id         = $request->get_param( 'widgetId' ) ?? '';
		$post_title        = $request->get_param( 'postTitle' ) ?? 'Converted HTML';
		$post_status       = $request->get_param( 'postStatus' ) ?? 'draft';
		$save_as_template  = $request->get_param( 'save_as_template' ) ?? false;

		$options['css_variables']    = $css_variables;
		$options['import_variables'] = $import_variables;
		$options['import_classes']   = $import_classes;
		$options['import_images']    = $import_images;
		$options['update_mode']      = $update_mode;

		$result = $this->html_converter->convert_html_to_atomic_widgets( $html, $options );

		if ( ! $result['success'] || empty( $result['widgets'] ) ) {
			return rest_ensure_response( $result );
		}

		if ( $save_as_template ) {
			$template_id = $this->document_service->save_as_template( $post_title, $result['widgets'] );

			if ( false === $template_id ) {
				return rest_ensure_response( [
					'success' => false,
					'error'   => 'Failed to save widgets as template.',
					'widgets' => $result['widgets'],
				] );
			}

			$result['template_id'] = $template_id;
			return rest_ensure_response( $result );
		}

		if ( 0 === $post_id ) {
			$post_result = $this->document_service->create_post( $post_title, $post_status );

			if ( ! $post_result || empty( $post_result['post_id'] ) || ! is_numeric( $post_result['post_id'] ) ) {
				return rest_ensure_response( [
					'success' => false,
					'error'   => 'Failed to create new Elementor page.',
					'widgets' => $result['widgets'],
				] );
			}

			$post_id               = (int) $post_result['post_id'];
			$result['page_created'] = true;
		}

		if ( ! empty( $widget_id ) ) {
			$inserted_ids = $this->document_service->add_widgets_to_container(
				$post_id,
				$widget_id,
				$result['widgets']
			);

			if ( false === $inserted_ids ) {
				return rest_ensure_response( [
					'success' => false,
					'error'   => 'Failed to insert widgets into the container. Check that postId and widgetId are valid.',
					'widgets' => $result['widgets'],
				] );
			}

			$result['inserted']   = true;
			$result['widget_ids'] = $inserted_ids;
		} else {
			$insert_result = $this->document_service->add_widgets_to_root(
				$post_id,
				$result['widgets']
			);

			if ( false === $insert_result ) {
				return rest_ensure_response( [
					'success' => false,
					'error'   => 'Failed to insert widgets into the post. Check that postId is valid.',
					'widgets' => $result['widgets'],
				] );
			}

			$result['inserted']     = true;
			$result['container_id'] = $insert_result['container_id'];
			$result['widget_ids']   = $insert_result['widget_ids'];
		}

		$result['post_id']  = $post_id;
		$result['edit_url'] = $this->document_service->get_edit_url( $post_id );

		return rest_ensure_response( $result );
	}
}
