<?php
/**
 * Real-plugin pricing compatibility smoke test.
 *
 * @package DestinXAICommerce
 */

require_once '/wordpress/wp-load.php';

$failures = array();

if ( ! defined( 'YITH_YWRAQ_VERSION' ) ) {
	$failures[] = 'YITH Request a Quote did not load.';
}

$call_for_price_class = 'TycheSoftwares\\CallForPrice\\Lite\\Compatibility';
if ( ! class_exists( $call_for_price_class ) ) {
	$failures[] = 'Call for Price for WooCommerce did not load.';
}

if ( ! class_exists( 'DestinX\\AICommerce\\Pricing_Adapter_Registry' ) ) {
	$failures[] = 'The DestinX pricing adapter registry did not load.';
}

if ( empty( $failures ) ) {
	update_option( 'ywraq_show_btn_single_page', 'yes', false );

	$yith_product = new WC_Product_Simple();
	$yith_product->set_name( 'Custom industrial product available by quotation' );
	$yith_product->set_status( 'publish' );
	$yith_product_id = $yith_product->save();

	$extractor  = new DestinX\AICommerce\Product_Data_Extractor();
	$evaluator  = new DestinX\AICommerce\Product_Readiness_Evaluator();
	$yith_data  = $extractor->extract( wc_get_product( $yith_product_id ) );
	$yith_audit = $evaluator->evaluate( $yith_data );
	if ( 'quote' !== $yith_audit['pricing']['mode'] || 'yith_request_a_quote' !== $yith_audit['pricing']['adapter'] ) {
		$failures[] = 'YITH quote pricing was not verified: ' . wp_json_encode( $yith_audit['pricing'] ) . '.';
	}
	if ( in_array( 'price_missing', array_column( $yith_audit['issues'], 'code' ), true ) ) {
		$failures[] = 'A verified YITH quote product received a missing-price finding.';
	}

	$call_product = new WC_Product_Simple();
	$call_product->set_name( 'Made to order product with call for price' );
	$call_product->set_status( 'publish' );
	$call_product_id = $call_product->save();
	update_post_meta( $call_product_id, '_ywraq_hide_quote_button', '1' );

	$call_data  = $extractor->extract( wc_get_product( $call_product_id ) );
	$call_audit = $evaluator->evaluate( $call_data );
	if ( 'quote' !== $call_audit['pricing']['mode'] || 'woocommerce_call_for_price' !== $call_audit['pricing']['adapter'] ) {
		$failures[] = 'Call for Price pricing was not verified: ' . wp_json_encode( $call_audit['pricing'] ) . '.';
	}
	if ( in_array( 'price_missing', array_column( $call_audit['issues'], 'code' ), true ) ) {
		$failures[] = 'A verified Call for Price product received a missing-price finding.';
	}

	$settings = get_option( 'cfp_pro_settings', array() );
	if ( ! is_array( $settings ) ) {
		$settings = array();
	}
	$settings['general']['enabled']        = true;
	$settings['general']['logged_in_only'] = array( 'administrator' );
	update_option( 'cfp_pro_settings', $settings, false );
	$call_for_price_class::bust_cache();

	$restricted_product = new WC_Product_Simple();
	$restricted_product->set_name( 'Restricted quote product without public pricing path' );
	$restricted_product->set_status( 'publish' );
	$restricted_product_id = $restricted_product->save();
	update_post_meta( $restricted_product_id, '_ywraq_hide_quote_button', '1' );

	$restricted_audit = $evaluator->evaluate( $extractor->extract( wc_get_product( $restricted_product_id ) ) );
	if ( ! in_array( 'price_missing', array_column( $restricted_audit['issues'], 'code' ), true ) ) {
		$failures[] = 'A role-restricted quote route incorrectly suppressed the missing-price finding.';
	}

	wp_delete_post( $yith_product_id, true );
	wp_delete_post( $call_product_id, true );
	wp_delete_post( $restricted_product_id, true );
}

if ( ! empty( $failures ) ) {
	fwrite( STDERR, implode( PHP_EOL, $failures ) . PHP_EOL );
	exit( 1 );
}

echo "Pricing compatibility smoke test passed.\n";
