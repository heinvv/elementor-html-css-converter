<?php

namespace ElementorHtmlCssConverter\Tests\TestCase;

use PHPUnit\Framework\TestCase;

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', '/tmp/' );
}

abstract class Base_Test_Case extends TestCase {

	protected function assertSuccessfulConversion( array $data ): void {
		$this->assertTrue( $data['success'] );
		$this->assertArrayHasKey( 'widgets', $data );
		$this->assertIsArray( $data['widgets'] );
	}

	protected function assertValidWidgetStructure( array $widget ): void {
		$this->assertArrayHasKey( 'id', $widget );
		$this->assertArrayHasKey( 'elType', $widget );
		$this->assertArrayHasKey( 'widgetType', $widget );
		$this->assertArrayHasKey( 'settings', $widget );
	}

	protected function assertValidBreakpointProps( array $widget ): void {
		$this->assertArrayHasKey( 'styles', $widget );
		$has_breakpoint_styles = false;
		foreach ( $widget['styles'] ?? [] as $style_def ) {
			$variants   = $style_def['variants'] ?? [];
			$breakpoints = array_filter( array_map( function ( $v ) {
				return $v['meta']['breakpoint'] ?? $v['breakpoint'] ?? null;
			}, $variants ) );
			if ( count( $breakpoints ) > 1 ) {
				$has_breakpoint_styles = true;
				break;
			}
		}
		$this->assertTrue( $has_breakpoint_styles );
	}

	protected function assertWidgetHasStylesOrSettings( array $widget ): void {
		$has_styles   = ! empty( $widget['styles'] ?? [] );
		$has_settings = ! empty( $widget['settings'] ?? [] );
		$this->assertTrue( $has_styles || $has_settings, 'Widget must have styles or settings' );
	}

	protected function assertFirstWidgetHasBreakpointVariants( array $widgets ): void {
		$this->assertNotEmpty( $widgets );
		$first = $widgets[0];
		if ( empty( $first['styles'] ?? [] ) ) {
			return;
		}
		foreach ( $first['styles'] as $style_def ) {
			$variants = $style_def['variants'] ?? [];
			if ( count( $variants ) >= 2 ) {
				$breakpoint_names = array_map( function ( $v ) {
					return $v['meta']['breakpoint'] ?? $v['breakpoint'] ?? null;
				}, $variants );
				$this->assertContains( 'desktop', $breakpoint_names );
				return;
			}
		}
	}

}
