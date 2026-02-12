<?php

namespace ElementorHtmlCssConverter\Tests\Converters\Css;

use ElementorHtmlCssConverter\Converters\Css\Media_Query_Parser;
use PHPUnit\Framework\TestCase;

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', '/tmp/' );
}

class Test_Media_Query_Parser extends TestCase {

	private Media_Query_Parser $parser;

	protected function setUp(): void {
		parent::setUp();
		$this->parser = new Media_Query_Parser();
	}

	public function test_parse_media_queries__max_width(): void {
		$css = '#hero { padding: 80px; }
@media (max-width: 1024px) {
  #hero { padding: 60px; }
}';
		$result = $this->parser->parse_media_queries( $css );

		$this->assertCount( 1, $result );
		$this->assertSame( 1024, $result[0]['width'] );
		$this->assertSame( 'max', $result[0]['direction'] );
		$this->assertStringContainsString( 'padding: 60px', $result[0]['css'] );
	}

	public function test_parse_media_queries__min_width(): void {
		$css = '@media (min-width: 768px) {
  .container { display: flex; }
}';
		$result = $this->parser->parse_media_queries( $css );

		$this->assertCount( 1, $result );
		$this->assertSame( 768, $result[0]['width'] );
		$this->assertSame( 'min', $result[0]['direction'] );
	}

	public function test_parse_media_queries__multiple(): void {
		$css = '@media (max-width: 1024px) { #a { x: 1; } }
@media (max-width: 767px) { #b { y: 2; } }';
		$result = $this->parser->parse_media_queries( $css );

		$this->assertCount( 2, $result );
		$this->assertSame( 1024, $result[0]['width'] );
		$this->assertSame( 767, $result[1]['width'] );
	}

	public function test_parse_media_queries__screen_and(): void {
		$css = '@media screen and (max-width: 880px) {
  #box { width: 90%; }
}';
		$result = $this->parser->parse_media_queries( $css );

		$this->assertCount( 1, $result );
		$this->assertSame( 880, $result[0]['width'] );
		$this->assertSame( 'max', $result[0]['direction'] );
	}

	public function test_parse_media_queries__empty_returns_empty(): void {
		$this->assertSame( [], $this->parser->parse_media_queries( '' ) );
	}

	public function test_extract_desktop_css__removes_media_blocks(): void {
		$css = '#hero { padding: 80px; }
@media (max-width: 1024px) {
  #hero { padding: 60px; }
}';
		$desktop = $this->parser->extract_desktop_css( $css );

		$this->assertStringContainsString( '#hero { padding: 80px; }', $desktop );
		$this->assertStringNotContainsString( '@media', $desktop );
	}

	public function test_extract_desktop_css__only_desktop_returns_unchanged(): void {
		$css = '#hero { padding: 80px; font-size: 48px; }';
		$desktop = $this->parser->extract_desktop_css( $css );

		$this->assertSame( trim( $css ), $desktop );
	}

}
