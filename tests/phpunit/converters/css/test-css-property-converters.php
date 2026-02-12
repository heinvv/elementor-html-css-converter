<?php

namespace ElementorHtmlCssConverter\Tests\Converters\Css;

use ElementorHtmlCssConverter\Converters\Classes\Converter_Registry;
use ElementorHtmlCssConverter\Converters\Css\Css_Converter;
use PHPUnit\Framework\TestCase;

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', '/tmp/' );
}

class Test_Css_Property_Converters extends TestCase {

	private function requires_elementor(): void {
		if ( ! class_exists( '\Elementor\Modules\AtomicWidgets\PropTypes\Size_Prop_Type' ) ) {
			$this->markTestSkipped( 'Requires Elementor.' );
		}
	}

	private function create_registry_with_converters(): Converter_Registry {
		$registry        = new Converter_Registry();
		$properties_dir  = EHCC_PATH . 'includes/converters/css/properties/';
		if ( ! is_dir( $properties_dir ) ) {
			return $registry;
		}
		foreach ( glob( $properties_dir . 'class-*-converter.php' ) as $file ) {
			$basename = basename( $file, '.php' );
			if ( strpos( $basename, 'class-' ) !== 0 || strpos( $basename, '-converter' ) === false ) {
				continue;
			}
			$parts     = explode( '-', substr( $basename, 6, -10 ) );
			$class_name = 'ElementorHtmlCssConverter\\Converters\\Css\\Properties\\' . implode( '_', array_map( 'ucfirst', $parts ) ) . '_Converter';
			if ( class_exists( $class_name ) ) {
				$ref = new \ReflectionClass( $class_name );
				if ( ! $ref->isAbstract() && $ref->implementsInterface( \ElementorHtmlCssConverter\Converters\Css\Property_Converter_Interface::class ) ) {
					$registry->register( new $class_name() );
				}
			}
		}
		return $registry;
	}

	public function test_padding_converts_to_atomic_props(): void {
		$this->requires_elementor();
		$registry  = $this->create_registry_with_converters();
		$converter = new Css_Converter( $registry );

		$result = $converter->convert( [ 'cssString' => 'padding: 20px;' ] );

		$this->assertArrayHasKey( 'props', $result );
		$this->assertArrayHasKey( 'padding', $result['props'] );
	}

	public function test_margin_converts_to_atomic_props(): void {
		$this->requires_elementor();
		$registry  = $this->create_registry_with_converters();
		$converter = new Css_Converter( $registry );

		$result = $converter->convert( [ 'cssString' => 'margin: 16px;' ] );

		$this->assertArrayHasKey( 'props', $result );
		$this->assertArrayHasKey( 'margin', $result['props'] );
	}

	public function test_gap_converts_to_atomic_props(): void {
		$this->requires_elementor();
		$registry  = $this->create_registry_with_converters();
		$converter = new Css_Converter( $registry );

		$result = $converter->convert( [ 'cssString' => 'gap: 16px;' ] );

		$this->assertArrayHasKey( 'props', $result );
		$this->assertArrayHasKey( 'gap', $result['props'] );
	}

	public function test_display_converts_to_atomic_props(): void {
		$this->requires_elementor();
		$registry  = $this->create_registry_with_converters();
		$converter = new Css_Converter( $registry );

		$result = $converter->convert( [ 'cssString' => 'display: flex;' ] );

		$this->assertArrayHasKey( 'props', $result );
		$this->assertArrayHasKey( 'display', $result['props'] );
	}

	public function test_color_converts_to_atomic_props(): void {
		$this->requires_elementor();
		$registry  = $this->create_registry_with_converters();
		$converter = new Css_Converter( $registry );

		$result = $converter->convert( [ 'cssString' => 'color: #ff0000;' ] );

		$this->assertArrayHasKey( 'props', $result );
		$this->assertArrayHasKey( 'color', $result['props'] );
	}

	public function test_background_color_converts_to_atomic_props(): void {
		$this->requires_elementor();
		$registry  = $this->create_registry_with_converters();
		$converter = new Css_Converter( $registry );

		$result = $converter->convert( [ 'cssString' => 'background-color: #ffffff;' ] );

		$this->assertArrayHasKey( 'props', $result );
		$this->assertArrayHasKey( 'background', $result['props'] );
	}

	public function test_font_size_converts_to_atomic_props(): void {
		$this->requires_elementor();
		$registry  = $this->create_registry_with_converters();
		$converter = new Css_Converter( $registry );

		$result = $converter->convert( [ 'cssString' => 'font-size: 16px;' ] );

		$this->assertArrayHasKey( 'props', $result );
		$this->assertArrayHasKey( 'font-size', $result['props'] );
	}

	public function test_flex_direction_converts_to_atomic_props(): void {
		$this->requires_elementor();
		$registry  = $this->create_registry_with_converters();
		$converter = new Css_Converter( $registry );

		$result = $converter->convert( [ 'cssString' => 'flex-direction: column;' ] );

		$this->assertArrayHasKey( 'props', $result );
		$this->assertArrayHasKey( 'flex-direction', $result['props'] );
	}

	public function test_align_items_converts_to_atomic_props(): void {
		$this->requires_elementor();
		$registry  = $this->create_registry_with_converters();
		$converter = new Css_Converter( $registry );

		$result = $converter->convert( [ 'cssString' => 'align-items: center;' ] );

		$this->assertArrayHasKey( 'props', $result );
		$this->assertArrayHasKey( 'align-items', $result['props'] );
	}

	public function test_justify_content_converts_to_atomic_props(): void {
		$this->requires_elementor();
		$registry  = $this->create_registry_with_converters();
		$converter = new Css_Converter( $registry );

		$result = $converter->convert( [ 'cssString' => 'justify-content: space-between;' ] );

		$this->assertArrayHasKey( 'props', $result );
		$this->assertArrayHasKey( 'justify-content', $result['props'] );
	}

	public function test_unsupported_property_goes_to_custom_css(): void {
		$this->requires_elementor();
		$registry  = $this->create_registry_with_converters();
		$converter = new Css_Converter( $registry );

		$result = $converter->convert( [ 'cssString' => 'vertical-align: middle;' ] );

		$this->assertArrayHasKey( 'customCss', $result );
		$this->assertStringContainsString( 'vertical-align', $result['customCss'] );
	}

	public function test_mixed_supported_and_unsupported_splits_correctly(): void {
		$this->requires_elementor();
		$registry  = $this->create_registry_with_converters();
		$converter = new Css_Converter( $registry );

		$result = $converter->convert( [ 'cssString' => 'padding: 20px; vertical-align: middle; color: red;' ] );

		$this->assertArrayHasKey( 'padding', $result['props'] );
		$this->assertArrayHasKey( 'color', $result['props'] );
		$this->assertArrayHasKey( 'customCss', $result );
		$this->assertStringContainsString( 'vertical-align', $result['customCss'] );
	}

}
