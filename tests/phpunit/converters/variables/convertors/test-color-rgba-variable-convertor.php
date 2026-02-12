<?php

namespace ElementorHtmlCssConverter\Tests\Converters\Variables\Convertors;

use ElementorHtmlCssConverter\Converters\Variables\Convertors\Color_Rgba_Variable_Convertor;
use PHPUnit\Framework\TestCase;

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', '/tmp/' );
}

class Test_Color_Rgba_Variable_Convertor extends TestCase {

	private Color_Rgba_Variable_Convertor $convertor;

	protected function setUp(): void {
		parent::setUp();
		$this->convertor = new Color_Rgba_Variable_Convertor();
	}

	public function test_supports__accepts_rgba(): void {
		$this->assertTrue( $this->convertor->supports( '--color', 'rgba(255, 0, 0, 0.5)' ) );
		$this->assertTrue( $this->convertor->supports( '--color', 'rgba(0, 128, 255, 1)' ) );
		$this->assertTrue( $this->convertor->supports( '--color', 'rgba(0, 0, 0, 0)' ) );
	}

	public function test_supports__rejects_rgb(): void {
		$this->assertFalse( $this->convertor->supports( '--color', 'rgb(255, 0, 0)' ) );
	}

	public function test_convert__normalizes_spacing(): void {
		$result = $this->convertor->convert( '--overlay', 'rgba(255, 0, 0, 0.5)' );
		$this->assertSame( 'rgba(255, 0, 0, 0.5)', $result['value'] );
	}

	public function test_get_type__returns_color_rgba(): void {
		$result = $this->convertor->convert( '--color', 'rgba(100, 100, 100, 0.8)' );
		$this->assertSame( 'color-rgba', $result['type'] );
	}

	public function test_convert__generates_correct_id(): void {
		$result = $this->convertor->convert( '--background-overlay', 'rgba(0, 0, 0, 0.5)' );
		$this->assertSame( 'e-gv-color-rgba-background-overlay-variable', $result['id'] );
	}

}
