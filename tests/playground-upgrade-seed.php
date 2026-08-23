<?php
/**
 * Seed persistent data while a previous plugin release is active.
 *
 * @package DestinXAICommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	require_once '/wordpress/wp-load.php';
}

$term = wp_insert_term( 'Upgrade Test Products', 'product_cat' );
if ( is_wp_error( $term ) && 'term_exists' === $term->get_error_code() ) {
	$term = array( 'term_id' => (int) $term->get_error_data() );
}

$product = new WC_Product_Simple();
$product->set_name( 'Complete upgrade test product' );
$product->set_description( str_repeat( 'Detailed product information retained during the plugin upgrade. ', 3 ) );
$product->set_status( 'publish' );
$product->set_regular_price( '129.00' );
$product->set_sku( 'DXAIC-UPGRADE-001' );
$product->set_image_id( 321 );
$product->set_category_ids( array( (int) $term['term_id'] ) );
$product->set_weight( '0.9' );
$product->set_length( '30' );
$product->set_width( '20' );
$product->set_height( '12' );

$attribute = new WC_Product_Attribute();
$attribute->set_name( 'Material' );
$attribute->set_options( array( 'Recycled textile' ) );
$attribute->set_visible( true );
$product->set_attributes( array( $attribute ) );

$product_id = $product->save();
update_post_meta( $product_id, '_brand', 'DestinX Upgrade Brand' );
update_post_meta( $product_id, '_gtin', '1234567890123' );

$repository = new DestinX\AICommerce\Audit_Repository();
$extractor  = new DestinX\AICommerce\Product_Data_Extractor();
$evaluator  = new DestinX\AICommerce\Product_Readiness_Evaluator();
$scan_id    = 'upgrade-preserved-snapshot';
$data       = $extractor->extract( wc_get_product( $product_id ) );
$evaluation = $evaluator->evaluate( $data );

if ( 100 !== $evaluation['score'] || ! $repository->save( $scan_id, $product_id, $evaluation, hash( 'sha256', wp_json_encode( $data ) ) ) ) {
	throw new RuntimeException( 'The previous release could not create the upgrade fixture.' );
}

$repository->activate_scan( $scan_id );
update_option(
	DestinX\AICommerce\Background_Audit::STATE_OPTION,
	array(
		'scan_id'          => $scan_id,
		'status'           => 'complete',
		'total'            => 1,
		'processed'        => 1,
		'next_page'        => 1,
		'current_page'     => 1,
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
update_option( 'dxaic_upgrade_fixture_product', $product_id, false );
update_option( 'dxaic_upgrade_fixture_version', DXAIC_VERSION, false );

echo "Previous-release upgrade fixture created.\n";
