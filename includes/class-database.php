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
	const VERSION            = '3.0.0';
	const VERSION_OPTION     = 'dxaic_database_version';
	const ACTIVE_SCAN_OPTION = 'dxaic_active_audit_scan';

	/**
	 * Install schema on one site or across a network activation.
	 *
	 * @param bool $network_wide Whether WordPress is activating the plugin network-wide.
	 * @return void
	 */
	public static function activate( $network_wide = false ) {
		if ( is_multisite() && $network_wide ) {
			self::for_each_site( array( __CLASS__, 'install' ) );
			return;
		}

		self::install();
	}

	/**
	 * Stop unfinished work without deleting the last completed audit.
	 *
	 * @param bool $network_wide Whether WordPress is deactivating the plugin network-wide.
	 * @return void
	 */
	public static function deactivate( $network_wide = false ) {
		if ( is_multisite() && $network_wide ) {
			self::for_each_site( array( __CLASS__, 'deactivate_current_site' ) );
			return;
		}

		self::deactivate_current_site();
	}

	/**
	 * Install schema for a site created while the plugin is network active.
	 *
	 * @param \WP_Site $site New site object.
	 * @return void
	 */
	public static function initialize_site( $site ) {
		if ( ! is_multisite() || empty( $site->blog_id ) ) {
			return;
		}

		if ( ! function_exists( 'is_plugin_active_for_network' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		if ( ! is_plugin_active_for_network( plugin_basename( DXAIC_FILE ) ) ) {
			return;
		}

		switch_to_blog( (int) $site->blog_id );
		try {
			self::install();
		} finally {
			restore_current_blog();
		}
	}

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
			pricing longtext NOT NULL,
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
		if ( is_multisite() ) {
			self::for_each_site( array( __CLASS__, 'uninstall_current_site' ) );
			return;
		}

		self::uninstall_current_site();
	}

	/**
	 * Remove plugin-owned data from the current site.
	 *
	 * @return void
	 */
	private static function uninstall_current_site() {
		global $wpdb;

		self::deactivate_current_site();

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
	 * Stop unfinished scan work for the current site while preserving completed data.
	 *
	 * @return void
	 */
	private static function deactivate_current_site() {
		global $wpdb;

		if ( function_exists( 'as_unschedule_all_actions' ) ) {
			// Omitting arguments and group cancels every batch for this plugin-owned hook.
			as_unschedule_all_actions( Background_Audit::ACTION_HOOK );
		}
		wp_unschedule_hook( Background_Audit::ACTION_HOOK );

		$state = get_option( Background_Audit::STATE_OPTION, array() );
		if ( is_array( $state ) && isset( $state['status'] ) && in_array( $state['status'], array( 'queued', 'running' ), true ) ) {
			$scan_id        = isset( $state['scan_id'] ) ? substr( sanitize_key( (string) $state['scan_id'] ), 0, 40 ) : '';
			$active_scan_id = substr( sanitize_key( (string) get_option( self::ACTIVE_SCAN_OPTION, '' ) ), 0, 40 );
			if ( '' !== $scan_id && $scan_id !== $active_scan_id ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->delete( self::table_name(), array( 'scan_id' => $scan_id ), array( '%s' ) );
			}

			$state['status']      = 'failed';
			$state['heartbeat']   = time();
			$state['finished_at'] = current_time( 'mysql', true );
			$state['error']       = __( 'The scan was stopped because the plugin was deactivated.', 'destinx-ai-commerce' );
			update_option( Background_Audit::STATE_OPTION, $state, false );
		}

		delete_option( Background_Audit::START_LOCK_OPTION );
		delete_option( Background_Audit::PROCESS_LOCK_OPTION );
	}

	/**
	 * Run a schema callback in every site without loading all site objects at once.
	 *
	 * @param callable $callback Callback that operates on the current site.
	 * @return void
	 */
	private static function for_each_site( $callback ) {
		$offset = 0;
		do {
			$site_ids   = get_sites(
				array(
					'fields' => 'ids',
					'number' => 100,
					'offset' => $offset,
					'order'  => 'ASC',
				)
			);
			$site_count = count( $site_ids );

			foreach ( $site_ids as $site_id ) {
				switch_to_blog( (int) $site_id );
				try {
					call_user_func( $callback );
				} finally {
					restore_current_blog();
				}
			}

			$offset += $site_count;
		} while ( 100 === $site_count );
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
					"INSERT INTO %i (scan_id, product_id, score, status, issues, pricing, product_hash, model_version, scanned_at)
					SELECT %s, product_id, score, status, issues, '{}', '', 'pre-1.0', scanned_at FROM %i",
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
