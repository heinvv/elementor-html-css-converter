<?php

namespace ElementorHtmlCssConverter\Tests\Converters\Variables\Convertors;

use ElementorHtmlCssConverter\Converters\Variables\Convertors\Color_Hsl_Variable_Convertor;
use PHPUnit\Framework\TestCase;

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', '/tmp/' );
}

class Test_Color_Hsl_Variable_Convertor extends TestCase {

	private Color_Hsl_Variable_Convertor $convertor;

	protected function setUp(): void {
		parent::setUp();
		$this->convertor = new Color_Hsl_Variable_Convertor();
	}

	public function test_supports__accepts_hsl_comma(): void {
		$this->assertTrue( $this->convertor->supports( '--color', 'hsl(0, 100%, 50%)' ) );
		$this->assertTrue( $this->convertor->supports( '--color', 'hsl(120, 50%, 30%)' ) );
	}

	public function test_supports__accepts_hsl_with_deg(): void {
		$this->assertTrue( $this->convertor->supports( '--color', 'hsl(0deg, 100%, 50%)' ) );
	}

	public function test_supports__accepts_hsl_space_syntax(): void {
		$this->assertTrue( $this->convertor->supports( '--color', 'hsl(0 100% 50%)' ) );
	}

	public function test_supports__rejects_hsla(): void {
		$this->assertFalse( $this->convertor->supports( '--color', 'hsla(0, 100%, 50%, 0.5)' ) );
	}

	public function test_convert__converts_hsl_to_hex(): void {
		$result = $this->convertor->convert( '--color', 'hsl(0, 100%, 50%)' );
		$this->assertSame( '#ff0000', $result['value'] );
	}

	public function test_convert__converts_white_hsl(): void {
		$result = $this->convertor->convert( '--color', 'hsl(0, 0%, 100%)' );
		$this->assertSame( '#ffffff', $result['value'] );
	}

	public function test_convert__converts_black_hsl(): void {
		$result = $this->convertor->convert( '--color', 'hsl(0, 0%, 0%)' );
		$this->assertSame( '#000000', $result['value'] );
	}

	public function test_get_type__returns_color_hsl(): void {
		$result = $this->convertor->convert( '--primary', 'hsl(200, 50%, 50%)' );
		$this->assertSame( 'color-hsl', $result['type'] );
	}

	public function test_convert__generates_correct_id(): void {
		$result = $this->convertor->convert( '--brand-color', 'hsl(220, 90%, 56%)' );
		$this->assertSame( 'e-gv-color-hsl-brand-color-variable', $result['id'] );
	}

}
