<?php
/**
 * Main Plugin Class
 *
 * @package ElementorHtmlCssConverter
 */

namespace ElementorHtmlCssConverter;

use ElementorHtmlCssConverter\Converters\Color_Converter;
use ElementorHtmlCssConverter\Converters\Background_Color_Converter;
use ElementorHtmlCssConverter\Converters\Font_Size_Converter;
use ElementorHtmlCssConverter\Converters\Width_Converter;
use ElementorHtmlCssConverter\Converters\Height_Converter;
use ElementorHtmlCssConverter\Converters\Padding_Converter;
use ElementorHtmlCssConverter\Converters\Margin_Converter;
use ElementorHtmlCssConverter\Converters\Display_Converter;
use ElementorHtmlCssConverter\Converters\Position_Converter;
use ElementorHtmlCssConverter\Converters\Flex_Direction_Converter;
use ElementorHtmlCssConverter\Converters\Justify_Content_Converter;
use ElementorHtmlCssConverter\Converters\Align_Items_Converter;
use ElementorHtmlCssConverter\Converters\Gap_Converter;
use ElementorHtmlCssConverter\Converters\Align_Content_Converter;
use ElementorHtmlCssConverter\Converters\Align_Self_Converter;
use ElementorHtmlCssConverter\Converters\Flex_Wrap_Converter;
use ElementorHtmlCssConverter\Converters\Flex_Converter;
use ElementorHtmlCssConverter\Converters\Flex_Grow_Converter;
use ElementorHtmlCssConverter\Converters\Flex_Shrink_Converter;
use ElementorHtmlCssConverter\Converters\Flex_Basis_Converter;
use ElementorHtmlCssConverter\Converters\Order_Converter;
use ElementorHtmlCssConverter\Converters\Border_Radius_Converter;
use ElementorHtmlCssConverter\Converters\Box_Shadow_Converter;
use ElementorHtmlCssConverter\Converters\Opacity_Converter;
use ElementorHtmlCssConverter\Converters\Font_Weight_Converter;
use ElementorHtmlCssConverter\Converters\Text_Align_Converter;
use ElementorHtmlCssConverter\Converters\Line_Height_Converter;
use ElementorHtmlCssConverter\Converters\Letter_Spacing_Converter;
use ElementorHtmlCssConverter\Converters\Text_Decoration_Converter;
use ElementorHtmlCssConverter\Converters\Text_Transform_Converter;
use ElementorHtmlCssConverter\Converters\Font_Style_Converter;
use ElementorHtmlCssConverter\Converters\Word_Spacing_Converter;
use ElementorHtmlCssConverter\Converters\Text_Shadow_Converter;
use ElementorHtmlCssConverter\Converters\Positioning_Converter;
use ElementorHtmlCssConverter\Converters\Border_Width_Converter;
use ElementorHtmlCssConverter\Converters\Border_Style_Converter;
use ElementorHtmlCssConverter\Converters\Border_Color_Converter;
use ElementorHtmlCssConverter\Converters\Border_Converter;
use ElementorHtmlCssConverter\Converters\Transform_Converter;

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
		$this->registry = new Converter_Registry();
		$this->register_converters();

		$this->widget_style_applicator = $this->create_widget_style_applicator();

		$this->rest_api = new Rest_Api( $this->registry, $this->widget_style_applicator );
		$this->rest_api->register_hooks();
	}

	/**
	 * Create the widget style applicator with its dependencies.
	 *
	 * @return Widget_Style_Applicator The widget style applicator.
	 */
	private function create_widget_style_applicator(): Widget_Style_Applicator {
		$css_converter    = new Css_Converter( $this->registry );
		$style_builder    = new Style_Definition_Builder();
		$document_service = new Elementor_Document_Service();

		return new Widget_Style_Applicator( $css_converter, $style_builder, $document_service );
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
