<?php

namespace ElementorHtmlCssConverter\Converters\Css;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

interface Widget_Style_Applicator_Interface {
	public function apply( array $widget, string $css_string ): array;
}
