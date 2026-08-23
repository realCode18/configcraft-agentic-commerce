<?php
/**
 * Persistent catalog audit results.
 *
 * @package DestinXAICommerce
 */

namespace DestinX\AICommerce;

defined( 'ABSPATH' ) || exit;

/**
 * Reads and writes product audit results in the plugin table.
 */
final class Audit_Repository {
	/**
	 * Save one product result.
	 *
	 * @param int                  $product_id Product ID.
	 * @param array<string, mixed> $evaluation Evaluation result.
	 * @return bool
	 */
	public function save( $product_id, array $evaluation ) {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$result = $wpdb->replace(
			Database::table_name(),
			array(
				'product_id' => (int) $product_id,
				'score'      => (int) $evaluation['score'],
				'status'     => sanitize_key( $evaluation['status'] ),
				'issues'     => wp_json_encode( $evaluation['issues'] ),
				'scanned_at' => current_time( 'mysql', true ),
			),
			array( '%d', '%d', '%s', '%s', '%s' )
		);

		return false !== $result;
	}

	/**
	 * Delete results before a new full scan.
	 *
	 * @return void
	 */
	public function clear() {
		global $wpdb;

		$table_name = Database::table_name();
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query( $wpdb->prepare( 'DELETE FROM %i', $table_name ) );
	}

	/**
	 * Return one page of stored product results.
	 *
	 * @param int $page     Page number.
	 * @param int $per_page Results per page.
	 * @return array<int, array<string, mixed>>
	 */
	public function get_page( $page = 1, $per_page = 20 ) {
		global $wpdb;

		$page       = max( 1, (int) $page );
		$per_page   = max( 1, min( 100, (int) $per_page ) );
		$offset     = ( $page - 1 ) * $per_page;
		$table_name = Database::table_name();
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT product_id, score, status, issues, scanned_at FROM %i ORDER BY score ASC, product_id DESC LIMIT %d OFFSET %d',
				$table_name,
				$per_page,
				$offset
			),
			ARRAY_A
		);

		foreach ( $rows as &$row ) {
			$row['product_id'] = (int) $row['product_id'];
			$row['score']      = (int) $row['score'];
			$row['issues']     = json_decode( $row['issues'], true );
			if ( ! is_array( $row['issues'] ) ) {
				$row['issues'] = array();
			}
		}
		unset( $row );

		return $rows;
	}

	/**
	 * Return aggregate values for completed results.
	 *
	 * @return array<string, int>
	 */
	public function get_summary() {
		global $wpdb;

		$table_name = Database::table_name();
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT COUNT(*) AS scanned, COALESCE(AVG(score), 0) AS average_score, SUM(status = 'ready') AS ready, SUM(status = 'needs_work') AS needs_work, SUM(status = 'at_risk') AS at_risk FROM %i",
				$table_name
			),
			ARRAY_A
		);

		return array(
			'scanned'       => isset( $row['scanned'] ) ? (int) $row['scanned'] : 0,
			'average_score' => isset( $row['average_score'] ) ? (int) round( $row['average_score'] ) : 0,
			'ready'         => isset( $row['ready'] ) ? (int) $row['ready'] : 0,
			'needs_work'    => isset( $row['needs_work'] ) ? (int) $row['needs_work'] : 0,
			'at_risk'       => isset( $row['at_risk'] ) ? (int) $row['at_risk'] : 0,
		);
	}
}
