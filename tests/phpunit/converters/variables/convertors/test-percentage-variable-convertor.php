<?php

namespace ElementorHtmlCssConverter\Tests\Converters\Variables\Convertors;

use ElementorHtmlCssConverter\Converters\Variables\Convertors\Percentage_Variable_Convertor;
use PHPUnit\Framework\TestCase;

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', '/tmp/' );
}

class Test_Percentage_Variable_Convertor extends TestCase {

	private Percentage_Variable_Convertor $convertor;

	protected function setUp(): void {
		parent::setUp();
		$this->convertor = new Percentage_Variable_Convertor();
	}

	public function test_supports__accepts_percentage(): void {
		$this->assertTrue( $this->convertor->supports( '--width', '50%' ) );
		$this->assertTrue( $this->convertor->supports( '--height', '100%' ) );
		$this->assertTrue( $this->convertor->supports( '--opacity', '33.5%' ) );
	}

	public function test_supports__rejects_unitless(): void {
		$this->assertFalse( $this->convertor->supports( '--line', '1.5' ) );
		$this->assertFalse( $this->convertor->supports( '--scale', '2' ) );
	}

	public function test_supports__rejects_px(): void {
		$this->assertFalse( $this->convertor->supports( '--size', '16px' ) );
	}

	public function test_supports__accepts_with_whitespace(): void {
		$this->assertTrue( $this->convertor->supports( '--width', ' 50% ' ) );
	}

	public function test_convert__normalizes_integer_percentage(): void {
		$result = $this->convertor->convert( '--width', '50%' );
		$this->assertSame( '50%', $result['value'] );
	}

	public function test_convert__normalizes_decimal_percentage(): void {
		$result = $this->convertor->convert( '--width', '33.5%' );
		$this->assertSame( '33.5%', $result['value'] );
	}

	public function test_get_type__returns_size_percentage(): void {
		$result = $this->convertor->convert( '--progress', '75%' );
		$this->assertSame( 'size-percentage', $result['type'] );
	}

	public function test_convert__generates_correct_id(): void {
		$result = $this->convertor->convert( '--progress-width', '100%' );
		$this->assertSame( 'e-gv-size-percentage-progress-width-variable', $result['id'] );
	}

	public function test_convert__sets_source_and_name(): void {
		$result = $this->convertor->convert( '--opacity-level', '80%' );
		$this->assertSame( 'css-variable', $result['source'] );
		$this->assertSame( '--opacity-level', $result['name'] );
	}

}
