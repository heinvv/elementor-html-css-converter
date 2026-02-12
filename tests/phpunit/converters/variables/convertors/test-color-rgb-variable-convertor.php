<?php

namespace ElementorHtmlCssConverter\Tests\Converters\Variables\Convertors;

use ElementorHtmlCssConverter\Converters\Variables\Convertors\Color_Rgb_Variable_Convertor;
use PHPUnit\Framework\TestCase;

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', '/tmp/' );
}

class Test_Color_Rgb_Variable_Convertor extends TestCase {

	private Color_Rgb_Variable_Convertor $convertor;

	protected function setUp(): void {
		parent::setUp();
		$this->convertor = new Color_Rgb_Variable_Convertor();
	}

	public function test_supports__accepts_rgb(): void {
		$this->assertTrue( $this->convertor->supports( '--color', 'rgb(255, 0, 0)' ) );
		$this->assertTrue( $this->convertor->supports( '--color', 'rgb(0, 128, 255)' ) );
	}

	public function test_supports__rejects_rgba(): void {
		$this->assertFalse( $this->convertor->supports( '--color', 'rgba(255, 0, 0, 0.5)' ) );
	}

	public function test_supports__rejects_hex(): void {
		$this->assertFalse( $this->convertor->supports( '--color', '#ff0000' ) );
	}

	public function test_convert__normalizes_spacing(): void {
		$result = $this->convertor->convert( '--primary', 'rgb(255, 0, 0)' );
		$this->assertSame( 'rgb(255, 0, 0)', $result['value'] );
	}

	public function test_get_type__returns_color_rgb(): void {
		$result = $this->convertor->convert( '--color', 'rgb(100, 100, 100)' );
		$this->assertSame( 'color-rgb', $result['type'] );
	}

	public function test_convert__generates_correct_id(): void {
		$result = $this->convertor->convert( '--background', 'rgb(255, 255, 255)' );
		$this->assertSame( 'e-gv-color-rgb-background-variable', $result['id'] );
	}

}
