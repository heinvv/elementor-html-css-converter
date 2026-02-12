<?php

namespace ElementorHtmlCssConverter\Tests\Integration;

use PHPUnit\Framework\TestCase;

class Test_Wp_Bootstrap extends TestCase {

	public function test_wordpress_loaded(): void {
		$this->assertTrue( defined( 'ABSPATH' ) );
		$this->assertTrue( function_exists( 'get_bloginfo' ) );
	}

	public function test_elementor_loaded(): void {
		$this->assertTrue( class_exists( '\Elementor\Plugin' ) );
		$this->assertNotNull( \Elementor\Plugin::$instance );
	}

	public function test_plugin_loaded(): void {
		$this->assertTrue( defined( 'EHCC_PATH' ) );
		$this->assertTrue( class_exists( '\ElementorHtmlCssConverter\Plugin' ) );
	}

}
