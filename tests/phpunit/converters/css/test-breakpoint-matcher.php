<?php

namespace ElementorHtmlCssConverter\Tests\Converters\Css;

use ElementorHtmlCssConverter\Converters\Css\Breakpoint_Matcher;
use ElementorHtmlCssConverter\Tests\TestCase\Test_Constants;
use PHPUnit\Framework\TestCase;

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', '/tmp/' );
}

class Test_Breakpoint_Matcher extends TestCase {

	private Breakpoint_Matcher $matcher;

	protected function setUp(): void {
		parent::setUp();
		$this->matcher = new Breakpoint_Matcher();
	}

	public function test_get_breakpoints_config__returns_empty_without_elementor(): void {
		$config = $this->matcher->get_breakpoints_config();
		$this->assertIsArray( $config );
		$this->assertEmpty( $config );
	}

	public function test_match_css_to_elementor_breakpoint__returns_null_without_elementor(): void {
		$this->assertNull( $this->matcher->match_css_to_elementor_breakpoint( Test_Constants::DESKTOP_TO_TABLET_BREAKPOINT, 'max' ) );
		$this->assertNull( $this->matcher->match_css_to_elementor_breakpoint( Test_Constants::TABLET_TO_MOBILE_BREAKPOINT, 'max' ) );
		$this->assertNull( $this->matcher->match_css_to_elementor_breakpoint( Test_Constants::MIN_WIDTH_SAMPLE, 'min' ) );
	}

	public function test_match_css_to_elementor_breakpoint__accepts_direction_param(): void {
		$this->assertNull( $this->matcher->match_css_to_elementor_breakpoint( Test_Constants::DESKTOP_TO_TABLET_BREAKPOINT, 'min' ) );
		$this->assertNull( $this->matcher->match_css_to_elementor_breakpoint( Test_Constants::DESKTOP_TO_TABLET_BREAKPOINT, 'max' ) );
	}

}
