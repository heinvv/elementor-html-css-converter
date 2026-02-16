<?php

namespace ElementorHtmlCssConverter\Tests\Converters\Html;

use ElementorHtmlCssConverter\Converters\Classes\Converter_Registry;
use ElementorHtmlCssConverter\Converters\Css\Breakpoint_Matcher;
use ElementorHtmlCssConverter\Converters\Html\Atomic_Data_Parser;
use PHPUnit\Framework\TestCase;

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', '/tmp/' );
}

class Test_Atomic_Data_Parser extends TestCase {

	private function create_parser(): Atomic_Data_Parser {
		$registry = new Converter_Registry();
		$matcher  = new Breakpoint_Matcher();
		return new Atomic_Data_Parser( $registry, $matcher );
	}

	public function test_parse_html_for_atomic_widgets__returns_structure_for_div(): void {
		$html = '<style>#hero { padding: 80px; }</style><div id="hero">Hero text</div>';
		$parser = $this->create_parser();
		$result = $parser->parse_html_for_atomic_widgets( $html );

		$this->assertNotEmpty( $result );
		$this->assertIsArray( $result[0] );
		$this->assertArrayHasKey( 'tag', $result[0] );
		$this->assertArrayHasKey( 'widget_type', $result[0] );
		$this->assertArrayHasKey( 'breakpoint_props', $result[0] );
		$this->assertSame( 'e-paragraph', $result[0]['widget_type'] );
		$this->assertSame( 'hero', $result[0]['attributes']['id'] ?? null );
	}

	public function test_parse_html_for_atomic_widgets__extracts_breakpoint_props(): void {
		$html = '<style>#box { padding: 20px; }</style><div id="box">Content</div>';
		$parser = $this->create_parser();
		$result = $parser->parse_html_for_atomic_widgets( $html );

		$this->assertNotEmpty( $result );
		$this->assertArrayHasKey( 'breakpoint_props', $result[0] );
		$this->assertArrayHasKey( 'desktop', $result[0]['breakpoint_props'] );
	}

	public function test_parse_html_for_atomic_widgets__empty_returns_empty(): void {
		$parser = $this->create_parser();
		$this->assertSame( [], $parser->parse_html_for_atomic_widgets( '' ) );
	}

	public function test_parse_html_for_atomic_widgets__nested_structure(): void {
		$html = '<style>#outer { padding: 10px; }</style><div id="outer"><p>Inner</p></div>';
		$parser = $this->create_parser();
		$result = $parser->parse_html_for_atomic_widgets( $html );

		$this->assertNotEmpty( $result );
		$this->assertArrayHasKey( 'children', $result[0] );
	}

	public function test_parse_html_for_atomic_widgets__variable_fallback_option(): void {
		$html = '<style>#a { color: var(--primary); }</style><div id="a">Text</div>';
		$parser = $this->create_parser();
		$result = $parser->parse_html_for_atomic_widgets( $html, [
			'variable_fallback' => [ '--primary' => '#ff0000' ],
		] );

		$this->assertNotEmpty( $result );
	}

	public function test_parse_html_for_atomic_widgets__extracts_pseudo_state_props_from_hover(): void {
		$html   = '<style>#btn { color: red; } #btn:hover { color: blue; }</style><button id="btn">Click</button>';
		$parser = $this->create_parser();
		$result = $parser->parse_html_for_atomic_widgets( $html );

		$this->assertNotEmpty( $result );
		$this->assertArrayHasKey( 'pseudo_state_props', $result[0] );
		$this->assertArrayHasKey( 'hover', $result[0]['pseudo_state_props'] );
		$this->assertArrayHasKey( 'desktop', $result[0]['pseudo_state_props']['hover'] );
	}

	public function test_parse_html_for_atomic_widgets__div_with_text_promotes_to_paragraph(): void {
		$html = '<div>text</div>';
		$parser = $this->create_parser();
		$result = $parser->parse_html_for_atomic_widgets( $html );

		$this->assertNotEmpty( $result );
		$this->assertSame( 'e-paragraph', $result[0]['widget_type'] );
		$this->assertSame( 'text', $result[0]['content'] );
		$this->assertSame( 'div', $result[0]['attributes']['original_tag'] ?? null );
	}

	public function test_parse_html_for_atomic_widgets__e_paragraph_preserves_inline_html(): void {
		$html = '<style>#p1 { color: red; }</style><p id="p1"><s>Strikethrough</s><br><strong>second line</strong><br><em>italic</em><br><u>underline</u><br><sup>superscript</sup><br><sub>subscript</sub><br><a target="_blank" href="https://google.com">link</a></p>';
		$parser = $this->create_parser();
		$result = $parser->parse_html_for_atomic_widgets( $html );

		$this->assertNotEmpty( $result );
		$this->assertSame( 'e-paragraph', $result[0]['widget_type'] );
		$content = $result[0]['content'];
		$this->assertStringContainsString( '<s>Strikethrough</s>', $content );
		$this->assertStringContainsString( '<strong>second line</strong>', $content );
		$this->assertStringContainsString( '<em>italic</em>', $content );
		$this->assertStringContainsString( '<u>underline</u>', $content );
		$this->assertStringContainsString( '<sup>superscript</sup>', $content );
		$this->assertStringContainsString( '<sub>subscript</sub>', $content );
		$this->assertStringContainsString( '<a ', $content );
		$this->assertStringContainsString( 'href="https://google.com"', $content );
		$this->assertStringContainsString( '>link</a>', $content );
	}

	public function test_parse_html_for_atomic_widgets__e_heading_preserves_inline_html(): void {
		$html = '<h2 id="h1"><strong>Bold</strong> and <em>italic</em> text</h2>';
		$parser = $this->create_parser();
		$result = $parser->parse_html_for_atomic_widgets( $html );

		$this->assertNotEmpty( $result );
		$this->assertSame( 'e-heading', $result[0]['widget_type'] );
		$content = $result[0]['content'];
		$this->assertStringContainsString( '<strong>Bold</strong>', $content );
		$this->assertStringContainsString( '<em>italic</em>', $content );
	}

	public function test_parse_html_for_atomic_widgets__e_button_preserves_inline_html(): void {
		$html = '<button id="btn">Click <strong>here</strong></button>';
		$parser = $this->create_parser();
		$result = $parser->parse_html_for_atomic_widgets( $html );

		$this->assertNotEmpty( $result );
		$this->assertSame( 'e-button', $result[0]['widget_type'] );
		$content = $result[0]['content'];
		$this->assertStringContainsString( '<strong>here</strong>', $content );
	}

	public function test_parse_html_for_atomic_widgets__plain_text_without_html_still_works(): void {
		$html = '<p id="p1">Plain text only</p>';
		$parser = $this->create_parser();
		$result = $parser->parse_html_for_atomic_widgets( $html );

		$this->assertNotEmpty( $result );
		$this->assertSame( 'e-paragraph', $result[0]['widget_type'] );
		$this->assertSame( 'Plain text only', $result[0]['content'] );
	}

	public function test_parse_html_for_atomic_widgets__span_promotes_to_e_paragraph(): void {
		$html = '<span id="s1"><b>text</b></span>';
		$parser = $this->create_parser();
		$result = $parser->parse_html_for_atomic_widgets( $html );

		$this->assertNotEmpty( $result );
		$this->assertSame( 'e-paragraph', $result[0]['widget_type'] );
		$this->assertStringContainsString( '<strong>text</strong>', $result[0]['content'] );
		$this->assertStringNotContainsString( '<b>', $result[0]['content'] );
	}

	public function test_parse_html_for_atomic_widgets__div_promotes_to_e_paragraph(): void {
		$html = '<div id="d1"><strong>text</strong></div>';
		$parser = $this->create_parser();
		$result = $parser->parse_html_for_atomic_widgets( $html );

		$this->assertNotEmpty( $result );
		$this->assertSame( 'e-paragraph', $result[0]['widget_type'] );
		$this->assertStringContainsString( '<strong>text</strong>', $result[0]['content'] );
	}

	public function test_parse_html_for_atomic_widgets__normalizes_b_to_strong(): void {
		$html = '<p id="p1"><b>text</b></p>';
		$parser = $this->create_parser();
		$result = $parser->parse_html_for_atomic_widgets( $html );

		$this->assertNotEmpty( $result );
		$this->assertSame( 'e-paragraph', $result[0]['widget_type'] );
		$this->assertStringContainsString( '<strong>text</strong>', $result[0]['content'] );
		$this->assertStringNotContainsString( '<b>', $result[0]['content'] );
	}

	public function test_parse_html_for_atomic_widgets__strips_script_tags(): void {
		$html = '<p id="p1">Safe <script>alert(1)</script> text</p>';
		$parser = $this->create_parser();
		$result = $parser->parse_html_for_atomic_widgets( $html );

		$this->assertNotEmpty( $result );
		$content = $result[0]['content'];
		$this->assertStringNotContainsString( '<script>', $content );
		$this->assertStringNotContainsString( '</script>', $content );
		$this->assertStringContainsString( 'Safe', $content );
		$this->assertStringContainsString( 'text', $content );
	}

	public function test_parse_html_for_atomic_widgets__div_with_direct_text_and_children_synthetic_paragraph_has_span_tag_and_empty_props(): void {
		$html = '<style>#parent { display: flex; font-size: 0.875rem; }</style><div id="parent">2 december 2025<span id="child">Persberichten</span></div>';
		$parser = $this->create_parser();
		$result = $parser->parse_html_for_atomic_widgets( $html );

		$this->assertNotEmpty( $result );
		$this->assertNotEmpty( $result[0]['children'] ?? [] );
		$synthetic_paragraph = $result[0]['children'][0];
		$this->assertSame( 'e-paragraph', $synthetic_paragraph['widget_type'] );
		$this->assertSame( '2 december 2025', $synthetic_paragraph['content'] );
		$this->assertSame( 'span', $synthetic_paragraph['attributes']['original_tag'] ?? null );
		$this->assertEmpty( $synthetic_paragraph['breakpoint_props']['desktop']['atomic_props'] ?? null );
	}

	public function test_parse_html_for_atomic_widgets__span_with_only_text_keeps_breakpoint_props_and_has_span_tag(): void {
		$html   = '<style>#s1 { padding: 10px; }</style><span id="s1"><b>text</b></span>';
		$parser = $this->create_parser();
		$result = $parser->parse_html_for_atomic_widgets( $html );

		$this->assertNotEmpty( $result );
		$this->assertSame( 'e-paragraph', $result[0]['widget_type'] );
		$this->assertSame( 'span', $result[0]['attributes']['original_tag'] ?? null );
		$this->assertArrayHasKey( 'breakpoint_props', $result[0] );
	}

	public function test_parse_html_for_atomic_widgets__div_with_direct_text_and_children_no_style_duplication(): void {
		$html   = '<style>#row { padding: 20px; }</style><div id="row">Date text<div>Other</div></div>';
		$parser = $this->create_parser();
		$result = $parser->parse_html_for_atomic_widgets( $html );

		$this->assertNotEmpty( $result );
		$parent              = $result[0];
		$this->assertNotEmpty( $parent['children'] ?? [] );
		$synthetic_paragraph = $parent['children'][0];
		$this->assertEmpty( $synthetic_paragraph['breakpoint_props']['desktop']['atomic_props'] ?? null );
	}

}
