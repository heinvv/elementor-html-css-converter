<?php
namespace ElementorHtmlCssConverter\Tests\Converters\Variables\Convertors;

use ElementorHtmlCssConverter\Converters\Variables\Convertors\Line_Height_Variable_Convertor;
use PHPUnit\Framework\TestCase;

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', '/tmp/' );
}

class Test_Line_Height_Variable_Convertor extends TestCase {

	private Line_Height_Variable_Convertor $convertor;

	protected function setUp(): void {
		parent::setUp();
		$this->convertor = new Line_Height_Variable_Convertor();
	}

	public function test_supports__accepts_line_height_with_decimal(): void {
		$this->assertTrue(
			$this->convertor->supports( '--line-height', '1.5' )
		);
	}

	public function test_supports__accepts_body_line_height(): void {
		$this->assertTrue(
			$this->convertor->supports( '--body-line-height', '1.2' )
		);
	}

	public function test_supports__accepts_lineheight_no_dash(): void {
		$this->assertTrue(
			$this->convertor->supports( '--lineheight', '2' )
		);
	}

	public function test_supports__accepts_heading_lineheight(): void {
		$this->assertTrue(
			$this->convertor->supports( '--heading-lineheight', '1.75' )
		);
	}

	public function test_supports__accepts_integer_value(): void {
		$this->assertTrue(
			$this->convertor->supports( '--line-height', '2' )
		);
	}

	public function test_supports__accepts_name_case_insensitive(): void {
		$this->assertTrue(
			$this->convertor->supports( '--LINE-HEIGHT', '1.5' )
		);
	}

	public function test_supports__accepts_lineheight_case_insensitive(): void {
		$this->assertTrue(
			$this->convertor->supports( '--LINEHEIGHT', '1.5' )
		);
	}

	public function test_supports__accepts_with_whitespace_in_value(): void {
		$this->assertTrue(
			$this->convertor->supports( '--line-height', ' 1.5 ' )
		);
	}

	public function test_supports__rejects_name_without_lineheight(): void {
		$this->assertFalse(
			$this->convertor->supports( '--font-size', '1.5' )
		);
	}

	public function test_supports__rejects_value_with_em_unit(): void {
		$this->assertFalse(
			$this->convertor->supports( '--line-height', '1.5em' )
		);
	}

	public function test_supports__rejects_value_with_px_unit(): void {
		$this->assertFalse(
			$this->convertor->supports( '--line-height', '24px' )
		);
	}

	public function test_supports__rejects_non_numeric_value(): void {
		$this->assertFalse(
			$this->convertor->supports( '--line-height', 'abc' )
		);
	}

	public function test_supports__rejects_empty_value(): void {
		$this->assertFalse(
			$this->convertor->supports( '--line-height', '' )
		);
	}

	public function test_supports__rejects_percentage_value(): void {
		$this->assertFalse(
			$this->convertor->supports( '--line-height', '150%' )
		);
	}

	public function test_supports__rejects_value_with_rem_unit(): void {
		$this->assertFalse(
			$this->convertor->supports( '--line-height', '1.5rem' )
		);
	}

	public function test_convert__converts_1_5_to_1_5em(): void {
		$result = $this->convertor->convert( '--line-height', '1.5' );

		$this->assertSame( '1.5em', $result['value'] );
	}

	public function test_convert__converts_2_to_2em(): void {
		$result = $this->convertor->convert( '--line-height', '2' );

		$this->assertSame( '2em', $result['value'] );
	}

	public function test_convert__converts_1_2_to_1_2em(): void {
		$result = $this->convertor->convert( '--line-height', '1.2' );

		$this->assertSame( '1.2em', $result['value'] );
	}

	public function test_convert__normalizes_2_0_to_2em(): void {
		$result = $this->convertor->convert( '--line-height', '2.0' );

		$this->assertSame( '2em', $result['value'] );
	}

	public function test_convert__converts_1_0_to_1em(): void {
		$result = $this->convertor->convert( '--line-height', '1.0' );

		$this->assertSame( '1em', $result['value'] );
	}

	public function test_get_type__returns_size_length_viewport(): void {
		$result = $this->convertor->convert( '--line-height', '1.5' );

		$this->assertSame( 'size-length-viewport', $result['type'] );
	}

	public function test_convert__generates_correct_id(): void {
		$result = $this->convertor->convert( '--body-line-height', '1.5' );

		$this->assertSame( 'e-gv-size-length-viewport-body-line-height-variable', $result['id'] );
	}

	public function test_convert__sets_source_to_css_variable(): void {
		$result = $this->convertor->convert( '--line-height', '1.5' );

		$this->assertSame( 'css-variable', $result['source'] );
	}

	public function test_convert__preserves_original_name(): void {
		$result = $this->convertor->convert( '--body-line-height', '1.5' );

		$this->assertSame( '--body-line-height', $result['name'] );
	}
}
