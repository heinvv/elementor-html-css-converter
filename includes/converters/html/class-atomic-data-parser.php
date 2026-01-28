<?php
/**
 * Atomic Data Parser
 *
 * Parses HTML and extracts element data for atomic widget conversion.
 * Uses ID-based CSS styling only (inline styles are ignored).
 *
 * @package ElementorHtmlCssConverter
 */

namespace ElementorHtmlCssConverter\Converters\Html;

use ElementorHtmlCssConverter\Converter_Registry;
use ElementorHtmlCssConverter\Parsers\Id_Style_Extractor;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Atomic_Data_Parser
 *
 * Parses HTML using DOMDocument and prepares data for atomic widget conversion.
 * Styling is extracted from <style> tags using #id selectors only.
 */
class Atomic_Data_Parser {

	/**
	 * Widget mapper instance.
	 *
	 * @var HTML_To_Atomic_Widget_Mapper
	 */
	private HTML_To_Atomic_Widget_Mapper $widget_mapper;

	/**
	 * Converter registry for CSS to atomic props conversion.
	 *
	 * @var Converter_Registry
	 */
	private Converter_Registry $converter_registry;

	/**
	 * ID style extractor for parsing #id CSS rules.
	 *
	 * @var Id_Style_Extractor
	 */
	private Id_Style_Extractor $id_style_extractor;

	/**
	 * Parsed ID rules for current document.
	 *
	 * @var array
	 */
	private array $id_rules = [];

	/**
	 * Tags that should have their text content wrapped in paragraphs.
	 *
	 * @var array
	 */
	private array $text_wrapping_tags = [
		'div',
		'span',
		'section',
		'article',
		'aside',
		'header',
		'footer',
		'main',
		'nav',
	];

	/**
	 * Constructor.
	 *
	 * @param Converter_Registry $converter_registry The CSS converter registry.
	 */
	public function __construct( Converter_Registry $converter_registry ) {
		$this->widget_mapper      = new HTML_To_Atomic_Widget_Mapper();
		$this->converter_registry = $converter_registry;
		$this->id_style_extractor = new Id_Style_Extractor();
	}

	/**
	 * Parse HTML for atomic widgets.
	 *
	 * Extracts CSS from <style> tags (ID selectors only) and applies
	 * styles to elements based on their id attribute.
	 *
	 * @param string $html HTML content to parse.
	 * @return array Array of widget data.
	 */
	public function parse_html_for_atomic_widgets( string $html ): array {
		if ( empty( trim( $html ) ) ) {
			return [];
		}

		$dom = $this->create_dom( $html );

		// Extract ID-based CSS rules from <style> tags.
		$this->id_rules = $this->id_style_extractor->extract_all_id_styles( $dom );

		$dom_elements = $this->parse_dom_structure_from_dom( $dom );
		if ( empty( $dom_elements ) ) {
			return [];
		}

		$dom_elements = $this->preprocess_elements_for_text_wrapping( $dom_elements );

		return $dom_elements;
	}

	/**
	 * Create DOMDocument from HTML.
	 *
	 * @param string $html HTML content.
	 * @return \DOMDocument DOM document.
	 */
	private function create_dom( string $html ): \DOMDocument {
		$dom = new \DOMDocument();
		libxml_use_internal_errors( true );
		$dom->loadHTML(
			'<html><body>' . $html . '</body></html>',
			LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
		);
		libxml_clear_errors();

		return $dom;
	}

	/**
	 * Parse DOM structure from DOMDocument.
	 *
	 * @param \DOMDocument $dom DOM document.
	 * @return array Array of element data.
	 */
	private function parse_dom_structure_from_dom( \DOMDocument $dom ): array {
		$body = $dom->getElementsByTagName( 'body' )->item( 0 );

		if ( ! $body ) {
			return [];
		}

		return $this->extract_elements_recursively( $body );
	}

	/**
	 * Recursively extract elements from DOM node.
	 *
	 * @param \DOMNode $node DOM node.
	 * @return array Array of element data.
	 */
	private function extract_elements_recursively( \DOMNode $node ): array {
		$elements = [];

		foreach ( $node->childNodes as $child ) {
			if ( XML_ELEMENT_NODE !== $child->nodeType ) {
				continue;
			}

			if ( 'style' === strtolower( $child->tagName ) ) {
				continue;
			}

			$element_data = $this->extract_element_data( $child );
			if ( $element_data ) {
				$elements[] = $element_data;
			}
		}

		return $elements;
	}

	/**
	 * Extract data from a DOM element.
	 *
	 * Styles are looked up by the element's id attribute from the parsed ID rules.
	 * Inline style attributes are ignored.
	 *
	 * @param \DOMElement $element DOM element.
	 * @return array|null Element data or null if not supported.
	 */
	private function extract_element_data( \DOMElement $element ): ?array {
		$tag_name      = strtolower( $element->tagName );
		$widget_config = $this->widget_mapper->get_widget_config( $tag_name );

		if ( ! $widget_config ) {
			return null;
		}

		// Get styles from ID-based CSS rules (not inline styles).
		$element_id   = $element->getAttribute( 'id' );
		$id_styles    = $this->id_style_extractor->get_styles_for_id( $element_id, $this->id_rules );
		$atomic_props = $this->convert_styles_to_atomic_props( $id_styles );

		$content    = $this->extract_text_content( $element );
		$attributes = $this->extract_attributes( $element );
		$children   = $this->extract_children( $element );

		$attributes['original_tag'] = $tag_name;

		return [
			'tag'           => $tag_name,
			'widget_type'   => $widget_config['type'],
			'widget_config' => $widget_config,
			'atomic_props'  => $atomic_props,
			'content'       => $content,
			'attributes'    => $attributes,
			'children'      => $children,
		];
	}

	/**
	 * Convert CSS styles to atomic props using the converter registry.
	 *
	 * @param array $styles CSS property-value pairs.
	 * @return array Atomic props.
	 */
	private function convert_styles_to_atomic_props( array $styles ): array {
		$atomic_props = [];

		foreach ( $styles as $property => $value ) {
			$converter = $this->converter_registry->resolve( $property );
			if ( ! $converter ) {
				continue;
			}

			$converted = $converter->convert( $property, $value );
			if ( null === $converted ) {
				continue;
			}

			$output_property = $converter->get_output_property( $property );

			if ( $this->is_multi_property_result( $converted ) ) {
				foreach ( $converted as $expanded_property => $expanded_value ) {
					$atomic_props[ $expanded_property ] = $this->merge_props(
						$atomic_props[ $expanded_property ] ?? null,
						$expanded_value
					);
				}
			} else {
				$atomic_props[ $output_property ] = $this->merge_props(
					$atomic_props[ $output_property ] ?? null,
					$converted
				);
			}
		}

		return $atomic_props;
	}

	/**
	 * Check if conversion result contains multiple properties.
	 *
	 * @param array $result Conversion result.
	 * @return bool True if multi-property result.
	 */
	private function is_multi_property_result( array $result ): bool {
		if ( isset( $result['$$type'] ) ) {
			return false;
		}

		foreach ( $result as $value ) {
			if ( is_array( $value ) && isset( $value['$$type'] ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Merge atomic props, handling dimensions specially.
	 *
	 * @param array|null $existing Existing prop.
	 * @param array      $new      New prop.
	 * @return array Merged prop.
	 */
	private function merge_props( ?array $existing, array $new ): array {
		if ( null === $existing ) {
			return $new;
		}

		if ( $this->is_dimensions_prop( $existing ) && $this->is_dimensions_prop( $new ) ) {
			return $this->merge_dimensions_props( $existing, $new );
		}

		return $new;
	}

	/**
	 * Check if prop is a dimensions type.
	 *
	 * @param array $prop Atomic prop.
	 * @return bool True if dimensions.
	 */
	private function is_dimensions_prop( array $prop ): bool {
		return isset( $prop['$$type'] ) && 'dimensions' === $prop['$$type'];
	}

	/**
	 * Merge two dimensions props.
	 *
	 * @param array $existing Existing dimensions prop.
	 * @param array $new      New dimensions prop.
	 * @return array Merged dimensions prop.
	 */
	private function merge_dimensions_props( array $existing, array $new ): array {
		$merged_value = $existing['value'] ?? [];
		$new_value    = $new['value'] ?? [];

		foreach ( [ 'block-start', 'block-end', 'inline-start', 'inline-end' ] as $direction ) {
			if ( isset( $new_value[ $direction ] ) && null !== $new_value[ $direction ] ) {
				$merged_value[ $direction ] = $new_value[ $direction ];
			}
			if ( ! isset( $merged_value[ $direction ] ) ) {
				$merged_value[ $direction ] = null;
			}
		}

		return [
			'$$type' => 'dimensions',
			'value'  => $merged_value,
		];
	}

	/**
	 * Extract direct text content from element.
	 *
	 * @param \DOMElement $element DOM element.
	 * @return string Text content.
	 */
	private function extract_text_content( \DOMElement $element ): string {
		$text_content = '';

		foreach ( $element->childNodes as $child ) {
			if ( XML_TEXT_NODE === $child->nodeType ) {
				$text_content .= $child->textContent;
			} elseif ( XML_ELEMENT_NODE === $child->nodeType && $this->is_inline_element( $child->tagName ) ) {
				$text_content .= $this->extract_text_content( $child );
			}
		}

		return trim( $text_content );
	}

	/**
	 * Check if tag is an inline element.
	 *
	 * @param string $tag_name Tag name.
	 * @return bool True if inline.
	 */
	private function is_inline_element( string $tag_name ): bool {
		$inline_elements = [
			'span',
			'strong',
			'em',
			'b',
			'i',
			'u',
			'small',
			'mark',
			'del',
			'ins',
			'sub',
			'sup',
			'code',
		];

		return in_array( strtolower( $tag_name ), $inline_elements, true );
	}

	/**
	 * Extract attributes from element.
	 *
	 * Note: The 'style' attribute is excluded as inline styles are not supported.
	 *
	 * @param \DOMElement $element DOM element.
	 * @return array Attributes.
	 */
	private function extract_attributes( \DOMElement $element ): array {
		$attributes = [];

		foreach ( $element->attributes as $attr ) {
			// Exclude style attribute - inline styles are not supported.
			if ( 'style' !== $attr->name ) {
				$attributes[ $attr->name ] = $attr->value;
			}
		}

		return $attributes;
	}

	/**
	 * Extract children elements.
	 *
	 * @param \DOMElement $element DOM element.
	 * @return array Children element data.
	 */
	private function extract_children( \DOMElement $element ): array {
		$children = [];

		foreach ( $element->childNodes as $child ) {
			if ( XML_ELEMENT_NODE === $child->nodeType ) {
				$child_data = $this->extract_element_data( $child );
				if ( $child_data ) {
					$children[] = $child_data;
				}
			}
		}

		return $children;
	}

	/**
	 * Preprocess elements for text wrapping.
	 *
	 * @param array $elements Array of element data.
	 * @return array Processed elements.
	 */
	private function preprocess_elements_for_text_wrapping( array $elements ): array {
		$processed_elements = [];

		foreach ( $elements as $element ) {
			$processed_elements[] = $this->wrap_text_content_in_paragraphs( $element );
		}

		return $processed_elements;
	}

	/**
	 * Wrap text content in paragraph elements for container tags.
	 *
	 * @param array $element Element data.
	 * @return array Processed element.
	 */
	private function wrap_text_content_in_paragraphs( array $element ): array {
		if ( ! in_array( $element['tag'], $this->text_wrapping_tags, true ) ) {
			if ( ! empty( $element['children'] ) ) {
				$element['children'] = $this->preprocess_elements_for_text_wrapping( $element['children'] );
			}
			return $element;
		}

		$has_direct_text = ! empty( trim( $element['content'] ) );
		$has_children    = ! empty( $element['children'] );

		if ( $has_direct_text ) {
			$paragraph_element = [
				'tag'           => 'p',
				'widget_type'   => 'e-paragraph',
				'widget_config' => [ 'type' => 'e-paragraph' ],
				'atomic_props'  => [],
				'content'       => trim( $element['content'] ),
				'attributes'    => [ 'original_tag' => 'p' ],
				'children'      => [],
				'synthetic'     => true,
			];

			if ( $has_children ) {
				$processed_children  = $this->preprocess_elements_for_text_wrapping( $element['children'] );
				$element['children'] = array_merge( [ $paragraph_element ], $processed_children );
			} else {
				$element['children'] = [ $paragraph_element ];
			}

			$element['content'] = '';
		} elseif ( $has_children ) {
			$element['children'] = $this->preprocess_elements_for_text_wrapping( $element['children'] );
		}

		return $element;
	}

	/**
	 * Get the widget mapper instance.
	 *
	 * @return HTML_To_Atomic_Widget_Mapper Widget mapper.
	 */
	public function get_widget_mapper(): HTML_To_Atomic_Widget_Mapper {
		return $this->widget_mapper;
	}

	/**
	 * Get the ID style extractor instance.
	 *
	 * @return Id_Style_Extractor ID style extractor.
	 */
	public function get_id_style_extractor(): Id_Style_Extractor {
		return $this->id_style_extractor;
	}
}
