<?php

namespace ElementorHtmlCssConverter\Tests\Converters\Variables\Convertors;

use ElementorHtmlCssConverter\Converters\Variables\Convertors\Color_Hex_Variable_Convertor;
use PHPUnit\Framework\TestCase;

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', '/tmp/' );
}

class Test_Color_Hex_Variable_Convertor extends TestCase {

	private Color_Hex_Variable_Convertor $convertor;

	protected function setUp(): void {
		parent::setUp();
		$this->convertor = new Color_Hex_Variable_Convertor();
	}

	public function test_supports__hex3_shorthand(): void {
		$this->assertTrue( $this->convertor->supports( '--color', '#fff' ) );
		$this->assertTrue( $this->convertor->supports( '--color', '#f00' ) );
		$this->assertTrue( $this->convertor->supports( '--color', '#abc' ) );
	}

	public function test_supports__hex6_full(): void {
		$this->assertTrue( $this->convertor->supports( '--color', '#ffffff' ) );
		$this->assertTrue( $this->convertor->supports( '--color', '#ff0000' ) );
		$this->assertTrue( $this->convertor->supports( '--color', '#ABCDEF' ) );
	}

	public function test_supports__hex8_with_alpha(): void {
		$this->assertTrue( $this->convertor->supports( '--color', '#ffffff80' ) );
	}

	public function test_supports__rejects_invalid_hex(): void {
		$this->assertFalse( $this->convertor->supports( '--color', '#gg' ) );
		$this->assertFalse( $this->convertor->supports( '--color', '#12345' ) );
		$this->assertFalse( $this->convertor->supports( '--color', 'rgb(0,0,0)' ) );
		$this->assertFalse( $this->convertor->supports( '--color', '' ) );
	}

	public function test_convert__normalizes_hex3_to_hex6(): void {
		$result = $this->convertor->convert( '--color', '#fff' );
		$this->assertSame( '#ffffff', $result['value'] );
	}

	public function test_convert__normalizes_hex6_unchanged(): void {
		$result = $this->convertor->convert( '--color', '#ff5733' );
		$this->assertSame( '#ff5733', $result['value'] );
	}

	public function test_convert__preserves_hex8(): void {
		$result = $this->convertor->convert( '--color', '#ffffff80' );
		$this->assertSame( '#ffffff80', $result['value'] );
	}

	public function test_convert__lowercases_output(): void {
		$result = $this->convertor->convert( '--color', '#FFF' );
		$this->assertSame( '#ffffff', $result['value'] );
	}

	public function test_get_type__returns_color_hex(): void {
		$result = $this->convertor->convert( '--primary', '#f00' );
		$this->assertSame( 'color-hex', $result['type'] );
	}

	public function test_convert__generates_correct_id(): void {
		$result = $this->convertor->convert( '--primary-color', '#fff' );
		$this->assertSame( 'e-gv-color-hex-primary-color-variable', $result['id'] );
	}

	public function test_convert__sets_source_and_name(): void {
		$result = $this->convertor->convert( '--accent', '#000' );
		$this->assertSame( 'css-variable', $result['source'] );
		$this->assertSame( '--accent', $result['name'] );
	}

}
