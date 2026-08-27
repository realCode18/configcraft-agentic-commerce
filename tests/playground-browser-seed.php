<?php
/**
 * Populate an ephemeral Playground site for visual dashboard QA.
 *
 * @package DestinXAICommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	require_once '/wordpress/wp-load.php';
}

if ( ! current_user_can( 'manage_woocommerce' ) ) {
	wp_die( esc_html__( 'You do not have permission to seed visual test data.', 'destinx-ai-commerce' ) );
}

$term = wp_insert_term( 'Visual QA Products', 'product_cat' );
if ( is_wp_error( $term ) && 'term_exists' === $term->get_error_code() ) {
	$term = array( 'term_id' => (int) $term->get_error_data() );
}

$repository = new DestinX\AICommerce\Audit_Repository();
$extractor  = new DestinX\AICommerce\Product_Data_Extractor();
$evaluator  = new DestinX\AICommerce\Product_Readiness_Evaluator();
$scan_id    = 'visual-qa-complete';

for ( $index = 1; $index <= 24; $index++ ) {
	$product = new WC_Product_Simple();
	$product->set_name( 0 === $index % 3 ? 'Complete trail product ' . $index : 'Item ' . $index );
	$product->set_status( 'publish' );
	$product->set_sku( 0 === $index % 4 ? '' : 'VISUAL-' . str_pad( (string) $index, 3, '0', STR_PAD_LEFT ) );

	if ( 0 === $index % 3 ) {
		$product->set_description( str_repeat( 'Complete product information for shoppers and catalog systems. ', 3 ) );
		$product->set_regular_price( (string) ( 80 + $index ) );
		$product->set_image_id( 200 + $index );
		$product->set_category_ids( array( (int) $term['term_id'] ) );
		$product->set_weight( '0.8' );
		$attribute = new WC_Product_Attribute();
		$attribute->set_name( 'Material' );
		$attribute->set_options( array( 'Recycled fabric' ) );
		$attribute->set_visible( true );
		$product->set_attributes( array( $attribute ) );
	}

	$product_id = $product->save();
	if ( 0 === $index % 3 ) {
		update_post_meta( $product_id, '_brand', 'DestinX Visual Brand' );
		update_post_meta( $product_id, '_gtin', '123456789' . str_pad( (string) $index, 4, '0', STR_PAD_LEFT ) );
	}

	$data       = $extractor->extract( wc_get_product( $product_id ) );
	$evaluation = $evaluator->evaluate( $data );
	$repository->save( $scan_id, $product_id, $evaluation, hash( 'sha256', wp_json_encode( $data ) ) );
}

$repository->activate_scan( $scan_id );
update_option(
	DestinX\AICommerce\Background_Audit::STATE_OPTION,
	array(
		'scan_id'          => $scan_id,
		'status'           => 'complete',
		'total'            => 24,
		'processed'        => 24,
		'next_page'        => 3,
		'current_page'     => 2,
		'retry_count'      => 0,
		'reconcile_count'  => 0,
		'heartbeat'        => time(),
		'catalog_revision' => get_option( DestinX\AICommerce\Background_Audit::CATALOG_REVISION_OPTION, '' ),
		'model_version'    => DestinX\AICommerce\Product_Readiness_Evaluator::MODEL_VERSION,
		'started_at'       => current_time( 'mysql', true ),
		'finished_at'      => current_time( 'mysql', true ),
		'error'            => '',
	),
	false
);

echo esc_html__( 'Visual test data created.', 'destinx-ai-commerce' );
