<?php

namespace ElementorHtmlCssConverter\Tests\Converters\Css;

use ElementorHtmlCssConverter\Converters\Css\Breakpoint_Matcher;
use ElementorHtmlCssConverter\Converters\Html\Id_Style_Extractor;
use ElementorHtmlCssConverter\Tests\TestCase\Test_Constants;
use PHPUnit\Framework\TestCase;

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', '/tmp/' );
}

class Test_Id_Style_Extractor extends TestCase {

	private Id_Style_Extractor $extractor;
	private Breakpoint_Matcher $matcher;

	protected function setUp(): void {
		parent::setUp();
		$this->extractor = new Id_Style_Extractor();
		$this->matcher   = new Breakpoint_Matcher();
	}

	public function test_extract_style_tags__from_dom(): void {
		$dom = new \DOMDocument();
		$dom->loadHTML( '<html><head><style>#hero { padding: 80px; }</style></head><body></body></html>' );
		$css = $this->extractor->extract_style_tags( $dom );

		$this->assertStringContainsString( '#hero { padding: 80px; }', $css );
	}

	public function test_extract_style_tags__multiple_style_tags(): void {
		$dom = new \DOMDocument();
		$dom->loadHTML( '<html><head><style>#a { x: 1; }</style><style>#b { y: 2; }</style></head></html>' );
		$css = $this->extractor->extract_style_tags( $dom );

		$this->assertStringContainsString( '#a { x: 1; }', $css );
		$this->assertStringContainsString( '#b { y: 2; }', $css );
	}

	public function test_parse_id_rules__desktop_only(): void {
		$css = '#hero { padding: 80px 20px; font-size: 48px; }';
		$result = $this->extractor->parse_id_rules( $css, $this->matcher );

		$this->assertArrayHasKey( 'hero', $result );
		$this->assertArrayHasKey( 'desktop', $result['hero'] );
		$this->assertSame( '80px 20px', $result['hero']['desktop']['padding'] );
		$this->assertSame( '48px', $result['hero']['desktop']['font-size'] );
	}

	public function test_parse_id_rules__empty_returns_empty(): void {
		$this->assertSame( [], $this->extractor->parse_id_rules( '', $this->matcher ) );
	}

	public function test_parse_id_rules__media_blocks_skipped_without_elementor(): void {
		$css = '#hero { padding: 80px; }
@media (max-width: ' . Test_Constants::DESKTOP_TO_TABLET_BREAKPOINT . 'px) { #hero { padding: 60px; } }';
		$result = $this->extractor->parse_id_rules( $css, $this->matcher );

		$this->assertArrayHasKey( 'hero', $result );
		$this->assertArrayHasKey( 'desktop', $result['hero'] );
		$this->assertSame( '80px', $result['hero']['desktop']['padding'] );
		$this->assertCount( 1, $result['hero'] );
	}

	public function test_parse_id_rules__ignores_class_selectors(): void {
		$css = '#hero { padding: 80px; }
.other { color: red; }';
		$result = $this->extractor->parse_id_rules( $css, $this->matcher );

		$this->assertArrayHasKey( 'hero', $result );
		$this->assertArrayNotHasKey( 'other', $result );
	}

}
