<?php

namespace ElementorHtmlCssConverter\Tests\Converters\Variables;

use ElementorHtmlCssConverter\Converters\Variables\Variable_Extractor;
use PHPUnit\Framework\TestCase;

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', '/tmp/' );
}

class Test_Variable_Extractor extends TestCase {

	private Variable_Extractor $extractor;

	protected function setUp(): void {
		parent::setUp();
		$this->extractor = new Variable_Extractor();
	}

	public function test_extract_from_css__single_variable(): void {
		$css = ':root { --primary-color: #ff0000; }';
		$result = $this->extractor->extract_from_css( $css );

		$this->assertCount( 1, $result );
		$this->assertSame( '--primary-color', $result[0]['name'] );
		$this->assertSame( '#ff0000', $result[0]['value'] );
	}

	public function test_extract_from_css__multiple_variables(): void {
		$css = ':root {
			--primary: #ff0000;
			--spacing: 16px;
			--font: sans-serif;
		}';
		$result = $this->extractor->extract_from_css( $css );

		$this->assertCount( 3, $result );
		$this->assertSame( '--primary', $result[0]['name'] );
		$this->assertSame( '#ff0000', $result[0]['value'] );
		$this->assertSame( '--spacing', $result[1]['name'] );
		$this->assertSame( '16px', $result[1]['value'] );
		$this->assertSame( '--font', $result[2]['name'] );
		$this->assertSame( 'sans-serif', $result[2]['value'] );
	}

	public function test_extract_from_css__strips_comments(): void {
		$css = '/* comment */ --primary: #f00; /* another */';
		$result = $this->extractor->extract_from_css( $css );

		$this->assertCount( 1, $result );
		$this->assertSame( '--primary', $result[0]['name'] );
		$this->assertSame( '#f00', $result[0]['value'] );
	}

	public function test_extract_from_css__empty_returns_empty(): void {
		$this->assertSame( [], $this->extractor->extract_from_css( '' ) );
		$this->assertSame( [], $this->extractor->extract_from_css( '/* no variables */' ) );
	}

	public function test_extract_from_css__var_with_fallback_value(): void {
		$css = '--gap: 1rem; --width: calc(100% - var(--gap));';
		$result = $this->extractor->extract_from_css( $css );

		$this->assertCount( 2, $result );
		$this->assertSame( '--gap', $result[0]['name'] );
		$this->assertSame( '1rem', $result[0]['value'] );
		$this->assertSame( '--width', $result[1]['name'] );
		$this->assertStringContainsString( 'var(--gap)', $result[1]['value'] );
	}

	public function test_extract_from_css__malformed_css_returns_empty_array(): void {
		$malformed = '--color: ; --incomplete';
		$result = $this->extractor->extract_from_css( $malformed );

		$this->assertIsArray( $result );
		$this->assertEmpty( $result );
	}

	public function test_extract_from_css__only_semicolon_no_match(): void {
		$css = '; ; ;';
		$result = $this->extractor->extract_from_css( $css );

		$this->assertSame( [], $result );
	}

	public function test_extract_from_css__name_without_value_skipped(): void {
		$css = '--broken: ;';
		$result = $this->extractor->extract_from_css( $css );

		$this->assertEmpty( $result );
	}

}
