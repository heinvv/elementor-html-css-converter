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

use ElementorHtmlCssConverter\Converters\Classes\Converter_Registry;
use ElementorHtmlCssConverter\Converters\Css\Css_Converter;
use ElementorHtmlCssConverter\Converters\Html\Id_Style_Extractor;
use ElementorHtmlCssConverter\Converters\Css\Breakpoint_Matcher;

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
	 * CSS converter for CSS to atomic props conversion.
	 *
	 * @var Css_Converter
	 */
	private Css_Converter $css_converter;

	/**
	 * ID style extractor for parsing #id CSS rules.
	 *
	 * @var Id_Style_Extractor
	 */
	private Id_Style_Extractor $id_style_extractor;

	/**
	 * Breakpoint matcher instance.
	 *
	 * @var Breakpoint_Matcher|null
	 */
	private ?Breakpoint_Matcher $breakpoint_matcher = null;

	private const REGEX_WHITESPACE_SPLIT = '/\s+/';
	private const REGEX_ELEMENTOR_CLASS_PREFIX = '/^(elementor-|e-con-|e-)/';
	private const REGEX_SVG_DANGEROUS_TAGS = '/<\s*\/?\s*(script|foreignObject|iframe|object|embed|applet|form|input|textarea|button|select)\b[^>]*>.*?<\s*\/\s*\1\s*>|<\s*\/?\s*(script|foreignObject|iframe|object|embed|applet|form|input|textarea|button|select)\b[^>]*\/?>/is';
	private const REGEX_SVG_EVENT_HANDLERS = '/\s+on\w+\s*=\s*["\'][^"\']*["\']/i';
	private const REGEX_SVG_JAVASCRIPT_HREF = '/(xlink:href|href)\s*=\s*["\']javascript:[^"\']*["\']/i';

	/**
	 * Parsed ID rules with breakpoints for current document.
	 *
	 * @var array
	 */
	private array $id_rules_with_breakpoints = [];

	/**
	 * Variable fallback map for substituting unresolved var() references.
	 *
	 * @var array<string, string>
	 */
	private array $variable_fallback = [];

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
		'ul',
		'ol',
		'li',
	];

	/**
	 * Constructor.
	 *
	 * @param Converter_Registry $converter_registry The CSS converter registry.
	 * @param Breakpoint_Matcher  $breakpoint_matcher Breakpoint matcher instance.
	 */
	public function __construct( Converter_Registry $converter_registry, Breakpoint_Matcher $breakpoint_matcher ) {
		$this->widget_mapper      = new HTML_To_Atomic_Widget_Mapper();
		$this->css_converter      = new Css_Converter( $converter_registry );
		$this->id_style_extractor = new Id_Style_Extractor();
		$this->breakpoint_matcher = $breakpoint_matcher;
	}

	/**
	 * Parse HTML for atomic widgets.
	 *
	 * Extracts CSS from <style> tags (ID selectors only) and applies
	 * styles to elements based on their id attribute.
	 *
	 * @param string $html    HTML content to parse.
	 * @param array  $options Optional. Keys: variable_fallback (map of --name => value for unresolved var() substitution).
	 * @return array Array of widget data.
	 */
	public function parse_html_for_atomic_widgets( string $html, array $options = [] ): array {
		if ( empty( trim( $html ) ) ) {
			return [];
		}

		$this->variable_fallback = $options['variable_fallback'] ?? [];

		$svg_map = $this->extract_svg_elements_from_html( $html );

		$dom = $this->create_dom( $html );

		$css = $this->id_style_extractor->extract_style_tags( $dom );

		$this->id_rules_with_breakpoints = $this->id_style_extractor->parse_id_rules( $css, $this->breakpoint_matcher );

		$dom_elements = $this->parse_dom_structure_from_dom( $dom, $svg_map );
		if ( empty( $dom_elements ) ) {
			return [];
		}

		$dom_elements = $this->preprocess_elements_for_text_wrapping( $dom_elements );

		return $dom_elements;
	}

	/**
	 * Extract SVG elements from raw HTML before DOMDocument parsing.
	 *
	 * DOMDocument may strip SVG elements, so we extract them from the raw HTML string.
	 *
	 * @param string $html Raw HTML content.
	 * @return array Map of anchor HTML hash to SVG content.
	 */
	private function extract_svg_elements_from_html( string $html ): array {
		$svg_map = [];

		$pattern = '/<a[^>]*>[\s\S]*?<svg[^>]*>[\s\S]*?<\/svg>[\s\S]*?<\/a>/i';

		if ( preg_match_all( $pattern, $html, $matches, PREG_SET_ORDER ) ) {
			foreach ( $matches as $match ) {
				$full_anchor = $match[0];
				if ( preg_match( '/<svg[^>]*>[\s\S]*?<\/svg>/i', $full_anchor, $svg_match ) ) {
					$svg_content = $this->sanitize_svg_content( $svg_match[0] );
					$anchor_hash = md5( trim( preg_replace( '/\s+/', ' ', $full_anchor ) ) );
					$svg_map[ $anchor_hash ] = $svg_content;
				}
			}
		}

		$standalone_pattern = '/<svg[^>]*>[\s\S]*?<\/svg>/i';
		if ( preg_match_all( $standalone_pattern, $html, $matches, PREG_SET_ORDER ) ) {
			foreach ( $matches as $match ) {
				$svg_content = $this->sanitize_svg_content( $match[0] );
				$svg_hash = md5( $svg_content );
				$key = 'standalone_' . $svg_hash;
				if ( ! isset( $svg_map[ $key ] ) ) {
					$svg_map[ $key ] = $svg_content;
				}
			}
		}

		return $svg_map;
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
			mb_convert_encoding( '<html><body>' . $html . '</body></html>', 'HTML-ENTITIES', 'UTF-8' ),
			LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NOERROR | LIBXML_NOWARNING
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
	private function parse_dom_structure_from_dom( \DOMDocument $dom, array $svg_map = [] ): array {
		$body = $dom->getElementsByTagName( 'body' )->item( 0 );

		if ( ! $body ) {
			return [];
		}

		return $this->extract_elements_recursively( $body, $svg_map );
	}

	/**
	 * Recursively extract elements from DOM node.
	 *
	 * @param \DOMNode $node DOM node.
	 * @return array Array of element data.
	 */
	private function extract_elements_recursively( \DOMNode $node, array $svg_map = [] ): array {
		$elements = [];

		foreach ( $node->childNodes as $child ) {
			if ( XML_ELEMENT_NODE !== $child->nodeType ) {
				continue;
			}

			if ( 'style' === strtolower( $child->tagName ) ) {
				continue;
			}

			$child_tag = strtolower( $child->tagName );

			$element_data = $this->extract_element_data( $child, $svg_map );
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
	private function extract_element_data( \DOMElement $element, array $svg_map = [] ): ?array {
		$tag_name      = strtolower( $element->tagName );
		
		if ( 'a' === $tag_name ) {
			$anchor_html = $element->ownerDocument->saveXML( $element );
			
			$svg_content = null;
			foreach ( $svg_map as $hash => $content ) {
				if ( strpos( $hash, 'standalone_' ) === 0 ) {
					continue;
				}
				$test_hash = md5( trim( preg_replace( '/\s+/', ' ', $anchor_html ) ) );
				if ( $hash === $test_hash ) {
					$svg_content = $content;
					break;
				}
			}
			
			if ( ! $svg_content && ! empty( $svg_map ) ) {
				$first_svg = reset( $svg_map );
				if ( strpos( $first_svg, '<svg' ) !== false ) {
					$svg_content = $first_svg;
				}
			}
			
			if ( $svg_content ) {
				return $this->extract_svg_from_link_with_content( $element, $svg_content );
			}
			
			$svg_child = null;
			$element_children = [];
			foreach ( $element->childNodes as $child ) {
				if ( XML_ELEMENT_NODE === $child->nodeType ) {
					$element_children[] = $child;
					$child_tag = strtolower( $child->tagName );
					if ( ( 'svg' === $child_tag || strpos( $child_tag, 'svg' ) !== false ) && null === $svg_child ) {
						$svg_child = $child;
					}
				}
			}
			if ( $svg_child && count( $element_children ) === 1 ) {
				return $this->extract_svg_from_link( $element, $svg_child );
			}
		}

		if ( in_array( $tag_name, [ 'ul', 'ol' ], true ) ) {
			$detector = new List_Element_Detector();
			if ( ! $detector->is_layout_list( $element ) ) {
				return null;
			}
		}
		
		$widget_config = $this->widget_mapper->get_widget_config( $tag_name );

		if ( ! $widget_config ) {
			return null;
		}

		$element_id = $element->getAttribute( 'id' );

		$breakpoint_props = [];
		if ( ! empty( $element_id ) && isset( $this->id_rules_with_breakpoints[ $element_id ] ) ) {
			$breakpoint_styles = $this->id_rules_with_breakpoints[ $element_id ];

			foreach ( $breakpoint_styles as $breakpoint => $styles ) {
				if ( ! empty( $styles ) ) {
					$breakpoint_props[ $breakpoint ] = $this->convert_styles_to_atomic_props( $styles );
				}
			}
		}

		if ( empty( $breakpoint_props ) ) {
			$breakpoint_props['desktop'] = [
				'atomic_props' => [],
				'custom_css'   => null,
			];
		}

		$element_classes = $this->extract_class_names( $element );

		$content    = $this->extract_text_content( $element );
		$attributes = $this->extract_attributes( $element );
		
		if ( 'svg' === $tag_name ) {
			$element_html = $element->ownerDocument->saveXML( $element );
			$element_hash = md5( $element_html );
			if ( isset( $svg_map[ 'standalone_' . $element_hash ] ) ) {
				$attributes['svg_content'] = $svg_map[ 'standalone_' . $element_hash ];
			} else {
				$attributes['svg_content'] = $this->extract_svg_content( $element );
			}
		}
		
		$children   = $this->extract_children( $element, $svg_map );

		$attributes['original_tag'] = $tag_name;

		return [
			'tag'              => $tag_name,
			'widget_type'      => $widget_config['type'],
			'widget_config'    => $widget_config,
			'breakpoint_props' => $breakpoint_props,
			'element_classes'  => $element_classes,
			'content'          => $content,
			'attributes'       => $attributes,
			'children'         => $children,
		];
	}

	/**
	 * Find SVG element in an anchor tag (direct child or nested).
	 *
	 * @param \DOMElement $element DOM element (should be an anchor tag).
	 * @return \DOMElement|null SVG element or null.
	 */
	private function find_svg_in_element( \DOMElement $element ): ?\DOMElement {
		foreach ( $element->childNodes as $child ) {
			if ( XML_ELEMENT_NODE === $child->nodeType ) {
				$child_tag = strtolower( $child->tagName );
				$child_local_name = strtolower( $child->localName ?? '' );
				
				if ( 'svg' === $child_tag || 'svg' === $child_local_name || strpos( $child_tag, 'svg' ) !== false ) {
					return $child;
				}
				
				$nested_svg = $this->find_svg_in_element( $child );
				if ( $nested_svg ) {
					return $nested_svg;
				}
			}
		}

		return null;
	}

	/**
	 * Extract SVG widget data from a link element with SVG content string.
	 *
	 * @param \DOMElement $link_element Link DOM element.
	 * @param string      $svg_content  SVG content as string.
	 * @return array SVG widget data.
	 */
	private function extract_svg_from_link_with_content( \DOMElement $link_element, string $svg_content ): array {
		$link_attributes = $this->extract_attributes( $link_element );
		
		$element_id = $link_element->getAttribute( 'id' );

		$breakpoint_props = [];
		if ( ! empty( $element_id ) && isset( $this->id_rules_with_breakpoints[ $element_id ] ) ) {
			$breakpoint_styles = $this->id_rules_with_breakpoints[ $element_id ];

			foreach ( $breakpoint_styles as $breakpoint => $styles ) {
				if ( ! empty( $styles ) ) {
					$breakpoint_props[ $breakpoint ] = $this->convert_styles_to_atomic_props( $styles );
				}
			}
		}

		if ( empty( $breakpoint_props ) ) {
			$breakpoint_props['desktop'] = [
				'atomic_props' => [],
				'custom_css'   => null,
			];
		}

		$link_classes = $this->extract_class_names( $link_element );
		$element_classes = array_values( $link_classes );

		$svg_attributes = [];
		if ( preg_match( '/<svg\s+([^>]*?)>/i', $svg_content, $svg_attr_match ) ) {
			$svg_attr_string = $svg_attr_match[1];
			if ( preg_match_all( '/(\w+)=["\']([^"\']*)["\']/', $svg_attr_string, $attr_matches, PREG_SET_ORDER ) ) {
				foreach ( $attr_matches as $attr_match ) {
					$svg_attributes[ $attr_match[1] ] = $attr_match[2];
				}
			}
		}
		
		$svg_attributes['svg_content'] = $svg_content;
		
		if ( isset( $link_attributes['href'] ) ) {
			$svg_attributes['href'] = $link_attributes['href'];
		}
		if ( isset( $link_attributes['target'] ) ) {
			$svg_attributes['target'] = $link_attributes['target'];
		}
		
		$svg_attributes['original_tag'] = 'svg';

		return [
			'tag'              => 'svg',
			'widget_type'      => 'e-svg',
			'widget_config'    => [ 'type' => 'e-svg' ],
			'breakpoint_props' => $breakpoint_props,
			'element_classes'  => $element_classes,
			'content'          => '',
			'attributes'       => $svg_attributes,
			'children'         => [],
		];
	}

	/**
	 * Extract SVG widget data from a link element containing an SVG.
	 *
	 * @param \DOMElement $link_element Link DOM element.
	 * @param \DOMElement $svg_element  SVG DOM element.
	 * @return array SVG widget data.
	 */
	private function extract_svg_from_link( \DOMElement $link_element, \DOMElement $svg_element ): array {
		$link_attributes = $this->extract_attributes( $link_element );
		$svg_tag_name = strtolower( $svg_element->tagName );
		
		$element_id = $svg_element->getAttribute( 'id' );
		if ( empty( $element_id ) ) {
			$element_id = $link_element->getAttribute( 'id' );
		}

		$breakpoint_props = [];
		if ( ! empty( $element_id ) && isset( $this->id_rules_with_breakpoints[ $element_id ] ) ) {
			$breakpoint_styles = $this->id_rules_with_breakpoints[ $element_id ];

			foreach ( $breakpoint_styles as $breakpoint => $styles ) {
				if ( ! empty( $styles ) ) {
					$breakpoint_props[ $breakpoint ] = $this->convert_styles_to_atomic_props( $styles );
				}
			}
		}

		if ( empty( $breakpoint_props ) ) {
			$breakpoint_props['desktop'] = [
				'atomic_props' => [],
				'custom_css'   => null,
			];
		}

		$link_classes = $this->extract_class_names( $link_element );
		$svg_classes = $this->extract_class_names( $svg_element );
		$element_classes = array_unique( array_merge( $link_classes, $svg_classes ) );

		$svg_attributes = $this->extract_attributes( $svg_element );
		$svg_content = $this->extract_svg_content( $svg_element );
		$svg_attributes['svg_content'] = $svg_content;
		
		if ( isset( $link_attributes['href'] ) ) {
			$svg_attributes['href'] = $link_attributes['href'];
		}
		if ( isset( $link_attributes['target'] ) ) {
			$svg_attributes['target'] = $link_attributes['target'];
		}
		
		$svg_attributes['original_tag'] = $svg_tag_name;

		return [
			'tag'              => $svg_tag_name,
			'widget_type'      => 'e-svg',
			'widget_config'    => [ 'type' => 'e-svg' ],
			'breakpoint_props' => $breakpoint_props,
			'element_classes'  => array_values( $element_classes ),
			'content'          => '',
			'attributes'       => $svg_attributes,
			'children'         => [],
		];
	}

	/**
	 * Convert CSS styles to atomic props using the CSS converter.
	 *
	 * Delegates to Css_Converter to ensure consistent conversion logic
	 * across all entry points (CSS string conversion and HTML parsing).
	 *
	 * @param array $styles CSS property-value pairs.
	 * @return array Unified structure with 'atomic_props' and 'custom_css'.
	 */
	private function convert_styles_to_atomic_props( array $styles ): array {
		$result = $this->css_converter->convert_properties( $styles, $this->variable_fallback );
		return [
			'atomic_props' => $result['props'] ?? [],
			'custom_css'   => $result['customCss'] ?? null,
		];
	}

	/**
	 * Extract class names from an element's class attribute.
	 *
	 * Parses the space-separated class attribute into an array of individual class names.
	 * Filters out empty strings and Elementor internal classes.
	 *
	 * @param \DOMElement $element DOM element.
	 * @return array Array of class names.
	 */
	private function extract_class_names( \DOMElement $element ): array {
		$class_attr = $element->getAttribute( 'class' );

		if ( empty( $class_attr ) ) {
			return [];
		}

		$classes = preg_split( self::REGEX_WHITESPACE_SPLIT, trim( $class_attr ), -1, PREG_SPLIT_NO_EMPTY );

		if ( empty( $classes ) ) {
			return [];
		}

		$filtered = array_filter(
			$classes,
			function ( $class_name ) {
				if ( preg_match( self::REGEX_ELEMENTOR_CLASS_PREFIX, $class_name ) ) {
					return false;
				}
				return true;
			}
		);

		return array_values( $filtered );
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
			if ( 'style' !== $attr->name ) {
				$attributes[ $attr->name ] = $attr->value;
			}
		}

		return $attributes;
	}

	/**
	 * Strip dangerous elements and attributes from SVG content.
	 *
	 * Removes script tags, foreignObject, event handler attributes, and javascript: URLs
	 * as a defense-in-depth layer on top of wp_kses sanitization.
	 *
	 * @param string $svg_content Raw SVG content string.
	 * @return string Sanitized SVG content.
	 */
	private function sanitize_svg_content( string $svg_content ): string {
		$sanitized = preg_replace( self::REGEX_SVG_DANGEROUS_TAGS, '', $svg_content );
		$sanitized = preg_replace( self::REGEX_SVG_EVENT_HANDLERS, '', $sanitized );
		$sanitized = preg_replace( self::REGEX_SVG_JAVASCRIPT_HREF, '', $sanitized );

		return $sanitized;
	}

	/**
	 * Extract SVG content from DOMElement.
	 *
	 * Gets the full outerHTML of the SVG element including all nested elements and attributes.
	 *
	 * @param \DOMElement $element SVG DOM element.
	 * @return string SVG HTML content.
	 */
	private function extract_svg_content( \DOMElement $element ): string {
		$raw_svg = $element->ownerDocument->saveXML( $element );

		return $this->sanitize_svg_content( $raw_svg );
	}

	/**
	 * Extract children elements.
	 *
	 * @param \DOMElement $element DOM element.
	 * @param array       $svg_map SVG content map.
	 * @return array Children element data.
	 */
	private function extract_children( \DOMElement $element, array $svg_map = [] ): array {
		$children = [];

		foreach ( $element->childNodes as $child ) {
			if ( XML_ELEMENT_NODE === $child->nodeType ) {
				$child_data = $this->extract_element_data( $child, $svg_map );
				if ( null !== $child_data ) {
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
				'tag'             => 'p',
				'widget_type'     => 'e-paragraph',
				'widget_config'   => [ 'type' => 'e-paragraph' ],
				'atomic_props'    => [],
				'element_classes' => [],
				'content'         => trim( $element['content'] ),
				'attributes'      => [ 'original_tag' => 'p' ],
				'children'        => [],
				'synthetic'       => true,
			];

			if ( $has_children ) {
				$processed_children  = $this->preprocess_elements_for_text_wrapping( $element['children'] );
				$element['children'] = array_merge( [ $paragraph_element ], $processed_children );
			} else {
				$element['children'] = [ $paragraph_element ];
			}

			$element['content'] = '';
		}

		if ( $has_children && ! $has_direct_text ) {
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

