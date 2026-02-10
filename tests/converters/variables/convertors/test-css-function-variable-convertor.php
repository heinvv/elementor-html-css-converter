<?php
namespace ElementorHtmlCssConverter\Tests\Converters\Variables\Convertors;

use ElementorHtmlCssConverter\Converters\Variables\Convertors\Css_Function_Variable_Convertor;
use PHPUnit\Framework\TestCase;

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', '/tmp/' );
}

class Test_Css_Function_Variable_Convertor extends TestCase {

	private Css_Function_Variable_Convertor $convertor;

	protected function setUp(): void {
		parent::setUp();
		$this->convertor = new Css_Function_Variable_Convertor();
	}

	public function test_supports__accepts_calc(): void {
		$this->assertTrue(
			$this->convertor->supports( '--max-width', 'calc(100% - 40px)' )
		);
	}

	public function test_supports__accepts_min(): void {
		$this->assertTrue(
			$this->convertor->supports( '--width', 'min(100%, 500px)' )
		);
	}

	public function test_supports__accepts_max(): void {
		$this->assertTrue(
			$this->convertor->supports( '--width', 'max(50vw, 300px)' )
		);
	}

	public function test_supports__accepts_clamp(): void {
		$this->assertTrue(
			$this->convertor->supports( '--font-size', 'clamp(1rem, 2.5vw, 2rem)' )
		);
	}

	public function test_supports__accepts_case_insensitive(): void {
		$this->assertTrue(
			$this->convertor->supports( '--size', 'CALC(100% - 20px)' )
		);
	}

	public function test_supports__accepts_with_leading_whitespace(): void {
		$this->assertTrue(
			$this->convertor->supports( '--size', '  calc(100% - 20px)  ' )
		);
	}

	public function test_supports__accepts_nested_functions(): void {
		$this->assertTrue(
			$this->convertor->supports( '--size', 'calc(min(100%, 500px) - 40px)' )
		);
	}

	public function test_supports__accepts_calc_with_var_reference(): void {
		$this->assertTrue(
			$this->convertor->supports( '--size', 'calc(var(--spacing) * 2)' )
		);
	}

	public function test_supports__rejects_plain_length(): void {
		$this->assertFalse(
			$this->convertor->supports( '--size', '16px' )
		);
	}

	public function test_supports__rejects_color_hex(): void {
		$this->assertFalse(
			$this->convertor->supports( '--color', '#ff0000' )
		);
	}

	public function test_supports__rejects_percentage(): void {
		$this->assertFalse(
			$this->convertor->supports( '--size', '50%' )
		);
	}

	public function test_supports__rejects_empty(): void {
		$this->assertFalse(
			$this->convertor->supports( '--size', '' )
		);
	}

	public function test_supports__rejects_linear_gradient(): void {
		$this->assertFalse(
			$this->convertor->supports( '--bg', 'linear-gradient(to right, red, blue)' )
		);
	}

	public function test_supports__rejects_url(): void {
		$this->assertFalse(
			$this->convertor->supports( '--bg', 'url(image.png)' )
		);
	}

	public function test_supports__rejects_var(): void {
		$this->assertFalse(
			$this->convertor->supports( '--size', 'var(--other)' )
		);
	}

	public function test_convert__returns_size_function_type(): void {
		$result = $this->convertor->convert( '--max-width', 'calc(100% - 40px)' );

		$this->assertSame( 'size-function', $result['type'] );
	}

	public function test_convert__preserves_calc_expression(): void {
		$result = $this->convertor->convert( '--max-width', 'calc(100% - 40px)' );

		$this->assertSame( 'calc(100% - 40px)', $result['value'] );
	}

	public function test_convert__preserves_clamp_expression(): void {
		$result = $this->convertor->convert( '--font-size', 'clamp(1rem, 2.5vw, 2rem)' );

		$this->assertSame( 'clamp(1rem, 2.5vw, 2rem)', $result['value'] );
	}

	public function test_convert__trims_whitespace(): void {
		$result = $this->convertor->convert( '--size', '  calc(100% - 20px)  ' );

		$this->assertSame( 'calc(100% - 20px)', $result['value'] );
	}

	public function test_convert__generates_correct_id(): void {
		$result = $this->convertor->convert( '--max-width', 'calc(100% - 40px)' );

		$this->assertSame( 'e-gv-size-function-max-width-variable', $result['id'] );
	}

	public function test_convert__sets_source_to_css_variable(): void {
		$result = $this->convertor->convert( '--max-width', 'calc(100% - 40px)' );

		$this->assertSame( 'css-variable', $result['source'] );
	}

	public function test_convert__preserves_original_name(): void {
		$result = $this->convertor->convert( '--max-width', 'calc(100% - 40px)' );

		$this->assertSame( '--max-width', $result['name'] );
	}
}
