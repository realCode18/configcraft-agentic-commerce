<?php
/**
 * WordPress Playground smoke test.
 *
 * @package DestinXAICommerce
 */

require_once '/wordpress/wp-load.php';
require_once ABSPATH . 'wp-admin/includes/plugin.php';

$failures = array();

if ( defined( 'DXAIC_EXPECTED_WP' ) && 0 !== strpos( get_bloginfo( 'version' ), DXAIC_EXPECTED_WP ) ) {
	$failures[] = 'Expected WordPress ' . DXAIC_EXPECTED_WP . ', loaded ' . get_bloginfo( 'version' ) . '.';
}

if ( defined( 'DXAIC_EXPECTED_WC' ) && ( ! defined( 'WC_VERSION' ) || DXAIC_EXPECTED_WC !== WC_VERSION ) ) {
	$loaded_woocommerce = defined( 'WC_VERSION' ) ? WC_VERSION : 'not loaded';
	$failures[]         = 'Expected WooCommerce ' . DXAIC_EXPECTED_WC . ', loaded ' . $loaded_woocommerce . '.';
}

if ( ! class_exists( 'WooCommerce' ) ) {
	$failures[] = 'WooCommerce did not load.';
}

if ( ! is_plugin_active( 'destinx-ai-commerce/destinx-ai-commerce.php' ) ) {
	$failures[] = 'DestinX AI Commerce for WooCommerce is not active.';
}

if ( ! class_exists( 'DestinX\\AICommerce\\Catalog_Auditor' ) ) {
	$failures[] = 'Plugin services did not load.';
}

if ( class_exists( 'Automattic\\WooCommerce\\Utilities\\FeaturesUtil' ) ) {
	$compatibility = Automattic\WooCommerce\Utilities\FeaturesUtil::get_compatible_features_for_plugin( 'destinx-ai-commerce/destinx-ai-commerce.php' );
	if ( ! in_array( 'custom_order_tables', $compatibility['compatible'], true ) ) {
		$failures[] = 'HPOS compatibility was not declared.';
	}
}

$plugin_headers = get_file_data(
	DXAIC_FILE,
	array(
		'wc_requires' => 'WC requires at least',
		'wc_tested'   => 'WC tested up to',
	)
);
if ( '8.2' !== $plugin_headers['wc_requires'] || '11.0' !== $plugin_headers['wc_tested'] ) {
	$failures[] = 'WooCommerce compatibility headers are missing or unexpected.';
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
	$product->set_sku( 'DXAIC-SMOKE-001' );
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
	update_post_meta( $product_id, '_brand', 'DestinX Test Brand' );
	update_post_meta( $product_id, '_gtin', '1234567890123' );

	$extractor = new DestinX\AICommerce\Product_Data_Extractor();
	$evaluator = new DestinX\AICommerce\Product_Readiness_Evaluator();
	$auditor   = new DestinX\AICommerce\Catalog_Auditor( $extractor, $evaluator );
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

	$dynamic_pricing_filter = static function ( $data, $candidate ) use ( $product_id ) {
		if ( (int) $candidate->get_id() !== $product_id ) {
			return $data;
		}

		$data['price']   = '';
		$data['pricing'] = array(
			'mode'         => 'dynamic',
			'source'       => 'smoke_pricing_engine',
			'label'        => 'Smoke pricing engine',
			'is_available' => true,
		);
		$data['is_purchasable'] = false;

		return $data;
	};
	add_filter( 'destinx_ai_commerce_product_data', $dynamic_pricing_filter, 10, 2 );
	$dynamic_evaluation = $evaluator->evaluate( $extractor->extract( wc_get_product( $product_id ) ) );
	remove_filter( 'destinx_ai_commerce_product_data', $dynamic_pricing_filter, 10 );
	if ( 100 !== $dynamic_evaluation['score'] || 'dynamic' !== $dynamic_evaluation['pricing']['mode'] ) {
		$failures[] = 'A declared dynamic price was not accepted by the public pricing contract: ' . wp_json_encode( $dynamic_evaluation ) . '.';
	}
	if ( in_array( 'price_missing', array_column( $dynamic_evaluation['issues'], 'code' ), true ) ) {
		$failures[] = 'A declared dynamic price received a missing-price finding.';
	}

	$variable_attribute = new WC_Product_Attribute();
	$variable_attribute->set_name( 'Size' );
	$variable_attribute->set_options( array( 'Medium' ) );
	$variable_attribute->set_visible( true );
	$variable_attribute->set_variation( true );
	$variable_product = new WC_Product_Variable();
	$variable_product->set_name( 'Technical trail jacket with adjustable fit' );
	$variable_product->set_description( str_repeat( 'Detailed variable product information for customers and catalog systems. ', 3 ) );
	$variable_product->set_status( 'publish' );
	$variable_product->set_sku( 'DXAIC-VARIABLE-001' );
	$variable_product->set_image_id( 124 );
	$variable_product->set_category_ids( array( (int) $term['term_id'] ) );
	$variable_product->set_attributes( array( $variable_attribute ) );
	$variable_product_id = $variable_product->save();
	update_post_meta( $variable_product_id, '_brand', 'DestinX Test Brand' );
	update_post_meta( $variable_product_id, '_gtin', '1234567890124' );

	$variation = new WC_Product_Variation();
	$variation->set_parent_id( $variable_product_id );
	$variation->set_status( 'publish' );
	$variation->set_regular_price( '149.00' );
	$variation->set_sku( 'DXAIC-VARIABLE-001-M' );
	$variation->set_attributes( array( 'size' => 'Medium' ) );
	$variation->set_weight( '0.6' );
	$variation_id = $variation->save();
	WC_Product_Variable::sync( $variable_product_id );
	clean_post_cache( $variable_product_id );

	$variable_product    = wc_get_product( $variable_product_id );
	$variable_data       = $extractor->extract( $variable_product );
	$variable_evaluation = $evaluator->evaluate( $variable_data );
	if ( 1 !== $variable_data['variation_count'] || 1 !== $variable_data['purchasable_variation_count'] ) {
		$failures[] = 'The variable-product extractor did not find its purchasable variation.';
	}
	if ( 100 !== $variable_evaluation['score'] || 'ready' !== $variable_evaluation['status'] ) {
		$failures[] = 'The complete variable product did not pass model 1.0.0: ' . wp_json_encode( $variable_evaluation ) . '.';
	}
	wp_delete_post( $variation_id, true );
	wp_delete_post( $variable_product_id, true );

	if ( DestinX\AICommerce\Database::VERSION !== get_option( DestinX\AICommerce\Database::VERSION_OPTION ) ) {
		$failures[] = 'The plugin database schema was not installed.';
	}

	global $wpdb;
	$legacy_table = DestinX\AICommerce\Database::legacy_table_name();
	$new_table    = DestinX\AICommerce\Database::table_name();
	$pricing_column = $wpdb->get_var( $wpdb->prepare( 'SHOW COLUMNS FROM %i LIKE %s', $new_table, 'pricing' ) );
	if ( 'pricing' !== $pricing_column ) {
		$failures[] = 'The pricing context column is missing from the audit schema.';
	}
	$wpdb->query( $wpdb->prepare( 'DROP TABLE IF EXISTS %i', $legacy_table ) );
	$wpdb->query( $wpdb->prepare( 'DELETE FROM %i', $new_table ) );
	delete_option( DestinX\AICommerce\Database::ACTIVE_SCAN_OPTION );
	$wpdb->query(
		"CREATE TABLE {$legacy_table} (
			product_id bigint(20) unsigned NOT NULL,
			score smallint(3) unsigned NOT NULL DEFAULT 0,
			status varchar(20) NOT NULL DEFAULT '',
			issues longtext NOT NULL,
			scanned_at datetime NOT NULL,
			PRIMARY KEY (product_id)
		) {$wpdb->get_charset_collate()}"
	);
	$wpdb->insert(
		$legacy_table,
		array(
			'product_id' => $product_id,
			'score'      => 73,
			'status'     => 'needs_work',
			'issues'     => '[]',
			'scanned_at' => current_time( 'mysql', true ),
		),
		array( '%d', '%d', '%s', '%s', '%s' )
	);
	update_option( DestinX\AICommerce\Database::VERSION_OPTION, '1.0.0', false );
	DestinX\AICommerce\Database::install();

	$migration_repository = new DestinX\AICommerce\Audit_Repository();
	$migration_summary    = $migration_repository->get_summary();
	$legacy_after_upgrade = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $legacy_table ) ) );
	if ( 1 !== $migration_summary['scanned'] || 73 !== $migration_summary['average_score'] ) {
		$failures[] = 'The pre-snapshot audit result was not migrated.';
	}
	if ( $legacy_table === $legacy_after_upgrade ) {
		$failures[] = 'The legacy audit table remained after a successful migration.';
	}
	$migration_repository->clear();

	$repository = new DestinX\AICommerce\Audit_Repository();
	$background = new DestinX\AICommerce\Background_Audit( $repository, $extractor, $evaluator );
	add_filter(
		'destinx_ai_commerce_batch_size',
		static function () {
			return 5;
		}
	);
	delete_option( DestinX\AICommerce\Background_Audit::STATE_OPTION );
	delete_option( DestinX\AICommerce\Background_Audit::START_LOCK_OPTION );
	delete_option( DestinX\AICommerce\Background_Audit::PROCESS_LOCK_OPTION );
	$repository->clear();

	if ( ! $background->start() ) {
		$failures[] = 'The initial full catalog scan did not start.';
	} else {
		$initial_state = $background->get_state();
		if ( 'queued' !== $initial_state['status'] || 1 !== $initial_state['total'] || empty( $initial_state['scan_id'] ) ) {
			$failures[] = 'The initial scan did not enter the expected queued state.';
		}
		if ( $background->start() ) {
			$failures[] = 'A second scan started while the initial scan was still queued.';
		}

		$background->process_batch( $initial_state['scan_id'], 1, 0 );
		$initial_complete = $background->get_state();
		$initial_summary  = $repository->get_summary();
		if ( 'complete' !== $initial_complete['status'] || 1 !== $initial_complete['processed'] ) {
			$failures[] = 'The initial single-product snapshot did not complete.';
		}
		if ( 1 !== $initial_summary['scanned'] || 100 !== $initial_summary['average_score'] ) {
			$failures[] = 'The initial snapshot was not activated.';
		}

		$scan_complete_event = null;
		add_action(
			'destinx_ai_commerce_scan_completed',
			static function ( $payload ) use ( &$scan_complete_event ) {
				$scan_complete_event = $payload;
			}
		);

		if ( ! $background->start( true ) ) {
			$failures[] = 'The immediate first-batch scan did not start.';
		} else {
			$immediate_state = $background->get_state();
			if ( 'complete' !== $immediate_state['status'] || 1 !== $immediate_state['processed'] ) {
				$failures[] = 'The immediate first batch did not complete the small catalog scan.';
			}
			if ( ! is_array( $scan_complete_event ) || '1.0.0' !== ( $scan_complete_event['api_version'] ?? '' ) || 1 !== ( $scan_complete_event['summary']['scanned'] ?? 0 ) ) {
				$failures[] = 'The completed scan did not emit the stable extension event payload.';
			}
		}

		$incomplete_product_ids = array();
		for ( $index = 1; $index <= 5; $index++ ) {
			$incomplete_product = new WC_Product_Simple();
			$name = 1 === $index ? '=Incomplete smoke product 1' : 'Incomplete smoke product ' . $index;
			$incomplete_product->set_name( $name );
			$incomplete_product->set_status( 'publish' );
			$incomplete_product_ids[] = $incomplete_product->save();
		}

		if ( ! $background->start() ) {
			$failures[] = 'The replacement catalog scan did not start.';
		}

		$queued_state = $background->get_state();
		if ( 'queued' !== $queued_state['status'] || 6 !== $queued_state['total'] || $initial_state['scan_id'] === $queued_state['scan_id'] ) {
			$failures[] = 'The replacement scan did not receive an isolated snapshot.';
		}

		$visible_while_queued = $repository->get_summary();
		if ( 1 !== $visible_while_queued['scanned'] || 100 !== $visible_while_queued['average_score'] ) {
			$failures[] = 'Starting a new scan replaced the last valid snapshot too early.';
		}

		$background->process_batch( $queued_state['scan_id'], 1, 0 );
		$running_state = $background->get_state();
		if ( 'running' !== $running_state['status'] || 5 !== $running_state['processed'] ) {
			$failures[] = 'The full catalog scan did not continue to its second batch.';
		}
		$visible_while_running = $repository->get_summary();
		if ( 1 !== $visible_while_running['scanned'] || 100 !== $visible_while_running['average_score'] ) {
			$failures[] = 'A partial staging snapshot became visible.';
		}

		$background->process_batch( $queued_state['scan_id'], 1, 0 );
		$duplicate_state = $background->get_state();
		if ( 5 !== $duplicate_state['processed'] || 2 !== $duplicate_state['next_page'] ) {
			$failures[] = 'A duplicate batch was not handled idempotently.';
		}

		$wpdb->update(
			$wpdb->posts,
			array( 'post_modified_gmt' => gmdate( 'Y-m-d H:i:s', time() + 60 ) ),
			array( 'ID' => $product_id ),
			array( '%s' ),
			array( '%d' )
		);
		clean_post_cache( $product_id );
		$background->process_batch( $queued_state['scan_id'], 2, 0 );
		$reconcile_state = $background->get_state();
		if ( 'queued' !== $reconcile_state['status'] || 0 !== $reconcile_state['processed'] || 1 !== $reconcile_state['reconcile_count'] ) {
			$failures[] = 'A catalog change during scanning did not trigger one clean reconciliation pass.';
		}
		$visible_during_reconciliation = $repository->get_summary();
		if ( 1 !== $visible_during_reconciliation['scanned'] || 100 !== $visible_during_reconciliation['average_score'] ) {
			$failures[] = 'Reconciliation replaced the last valid snapshot too early.';
		}

		$background->process_batch( $queued_state['scan_id'], 1, 0 );
		$background->process_batch( $queued_state['scan_id'], 2, 0 );
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

		$public_api = function_exists( 'destinx_ai_commerce_api' ) ? destinx_ai_commerce_api() : null;
		if ( ! $public_api instanceof DestinX\AICommerce\Public_API || '1.0.0' !== $public_api->get_version() || DXAIC_VERSION !== $public_api->get_plugin_version() ) {
			$failures[] = 'The public read-only extension API is unavailable or has an unexpected version.';
		} elseif ( 6 !== $public_api->get_summary()['scanned'] || 2 !== count( $public_api->get_results( 1, 2 ) ) || 'complete' !== $public_api->get_scan_state()['status'] ) {
			$failures[] = 'The public extension API did not expose the active Free snapshot correctly.';
		}

		$first_page  = $repository->get_page( 1, 5 );
		$second_page = $repository->get_page( 2, 5 );
		if ( 5 !== count( $first_page ) || 36 !== $first_page[0]['score'] ) {
			$failures[] = 'The lowest-scoring persisted results were not returned first: ' . wp_json_encode( $first_page ) . '.';
		}
		if ( 1 !== count( $second_page ) || $product_id !== $second_page[0]['product_id'] || 100 !== $second_page[0]['score'] ) {
			$failures[] = 'The persisted product result could not be read.';
		}
		if ( 64 !== strlen( $second_page[0]['product_hash'] ) ) {
			$failures[] = 'The product data hash was not stored with the snapshot.';
		}
		if ( DestinX\AICommerce\Product_Readiness_Evaluator::MODEL_VERSION !== $second_page[0]['model_version'] ) {
			$failures[] = 'The scoring model version was not stored with the snapshot: ' . wp_json_encode( $second_page[0] ) . '.';
		}
		if ( 'fixed' !== $second_page[0]['pricing']['mode'] || 'woocommerce' !== $second_page[0]['pricing']['source'] || empty( $second_page[0]['pricing']['is_available'] ) ) {
			$failures[] = 'The normalized pricing context was not stored with the snapshot: ' . wp_json_encode( $second_page[0] ) . '.';
		}

		update_post_meta( $incomplete_product_ids[0], '_sku', 'DXAIC-SMOKE-001-EXTENDED' );
		if ( 1 !== $repository->count( array( 'search' => 'DXAIC-SMOKE-001' ) ) ) {
			$failures[] = 'Exact SKU search returned a partial-SKU collision.';
		}
		if ( 5 !== $repository->count( array( 'search' => 'Incomplete smoke product' ) ) ) {
			$failures[] = 'Searching the active snapshot by product name did not return all matching products.';
		}
		if ( 5 !== $repository->count( array( 'status' => 'at_risk' ) ) ) {
			$failures[] = 'The catalog status filter returned an incorrect count.';
		}
		if ( 5 !== $repository->count( array( 'issue' => 'brand_missing' ) ) ) {
			$failures[] = 'The catalog finding filter returned an incorrect count.';
		}
		if ( 1 !== $repository->count( array( 'category' => (int) $term['term_id'] ) ) ) {
			$failures[] = 'The product category filter returned an incorrect count.';
		}

		$filtered_rows = $repository->get_page(
			1,
			20,
			array(
				'status' => 'at_risk',
				'issue'  => 'sku_missing',
			)
		);
		if ( 5 !== count( $filtered_rows ) ) {
			$failures[] = 'Combined catalog filters did not return the expected products.';
		}

		$snapshot_metadata = $repository->get_snapshot_metadata();
		if ( 6 !== $snapshot_metadata['products'] || empty( $snapshot_metadata['scanned_at'] ) || DestinX\AICommerce\Product_Readiness_Evaluator::MODEL_VERSION !== $snapshot_metadata['model_version'] ) {
			$failures[] = 'The visible snapshot metadata is incomplete: ' . wp_json_encode( $snapshot_metadata ) . '.';
		}

		$csv_exporter = new DestinX\AICommerce\Catalog_Csv_Exporter( $repository );
		$csv_stream   = fopen( 'php://temp', 'w+' );
		$exported     = $csv_exporter->export( $csv_stream, array( 'issue' => 'brand_missing' ) );
		rewind( $csv_stream );
		$csv_content = stream_get_contents( $csv_stream );
		fclose( $csv_stream );
		if ( 5 !== $exported || 0 !== strpos( $csv_content, "\xEF\xBB\xBF" ) ) {
			$failures[] = 'The filtered CSV export did not return five UTF-8 rows.';
		}
		if ( false === strpos( $csv_content, "'=Incomplete smoke product 1" ) || false === strpos( $csv_content, 'brand_missing' ) || false === strpos( $csv_content, 'Fixed price' ) ) {
			$failures[] = 'The CSV export did not neutralize formulas or include finding codes.';
		}
		if ( false === has_action( 'admin_post_' . DestinX\AICommerce\Catalog_Csv_Exporter::ACTION ) ) {
			$failures[] = 'The authenticated CSV export action was not registered.';
		}

		$wp_die_filter = static function () {
			return static function ( $message ) {
				throw new RuntimeException( wp_strip_all_tags( (string) $message ) );
			};
		};
		add_filter( 'wp_die_handler', $wp_die_filter );
		wp_set_current_user( 0 );
		$capability_blocked = false;
		try {
			$csv_exporter->handle_request();
		} catch ( RuntimeException $exception ) {
			$capability_blocked = false !== strpos( $exception->getMessage(), 'permission' );
		}
		if ( ! $capability_blocked ) {
			$failures[] = 'The CSV export handler did not reject an unauthorized user.';
		}

		wp_set_current_user( 1 );
		$_REQUEST['_wpnonce'] = 'invalid-export-nonce';
		$_POST['_wpnonce']    = 'invalid-export-nonce';
		$nonce_blocked        = false;
		try {
			$csv_exporter->handle_request();
		} catch ( RuntimeException $exception ) {
			$nonce_blocked = true;
		}
		if ( ! $nonce_blocked ) {
			$failures[] = 'The CSV export handler did not reject an invalid nonce.';
		}
		unset( $_REQUEST['_wpnonce'], $_POST['_wpnonce'] );
		remove_filter( 'wp_die_handler', $wp_die_filter );

		$stale_scan_id = wp_generate_uuid4();
		$repository->save( $stale_scan_id, $product_id, $evaluator->evaluate( $extractor->extract( wc_get_product( $product_id ) ) ) );
		update_option(
			DestinX\AICommerce\Background_Audit::STATE_OPTION,
			array(
				'scan_id'     => $stale_scan_id,
				'status'      => 'queued',
				'total'       => 6,
				'processed'   => 1,
				'next_page'   => 1,
				'heartbeat'   => time() - DestinX\AICommerce\Background_Audit::DEFAULT_STALE_AFTER - 1,
				'started_at'  => current_time( 'mysql', true ),
				'finished_at' => '',
				'error'       => '',
			),
			false
		);
		$recovered_state = $background->get_state();
		if ( 'failed' !== $recovered_state['status'] || 0 !== $repository->count_for_scan( $stale_scan_id ) ) {
			$failures[] = 'The stale scan was not released and discarded.';
		}
		$visible_after_recovery = $repository->get_summary();
		if ( 6 !== $visible_after_recovery['scanned'] || 47 !== $visible_after_recovery['average_score'] ) {
			$failures[] = 'Stale scan recovery changed the active snapshot.';
		}

		$throw_once = true;
		$failure_callback = static function ( $issues ) use ( &$throw_once ) {
			if ( $throw_once ) {
				$throw_once = false;
				throw new RuntimeException( 'Intentional smoke-test batch failure.' );
			}

			return $issues;
		};
		add_filter( 'destinx_ai_commerce_product_issues', $failure_callback, 99 );
		if ( ! $background->start() ) {
			$failures[] = 'A new scan could not start after stale-scan recovery.';
		}
		$retry_scan = $background->get_state();
		$background->process_batch( $retry_scan['scan_id'], 1, 0 );
		$retry_state = $background->get_state();
		if ( 'queued' !== $retry_state['status'] || 1 !== $retry_state['retry_count'] ) {
			$failures[] = 'A temporary batch failure was not queued for a bounded retry.';
		}
		if ( 6 !== $repository->get_summary()['scanned'] ) {
			$failures[] = 'A temporary batch failure replaced the active snapshot.';
		}
		remove_filter( 'destinx_ai_commerce_product_issues', $failure_callback, 99 );
		$background->process_batch( $retry_scan['scan_id'], 1, 1 );
		$background->process_batch( $retry_scan['scan_id'], 2, 0 );
		$retry_complete = $background->get_state();
		if ( 'complete' !== $retry_complete['status'] || 6 !== $retry_complete['processed'] ) {
			$failures[] = 'The catalog scan did not recover after its temporary batch failure.';
		}

		wp_set_current_user( 1 );
		$store_extractor = new DestinX\AICommerce\Store_Data_Extractor();
		$store_evaluator = new DestinX\AICommerce\Store_Readiness_Evaluator();
		$store_data      = $store_extractor->extract();
		$store_readiness = $store_evaluator->evaluate( $store_data );
		if ( 15 !== count( $store_readiness['checks'] ) || '1.0.0' !== $store_readiness['model_version'] ) {
			$failures[] = 'The store readiness evaluator did not return its complete versioned checklist.';
		}
		if ( empty( $store_data['has_published_products'] ) || empty( $store_data['has_physical_products'] ) ) {
			$failures[] = 'The store extractor did not detect the published physical smoke products.';
		}

		$admin_page = new DestinX\AICommerce\Admin_Page( $auditor, $repository, $background, $store_extractor, $store_evaluator );
		ob_start();
		$admin_page->render();
		$dashboard_html = ob_get_clean();
		if ( false === strpos( $dashboard_html, 'Catalog results' ) || false === strpos( $dashboard_html, '6/6' ) ) {
			$failures[] = 'The persisted catalog dashboard did not render its completed results.';
		}
		if ( false === strpos( $dashboard_html, 'Store readiness' ) || false === strpos( $dashboard_html, 'Technical store settings are checked separately' ) ) {
			$failures[] = 'The store readiness checklist did not render separately from product scoring.';
		}
		if ( false === strpos( $dashboard_html, 'Product or SKU' ) || false === strpos( $dashboard_html, 'Export filtered CSV' ) || false === strpos( $dashboard_html, 'Visible catalog data updated' ) ) {
			$failures[] = 'The catalog operations toolbar or snapshot freshness message did not render.';
		}
		if ( false === strpos( $dashboard_html, 'Fixed price' ) || false === strpos( $dashboard_html, 'WooCommerce' ) ) {
			$failures[] = 'The catalog dashboard did not render stored pricing context.';
		}
		if ( false === strpos( $dashboard_html, 'aria-live="polite"' ) || false === strpos( $dashboard_html, 'Scrollable catalog results' ) || false === strpos( $dashboard_html, '<caption class="screen-reader-text">' ) ) {
			$failures[] = 'The dashboard is missing its accessible status or table-region semantics.';
		}

		$complete_state = get_option( DestinX\AICommerce\Background_Audit::STATE_OPTION );
		$running_state  = array_merge(
			$complete_state,
			array(
				'status'     => 'running',
				'processed'  => 1,
				'total'      => 6,
				'heartbeat'  => time(),
				'finished_at' => '',
			)
		);
		update_option( DestinX\AICommerce\Background_Audit::STATE_OPTION, $running_state, false );
		ob_start();
		$admin_page->render();
		$running_dashboard_html = ob_get_clean();
		update_option( DestinX\AICommerce\Background_Audit::STATE_OPTION, $complete_state, false );
		if ( false === strpos( $running_dashboard_html, 'dxaic-auto-refresh-toggle' ) || false === strpos( $running_dashboard_html, 'aria-pressed="false"' ) ) {
			$failures[] = 'The running scan has no accessible auto-refresh control.';
		}

		$meta_box = new DestinX\AICommerce\Product_Meta_Box( $extractor, $evaluator );
		ob_start();
		$meta_box->render( get_post( $product_id ) );
		$product_panel_html = ob_get_clean();
		if ( false === strpos( $product_panel_html, '100/100' ) || false === strpos( $product_panel_html, 'No catalog findings' ) ) {
			$failures[] = 'The product readiness panel did not render the live evaluation.';
		}
	}

	if ( '' === DestinX\AICommerce\Issue_Catalog::guidance( 'brand_missing' ) ) {
		$failures[] = 'Product remediation guidance is unavailable.';
	}
}

if ( ! empty( $failures ) ) {
	fwrite( STDERR, implode( PHP_EOL, $failures ) . PHP_EOL );
	exit( 1 );
}

echo "WordPress and WooCommerce smoke test passed.\n";
