<?php

namespace ElementorHtmlCssConverter\Tests\Converters\Css;

use ElementorHtmlCssConverter\Converters\Css\Media_Query_Parser;
use ElementorHtmlCssConverter\Tests\TestCase\Test_Constants;
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

	public function media_query_parse_provider(): array {
		return [
			'max_width' => [
				'#hero { padding: 80px; }
@media (max-width: ' . Test_Constants::DESKTOP_TO_TABLET_BREAKPOINT . 'px) {
  #hero { padding: 60px; }
}',
				[
					[ 'width' => Test_Constants::DESKTOP_TO_TABLET_BREAKPOINT, 'direction' => 'max', 'contains' => 'padding: 60px' ],
				],
			],
			'min_width' => [
				'@media (min-width: ' . Test_Constants::MIN_WIDTH_SAMPLE . 'px) {
  .container { display: flex; }
}',
				[
					[ 'width' => Test_Constants::MIN_WIDTH_SAMPLE, 'direction' => 'min', 'contains' => null ],
				],
			],
			'multiple' => [
				'@media (max-width: ' . Test_Constants::DESKTOP_TO_TABLET_BREAKPOINT . 'px) { #a { x: 1; } }
@media (max-width: ' . Test_Constants::TABLET_TO_MOBILE_BREAKPOINT . 'px) { #b { y: 2; } }',
				[
					[ 'width' => Test_Constants::DESKTOP_TO_TABLET_BREAKPOINT, 'direction' => 'max', 'contains' => null ],
					[ 'width' => Test_Constants::TABLET_TO_MOBILE_BREAKPOINT, 'direction' => 'max', 'contains' => null ],
				],
			],
			'screen_and' => [
				'@media screen and (max-width: ' . Test_Constants::TOLERANCE_BREAKPOINT . 'px) {
  #box { width: 90%; }
}',
				[
					[ 'width' => Test_Constants::TOLERANCE_BREAKPOINT, 'direction' => 'max', 'contains' => null ],
				],
			],
		];
	}

	/**
	 * @dataProvider media_query_parse_provider
	 */
	public function test_parse_media_queries__extracts_width_and_direction( string $css, array $expected ): void {
		$result = $this->parser->parse_media_queries( $css );

		$this->assertCount( count( $expected ), $result );
		foreach ( $expected as $i => $exp ) {
			$this->assertSame( $exp['width'], $result[ $i ]['width'], "Result $i width" );
			$this->assertSame( $exp['direction'], $result[ $i ]['direction'], "Result $i direction" );
			if ( isset( $exp['contains'] ) && $exp['contains'] !== null ) {
				$this->assertStringContainsString( $exp['contains'], $result[ $i ]['css'], "Result $i css content" );
			}
		}
	}

	public function test_parse_media_queries__empty_returns_empty(): void {
		$this->assertSame( [], $this->parser->parse_media_queries( '' ) );
	}

	public function test_extract_desktop_css__removes_media_blocks(): void {
		$css = '#hero { padding: 80px; }
@media (max-width: ' . Test_Constants::DESKTOP_TO_TABLET_BREAKPOINT . 'px) {
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

	public function test_parse_media_queries__invalid_media_syntax_returns_empty(): void {
		$css = '@media invalid { #x { y: 1; } }';
		$result = $this->parser->parse_media_queries( $css );

		$this->assertSame( [], $result );
	}

	public function test_parse_media_queries__min_height_ignored(): void {
		$css = '@media (min-height: 600px) { .tall { height: 100vh; } }';
		$result = $this->parser->parse_media_queries( $css );

		$this->assertSame( [], $result );
	}

}

