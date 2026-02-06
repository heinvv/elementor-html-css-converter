<?php
/**
 * Widget Style Applicator Interface
 *
 * @package ElementorHtmlCssConverter
 */

namespace ElementorHtmlCssConverter\Converters\Interfaces;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Interface Widget_Style_Applicator_Interface
 *
 * Defines the contract for applying CSS styles to Elementor atomic widgets.
 */
interface Widget_Style_Applicator_Interface {
	/**
	 * Apply CSS styles to a widget.
	 *
	 * Converts CSS string to atomic format and adds it to the widget's
	 * styles property following the atomic widget style structure.
	 *
	 * @param array  $widget     The widget data structure.
	 * @param string $css_string The CSS styles to apply.
	 * @return array Result with 'success', 'widget', and 'customCss' keys.
	 */
	public function apply( array $widget, string $css_string ): array;
}
