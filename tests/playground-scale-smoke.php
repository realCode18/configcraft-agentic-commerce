<?php
/**
 * WordPress Playground catalog scale and performance smoke test.
 *
 * @package DestinXAICommerce
 */

require_once '/wordpress/wp-load.php';

$failures = array();
$metrics  = array();

/**
 * Insert synthetic simple products efficiently without measuring seed overhead.
 *
 * Runtime plugin code still reads every product through WooCommerce CRUD.
 *
 * @param int $count        Number of products to add.
 * @param int $start_number First synthetic product number.
 * @return void
 */
function dxaic_scale_seed_products( $count, $start_number ) {
	global $failures, $wpdb;

	$meta_rows = array();
	$relationship_ids = array();
	$now       = current_time( 'mysql' );
	$now_gmt   = current_time( 'mysql', true );
	$simple    = get_term_by( 'slug', 'simple', 'product_type' );
	if ( ! $simple ) {
		$failures[] = 'The WooCommerce simple product type term is unavailable.';
		return;
	}

	for ( $offset = 0; $offset < $count; $offset++ ) {
		$number = $start_number + $offset;
		$name   = 'Synthetic catalog product ' . str_pad( (string) $number, 5, '0', STR_PAD_LEFT );
		$result = $wpdb->insert(
			$wpdb->posts,
			array(
				'post_author'           => 1,
				'post_date'             => $now,
				'post_date_gmt'         => $now_gmt,
				'post_content'          => str_repeat( 'Detailed synthetic catalog information for scale testing. ', 3 ),
				'post_title'            => $name,
				'post_excerpt'          => '',
				'post_status'           => 'publish',
				'comment_status'        => 'closed',
				'ping_status'           => 'closed',
				'post_password'         => '',
				'post_name'             => 'dxaic-scale-' . $number,
				'to_ping'               => '',
				'pinged'                => '',
				'post_modified'         => $now,
				'post_modified_gmt'     => $now_gmt,
				'post_content_filtered' => '',
				'post_parent'           => 0,
				'guid'                  => '',
				'menu_order'            => 0,
				'post_type'             => 'product',
				'post_mime_type'        => '',
				'comment_count'         => 0,
			),
			array( '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%d', '%s', '%s', '%d' )
		);

		if ( false === $result ) {
			$failures[] = 'A synthetic product could not be inserted: ' . $wpdb->last_error;
			return;
		}

		$product_id  = (int) $wpdb->insert_id;
		$price       = (string) ( 10 + ( $number % 90 ) );
		$sku         = 'DXAIC-SCALE-' . str_pad( (string) $number, 5, '0', STR_PAD_LEFT );
		$meta_rows[] = array( $product_id, '_regular_price', $price );
		$meta_rows[] = array( $product_id, '_price', $price );
		$meta_rows[] = array( $product_id, '_sku', $sku );
		$meta_rows[] = array( $product_id, '_stock_status', 'instock' );
		$relationship_ids[] = $product_id;

		if ( 160 === count( $meta_rows ) || $offset === $count - 1 ) {
			dxaic_scale_insert_meta_rows( $meta_rows );
			$meta_rows = array();
		}
		if ( 200 === count( $relationship_ids ) || $offset === $count - 1 ) {
			dxaic_scale_insert_relationship_rows( $relationship_ids, (int) $simple->term_taxonomy_id );
			$relationship_ids = array();
		}
	}

	wp_update_term_count_now( array( (int) $simple->term_taxonomy_id ), 'product_type' );
	wp_cache_flush();
	if ( function_exists( 'wc_delete_product_transients' ) ) {
		wc_delete_product_transients();
	}
	WC_Cache_Helper::get_transient_version( 'product', true );
	update_option( DestinX\AICommerce\Background_Audit::CATALOG_REVISION_OPTION, wp_generate_uuid4(), false );
}

/**
 * Assign synthetic products to WooCommerce's simple product type.
 *
 * @param array<int, int> $product_ids     Product IDs.
 * @param int             $term_taxonomy_id Simple product term taxonomy ID.
 * @return void
 */
function dxaic_scale_insert_relationship_rows( array $product_ids, $term_taxonomy_id ) {
	global $failures, $wpdb;

	if ( empty( $product_ids ) ) {
		return;
	}

	$placeholders = array();
	$values       = array();
	foreach ( $product_ids as $product_id ) {
		$placeholders[] = '(%d, %d, 0)';
		$values[]       = $product_id;
		$values[]       = $term_taxonomy_id;
	}

	$sql = 'INSERT INTO ' . $wpdb->term_relationships . ' (object_id, term_taxonomy_id, term_order) VALUES ' . implode( ', ', $placeholders );
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.NotPrepared -- Test-only bounded placeholders and generated fixture IDs.
	$result = $wpdb->query( $wpdb->prepare( $sql, $values ) );
	if ( false === $result ) {
		$failures[] = 'Synthetic product type relationships could not be inserted: ' . $wpdb->last_error;
	}
}

/**
 * Insert a bounded group of product metadata rows.
 *
 * @param array<int, array<int, int|string>> $rows Metadata rows.
 * @return void
 */
function dxaic_scale_insert_meta_rows( array $rows ) {
	global $failures, $wpdb;

	if ( empty( $rows ) ) {
		return;
	}

	$placeholders = array();
	$values       = array();
	foreach ( $rows as $row ) {
		$placeholders[] = '(%d, %s, %s)';
		$values[]       = $row[0];
		$values[]       = $row[1];
		$values[]       = $row[2];
	}

	$sql = 'INSERT INTO ' . $wpdb->postmeta . ' (post_id, meta_key, meta_value) VALUES ' . implode( ', ', $placeholders );
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.NotPrepared -- Test-only bounded placeholders and generated fixture values.
	$result = $wpdb->query( $wpdb->prepare( $sql, $values ) );
	if ( false === $result ) {
		$failures[] = 'Synthetic product metadata could not be inserted: ' . $wpdb->last_error;
	}
}

/**
 * Run a complete snapshot and capture bounded-resource measurements.
 *
 * @param DestinX\AICommerce\Background_Audit $background Background scanner.
 * @param DestinX\AICommerce\Audit_Repository $repository Result repository.
 * @param int                                  $expected   Expected product count.
 * @return array<string, int|float>
 */
function dxaic_scale_run_scan( $background, $repository, $expected ) {
	global $failures;

	delete_option( DestinX\AICommerce\Background_Audit::START_LOCK_OPTION );
	delete_option( DestinX\AICommerce\Background_Audit::PROCESS_LOCK_OPTION );
	$started_at = microtime( true );
	if ( ! $background->start() ) {
		$failures[] = 'The ' . $expected . '-product scale scan did not start.';
		return array();
	}

	$max_batch_seconds = 0.0;
	$batch_count       = 0;
	$state             = $background->get_state();
	while ( DestinX\AICommerce\Scan_State::is_active( $state ) ) {
		$batch_started = microtime( true );
		$background->process_batch( $state['scan_id'], $state['next_page'], $state['retry_count'] );
		$batch_elapsed     = microtime( true ) - $batch_started;
		$max_batch_seconds = max( $max_batch_seconds, $batch_elapsed );
		++$batch_count;
		$state = $background->get_state();

		if ( 'failed' === $state['status'] ) {
			$failures[] = 'The ' . $expected . '-product scan failed: ' . $state['error'];
			break;
		}
		if ( 60 < $batch_count ) {
			$failures[] = 'The scale scan exceeded its bounded batch count.';
			break;
		}
	}

	$summary = $repository->get_summary();
	if ( $expected !== $summary['scanned'] || 'complete' !== $state['status'] ) {
		$failures[] = 'Expected ' . $expected . ' completed results, received ' . wp_json_encode( $summary ) . '.';
	}

	return array(
		'products'          => $expected,
		'batches'           => $batch_count,
		'total_seconds'     => round( microtime( true ) - $started_at, 4 ),
		'max_batch_seconds' => round( $max_batch_seconds, 4 ),
	);
}

if ( ! class_exists( 'WooCommerce' ) || ! class_exists( 'DestinX\\AICommerce\\Background_Audit' ) ) {
	$failures[] = 'WooCommerce or plugin services did not load for the scale test.';
} else {
	add_filter(
		'destinx_ai_commerce_batch_size',
		static function () {
			return 100;
		}
	);

	$repository = new DestinX\AICommerce\Audit_Repository();
	$extractor  = new DestinX\AICommerce\Product_Data_Extractor();
	$evaluator  = new DestinX\AICommerce\Product_Readiness_Evaluator();
	$background = new DestinX\AICommerce\Background_Audit( $repository, $extractor, $evaluator );
	$repository->clear();
	delete_option( DestinX\AICommerce\Background_Audit::STATE_OPTION );

	$metrics[] = dxaic_scale_run_scan( $background, $repository, 0 );
	$targets   = array( 1, 26, 500, 5000 );
	$existing  = 0;
	foreach ( $targets as $target ) {
		dxaic_scale_seed_products( $target - $existing, $existing + 1 );
		$existing  = $target;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Test fixture verification.
		$raw_total = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'product' AND post_status = 'publish'" );
		$wp_query  = new WP_Query(
			array(
				'post_type'      => 'product',
				'post_status'    => 'publish',
				'posts_per_page' => 1,
				'fields'         => 'ids',
			)
		);
		$wc_query  = wc_get_products(
			array(
				'limit'    => 1,
				'status'   => 'publish',
				'return'   => 'ids',
				'paginate' => true,
			)
		);
		$metrics['visibility'][] = array(
			'target'         => $target,
			'raw_total'      => $raw_total,
			'wp_query_total' => (int) $wp_query->found_posts,
			'wc_query_total' => (int) $wc_query->total,
		);
		$metrics[] = dxaic_scale_run_scan( $background, $repository, $target );
	}

	if ( 1 !== $repository->count( array( 'search' => 'DXAIC-SCALE-05000' ) ) ) {
		$failures[] = 'The 5,000-product snapshot could not find its final SKU.';
	}
	if ( 20 !== count( $repository->get_page( 250, 20 ) ) ) {
		$failures[] = 'The final page of the 5,000-product snapshot is incomplete.';
	}

	wp_set_current_user( 1 );
	$auditor         = new DestinX\AICommerce\Catalog_Auditor( $extractor, $evaluator );
	$store_extractor = new DestinX\AICommerce\Store_Data_Extractor();
	$store_evaluator = new DestinX\AICommerce\Store_Readiness_Evaluator();
	$admin_page      = new DestinX\AICommerce\Admin_Page( $auditor, $repository, $background, $store_extractor, $store_evaluator );
	$queries_before  = $wpdb->num_queries;
	$render_started  = microtime( true );
	ob_start();
	$admin_page->render();
	$dashboard_html    = ob_get_clean();
	$dashboard_seconds = microtime( true ) - $render_started;
	$dashboard_queries = $wpdb->num_queries - $queries_before;
	$peak_memory       = memory_get_peak_usage( true );

	if ( false === strpos( $dashboard_html, '5000 products match' ) ) {
		$failures[] = 'The bounded 5,000-product dashboard did not render its result count.';
	}
	if ( 1.5 < $dashboard_seconds ) {
		$failures[] = 'The 5,000-product dashboard exceeded 1.5 seconds: ' . round( $dashboard_seconds, 4 ) . '.';
	}
	if ( 150 < $dashboard_queries ) {
		$failures[] = 'The dashboard exceeded 150 database queries: ' . $dashboard_queries . '.';
	}
	if ( 128 * 1024 * 1024 < $peak_memory ) {
		$failures[] = 'The scale test exceeded 128 MB peak memory: ' . size_format( $peak_memory ) . '.';
	}

	foreach ( $metrics as $scan_metrics ) {
		if ( isset( $scan_metrics['max_batch_seconds'] ) && 10 < $scan_metrics['max_batch_seconds'] ) {
			$failures[] = 'A ' . $scan_metrics['products'] . '-product scan batch exceeded 10 seconds.';
		}
	}

	$metrics['dashboard'] = array(
		'seconds'     => round( $dashboard_seconds, 4 ),
		'queries'     => $dashboard_queries,
		'peak_memory' => $peak_memory,
	);
}

if ( ! empty( $failures ) ) {
	fwrite( STDERR, implode( PHP_EOL, $failures ) . PHP_EOL );
	fwrite( STDERR, wp_json_encode( $metrics, JSON_PRETTY_PRINT ) . PHP_EOL );
	exit( 1 );
}

echo "Catalog scale and performance smoke test passed.\n";
echo wp_json_encode( $metrics, JSON_PRETTY_PRINT ) . "\n";
