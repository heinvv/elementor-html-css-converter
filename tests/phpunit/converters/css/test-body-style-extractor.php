<?php

namespace ElementorHtmlCssConverter\Tests\Converters\Css;

use ElementorHtmlCssConverter\Converters\Css\Breakpoint_Matcher;
use ElementorHtmlCssConverter\Converters\Html\Id_Style_Extractor;
use PHPUnit\Framework\TestCase;

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', '/tmp/' );
}

class Test_Body_Style_Extractor extends TestCase {

	private Id_Style_Extractor $extractor;
	private Breakpoint_Matcher $matcher;

	protected function setUp(): void {
		parent::setUp();
		$this->extractor = new Id_Style_Extractor();
		$this->matcher   = new Breakpoint_Matcher();
	}

	public function test_parse_body_rules__desktop_only(): void {
		$css    = 'body { background-color: #f4f4f4; padding: 20px; }';
		$result = $this->extractor->parse_body_rules( $css, $this->matcher );

		$this->assertArrayHasKey( 'breakpoint_rules', $result );
		$this->assertArrayHasKey( 'pseudo_rules', $result );
		$this->assertArrayHasKey( 'desktop', $result['breakpoint_rules'] );
		$this->assertSame( '#f4f4f4', $result['breakpoint_rules']['desktop']['background-color'] );
		$this->assertSame( '20px', $result['breakpoint_rules']['desktop']['padding'] );
	}

	public function test_parse_body_rules__empty_returns_empty(): void {
		$result = $this->extractor->parse_body_rules( '', $this->matcher );

		$this->assertSame( [ 'breakpoint_rules' => [], 'pseudo_rules' => [] ], $result );
	}

	public function test_parse_body_rules__ignores_id_selectors(): void {
		$css    = 'body { color: red; } #hero { font-size: 48px; }';
		$result = $this->extractor->parse_body_rules( $css, $this->matcher );

		$this->assertArrayHasKey( 'desktop', $result['breakpoint_rules'] );
		$this->assertArrayHasKey( 'color', $result['breakpoint_rules']['desktop'] );
		$this->assertArrayNotHasKey( 'font-size', $result['breakpoint_rules']['desktop'] );
	}

	public function test_parse_body_rules__ignores_class_selectors(): void {
		$css    = 'body { margin: 0; } .foo { padding: 10px; }';
		$result = $this->extractor->parse_body_rules( $css, $this->matcher );

		$this->assertArrayHasKey( 'desktop', $result['breakpoint_rules'] );
		$this->assertArrayHasKey( 'margin', $result['breakpoint_rules']['desktop'] );
		$this->assertArrayNotHasKey( 'padding', $result['breakpoint_rules']['desktop'] );
	}

	public function test_parse_body_rules__hover_pseudo_state(): void {
		$css    = 'body:hover { background: #000; }';
		$result = $this->extractor->parse_body_rules( $css, $this->matcher );

		$this->assertArrayHasKey( 'hover', $result['pseudo_rules'] );
		$this->assertArrayHasKey( 'desktop', $result['pseudo_rules']['hover'] );
		$this->assertSame( '#000', $result['pseudo_rules']['hover']['desktop']['background'] );
	}

	public function test_parse_body_rules__focus_pseudo_state(): void {
		$css    = 'body:focus { outline: 2px solid blue; }';
		$result = $this->extractor->parse_body_rules( $css, $this->matcher );

		$this->assertArrayHasKey( 'focus', $result['pseudo_rules'] );
		$this->assertArrayHasKey( 'desktop', $result['pseudo_rules']['focus'] );
		$this->assertSame( '2px solid blue', $result['pseudo_rules']['focus']['desktop']['outline'] );
	}

	public function test_parse_body_rules__merges_multiple_body_blocks(): void {
		$css    = 'body { color: red; } body { font-size: 16px; }';
		$result = $this->extractor->parse_body_rules( $css, $this->matcher );

		$this->assertArrayHasKey( 'desktop', $result['breakpoint_rules'] );
		$this->assertSame( 'red', $result['breakpoint_rules']['desktop']['color'] );
		$this->assertSame( '16px', $result['breakpoint_rules']['desktop']['font-size'] );
	}

	public function test_parse_body_rules__no_body_rules_in_css(): void {
		$css    = '#hero { padding: 20px; } .card { margin: 10px; }';
		$result = $this->extractor->parse_body_rules( $css, $this->matcher );

		$this->assertEmpty( $result['breakpoint_rules'] );
		$this->assertEmpty( $result['pseudo_rules'] );
	}

	public function test_parse_body_rules__removes_important_flag(): void {
		$css    = 'body { color: red !important; }';
		$result = $this->extractor->parse_body_rules( $css, $this->matcher );

		$this->assertSame( 'red', $result['breakpoint_rules']['desktop']['color'] );
	}

	public function test_parse_body_rules__ignores_css_comments(): void {
		$css    = '/* comment */ body { /* inner */ color: blue; }';
		$result = $this->extractor->parse_body_rules( $css, $this->matcher );

		$this->assertArrayHasKey( 'desktop', $result['breakpoint_rules'] );
		$this->assertSame( 'blue', $result['breakpoint_rules']['desktop']['color'] );
	}
}
