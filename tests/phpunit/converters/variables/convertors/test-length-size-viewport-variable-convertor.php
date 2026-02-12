<?php

namespace ElementorHtmlCssConverter\Tests\Converters\Variables\Convertors;

use ElementorHtmlCssConverter\Converters\Variables\Convertors\Length_Size_Viewport_Variable_Convertor;
use PHPUnit\Framework\TestCase;

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', '/tmp/' );
}

class Test_Length_Size_Viewport_Variable_Convertor extends TestCase {

	private Length_Size_Viewport_Variable_Convertor $convertor;

	protected function setUp(): void {
		parent::setUp();
		$this->convertor = new Length_Size_Viewport_Variable_Convertor();
	}

	public function test_supports__accepts_rem(): void {
		$this->assertTrue( $this->convertor->supports( '--size', '1rem' ) );
		$this->assertTrue( $this->convertor->supports( '--size', '1.5rem' ) );
	}

	public function test_supports__accepts_em(): void {
		$this->assertTrue( $this->convertor->supports( '--size', '16px' ) );
		$this->assertTrue( $this->convertor->supports( '--size', '1em' ) );
	}

	public function test_supports__accepts_viewport_units(): void {
		$this->assertTrue( $this->convertor->supports( '--width', '10vw' ) );
		$this->assertTrue( $this->convertor->supports( '--height', '50vh' ) );
		$this->assertTrue( $this->convertor->supports( '--size', '100vmin' ) );
		$this->assertTrue( $this->convertor->supports( '--size', '100vmax' ) );
	}

	public function test_supports__accepts_ch(): void {
		$this->assertTrue( $this->convertor->supports( '--width', '20ch' ) );
	}

	public function test_supports__rejects_unitless(): void {
		$this->assertFalse( $this->convertor->supports( '--size', '16' ) );
	}

	public function test_supports__rejects_percentage(): void {
		$this->assertFalse( $this->convertor->supports( '--width', '100%' ) );
	}

	public function test_convert__normalizes_decimals(): void {
		$result = $this->convertor->convert( '--size', '16.0px' );
		$this->assertSame( '16px', $result['value'] );
	}

	public function test_convert__preserves_decimal_when_needed(): void {
		$result = $this->convertor->convert( '--size', '1.5rem' );
		$this->assertSame( '1.5rem', $result['value'] );
	}

	public function test_get_type__returns_size_length_viewport(): void {
		$result = $this->convertor->convert( '--spacing', '1rem' );
		$this->assertSame( 'size-length-viewport', $result['type'] );
	}

	public function test_convert__generates_correct_id(): void {
		$result = $this->convertor->convert( '--container-padding', '16px' );
		$this->assertSame( 'e-gv-size-length-viewport-container-padding-variable', $result['id'] );
	}

	public function test_convert__sets_source_and_name(): void {
		$result = $this->convertor->convert( '--gap-size', '1rem' );
		$this->assertSame( 'css-variable', $result['source'] );
		$this->assertSame( '--gap-size', $result['name'] );
	}

}
