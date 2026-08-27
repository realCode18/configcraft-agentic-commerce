<?php
/**
 * WordPress Playground multisite lifecycle smoke test.
 *
 * @package DestinXAICommerce
 */

require_once '/wordpress/wp-load.php';
require_once ABSPATH . 'wp-admin/includes/plugin.php';
require_once ABSPATH . 'wp-admin/includes/ms.php';

$failures   = array();
$plugin_file = 'destinx-ai-commerce/destinx-ai-commerce.php';
$woo_file    = 'woocommerce/woocommerce.php';

if ( ! is_multisite() ) {
	$failures[] = 'WordPress Multisite was not enabled.';
}

if ( empty( $failures ) ) {
	deactivate_plugins( $plugin_file, true, false );
	deactivate_plugins( $woo_file, true, false );

	$woo_activation = activate_plugin( $woo_file, '', true, false );
	if ( is_wp_error( $woo_activation ) ) {
		$failures[] = 'WooCommerce could not be activated network-wide: ' . $woo_activation->get_error_message();
	}

	$plugin_activation = activate_plugin( $plugin_file, '', true, false );
	if ( is_wp_error( $plugin_activation ) ) {
		$failures[] = 'The plugin could not be activated network-wide: ' . $plugin_activation->get_error_message();
	}

	if ( ! is_plugin_active_for_network( $plugin_file ) ) {
		$failures[] = 'The plugin is not network active after activation.';
	}
}

/**
 * Confirm the plugin table and version for the current site.
 *
 * @param int $site_id Site ID.
 * @return void
 */
function dxaic_assert_site_schema( $site_id ) {
	global $failures, $wpdb;

	switch_to_blog( $site_id );
	try {
		$table_name = DestinX\AICommerce\Database::table_name();
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$found_table = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table_name ) ) );
		if ( $table_name !== $found_table ) {
			$failures[] = 'The audit table is missing for site ' . $site_id . '.';
		}
		if ( DestinX\AICommerce\Database::VERSION !== get_option( DestinX\AICommerce\Database::VERSION_OPTION ) ) {
			$failures[] = 'The schema version is missing for site ' . $site_id . '.';
		}
	} finally {
		restore_current_blog();
	}
}

if ( empty( $failures ) ) {
	update_user_meta( 1, DestinX\AICommerce\Database::USER_META_ONBOARDING_HIDDEN, '1' );
	update_user_meta( 1, DestinX\AICommerce\Database::USER_META_ADDON_DISMISSED, time() );

	$primary_site_id = get_main_site_id();
	dxaic_assert_site_schema( $primary_site_id );

	$network = get_network();
	$site_id = wpmu_create_blog(
		$network->domain,
		trailingslashit( $network->path ) . 'destinx-test-store/',
		'DestinX Test Store',
		1,
		array(),
		$network->id
	);

	if ( is_wp_error( $site_id ) ) {
		$failures[] = 'A second network site could not be created: ' . $site_id->get_error_message();
	} else {
		dxaic_assert_site_schema( (int) $site_id );
	}

	DestinX\AICommerce\Database::uninstall();
	foreach ( get_sites( array( 'fields' => 'ids' ) ) as $tested_site_id ) {
		switch_to_blog( (int) $tested_site_id );
		try {
			$table_name = DestinX\AICommerce\Database::table_name();
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$found_table = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table_name ) ) );
			if ( null !== $found_table ) {
				$failures[] = 'Uninstall left the audit table on site ' . $tested_site_id . '.';
			}
			if ( false !== get_option( DestinX\AICommerce\Database::VERSION_OPTION, false ) ) {
				$failures[] = 'Uninstall left the schema option on site ' . $tested_site_id . '.';
			}
		} finally {
			restore_current_blog();
		}
	}
	if ( '' !== get_user_meta( 1, DestinX\AICommerce\Database::USER_META_ONBOARDING_HIDDEN, true ) || '' !== get_user_meta( 1, DestinX\AICommerce\Database::USER_META_ADDON_DISMISSED, true ) ) {
		$failures[] = 'Uninstall left per-user dashboard preferences behind.';
	}

	// Leave the ephemeral test environment in an installed state.
	DestinX\AICommerce\Database::activate( true );
}

if ( ! empty( $failures ) ) {
	fwrite( STDERR, implode( PHP_EOL, $failures ) . PHP_EOL );
	exit( 1 );
}

echo "WordPress Multisite lifecycle smoke test passed.\n";
