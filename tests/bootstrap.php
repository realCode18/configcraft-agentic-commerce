<?php
/**
 * Unit test bootstrap.
 */

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', dirname( __DIR__ ) . '/' );
}

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

if ( ! function_exists( '__' ) ) {
	function __( $text ) {
		return $text;
	}
}

if ( ! function_exists( 'get_option' ) ) {
	function get_option( $name, $default = false ) {
		return isset( $GLOBALS['dxaic_test_options'][ $name ] ) ? $GLOBALS['dxaic_test_options'][ $name ] : $default;
	}
}

if ( ! function_exists( 'wc_get_product' ) ) {
	function wc_get_product( $product_id ) {
		return isset( $GLOBALS['dxaic_test_products'][ $product_id ] ) ? $GLOBALS['dxaic_test_products'][ $product_id ] : false;
	}
}

require_once dirname( __DIR__ ) . '/includes/class-pricing-context.php';
require_once dirname( __DIR__ ) . '/includes/interface-pricing-adapter.php';
require_once dirname( __DIR__ ) . '/includes/class-name-your-price-adapter.php';
require_once dirname( __DIR__ ) . '/includes/class-yith-request-a-quote-adapter.php';
require_once dirname( __DIR__ ) . '/includes/class-call-for-price-adapter.php';
require_once dirname( __DIR__ ) . '/includes/class-container-pricing-adapter.php';
require_once dirname( __DIR__ ) . '/includes/class-measurement-price-calculator-adapter.php';
require_once dirname( __DIR__ ) . '/includes/class-product-addons-adapter.php';
require_once dirname( __DIR__ ) . '/includes/class-pricing-adapter-registry.php';
require_once dirname( __DIR__ ) . '/includes/class-product-readiness-evaluator.php';
require_once dirname( __DIR__ ) . '/includes/class-scan-state.php';
require_once dirname( __DIR__ ) . '/includes/class-store-readiness-evaluator.php';
require_once dirname( __DIR__ ) . '/includes/class-catalog-csv-exporter.php';
