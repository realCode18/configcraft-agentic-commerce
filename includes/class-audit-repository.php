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
	 * @param string               $scan_id     Owning scan identifier.
	 * @param int                  $product_id Product ID.
	 * @param array<string, mixed> $evaluation Evaluation result.
	 * @param string               $product_hash Normalized product data hash.
	 * @return bool
	 */
	public function save( $scan_id, $product_id, array $evaluation, $product_hash = '' ) {
		global $wpdb;
		$scan_id = $this->normalize_scan_id( $scan_id );
		if ( '' === $scan_id ) {
			return false;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$result = $wpdb->replace(
			Database::table_name(),
			array(
				'scan_id'       => $scan_id,
				'product_id'    => (int) $product_id,
				'score'         => (int) $evaluation['score'],
				'status'        => sanitize_key( $evaluation['status'] ),
				'issues'        => wp_json_encode( $evaluation['issues'] ),
				'product_hash'  => sanitize_key( $product_hash ),
				'model_version' => isset( $evaluation['model_version'] ) ? substr( sanitize_text_field( $evaluation['model_version'] ), 0, 20 ) : '',
				'scanned_at'    => current_time( 'mysql', true ),
			),
			array( '%s', '%d', '%d', '%s', '%s', '%s', '%s', '%s' )
		);

		return false !== $result;
	}

	/**
	 * Make a completed snapshot visible and remove older snapshots afterwards.
	 *
	 * @param string $scan_id Completed scan identifier.
	 * @return bool
	 */
	public function activate_scan( $scan_id ) {
		global $wpdb;
		$scan_id = $this->normalize_scan_id( $scan_id );
		if ( '' === $scan_id ) {
			return false;
		}

		update_option( Database::ACTIVE_SCAN_OPTION, $scan_id, false );
		if ( get_option( Database::ACTIVE_SCAN_OPTION ) !== $scan_id ) {
			return false;
		}

		$table_name = Database::table_name();
		// The active pointer changes before cleanup, so an interrupted cleanup cannot expose partial data.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query(
			$wpdb->prepare( 'DELETE FROM %i WHERE scan_id <> %s', $table_name, $scan_id )
		);

		return true;
	}

	/**
	 * Remove one incomplete or failed scan.
	 *
	 * @param string $scan_id Scan identifier.
	 * @return void
	 */
	public function discard_scan( $scan_id ) {
		global $wpdb;
		$scan_id = $this->normalize_scan_id( $scan_id );
		if ( '' === $scan_id || $scan_id === $this->get_active_scan_id() ) {
			return;
		}

		$table_name = Database::table_name();
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->delete( $table_name, array( 'scan_id' => $scan_id ), array( '%s' ) );
	}

	/**
	 * Delete all plugin results and the active pointer.
	 *
	 * @return void
	 */
	public function clear() {
		global $wpdb;

		$table_name = Database::table_name();
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query( $wpdb->prepare( 'DELETE FROM %i', $table_name ) );
		delete_option( Database::ACTIVE_SCAN_OPTION );
	}

	/**
	 * Return the visible snapshot identifier.
	 *
	 * @return string
	 */
	public function get_active_scan_id() {
		return $this->normalize_scan_id( get_option( Database::ACTIVE_SCAN_OPTION, '' ) );
	}

	/**
	 * Count stored products for a specific scan.
	 *
	 * @param string $scan_id Scan identifier.
	 * @return int
	 */
	public function count_for_scan( $scan_id ) {
		global $wpdb;
		$scan_id = $this->normalize_scan_id( $scan_id );
		if ( '' === $scan_id ) {
			return 0;
		}

		$table_name = Database::table_name();
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return (int) $wpdb->get_var(
			$wpdb->prepare( 'SELECT COUNT(*) FROM %i WHERE scan_id = %s', $table_name, $scan_id )
		);
	}

	/**
	 * Remove products that are no longer publicly published before activation.
	 *
	 * @param string $scan_id Scan identifier.
	 * @return bool
	 */
	public function prune_unpublished( $scan_id ) {
		global $wpdb;
		$scan_id = $this->normalize_scan_id( $scan_id );
		if ( '' === $scan_id ) {
			return false;
		}

		$table_name = Database::table_name();
		// This bounded SQL cleanup avoids loading every product object before snapshot activation.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$result = $wpdb->query(
			$wpdb->prepare(
				"DELETE FROM %i WHERE scan_id = %s AND product_id NOT IN (
					SELECT ID FROM %i WHERE post_type = 'product' AND post_status = 'publish'
				)",
				$table_name,
				$scan_id,
				$wpdb->posts
			)
		);

		return false !== $result;
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

		$scan_id = $this->get_active_scan_id();
		if ( '' === $scan_id ) {
			return array();
		}

		$page       = max( 1, (int) $page );
		$per_page   = max( 1, min( 100, (int) $per_page ) );
		$offset     = ( $page - 1 ) * $per_page;
		$table_name = Database::table_name();
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT product_id, score, status, issues, product_hash, model_version, scanned_at FROM %i WHERE scan_id = %s ORDER BY score ASC, product_id DESC LIMIT %d OFFSET %d',
				$table_name,
				$scan_id,
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
		$scan_id = $this->get_active_scan_id();
		if ( '' === $scan_id ) {
			return $this->empty_summary();
		}

		$table_name = Database::table_name();
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT COUNT(*) AS scanned, COALESCE(AVG(score), 0) AS average_score, SUM(status = 'ready') AS ready, SUM(status = 'needs_work') AS needs_work, SUM(status = 'at_risk') AS at_risk FROM %i WHERE scan_id = %s",
				$table_name,
				$scan_id
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

	/**
	 * Return a normalized empty aggregate.
	 *
	 * @return array<string, int>
	 */
	private function empty_summary() {
		return array(
			'scanned'       => 0,
			'average_score' => 0,
			'ready'         => 0,
			'needs_work'    => 0,
			'at_risk'       => 0,
		);
	}

	/**
	 * Normalize a scan identifier for storage and comparisons.
	 *
	 * @param mixed $scan_id Scan identifier.
	 * @return string
	 */
	private function normalize_scan_id( $scan_id ) {
		return substr( sanitize_key( (string) $scan_id ), 0, 40 );
	}
}
