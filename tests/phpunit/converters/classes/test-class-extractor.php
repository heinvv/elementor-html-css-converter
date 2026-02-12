<?php

namespace ElementorHtmlCssConverter\Tests\Converters\Classes;

use ElementorHtmlCssConverter\Converters\Classes\Class_Extractor;
use ElementorHtmlCssConverter\Converters\Css\Breakpoint_Matcher;
use PHPUnit\Framework\TestCase;

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', '/tmp/' );
}

class Test_Class_Extractor extends TestCase {

	private Class_Extractor $extractor;
	private Breakpoint_Matcher $matcher;

	protected function setUp(): void {
		parent::setUp();
		$this->extractor = new Class_Extractor();
		$this->matcher   = new Breakpoint_Matcher();
	}

	public function test_extract_from_css__single_class(): void {
		$css = '.card { padding: 30px; background-color: #fff; }';
		$result = $this->extractor->extract_from_css( $css, $this->matcher );

		$this->assertArrayHasKey( 'card', $result );
		$this->assertArrayHasKey( 'desktop', $result['card'] );
		$this->assertSame( '30px', $result['card']['desktop']['properties']['padding'] );
		$this->assertSame( '#fff', $result['card']['desktop']['properties']['background-color'] );
	}

	public function test_extract_from_css__multiple_classes(): void {
		$css = '.card { padding: 20px; }
.heading { font-size: 24px; }';
		$result = $this->extractor->extract_from_css( $css, $this->matcher );

		$this->assertArrayHasKey( 'card', $result );
		$this->assertArrayHasKey( 'heading', $result );
	}

	public function test_extract_from_css__ignores_id_selectors(): void {
		$css = '#hero { padding: 80px; }
.card { padding: 20px; }';
		$result = $this->extractor->extract_from_css( $css, $this->matcher );

		$this->assertArrayNotHasKey( 'hero', $result );
		$this->assertArrayHasKey( 'card', $result );
	}

	public function test_extract_from_css__ignores_compound_class(): void {
		$css = '.card.active { padding: 20px; }';
		$result = $this->extractor->extract_from_css( $css, $this->matcher );

		$this->assertEmpty( $result );
	}

	public function test_extract_from_css__ignores_descendant(): void {
		$css = '.parent .child { color: red; }';
		$result = $this->extractor->extract_from_css( $css, $this->matcher );

		$this->assertEmpty( $result );
	}

	public function test_extract_from_css__empty_returns_empty(): void {
		$this->assertSame( [], $this->extractor->extract_from_css( '', $this->matcher ) );
	}

	public function test_extract_from_css__media_blocks_skipped_without_elementor(): void {
		$css = '.box { padding: 20px; }
@media (max-width: 1024px) { .box { padding: 15px; } }';
		$result = $this->extractor->extract_from_css( $css, $this->matcher );

		$this->assertArrayHasKey( 'box', $result );
		$this->assertSame( '20px', $result['box']['desktop']['properties']['padding'] );
		$this->assertCount( 1, $result['box'] );
	}

}
