<?php
/**
 * Main Plugin Class
 *
 * @package ElementorHtmlCssConverter
 */

namespace ElementorHtmlCssConverter;

use ElementorHtmlCssConverter\Converters\Classes\Converter_Registry;
use ElementorHtmlCssConverter\Converters\Css\Css_Converter;
use ElementorHtmlCssConverter\Converters\Html\Html_Converter;
use ElementorHtmlCssConverter\Converters\Classes\Rest_Api;
use ElementorHtmlCssConverter\Converters\Classes\Elementor_Document_Service;
use ElementorHtmlCssConverter\Converters\Variables\Variables_Rest_Api;
use ElementorHtmlCssConverter\Converters\Classes\Classes_Rest_API;
use ElementorHtmlCssConverter\Converters\Css\Style_Definition_Builder;
use ElementorHtmlCssConverter\Converters\Css\Widget_Style_Applicator;
use ElementorHtmlCssConverter\Converters\Css\Properties\Color_Converter;
use ElementorHtmlCssConverter\Converters\Css\Properties\Background_Color_Converter;
use ElementorHtmlCssConverter\Converters\Css\Properties\Font_Size_Converter;
use ElementorHtmlCssConverter\Converters\Css\Properties\Width_Converter;
use ElementorHtmlCssConverter\Converters\Css\Properties\Height_Converter;
use ElementorHtmlCssConverter\Converters\Css\Properties\Padding_Converter;
use ElementorHtmlCssConverter\Converters\Css\Properties\Margin_Converter;
use ElementorHtmlCssConverter\Converters\Css\Properties\Display_Converter;
use ElementorHtmlCssConverter\Converters\Css\Properties\Position_Converter;
use ElementorHtmlCssConverter\Converters\Css\Properties\Flex_Direction_Converter;
use ElementorHtmlCssConverter\Converters\Css\Properties\Justify_Content_Converter;
use ElementorHtmlCssConverter\Converters\Css\Properties\Align_Items_Converter;
use ElementorHtmlCssConverter\Converters\Css\Properties\Gap_Converter;
use ElementorHtmlCssConverter\Converters\Css\Properties\Align_Content_Converter;
use ElementorHtmlCssConverter\Converters\Css\Properties\Align_Self_Converter;
use ElementorHtmlCssConverter\Converters\Css\Properties\Flex_Wrap_Converter;
use ElementorHtmlCssConverter\Converters\Css\Properties\Flex_Converter;
use ElementorHtmlCssConverter\Converters\Css\Properties\Flex_Grow_Converter;
use ElementorHtmlCssConverter\Converters\Css\Properties\Flex_Shrink_Converter;
use ElementorHtmlCssConverter\Converters\Css\Properties\Flex_Basis_Converter;
use ElementorHtmlCssConverter\Converters\Css\Properties\Order_Converter;
use ElementorHtmlCssConverter\Converters\Css\Properties\Border_Radius_Converter;
use ElementorHtmlCssConverter\Converters\Css\Properties\Box_Shadow_Converter;
use ElementorHtmlCssConverter\Converters\Css\Properties\Opacity_Converter;
use ElementorHtmlCssConverter\Converters\Css\Properties\Font_Weight_Converter;
use ElementorHtmlCssConverter\Converters\Css\Properties\Text_Align_Converter;
use ElementorHtmlCssConverter\Converters\Css\Properties\Line_Height_Converter;
use ElementorHtmlCssConverter\Converters\Css\Properties\Letter_Spacing_Converter;
use ElementorHtmlCssConverter\Converters\Css\Properties\Text_Decoration_Converter;
use ElementorHtmlCssConverter\Converters\Css\Properties\Text_Transform_Converter;
use ElementorHtmlCssConverter\Converters\Css\Properties\Font_Style_Converter;
use ElementorHtmlCssConverter\Converters\Css\Properties\Word_Spacing_Converter;
use ElementorHtmlCssConverter\Converters\Css\Properties\Text_Shadow_Converter;
use ElementorHtmlCssConverter\Converters\Css\Properties\Positioning_Converter;
use ElementorHtmlCssConverter\Converters\Css\Properties\Border_Width_Converter;
use ElementorHtmlCssConverter\Converters\Css\Properties\Border_Style_Converter;
use ElementorHtmlCssConverter\Converters\Css\Properties\Border_Color_Converter;
use ElementorHtmlCssConverter\Converters\Css\Properties\Border_Converter;
use ElementorHtmlCssConverter\Converters\Css\Properties\Transform_Converter;

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

		// Initialize Classes REST API
		new Classes_Rest_API( $this->registry );
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
