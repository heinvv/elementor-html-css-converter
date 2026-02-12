<?php
namespace ElementorHtmlCssConverter\Tests\Converters\Variables\Convertors;

use ElementorHtmlCssConverter\Converters\Variables\Convertors\Opacity_Variable_Convertor;
use PHPUnit\Framework\TestCase;

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', '/tmp/' );
}

class Test_Opacity_Variable_Convertor extends TestCase {

	private Opacity_Variable_Convertor $convertor;

	protected function setUp(): void {
		parent::setUp();
		$this->convertor = new Opacity_Variable_Convertor();
	}

	public function test_supports__accepts_opacity_with_decimal(): void {
		$this->assertTrue(
			$this->convertor->supports( '--opacity', '0.75' )
		);
	}

	public function test_supports__accepts_card_opacity(): void {
		$this->assertTrue(
			$this->convertor->supports( '--card-opacity', '0.5' )
		);
	}

	public function test_supports__accepts_hover_opacity_level(): void {
		$this->assertTrue(
			$this->convertor->supports( '--hover-opacity-level', '0.9' )
		);
	}

	public function test_supports__accepts_zero(): void {
		$this->assertTrue(
			$this->convertor->supports( '--opacity', '0' )
		);
	}

	public function test_supports__accepts_one(): void {
		$this->assertTrue(
			$this->convertor->supports( '--opacity', '1' )
		);
	}

	public function test_supports__accepts_one_point_zero(): void {
		$this->assertTrue(
			$this->convertor->supports( '--opacity', '1.0' )
		);
	}

	public function test_supports__accepts_zero_point_zero(): void {
		$this->assertTrue(
			$this->convertor->supports( '--opacity', '0.0' )
		);
	}

	public function test_supports__accepts_name_case_insensitive(): void {
		$this->assertTrue(
			$this->convertor->supports( '--OPACITY', '0.5' )
		);
	}

	public function test_supports__accepts_with_whitespace_in_value(): void {
		$this->assertTrue(
			$this->convertor->supports( '--opacity', ' 0.75 ' )
		);
	}

	public function test_supports__rejects_name_without_opacity(): void {
		$this->assertFalse(
			$this->convertor->supports( '--line-height', '0.75' )
		);
	}

	public function test_supports__rejects_value_above_one(): void {
		$this->assertFalse(
			$this->convertor->supports( '--opacity', '2' )
		);
	}

	public function test_supports__rejects_value_with_percentage(): void {
		$this->assertFalse(
			$this->convertor->supports( '--opacity', '50%' )
		);
	}

	public function test_supports__rejects_non_numeric_value(): void {
		$this->assertFalse(
			$this->convertor->supports( '--opacity', 'abc' )
		);
	}

	public function test_supports__rejects_empty_value(): void {
		$this->assertFalse(
			$this->convertor->supports( '--opacity', '' )
		);
	}

	public function test_supports__rejects_value_with_unit(): void {
		$this->assertFalse(
			$this->convertor->supports( '--opacity', '0.5px' )
		);
	}

	public function test_supports__rejects_negative_below_zero(): void {
		$this->assertFalse(
			$this->convertor->supports( '--opacity', '-0.5' )
		);
	}

	public function test_supports__rejects_value_above_one_decimal(): void {
		$this->assertFalse(
			$this->convertor->supports( '--opacity', '1.5' )
		);
	}

	public function test_convert__converts_075_to_75_percent(): void {
		$result = $this->convertor->convert( '--opacity', '0.75' );

		$this->assertSame( '75%', $result['value'] );
	}

	public function test_convert__converts_zero_to_zero_percent(): void {
		$result = $this->convertor->convert( '--opacity', '0' );

		$this->assertSame( '0%', $result['value'] );
	}

	public function test_convert__converts_one_to_100_percent(): void {
		$result = $this->convertor->convert( '--opacity', '1' );

		$this->assertSame( '100%', $result['value'] );
	}

	public function test_convert__converts_05_to_50_percent(): void {
		$result = $this->convertor->convert( '--opacity', '0.5' );

		$this->assertSame( '50%', $result['value'] );
	}

	public function test_convert__converts_0333_to_33_3_percent(): void {
		$result = $this->convertor->convert( '--opacity', '0.333' );

		$this->assertSame( '33.3%', $result['value'] );
	}

	public function test_convert__converts_1_point_0_to_100_percent(): void {
		$result = $this->convertor->convert( '--opacity', '1.0' );

		$this->assertSame( '100%', $result['value'] );
	}

	public function test_get_type__returns_size_percentage(): void {
		$result = $this->convertor->convert( '--opacity', '0.5' );

		$this->assertSame( 'size-percentage', $result['type'] );
	}

	public function test_convert__generates_correct_id(): void {
		$result = $this->convertor->convert( '--card-opacity', '0.75' );

		$this->assertSame( 'e-gv-size-percentage-card-opacity-variable', $result['id'] );
	}

	public function test_convert__sets_source_to_css_variable(): void {
		$result = $this->convertor->convert( '--opacity', '0.5' );

		$this->assertSame( 'css-variable', $result['source'] );
	}

	public function test_convert__preserves_original_name(): void {
		$result = $this->convertor->convert( '--card-opacity', '0.75' );

		$this->assertSame( '--card-opacity', $result['name'] );
	}
}
