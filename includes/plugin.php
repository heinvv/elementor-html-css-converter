<?php
/**
 * Main Plugin Class
 *
 * @package ElementorHtmlCssConverter
 */

namespace ElementorHtmlCssConverter;

use ElementorHtmlCssConverter\Core\Converter_Registry;
use ElementorHtmlCssConverter\Core\Css_Converter;
use ElementorHtmlCssConverter\Core\Html_Converter;
use ElementorHtmlCssConverter\Core\Rest_Api;
use ElementorHtmlCssConverter\Core\Variables_Rest_Api;
use ElementorHtmlCssConverter\Core\Elementor_Document_Service;
use ElementorHtmlCssConverter\Utilities\Style_Definition_Builder;
use ElementorHtmlCssConverter\Utilities\Widget_Style_Applicator;
use ElementorHtmlCssConverter\Converters\Css\Color_Converter;
use ElementorHtmlCssConverter\Converters\Css\Background_Color_Converter;
use ElementorHtmlCssConverter\Converters\Css\Font_Size_Converter;
use ElementorHtmlCssConverter\Converters\Css\Width_Converter;
use ElementorHtmlCssConverter\Converters\Css\Height_Converter;
use ElementorHtmlCssConverter\Converters\Css\Padding_Converter;
use ElementorHtmlCssConverter\Converters\Css\Margin_Converter;
use ElementorHtmlCssConverter\Converters\Css\Display_Converter;
use ElementorHtmlCssConverter\Converters\Css\Position_Converter;
use ElementorHtmlCssConverter\Converters\Css\Flex_Direction_Converter;
use ElementorHtmlCssConverter\Converters\Css\Justify_Content_Converter;
use ElementorHtmlCssConverter\Converters\Css\Align_Items_Converter;
use ElementorHtmlCssConverter\Converters\Css\Gap_Converter;
use ElementorHtmlCssConverter\Converters\Css\Align_Content_Converter;
use ElementorHtmlCssConverter\Converters\Css\Align_Self_Converter;
use ElementorHtmlCssConverter\Converters\Css\Flex_Wrap_Converter;
use ElementorHtmlCssConverter\Converters\Css\Flex_Converter;
use ElementorHtmlCssConverter\Converters\Css\Flex_Grow_Converter;
use ElementorHtmlCssConverter\Converters\Css\Flex_Shrink_Converter;
use ElementorHtmlCssConverter\Converters\Css\Flex_Basis_Converter;
use ElementorHtmlCssConverter\Converters\Css\Order_Converter;
use ElementorHtmlCssConverter\Converters\Css\Border_Radius_Converter;
use ElementorHtmlCssConverter\Converters\Css\Box_Shadow_Converter;
use ElementorHtmlCssConverter\Converters\Css\Opacity_Converter;
use ElementorHtmlCssConverter\Converters\Css\Font_Weight_Converter;
use ElementorHtmlCssConverter\Converters\Css\Text_Align_Converter;
use ElementorHtmlCssConverter\Converters\Css\Line_Height_Converter;
use ElementorHtmlCssConverter\Converters\Css\Letter_Spacing_Converter;
use ElementorHtmlCssConverter\Converters\Css\Text_Decoration_Converter;
use ElementorHtmlCssConverter\Converters\Css\Text_Transform_Converter;
use ElementorHtmlCssConverter\Converters\Css\Font_Style_Converter;
use ElementorHtmlCssConverter\Converters\Css\Word_Spacing_Converter;
use ElementorHtmlCssConverter\Converters\Css\Text_Shadow_Converter;
use ElementorHtmlCssConverter\Converters\Css\Positioning_Converter;
use ElementorHtmlCssConverter\Converters\Css\Border_Width_Converter;
use ElementorHtmlCssConverter\Converters\Css\Border_Style_Converter;
use ElementorHtmlCssConverter\Converters\Css\Border_Color_Converter;
use ElementorHtmlCssConverter\Converters\Css\Border_Converter;
use ElementorHtmlCssConverter\Converters\Css\Transform_Converter;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class Plugin
 *
 * Main plugin orchestration class using singleton pattern.
 */
final class Plugin {
	/**
	 * Plugin instance.
	 *
	 * @var Plugin|null
	 */
	private static ?Plugin $instance = null;

	/**
	 * The converter registry.
	 *
	 * @var Converter_Registry
	 */
	private Converter_Registry $registry;

	/**
	 * The REST API handler.
	 *
	 * @var Rest_Api
	 */
	private Rest_Api $rest_api;

	/**
	 * The widget style applicator.
	 *
	 * @var Widget_Style_Applicator
	 */
	private Widget_Style_Applicator $widget_style_applicator;

	/**
	 * The HTML converter.
	 *
	 * @var Html_Converter
	 */
	private Html_Converter $html_converter;

	/**
	 * The Elementor document service.
	 *
	 * @var Elementor_Document_Service
	 */
	private Elementor_Document_Service $document_service;

	/**
	 * Get plugin instance.
	 *
	 * @return Plugin The plugin instance.
	 */
	public static function instance(): Plugin {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		$this->registry         = new Converter_Registry();
		$this->document_service = new Elementor_Document_Service();

		$this->register_converters();

		$this->widget_style_applicator = $this->create_widget_style_applicator();
		$this->html_converter          = new Html_Converter( $this->registry );

		$this->rest_api = new Rest_Api(
			$this->registry,
			$this->widget_style_applicator,
			$this->html_converter,
			$this->document_service
		);
		$this->rest_api->register_hooks();

		// Initialize Variables REST API
		new Variables_Rest_API();
	}

	/**
	 * Create the widget style applicator with its dependencies.
	 *
	 * @return Widget_Style_Applicator The widget style applicator.
	 */
	private function create_widget_style_applicator(): Widget_Style_Applicator {
		$css_converter = new Css_Converter( $this->registry );
		$style_builder = new Style_Definition_Builder();

		return new Widget_Style_Applicator( $css_converter, $style_builder, $this->document_service );
	}

	/**
	 * Register all available converters.
	 *
	 * @return void
	 */
	private function register_converters(): void {
		$this->registry->register( new Color_Converter() );
		$this->registry->register( new Background_Color_Converter() );
		$this->registry->register( new Font_Size_Converter() );
		$this->registry->register( new Width_Converter() );
		$this->registry->register( new Height_Converter() );
		$this->registry->register( new Padding_Converter() );
		$this->registry->register( new Margin_Converter() );
		$this->registry->register( new Display_Converter() );
		$this->registry->register( new Position_Converter() );
		$this->registry->register( new Flex_Direction_Converter() );
		$this->registry->register( new Justify_Content_Converter() );
		$this->registry->register( new Align_Items_Converter() );
		$this->registry->register( new Gap_Converter() );
		$this->registry->register( new Align_Content_Converter() );
		$this->registry->register( new Align_Self_Converter() );
		$this->registry->register( new Flex_Wrap_Converter() );
		$this->registry->register( new Flex_Converter() );
		$this->registry->register( new Flex_Grow_Converter() );
		$this->registry->register( new Flex_Shrink_Converter() );
		$this->registry->register( new Flex_Basis_Converter() );
		$this->registry->register( new Order_Converter() );
		$this->registry->register( new Border_Radius_Converter() );
		$this->registry->register( new Box_Shadow_Converter() );
		$this->registry->register( new Opacity_Converter() );
		$this->registry->register( new Font_Weight_Converter() );
		$this->registry->register( new Text_Align_Converter() );
		$this->registry->register( new Line_Height_Converter() );
		$this->registry->register( new Letter_Spacing_Converter() );
		$this->registry->register( new Text_Decoration_Converter() );
		$this->registry->register( new Text_Transform_Converter() );
		$this->registry->register( new Font_Style_Converter() );
		$this->registry->register( new Word_Spacing_Converter() );
		$this->registry->register( new Text_Shadow_Converter() );
		$this->registry->register( new Positioning_Converter() );
		$this->registry->register( new Border_Width_Converter() );
		$this->registry->register( new Border_Style_Converter() );
		$this->registry->register( new Border_Color_Converter() );
		$this->registry->register( new Border_Converter() );
		$this->registry->register( new Transform_Converter() );
	}

	/**
	 * Get the converter registry.
	 *
	 * @return Converter_Registry The converter registry.
	 */
	public function get_registry(): Converter_Registry {
		return $this->registry;
	}

	/**
	 * Get the widget style applicator.
	 *
	 * @return Widget_Style_Applicator The widget style applicator.
	 */
	public function get_widget_style_applicator(): Widget_Style_Applicator {
		return $this->widget_style_applicator;
	}

	/**
	 * Get the HTML converter.
	 *
	 * @return Html_Converter The HTML converter.
	 */
	public function get_html_converter(): Html_Converter {
		return $this->html_converter;
	}

	/**
	 * Prevent cloning.
	 */
	private function __clone() {}

	/**
	 * Prevent unserializing.
	 *
	 * @throws \Exception When trying to unserialize.
	 */
	public function __wakeup() {
		throw new \Exception( 'Cannot unserialize singleton' );
	}
}
