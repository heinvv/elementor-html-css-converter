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
		$this->assertSame( 'div', $result[0]['tag'] );
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

}
