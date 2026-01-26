<?php
/**
 * Style Definition Builder Class
 *
 * @package ElementorHtmlCssConverter
 */

namespace ElementorHtmlCssConverter;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class Style_Definition_Builder
 *
 * Builds atomic widget style definitions from converted CSS properties.
 * Style definitions follow the Elementor atomic widget styles structure.
 */
class Style_Definition_Builder {
	/**
	 * Default style type.
	 */
	private const STYLE_TYPE = 'class';

	/**
	 * Default style label (matches Elementor editor).
	 */
	private const DEFAULT_LABEL = 'local';

	/**
	 * Default breakpoint.
	 */
	private const DEFAULT_BREAKPOINT = 'desktop';

	/**
	 * Style ID prefix.
	 */
	private const STYLE_ID_PREFIX = 'e-';

	/**
	 * Build a style definition from atomic props.
	 *
	 * @param array  $atomic_props The converted atomic properties.
	 * @param string $widget_id    The widget ID to use for generating style ID.
	 * @param string $label        Optional custom label for the style.
	 * @return array The complete style definition.
	 */
	public function build( array $atomic_props, string $widget_id, string $label = '' ): array {
		$style_id = $this->generate_style_id( $widget_id );

		return [
			'id'       => $style_id,
			'type'     => self::STYLE_TYPE,
			'label'    => $this->get_label( $label ),
			'variants' => [
				$this->create_default_variant( $atomic_props ),
			],
		];
	}

	/**
	 * Build a style definition with a specific state (hover, active, etc.).
	 *
	 * @param array       $atomic_props The converted atomic properties.
	 * @param string      $widget_id    The widget ID.
	 * @param string|null $state        The CSS state (hover, active, focus, etc.).
	 * @param string      $breakpoint   The breakpoint (desktop, tablet, mobile).
	 * @return array The complete style definition.
	 */
	public function build_with_state(
		array $atomic_props,
		string $widget_id,
		?string $state = null,
		string $breakpoint = self::DEFAULT_BREAKPOINT
	): array {
		$style_id = $this->generate_style_id( $widget_id );

		return [
			'id'       => $style_id,
			'type'     => self::STYLE_TYPE,
			'label'    => self::DEFAULT_LABEL,
			'variants' => [
				$this->create_variant( $atomic_props, $state, $breakpoint ),
			],
		];
	}

	/**
	 * Create a variant to add to an existing style definition.
	 *
	 * Structure matches Elementor editor output.
	 *
	 * @param array       $atomic_props The converted atomic properties.
	 * @param string|null $state        The CSS state (hover, active, focus, etc.).
	 * @param string      $breakpoint   The breakpoint.
	 * @return array The variant array.
	 */
	public function create_variant(
		array $atomic_props,
		?string $state = null,
		string $breakpoint = self::DEFAULT_BREAKPOINT
	): array {
		return [
			'meta'       => [
				'breakpoint' => $breakpoint,
				'state'      => $state,
			],
			'props'      => $atomic_props,
			'custom_css' => null,
		];
	}

	/**
	 * Generate a unique style ID based on widget ID.
	 *
	 * Format matches Elementor atomic widgets: e-{widgetId}-{7-char-hex}
	 * Based on css-converter module's generate_atomic_unique_id().
	 *
	 * @param string $widget_id The widget ID.
	 * @return string The generated style ID.
	 */
	public function generate_style_id( string $widget_id ): string {
		// Generate 7-character hex ID like atomic widgets do.
		// Based on css-converter's atomic-widget-data-formatter.php.
		$unique_id = substr( bin2hex( random_bytes( 4 ) ), 0, 7 );

		return self::STYLE_ID_PREFIX . $widget_id . '-' . $unique_id;
	}

	/**
	 * Create the default variant (no state, desktop breakpoint).
	 *
	 * @param array $atomic_props The converted atomic properties.
	 * @return array The default variant.
	 */
	private function create_default_variant( array $atomic_props ): array {
		return $this->create_variant( $atomic_props, null, self::DEFAULT_BREAKPOINT );
	}

	/**
	 * Get the label, using default if empty.
	 *
	 * @param string $label The provided label.
	 * @return string The label to use.
	 */
	private function get_label( string $label ): string {
		return ! empty( $label ) ? $label : self::DEFAULT_LABEL;
	}
}
