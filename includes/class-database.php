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
	const VERSION        = '1.0.0';
	const VERSION_OPTION = 'dxaic_database_version';

	/**
	 * Return the site-specific results table name.
	 *
	 * @return string
	 */
	public static function table_name() {
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
			product_id bigint(20) unsigned NOT NULL,
			score smallint(3) unsigned NOT NULL DEFAULT 0,
			status varchar(20) NOT NULL DEFAULT '',
			issues longtext NOT NULL,
			scanned_at datetime NOT NULL,
			PRIMARY KEY  (product_id),
			KEY status (status),
			KEY score (score)
		) {$charset_collate};";

		dbDelta( $sql );
		update_option( self::VERSION_OPTION, self::VERSION, false );
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

		$table_name = self::table_name();
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange
		$wpdb->query( $wpdb->prepare( 'DROP TABLE IF EXISTS %i', $table_name ) );
		delete_option( self::VERSION_OPTION );
		delete_option( Background_Audit::STATE_OPTION );
	}
}
