<?php
/**
 * List Element Detector
 *
 * Determines if a ul/ol element is used for layout (containing block elements)
 * or semantic (containing only text/inline content). Layout lists are converted
 * to containers; semantic lists are skipped until Elementor supports list atoms.
 *
 * @package ElementorHtmlCssConverter
 */

namespace ElementorHtmlCssConverter\Converters\Html;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class List_Element_Detector {

	private const LAYOUT_INDICATOR_ELEMENTS = [
		'div',
		'section',
		'aside',
		'article',
		'button',
		'header',
		'footer',
		'main',
		'nav',
	];

	public function is_layout_list( \DOMElement $list_element ): bool {
		$tag = strtolower( $list_element->tagName );

		if ( ! in_array( $tag, [ 'ul', 'ol' ], true ) ) {
			return false;
		}

		foreach ( $list_element->childNodes as $child ) {
			if ( XML_ELEMENT_NODE !== $child->nodeType ) {
				continue;
			}
			if ( 'li' !== strtolower( $child->tagName ) ) {
				continue;
			}
			if ( $this->contains_layout_elements( $child ) ) {
				return true;
			}
		}

		return false;
	}

	private function contains_layout_elements( \DOMElement $element ): bool {
		$tag = strtolower( $element->tagName );
		if ( in_array( $tag, self::LAYOUT_INDICATOR_ELEMENTS, true ) ) {
			return true;
		}

		foreach ( $element->childNodes as $child ) {
			if ( XML_ELEMENT_NODE !== $child->nodeType ) {
				continue;
			}
			if ( $this->contains_layout_elements( $child ) ) {
				return true;
			}
		}

		return false;
	}
}
