<?php
/**
 * Plugin database schema management.
 *
 * @package DestinXAICommerce
 */

namespace DestinX\AICommerce;

defined( 'ABSPATH' ) || exit;

/**
 * Creates and upgrades the local catalog audit table.
 */
final class Database {
	const VERSION            = '2.0.0';
	const VERSION_OPTION     = 'dxaic_database_version';
	const ACTIVE_SCAN_OPTION = 'dxaic_active_audit_scan';

	/**
	 * Return the site-specific results table name.
	 *
	 * @return string
	 */
	public static function table_name() {
		global $wpdb;

		return $wpdb->prefix . 'dxaic_audit_snapshots';
	}

	/**
	 * Return the pre-snapshot results table name.
	 *
	 * @return string
	 */
	public static function legacy_table_name() {
		global $wpdb;

		return $wpdb->prefix . 'dxaic_audit_results';
	}

	/**
	 * Create or update the database schema.
	 *
	 * @return void
	 */
	public static function install() {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$table_name      = self::table_name();
		$charset_collate = $wpdb->get_charset_collate();
		$sql             = "CREATE TABLE {$table_name} (
			scan_id varchar(40) NOT NULL,
			product_id bigint(20) unsigned NOT NULL,
			score smallint(3) unsigned NOT NULL DEFAULT 0,
			status varchar(20) NOT NULL DEFAULT '',
			issues longtext NOT NULL,
			product_hash char(64) NOT NULL DEFAULT '',
			model_version varchar(20) NOT NULL DEFAULT '',
			scanned_at datetime NOT NULL,
			PRIMARY KEY  (scan_id, product_id),
			KEY scan_status (scan_id, status),
			KEY scan_score (scan_id, score, product_id)
		) {$charset_collate};";

		dbDelta( $sql );
		if ( self::migrate_legacy_results() ) {
			update_option( self::VERSION_OPTION, self::VERSION, false );
		}
	}

	/**
	 * Apply schema upgrades after a plugin update.
	 *
	 * @return void
	 */
	public static function maybe_upgrade() {
		if ( self::VERSION !== get_option( self::VERSION_OPTION ) ) {
			self::install();
		}
	}

	/**
	 * Remove plugin-owned database data on uninstall.
	 *
	 * @return void
	 */
	public static function uninstall() {
		global $wpdb;

		if ( function_exists( 'as_unschedule_all_actions' ) ) {
			as_unschedule_all_actions( Background_Audit::ACTION_HOOK, array(), Background_Audit::ACTION_GROUP );
		}
		wp_clear_scheduled_hook( Background_Audit::ACTION_HOOK );

		$table_name        = self::table_name();
		$legacy_table_name = self::legacy_table_name();
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange
		$wpdb->query( $wpdb->prepare( 'DROP TABLE IF EXISTS %i', $table_name ) );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange
		$wpdb->query( $wpdb->prepare( 'DROP TABLE IF EXISTS %i', $legacy_table_name ) );
		delete_option( self::VERSION_OPTION );
		delete_option( self::ACTIVE_SCAN_OPTION );
		delete_option( Background_Audit::STATE_OPTION );
		delete_option( Background_Audit::START_LOCK_OPTION );
		delete_option( Background_Audit::PROCESS_LOCK_OPTION );
		delete_option( Background_Audit::CATALOG_REVISION_OPTION );
	}

	/**
	 * Copy pre-release results into a legacy snapshot before removing the old table.
	 *
	 * @return bool Whether migration completed and can be marked current.
	 */
	private static function migrate_legacy_results() {
		global $wpdb;

		$legacy_table_name = self::legacy_table_name();
		$table_name        = self::table_name();
		if ( ! self::table_exists( $legacy_table_name ) ) {
			return true;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$current_scan_id = $wpdb->get_var(
			$wpdb->prepare( 'SELECT scan_id FROM %i ORDER BY scanned_at DESC LIMIT 1', $table_name )
		);

		if ( ! $current_scan_id ) {
			$scan_id = substr( 'legacy-' . str_replace( '-', '', wp_generate_uuid4() ), 0, 40 );
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$inserted = $wpdb->query(
				$wpdb->prepare(
					"INSERT INTO %i (scan_id, product_id, score, status, issues, product_hash, model_version, scanned_at)
					SELECT %s, product_id, score, status, issues, '', 'pre-1.0', scanned_at FROM %i",
					$table_name,
					$scan_id,
					$legacy_table_name
				)
			);

			if ( false === $inserted ) {
				return false;
			}

			if ( 0 < $inserted ) {
				update_option( self::ACTIVE_SCAN_OPTION, $scan_id, false );
			}
		} elseif ( ! get_option( self::ACTIVE_SCAN_OPTION ) ) {
			update_option( self::ACTIVE_SCAN_OPTION, sanitize_key( $current_scan_id ), false );
		}

		// The legacy table is removed only after its rows have been copied or a snapshot already exists.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange
		$dropped = $wpdb->query( $wpdb->prepare( 'DROP TABLE IF EXISTS %i', $legacy_table_name ) );

		return false !== $dropped;
	}

	/**
	 * Check for a site-specific plugin table.
	 *
	 * @param string $table_name Complete table name.
	 * @return bool
	 */
	private static function table_exists( $table_name ) {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$found = $wpdb->get_var(
			$wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table_name ) )
		);

		return $table_name === $found;
	}
}
