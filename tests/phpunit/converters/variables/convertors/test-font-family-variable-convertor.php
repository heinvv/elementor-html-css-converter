<?php
namespace ElementorHtmlCssConverter\Tests\Converters\Variables\Convertors;

use ElementorHtmlCssConverter\Converters\Variables\Convertors\Font_Family_Variable_Convertor;
use PHPUnit\Framework\TestCase;

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', '/tmp/' );
}

class Test_Font_Family_Variable_Convertor extends TestCase {

	private Font_Family_Variable_Convertor $convertor;

	protected function setUp(): void {
		parent::setUp();
		$this->convertor = new Font_Family_Variable_Convertor();
	}

	private function requires_elementor_fonts(): void {
		if ( ! class_exists( '\Elementor\Fonts' ) ) {
			$this->markTestSkipped( 'Requires Elementor Fonts class.' );
		}
	}

	public function test_supports__generic_family_serif(): void {
		$this->assertTrue(
			$this->convertor->supports( '--font', 'serif' )
		);
	}

	public function test_supports__generic_family_sans_serif(): void {
		$this->assertTrue(
			$this->convertor->supports( '--font', 'sans-serif' )
		);
	}

	public function test_supports__generic_family_monospace(): void {
		$this->assertTrue(
			$this->convertor->supports( '--font', 'monospace' )
		);
	}

	public function test_supports__generic_family_cursive(): void {
		$this->assertTrue(
			$this->convertor->supports( '--font', 'cursive' )
		);
	}

	public function test_supports__generic_family_fantasy(): void {
		$this->assertTrue(
			$this->convertor->supports( '--font', 'fantasy' )
		);
	}

	public function test_supports__rejects_empty_value(): void {
		$this->assertFalse(
			$this->convertor->supports( '--font', '' )
		);
	}

	public function test_supports__rejects_whitespace_only(): void {
		$this->assertFalse(
			$this->convertor->supports( '--font', '   ' )
		);
	}

	public function test_supports__rejects_inherit(): void {
		$this->assertFalse(
			$this->convertor->supports( '--font', 'inherit' )
		);
	}

	public function test_supports__rejects_initial(): void {
		$this->assertFalse(
			$this->convertor->supports( '--font', 'initial' )
		);
	}

	public function test_supports__rejects_unset(): void {
		$this->assertFalse(
			$this->convertor->supports( '--font', 'unset' )
		);
	}

	public function test_supports__rejects_revert(): void {
		$this->assertFalse(
			$this->convertor->supports( '--font', 'revert' )
		);
	}

	public function test_supports__rejects_css_keywords_case_insensitive(): void {
		$this->assertFalse(
			$this->convertor->supports( '--font', 'INHERIT' )
		);
	}

	public function test_supports__rejects_unregistered_quoted_font(): void {
		$this->assertFalse(
			$this->convertor->supports( '--font', "'The Most Random Font'" )
		);
	}

	public function test_supports__rejects_unregistered_font_stack_without_generic(): void {
		$this->assertFalse(
			$this->convertor->supports( '--font', '"UnknownFont", "AnotherUnknown"' )
		);
	}

	public function test_supports__accepts_unregistered_font_stack_with_generic_fallback(): void {
		$this->assertTrue(
			$this->convertor->supports( '--font', '"UnknownFont", sans-serif' )
		);
	}

	public function test_supports__registered_google_font(): void {
		$this->requires_elementor_fonts();

		$this->assertTrue(
			$this->convertor->supports( '--heading-font', '"Roboto"' )
		);
	}

	public function test_supports__registered_system_font(): void {
		$this->requires_elementor_fonts();

		$this->assertTrue(
			$this->convertor->supports( '--font', 'Arial' )
		);
	}

	public function test_supports__registered_font_in_stack(): void {
		$this->requires_elementor_fonts();

		$this->assertTrue(
			$this->convertor->supports( '--font', '"Roboto", Arial, sans-serif' )
		);
	}

	public function test_supports__font_stack_comma_separated(): void {
		$this->requires_elementor_fonts();

		$this->assertTrue(
			$this->convertor->supports( '--font', 'Lato, sans-serif' )
		);
	}

	public function test_get_type__returns_font_family(): void {
		$result = $this->convertor->convert( '--font', 'monospace' );

		$this->assertSame( 'font-family', $result['type'] );
	}

	public function test_convert__returns_generic_family_when_only_option(): void {
		$result = $this->convertor->convert( '--font', 'sans-serif' );

		$this->assertSame( 'sans-serif', $result['value'] );
	}

	public function test_convert__returns_generic_family_monospace(): void {
		$result = $this->convertor->convert( '--code-font', 'monospace' );

		$this->assertSame( 'monospace', $result['value'] );
	}

	public function test_convert__handles_font_stack_with_only_generics(): void {
		$result = $this->convertor->convert( '--font', 'serif, sans-serif' );

		$this->assertSame( 'serif', $result['value'] );
	}

	public function test_convert__falls_back_to_generic_when_primary_unregistered(): void {
		$result = $this->convertor->convert( '--font', '"UnknownFont", sans-serif' );

		$this->assertSame( 'sans-serif', $result['value'] );
	}

	public function test_convert__generates_correct_id(): void {
		$result = $this->convertor->convert( '--heading-font', 'monospace' );

		$this->assertSame( 'e-gv-font-family-heading-font-variable', $result['id'] );
	}

	public function test_convert__sets_source_to_css_variable(): void {
		$result = $this->convertor->convert( '--heading-font', 'serif' );

		$this->assertSame( 'css-variable', $result['source'] );
	}

	public function test_convert__preserves_original_name(): void {
		$result = $this->convertor->convert( '--heading-font', 'monospace' );

		$this->assertSame( '--heading-font', $result['name'] );
	}

	public function test_convert__extracts_registered_font_from_quoted_value(): void {
		$this->requires_elementor_fonts();

		$result = $this->convertor->convert( '--heading-font', '"Roboto"' );

		$this->assertSame( 'Roboto', $result['value'] );
	}

	public function test_convert__extracts_registered_font_from_single_quoted_value(): void {
		$this->requires_elementor_fonts();

		$result = $this->convertor->convert( '--heading-font', "'Open Sans'" );

		$this->assertSame( 'Open Sans', $result['value'] );
	}

	public function test_convert__extracts_first_registered_font_from_stack(): void {
		$this->requires_elementor_fonts();

		$result = $this->convertor->convert( '--body-font', '"Roboto", Arial, sans-serif' );

		$this->assertSame( 'Roboto', $result['value'] );
	}

	public function test_convert__strips_quotes_from_multi_word_font(): void {
		$this->requires_elementor_fonts();

		$result = $this->convertor->convert( '--font', '"Times New Roman", serif' );

		$this->assertSame( 'Times New Roman', $result['value'] );
	}

	public function test_convert__handles_complex_font_stack(): void {
		$this->requires_elementor_fonts();

		$result = $this->convertor->convert(
			'--font',
			'"Helvetica Neue", Helvetica, Arial, sans-serif'
		);

		$this->assertSame( 'Helvetica Neue', $result['value'] );
	}

	public function test_convert__skips_generic_for_registered_font_later_in_stack(): void {
		$this->requires_elementor_fonts();

		$result = $this->convertor->convert( '--font', 'serif, "Georgia"' );

		$this->assertSame( 'Georgia', $result['value'] );
	}

	public function test_is_supported_font__returns_true_for_generic_families(): void {
		$this->assertTrue( $this->convertor->is_supported_font( 'serif' ) );
		$this->assertTrue( $this->convertor->is_supported_font( 'sans-serif' ) );
		$this->assertTrue( $this->convertor->is_supported_font( 'monospace' ) );
		$this->assertTrue( $this->convertor->is_supported_font( 'cursive' ) );
		$this->assertTrue( $this->convertor->is_supported_font( 'fantasy' ) );
	}

	public function test_is_supported_font__returns_false_for_unknown_font_without_elementor(): void {
		if ( class_exists( '\Elementor\Fonts' ) ) {
			$this->markTestSkipped( 'Elementor Fonts class is loaded, cannot test absence.' );
		}

		$this->assertFalse(
			$this->convertor->is_supported_font( 'The Most Random Font' )
		);
	}
}
