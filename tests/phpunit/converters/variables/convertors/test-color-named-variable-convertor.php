<?php

namespace ElementorHtmlCssConverter\Tests\Converters\Variables\Convertors;

use ElementorHtmlCssConverter\Converters\Variables\Convertors\Color_Named_Variable_Convertor;
use PHPUnit\Framework\TestCase;

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', '/tmp/' );
}

class Test_Color_Named_Variable_Convertor extends TestCase {

	private Color_Named_Variable_Convertor $convertor;

	protected function setUp(): void {
		parent::setUp();
		$this->convertor = new Color_Named_Variable_Convertor();
	}

	public function test_supports__accepts_red(): void {
		$this->assertTrue( $this->convertor->supports( '--color', 'red' ) );
	}

	public function test_supports__accepts_transparent(): void {
		$this->assertTrue( $this->convertor->supports( '--color', 'transparent' ) );
	}

	public function test_supports__accepts_case_insensitive(): void {
		$this->assertTrue( $this->convertor->supports( '--color', 'RED' ) );
		$this->assertTrue( $this->convertor->supports( '--color', 'DodgerBlue' ) );
	}

	public function test_supports__rejects_hex(): void {
		$this->assertFalse( $this->convertor->supports( '--color', '#ff0000' ) );
	}

	public function test_supports__rejects_rgb(): void {
		$this->assertFalse( $this->convertor->supports( '--color', 'rgb(255, 0, 0)' ) );
	}

	public function test_supports__rejects_unknown_named(): void {
		$this->assertFalse( $this->convertor->supports( '--color', 'notacolor' ) );
	}

	public function test_convert__converts_red_to_hex(): void {
		$result = $this->convertor->convert( '--color', 'red' );
		$this->assertSame( '#ff0000', $result['value'] );
	}

	public function test_convert__converts_transparent_to_hex8(): void {
		$result = $this->convertor->convert( '--bg', 'transparent' );
		$this->assertSame( '#00000000', $result['value'] );
	}

	public function test_get_type__returns_color_named(): void {
		$result = $this->convertor->convert( '--primary', 'blue' );
		$this->assertSame( 'color-named', $result['type'] );
	}

	public function test_convert__generates_correct_id(): void {
		$result = $this->convertor->convert( '--background-color', 'white' );
		$this->assertSame( 'e-gv-color-named-background-color-variable', $result['id'] );
	}

}
