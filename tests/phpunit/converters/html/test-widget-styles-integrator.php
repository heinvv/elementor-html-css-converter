<?php

namespace ElementorHtmlCssConverter\Tests\Converters\Html;

use ElementorHtmlCssConverter\Converters\Html\Widget_Styles_Integrator;
use PHPUnit\Framework\TestCase;

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', '/tmp/' );
}

class Test_Widget_Styles_Integrator extends TestCase {

	private Widget_Styles_Integrator $integrator;

	protected function setUp(): void {
		parent::setUp();
		$this->integrator = new Widget_Styles_Integrator();
	}

	public function test_integrate_styles_into_widget__empty_props_returns_unchanged(): void {
		$widget = [ 'widgetType' => 'e-div-block', 'elType' => 'widget' ];
		$result = $this->integrator->integrate_styles_into_widget( $widget, [] );

		$this->assertSame( $widget, $result );
	}

	public function test_integrate_styles_into_widget__adds_styles(): void {
		$widget = [ 'widgetType' => 'e-div-block' ];
		$breakpoint_props = [
			'desktop' => [
				'atomic_props' => [ 'padding' => [ 'value' => [ 'top' => '20px' ] ] ],
				'custom_css'   => null,
			],
		];
		$result = $this->integrator->integrate_styles_into_widget( $widget, $breakpoint_props );

		$this->assertArrayHasKey( 'styles', $result );
		$this->assertNotEmpty( $result['styles'] );
	}

	public function test_integrate_styles_into_multiple_widgets(): void {
		$widgets = [
			[ 'widgetType' => 'e-div-block' ],
			[ 'widgetType' => 'e-paragraph' ],
		];
		$props = [
			[ 'desktop' => [ 'atomic_props' => [ 'color' => [] ], 'custom_css' => null ] ],
			[],
		];
		$result = $this->integrator->integrate_styles_into_multiple_widgets( $widgets, $props );

		$this->assertCount( 2, $result );
		$this->assertArrayHasKey( 'styles', $result[0] );
	}

	public function test_create_global_classes_from_widgets__empty_input(): void {
		$result = $this->integrator->create_global_classes_from_widgets( [] );

		$this->assertSame( [ 'items' => [], 'order' => [] ], $result );
	}

	public function test_create_global_classes_from_widgets__extracts_from_widgets(): void {
		$widgets = [
			[
				'styles' => [
					'e-abc1234-567890' => [ 'id' => 'e-abc1234-567890', 'variants' => [] ],
				],
			],
		];
		$result = $this->integrator->create_global_classes_from_widgets( $widgets );

		$this->assertArrayHasKey( 'e-abc1234-567890', $result['items'] );
		$this->assertContains( 'e-abc1234-567890', $result['order'] );
	}

	public function test_extract_breakpoint_props_from_widget_data(): void {
		$widget_data = [
			'breakpoint_props' => [
				'desktop' => [ 'atomic_props' => [] ],
			],
		];
		$result = $this->integrator->extract_breakpoint_props_from_widget_data( $widget_data );

		$this->assertArrayHasKey( 'desktop', $result );
	}

	public function test_extract_pseudo_state_props_from_widget_data(): void {
		$widget_data = [
			'pseudo_state_props' => [
				'hover' => [
					'desktop' => [ 'atomic_props' => [ 'color' => [] ], 'custom_css' => null ],
				],
			],
		];
		$result = $this->integrator->extract_pseudo_state_props_from_widget_data( $widget_data );

		$this->assertArrayHasKey( 'hover', $result );
		$this->assertArrayHasKey( 'desktop', $result['hover'] );
	}

	public function test_integrate_styles_into_widget__with_pseudo_state_props_creates_state_variants(): void {
		$widget = [ 'widgetType' => 'e-button' ];
		$breakpoint_props = [
			'desktop' => [
				'atomic_props' => [ 'color' => [ 'value' => [ 'color' => '#333' ] ] ],
				'custom_css'   => null,
			],
		];
		$pseudo_state_props = [
			'hover' => [
				'desktop' => [
					'atomic_props' => [ 'color' => [ 'value' => [ 'color' => '#0073aa' ] ] ],
					'custom_css'   => null,
				],
			],
		];
		$result = $this->integrator->integrate_styles_into_widget( $widget, $breakpoint_props, $pseudo_state_props );

		$this->assertArrayHasKey( 'styles', $result );
		$style_definitions = $result['styles'];
		$this->assertNotEmpty( $style_definitions );
		$first_style = reset( $style_definitions );
		$variants = $first_style['variants'] ?? [];
		$state_variants = array_filter( $variants, fn( $v ) => isset( $v['meta']['state'] ) && null !== $v['meta']['state'] );
		$this->assertNotEmpty( $state_variants );
		$hover_variant = array_filter( $state_variants, fn( $v ) => 'hover' === ( $v['meta']['state'] ?? null ) );
		$this->assertNotEmpty( $hover_variant );
	}

}
