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
	$product->set_status( 'publish' );
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

	if ( ConfigCraft\AgenticCommerce\Database::VERSION !== get_option( ConfigCraft\AgenticCommerce\Database::VERSION_OPTION ) ) {
		$failures[] = 'The plugin database schema was not installed.';
	}

	for ( $index = 1; $index <= 5; $index++ ) {
		$incomplete_product = new WC_Product_Simple();
		$incomplete_product->set_name( 'Incomplete smoke product ' . $index );
		$incomplete_product->set_status( 'publish' );
		$incomplete_product->save();
	}

	$repository = new ConfigCraft\AgenticCommerce\Audit_Repository();
	$background = new ConfigCraft\AgenticCommerce\Background_Audit( $repository, $extractor, $evaluator );
	add_filter(
		'configcraft_agentic_commerce_batch_size',
		static function () {
			return 5;
		}
	);
	delete_option( ConfigCraft\AgenticCommerce\Background_Audit::STATE_OPTION );
	$repository->clear();

	if ( ! $background->start() ) {
		$failures[] = 'The full catalog scan did not start.';
	} else {
		$queued_state = $background->get_state();
		if ( 'queued' !== $queued_state['status'] || 6 !== $queued_state['total'] ) {
			$failures[] = 'The full catalog scan did not enter the expected queued state.';
		}

		$background->process_batch( 1 );
		$running_state = $background->get_state();
		if ( 'running' !== $running_state['status'] || 5 !== $running_state['processed'] ) {
			$failures[] = 'The full catalog scan did not continue to its second batch.';
		}

		$background->process_batch( 2 );
		$complete_state = $background->get_state();
		if ( 'complete' !== $complete_state['status'] || 6 !== $complete_state['processed'] ) {
			$failures[] = 'The full catalog scan did not complete.';
		}

		$stored_summary = $repository->get_summary();
		if ( 6 !== $stored_summary['scanned'] || 47 !== $stored_summary['average_score'] || 1 !== $stored_summary['ready'] ) {
			$failures[] = 'The stored catalog summary is incorrect: ' . wp_json_encode( $stored_summary ) . '.';
		}
		if ( 0 !== $stored_summary['needs_work'] || 5 !== $stored_summary['at_risk'] ) {
			$failures[] = 'The stored status totals are incorrect.';
		}

		$first_page  = $repository->get_page( 1, 5 );
		$second_page = $repository->get_page( 2, 5 );
		if ( 5 !== count( $first_page ) || 36 !== $first_page[0]['score'] ) {
			$failures[] = 'The lowest-scoring persisted results were not returned first: ' . wp_json_encode( $first_page ) . '.';
		}
		if ( 1 !== count( $second_page ) || $product_id !== $second_page[0]['product_id'] || 100 !== $second_page[0]['score'] ) {
			$failures[] = 'The persisted product result could not be read.';
		}

		wp_set_current_user( 1 );
		$admin_page = new ConfigCraft\AgenticCommerce\Admin_Page( $auditor, $repository, $background );
		ob_start();
		$admin_page->render();
		$dashboard_html = ob_get_clean();
		if ( false === strpos( $dashboard_html, 'Catalog results' ) || false === strpos( $dashboard_html, '6/6' ) ) {
			$failures[] = 'The persisted catalog dashboard did not render its completed results.';
		}

		$meta_box = new ConfigCraft\AgenticCommerce\Product_Meta_Box( $extractor, $evaluator );
		ob_start();
		$meta_box->render( get_post( $product_id ) );
		$product_panel_html = ob_get_clean();
		if ( false === strpos( $product_panel_html, '100/100' ) || false === strpos( $product_panel_html, 'No catalog findings' ) ) {
			$failures[] = 'The product readiness panel did not render the live evaluation.';
		}
	}

	if ( '' === ConfigCraft\AgenticCommerce\Issue_Catalog::guidance( 'brand_missing' ) ) {
		$failures[] = 'Product remediation guidance is unavailable.';
	}
}

if ( ! empty( $failures ) ) {
	fwrite( STDERR, implode( PHP_EOL, $failures ) . PHP_EOL );
	exit( 1 );
}

echo "WordPress and WooCommerce smoke test passed.\n";
