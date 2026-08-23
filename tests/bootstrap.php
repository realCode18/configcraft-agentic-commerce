<?php
/**
 * Unit test bootstrap.
 */

if ( ! function_exists( 'wp_strip_all_tags' ) ) {
	function wp_strip_all_tags( $text ) {
		return strip_tags( $text );
	}
}

if ( ! function_exists( 'apply_filters' ) ) {
	function apply_filters( $hook_name, $value ) {
		return $value;
	}
}

require_once dirname( __DIR__ ) . '/includes/class-product-readiness-evaluator.php';
require_once dirname( __DIR__ ) . '/includes/class-scan-state.php';
require_once dirname( __DIR__ ) . '/includes/class-store-readiness-evaluator.php';
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', dirname( __DIR__ ) . '/' );
}
require_once dirname( __DIR__ ) . '/includes/class-catalog-csv-exporter.php';
