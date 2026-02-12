<?php

namespace ElementorHtmlCssConverter\Tests\Converters\Variables\Convertors;

use ElementorHtmlCssConverter\Converters\Variables\Convertors\Color_Hsla_Variable_Convertor;
use PHPUnit\Framework\TestCase;

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', '/tmp/' );
}

class Test_Color_Hsla_Variable_Convertor extends TestCase {

	private Color_Hsla_Variable_Convertor $convertor;

	protected function setUp(): void {
		parent::setUp();
		$this->convertor = new Color_Hsla_Variable_Convertor();
	}

	public function test_supports__accepts_hsla(): void {
		$this->assertTrue( $this->convertor->supports( '--color', 'hsla(0, 100%, 50%, 0.5)' ) );
		$this->assertTrue( $this->convertor->supports( '--color', 'hsla(120, 50%, 30%, 1)' ) );
	}

	public function test_supports__accepts_hsl_slash_alpha(): void {
		$this->assertTrue( $this->convertor->supports( '--color', 'hsl(0 100% 50% / 0.5)' ) );
	}

	public function test_supports__rejects_hsl_without_alpha(): void {
		$this->assertFalse( $this->convertor->supports( '--color', 'hsl(0, 100%, 50%)' ) );
	}

	public function test_convert__converts_hsla_to_rgba(): void {
		$result = $this->convertor->convert( '--color', 'hsla(0, 100%, 50%, 1)' );
		$this->assertSame( 'rgba(255, 0, 0, 1)', $result['value'] );
	}

	public function test_convert__converts_hsla_with_alpha(): void {
		$result = $this->convertor->convert( '--color', 'hsla(0, 100%, 50%, 0.5)' );
		$this->assertStringStartsWith( 'rgba(', $result['value'] );
		$this->assertStringEndsWith( ', 0.5)', $result['value'] );
	}

	public function test_get_type__returns_color_hsla(): void {
		$result = $this->convertor->convert( '--primary', 'hsla(200, 50%, 50%, 0.8)' );
		$this->assertSame( 'color-hsla', $result['type'] );
	}

	public function test_convert__generates_correct_id(): void {
		$result = $this->convertor->convert( '--overlay-color', 'hsla(0, 0%, 0%, 0.5)' );
		$this->assertSame( 'e-gv-color-hsla-overlay-color-variable', $result['id'] );
	}

}
