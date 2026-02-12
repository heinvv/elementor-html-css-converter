<?php

namespace ElementorHtmlCssConverter\Tests\Converters\Classes;

use ElementorHtmlCssConverter\Converters\Classes\Class_Conversion_Service;
use ElementorHtmlCssConverter\Converters\Classes\Converter_Registry;
use PHPUnit\Framework\TestCase;

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', '/tmp/' );
}

class Test_Class_Conversion_Service extends TestCase {

	private function requires_elementor(): void {
		if ( ! class_exists( '\Elementor\Modules\AtomicWidgets\PropTypes\Size_Prop_Type' ) ) {
			$this->markTestSkipped( 'Requires Elementor.' );
		}
	}

	private function create_registry_with_converters(): Converter_Registry {
		$registry = new Converter_Registry();
		$properties_dir = EHCC_PATH . 'includes/converters/css/properties/';
		if ( is_dir( $properties_dir ) ) {
			foreach ( glob( $properties_dir . 'class-*-converter.php' ) as $file ) {
				$basename = basename( $file, '.php' );
				if ( strpos( $basename, 'class-' ) !== 0 || strpos( $basename, '-converter' ) === false ) {
					continue;
				}
				$parts = explode( '-', substr( $basename, 6, -10 ) );
				$class_name = 'ElementorHtmlCssConverter\\Converters\\Css\\Properties\\' . implode( '_', array_map( 'ucfirst', $parts ) ) . '_Converter';
				if ( class_exists( $class_name ) ) {
					$ref = new \ReflectionClass( $class_name );
					if ( ! $ref->isAbstract() && $ref->implementsInterface( \ElementorHtmlCssConverter\Converters\Css\Property_Converter_Interface::class ) ) {
						$registry->register( new $class_name() );
					}
				}
			}
		}
		return $registry;
	}

	public function test_convert_to_atomic__returns_structure(): void {
		$this->requires_elementor();
		$registry = $this->create_registry_with_converters();
		$service = new Class_Conversion_Service( $registry );

		$breakpoint_classes = [
			'card' => [
				'desktop' => [
					'selector'   => '.card',
					'properties' => [
						'padding'        => '20px',
						'background-color' => '#ffffff',
					],
				],
			],
		];

		$result = $service->convert_to_atomic( $breakpoint_classes );

		$this->assertArrayHasKey( 'classes', $result );
		$this->assertArrayHasKey( 'unsupported_fonts_created', $result );
		$this->assertIsArray( $result['classes'] );
	}

	public function test_convert_to_atomic__empty_input(): void {
		$registry = new Converter_Registry();
		$service = new Class_Conversion_Service( $registry );

		$result = $service->convert_to_atomic( [] );

		$this->assertSame( [], $result['classes'] );
		$this->assertSame( [], $result['unsupported_fonts_created'] );
	}

	public function test_get_conversion_stats__empty_input(): void {
		$registry = new Converter_Registry();
		$service = new Class_Conversion_Service( $registry );
		$stats = $service->get_conversion_stats( [] );

		$this->assertSame( 0, $stats['total_classes'] );
		$this->assertSame( 0, $stats['with_atomic_props'] );
		$this->assertSame( 0, $stats['with_custom_css'] );
	}

	public function test_get_conversion_stats__counts_classes(): void {
		$this->requires_elementor();
		$registry = $this->create_registry_with_converters();
		$service = new Class_Conversion_Service( $registry );

		$breakpoint_classes = [
			'card' => [
				'desktop' => [
					'selector'   => '.card',
					'properties' => [ 'padding' => '20px' ],
				],
			],
		];
		$converted = $service->convert_to_atomic( $breakpoint_classes );
		$stats = $service->get_conversion_stats( $converted['classes'] );

		$this->assertArrayHasKey( 'total_classes', $stats );
		$this->assertArrayHasKey( 'with_atomic_props', $stats );
		$this->assertArrayHasKey( 'with_custom_css', $stats );
	}

	public function test_get_conversion_stats__returns_expected_keys(): void {
		$this->requires_elementor();
		$registry = $this->create_registry_with_converters();
		$service = new Class_Conversion_Service( $registry );

		$converted = $service->convert_to_atomic( [
			'test' => [ 'desktop' => [ 'selector' => '.test', 'properties' => [ 'padding' => '10px' ] ] ],
		] );
		$stats = $service->get_conversion_stats( $converted['classes'] );

		$this->assertArrayHasKey( 'total_classes', $stats );
		$this->assertArrayHasKey( 'with_atomic_props', $stats );
		$this->assertArrayHasKey( 'with_custom_css', $stats );
		$this->assertArrayHasKey( 'empty_conversions', $stats );
		$this->assertArrayHasKey( 'total_atomic_props', $stats );
	}

}
