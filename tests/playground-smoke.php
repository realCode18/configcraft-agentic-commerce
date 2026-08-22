<?php
/**
 * WordPress Playground smoke test.
 *
 * @package ConfigCraftAgenticCommerce
 */

require_once '/wordpress/wp-load.php';
require_once ABSPATH . 'wp-admin/includes/plugin.php';

$failures = array();

if ( ! class_exists( 'WooCommerce' ) ) {
	$failures[] = 'WooCommerce did not load.';
}

if ( ! is_plugin_active( 'configcraft-agentic-commerce/configcraft-agentic-commerce.php' ) ) {
	$failures[] = 'ConfigCraft Agentic Commerce is not active.';
}

if ( ! class_exists( 'ConfigCraft\\AgenticCommerce\\Catalog_Auditor' ) ) {
	$failures[] = 'Plugin services did not load.';
}

if ( empty( $failures ) ) {
	$term = wp_insert_term( 'Smoke Test Products', 'product_cat' );
	if ( is_wp_error( $term ) && 'term_exists' === $term->get_error_code() ) {
		$term = array( 'term_id' => (int) $term->get_error_data() );
	}

	$product = new WC_Product_Simple();
	$product->set_name( 'Waterproof hiking shoe for mountain trails' );
	$product->set_description( str_repeat( 'Detailed information for customers and machine-readable catalog systems. ', 3 ) );
	$product->set_regular_price( '119.00' );
	$product->set_sku( 'CCAC-SMOKE-001' );
	$product->set_image_id( 123 );
	$product->set_category_ids( array( (int) $term['term_id'] ) );
	$product->set_weight( '0.8' );
	$product->set_length( '30' );
	$product->set_width( '20' );
	$product->set_height( '12' );

	$attribute = new WC_Product_Attribute();
	$attribute->set_name( 'Material' );
	$attribute->set_options( array( 'Leather' ) );
	$attribute->set_visible( true );
	$product->set_attributes( array( $attribute ) );

	$product_id = $product->save();
	update_post_meta( $product_id, '_brand', 'ConfigCraft Test Brand' );
	update_post_meta( $product_id, '_gtin', '1234567890123' );

	$extractor = new ConfigCraft\AgenticCommerce\Product_Data_Extractor();
	$evaluator = new ConfigCraft\AgenticCommerce\Product_Readiness_Evaluator();
	$auditor   = new ConfigCraft\AgenticCommerce\Catalog_Auditor( $extractor, $evaluator );
	$audit     = $auditor->audit( 25 );
	$match     = null;

	foreach ( $audit['products'] as $audited_product ) {
		if ( $product_id === $audited_product['id'] ) {
			$match = $audited_product;
			break;
		}
	}

	if ( null === $match ) {
		$failures[] = 'The saved WooCommerce product was not audited.';
	} elseif ( 100 !== $match['score'] ) {
		$failures[] = 'Expected a readiness score of 100, received ' . $match['score'] . '.';
	}
}

if ( ! empty( $failures ) ) {
	fwrite( STDERR, implode( PHP_EOL, $failures ) . PHP_EOL );
	exit( 1 );
}

echo "WordPress and WooCommerce smoke test passed.\n";
