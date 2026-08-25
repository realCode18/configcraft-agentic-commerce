<?php
/**
 * Mixed WooCommerce catalog smoke test.
 *
 * @package DestinXAICommerce
 */

require_once '/wordpress/wp-load.php';

$failures = array();
$ids      = array();
$term     = wp_insert_term( 'Catalog Matrix Smoke Test', 'product_cat' );
if ( is_wp_error( $term ) && 'term_exists' === $term->get_error_code() ) {
	$term = array( 'term_id' => (int) $term->get_error_data() );
}
if ( is_wp_error( $term ) ) {
	$failures[] = 'The test product category could not be created.';
}

$complete = static function ( $product, $suffix ) use ( $term ) {
	$product->set_name( 'Complete catalog matrix product ' . $suffix );
	$product->set_description( str_repeat( 'Complete customer-facing product information for catalog readiness validation. ', 3 ) );
	$product->set_status( 'publish' );
	$product->set_sku( 'DXAIC-MATRIX-' . strtoupper( $suffix ) );
	$product->set_image_id( 321 );
	$product->set_category_ids( array( (int) $term['term_id'] ) );
	$attribute = new WC_Product_Attribute();
	$attribute->set_name( 'Material' );
	$attribute->set_options( array( 'Test material' ) );
	$attribute->set_visible( true );
	$product->set_attributes( array( $attribute ) );
	return $product;
};

$save_complete = static function ( $product, $identifier ) use ( &$ids ) {
	$id    = $product->save();
	$ids[] = $id;
	update_post_meta( $id, '_brand', 'DestinX Matrix' );
	update_post_meta( $id, '_gtin', $identifier );
	return $id;
};

if ( empty( $failures ) ) {
	$virtual = $complete( new WC_Product_Simple(), 'virtual' );
	$virtual->set_regular_price( '59.00' );
	$virtual->set_virtual( true );
	$virtual_id = $save_complete( $virtual, 'MATRIX-VIRTUAL' );

	$downloadable = $complete( new WC_Product_Simple(), 'downloadable' );
	$downloadable->set_regular_price( '69.00' );
	$downloadable->set_downloadable( true );
	$downloadable_id = $save_complete( $downloadable, 'MATRIX-DOWNLOADABLE' );

	$external = $complete( new WC_Product_External(), 'external' );
	$external->set_regular_price( '79.00' );
	$external->set_product_url( 'https://example.com/catalog-matrix-product' );
	$external->set_button_text( 'Buy externally' );
	$external_id = $save_complete( $external, 'MATRIX-EXTERNAL' );

	$child_one = $complete( new WC_Product_Simple(), 'group-child-one' );
	$child_one->set_regular_price( '29.00' );
	$child_one->set_weight( '0.4' );
	$child_one_id = $save_complete( $child_one, 'MATRIX-GROUP-001' );

	$child_two = $complete( new WC_Product_Simple(), 'group-child-two' );
	$child_two->set_regular_price( '39.00' );
	$child_two->set_weight( '0.5' );
	$child_two_id = $save_complete( $child_two, 'MATRIX-GROUP-002' );

	$grouped = $complete( new WC_Product_Grouped(), 'grouped' );
	$grouped->set_children( array( $child_one_id, $child_two_id ) );
	$grouped_id = $save_complete( $grouped, 'MATRIX-GROUPED' );

	$draft = $complete( new WC_Product_Simple(), 'draft' );
	$draft->set_regular_price( '89.00' );
	$draft->set_status( 'draft' );
	$draft_id = $save_complete( $draft, 'MATRIX-DRAFT' );

	$extractor = new DestinX\AICommerce\Product_Data_Extractor();
	$evaluator = new DestinX\AICommerce\Product_Readiness_Evaluator();
	foreach ( array( $virtual_id, $downloadable_id, $external_id, $grouped_id ) as $product_id ) {
		$product = wc_get_product( $product_id );
		$data    = $extractor->extract( $product );
		$result  = $evaluator->evaluate( $data );
		if ( 100 !== $result['score'] || 'ready' !== $result['status'] ) {
			$failures[] = sprintf(
				'The complete %1$s product received score %2$d with issues %3$s.',
				$product->get_type(),
				$result['score'],
				implode( ', ', array_column( $result['issues'], 'code' ) )
			);
		}
	}

	$auditor     = new DestinX\AICommerce\Catalog_Auditor( $extractor, $evaluator );
	$audit       = $auditor->audit( 100 );
	$audited_ids = array_map( 'intval', array_column( $audit['products'], 'id' ) );
	if ( in_array( $draft_id, $audited_ids, true ) ) {
		$failures[] = 'A draft product was included in the catalog audit.';
	}
}

foreach ( $ids as $id ) {
	wp_delete_post( $id, true );
}

if ( ! empty( $failures ) ) {
	fwrite( STDERR, implode( PHP_EOL, $failures ) . PHP_EOL );
	exit( 1 );
}

echo 'Mixed catalog smoke test passed.' . PHP_EOL;
