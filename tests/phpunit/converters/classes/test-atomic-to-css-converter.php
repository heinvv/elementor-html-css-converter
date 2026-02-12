<?php

namespace ElementorHtmlCssConverter\Tests\Converters\Classes;

use ElementorHtmlCssConverter\Converters\Classes\Atomic_To_Css_Converter;
use PHPUnit\Framework\TestCase;

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', '/tmp/' );
}

class Test_Atomic_To_Css_Converter extends TestCase {

	public function test_convert_classes_to_css__empty_input(): void {
		$converter = new Atomic_To_Css_Converter();
		$result = $converter->convert_classes_to_css( [] );
		$this->assertSame( '', $result );
	}

	public function test_convert_classes_to_css__skips_empty_label(): void {
		$converter = new Atomic_To_Css_Converter();
		$classes = [
			[ 'label' => '', 'variants' => [ [ 'meta' => [ 'breakpoint' => 'desktop' ], 'props' => [ 'display' => [ '$$type' => 'string', 'value' => 'block' ] ] ] ] ],
		];
		$result = $converter->convert_classes_to_css( $classes );
		$this->assertSame( '', $result );
	}

	public function test_convert_classes_to_css__skips_empty_variants(): void {
		$converter = new Atomic_To_Css_Converter();
		$classes = [
			[ 'label' => 'box', 'variants' => [] ],
		];
		$result = $converter->convert_classes_to_css( $classes );
		$this->assertSame( '', $result );
	}

	public function test_convert_classes_to_css__desktop_size_prop(): void {
		$converter = new Atomic_To_Css_Converter();
		$classes = [
			[
				'label'   => 'card',
				'variants' => [
					[
						'meta'  => [ 'breakpoint' => 'desktop' ],
						'props' => [
							'gap' => [ '$$type' => 'size', 'value' => [ 'size' => 16, 'unit' => 'px' ] ],
						],
					],
				],
			],
		];
		$result = $converter->convert_classes_to_css( $classes );
		$this->assertStringContainsString( '.card', $result );
		$this->assertStringContainsString( 'gap: 16px', $result );
	}

	public function test_convert_classes_to_css__desktop_string_prop(): void {
		$converter = new Atomic_To_Css_Converter();
		$classes = [
			[
				'label'   => 'flex-container',
				'variants' => [
					[
						'meta'  => [ 'breakpoint' => 'desktop' ],
						'props' => [
							'display' => [ '$$type' => 'string', 'value' => 'flex' ],
						],
					],
				],
			],
		];
		$result = $converter->convert_classes_to_css( $classes );
		$this->assertStringContainsString( '.flex-container', $result );
		$this->assertStringContainsString( 'display: flex', $result );
	}

	public function test_convert_classes_to_css__desktop_color_prop(): void {
		$converter = new Atomic_To_Css_Converter();
		$classes = [
			[
				'label'   => 'heading',
				'variants' => [
					[
						'meta'  => [ 'breakpoint' => 'desktop' ],
						'props' => [
							'color' => [ '$$type' => 'color', 'value' => '#333333' ],
						],
					],
				],
			],
		];
		$result = $converter->convert_classes_to_css( $classes );
		$this->assertStringContainsString( 'color: #333333', $result );
	}

	public function test_convert_classes_to_css__desktop_dimensions_prop(): void {
		$converter = new Atomic_To_Css_Converter();
		$classes = [
			[
				'label'   => 'box',
				'variants' => [
					[
						'meta'  => [ 'breakpoint' => 'desktop' ],
						'props' => [
							'padding' => [
								'$$type'       => 'dimensions',
								'value'        => [
									'block-start'  => [ '$$type' => 'size', 'value' => [ 'size' => 20, 'unit' => 'px' ] ],
									'inline-end'   => [ '$$type' => 'size', 'value' => [ 'size' => 20, 'unit' => 'px' ] ],
									'block-end'    => [ '$$type' => 'size', 'value' => [ 'size' => 20, 'unit' => 'px' ] ],
									'inline-start' => [ '$$type' => 'size', 'value' => [ 'size' => 20, 'unit' => 'px' ] ],
								],
							],
						],
					],
				],
			],
		];
		$result = $converter->convert_classes_to_css( $classes );
		$this->assertStringContainsString( 'padding-block-start: 20px', $result );
		$this->assertStringContainsString( 'padding-inline-end: 20px', $result );
	}

	public function test_convert_classes_to_css__responsive_variants(): void {
		$converter = new Atomic_To_Css_Converter();
		$classes = [
			[
				'label'   => 'responsive-box',
				'variants' => [
					[
						'meta'  => [ 'breakpoint' => 'desktop' ],
						'props' => [
							'gap' => [ '$$type' => 'size', 'value' => [ 'size' => 24, 'unit' => 'px' ] ],
						],
					],
					[
						'meta'  => [ 'breakpoint' => 'tablet' ],
						'props' => [
							'gap' => [ '$$type' => 'size', 'value' => [ 'size' => 16, 'unit' => 'px' ] ],
						],
					],
					[
						'meta'  => [ 'breakpoint' => 'mobile' ],
						'props' => [
							'gap' => [ '$$type' => 'size', 'value' => [ 'size' => 8, 'unit' => 'px' ] ],
						],
					],
				],
			],
		];
		$result = $converter->convert_classes_to_css( $classes );
		$this->assertStringContainsString( 'gap: 24px', $result );
		$this->assertStringContainsString( '@media (max-width:', $result );
		$this->assertStringContainsString( 'gap: 16px', $result );
		$this->assertStringContainsString( 'gap: 8px', $result );
	}

	public function test_convert_classes_to_css__sanitizes_label(): void {
		$converter = new Atomic_To_Css_Converter();
		$classes = [
			[
				'label'   => 'my-class name.with.dots',
				'variants' => [
					[
						'meta'  => [ 'breakpoint' => 'desktop' ],
						'props' => [
							'display' => [ '$$type' => 'string', 'value' => 'block' ],
						],
					],
				],
			],
		];
		$result = $converter->convert_classes_to_css( $classes );
		$this->assertStringContainsString( '.my-class-name-with-dots', $result );
	}

	public function test_convert_classes_to_css__skips_props_without_type(): void {
		$converter = new Atomic_To_Css_Converter();
		$classes = [
			[
				'label'   => 'box',
				'variants' => [
					[
						'meta'  => [ 'breakpoint' => 'desktop' ],
						'props' => [
							'invalid' => [ 'value' => 'something' ],
							'display' => [ '$$type' => 'string', 'value' => 'block' ],
						],
					],
				],
			],
		];
		$result = $converter->convert_classes_to_css( $classes );
		$this->assertStringContainsString( 'display: block', $result );
		$this->assertStringNotContainsString( 'invalid', $result );
	}

}
