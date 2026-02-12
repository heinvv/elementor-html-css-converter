<?php

namespace ElementorHtmlCssConverter\Tests\TestCase;

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', '/tmp/' );
}

final class Test_Constants {

	public const HTTP_OK = 200;
	public const HTTP_BAD_REQUEST = 400;
	public const HTTP_FORBIDDEN = 403;

	public const DESKTOP_TO_TABLET_BREAKPOINT = 1024;
	public const TABLET_TO_MOBILE_BREAKPOINT = 767;
	public const TOLERANCE_BREAKPOINT = 880;
	public const TOLERANCE_THRESHOLD = 200;
	public const UNMATCHED_BREAKPOINT = 2000;
	public const MIN_WIDTH_SAMPLE = 768;

}
