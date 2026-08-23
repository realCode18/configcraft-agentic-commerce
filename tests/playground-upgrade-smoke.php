<?php
/**
 * WordPress Playground update, deactivation, and reactivation smoke test.
 *
 * @package DestinXAICommerce
 */

$source_root = '/wordpress/dxaic-current';
$plugin_root = '/wordpress/wp-content/plugins/destinx-ai-commerce';
$main_source = $source_root . '/destinx-ai-commerce.php';
$main_text   = file_get_contents( $main_source );
if ( false === $main_text || ! preg_match( '/^ \* Version:\s+([^\s]+)$/m', $main_text, $version_match ) ) {
	fwrite( STDERR, "The current plugin version could not be read.\n" );
	exit( 1 );
}
$expected_version = $version_match[1];

/**
 * Copy a current runtime path over the installed previous release.
 *
 * @param string $source Source path.
 * @param string $target Target path.
 * @return void
 */
function dxaic_upgrade_copy( $source, $target ) {
	if ( is_dir( $source ) ) {
		if ( ! is_dir( $target ) && ! mkdir( $target, 0777, true ) && ! is_dir( $target ) ) {
			throw new RuntimeException( 'Could not create upgrade target directory: ' . $target );
		}

		$entries = scandir( $source );
		if ( false === $entries ) {
			throw new RuntimeException( 'Could not read upgrade source directory: ' . $source );
		}
		foreach ( $entries as $entry ) {
			if ( '.' === $entry || '..' === $entry ) {
				continue;
			}
			dxaic_upgrade_copy( $source . '/' . $entry, $target . '/' . $entry );
		}
		return;
	}

	if ( ! copy( $source, $target ) ) {
		throw new RuntimeException( 'Could not replace runtime file: ' . $target );
	}
}

foreach ( array( 'assets', 'includes', 'languages' ) as $runtime_directory ) {
	dxaic_upgrade_copy( $source_root . '/' . $runtime_directory, $plugin_root . '/' . $runtime_directory );
}
foreach ( array( 'LICENSE.md', 'composer.json', 'destinx-ai-commerce.php', 'readme.txt', 'uninstall.php' ) as $runtime_file ) {
	dxaic_upgrade_copy( $source_root . '/' . $runtime_file, $plugin_root . '/' . $runtime_file );
}

require_once '/wordpress/wp-load.php';
require_once ABSPATH . 'wp-admin/includes/plugin.php';

$failures   = array();
$plugin_file = 'destinx-ai-commerce/destinx-ai-commerce.php';
$old_version = (string) get_option( 'dxaic_upgrade_fixture_version', '' );

if ( '' === $old_version || $expected_version === $old_version ) {
	$failures[] = 'The test did not begin with a distinct previous release.';
}
if ( ! defined( 'DXAIC_VERSION' ) || $expected_version !== DXAIC_VERSION ) {
	$loaded_version = defined( 'DXAIC_VERSION' ) ? DXAIC_VERSION : 'not loaded';
	$failures[]     = 'The updated runtime did not load. Expected ' . $expected_version . ', received ' . $loaded_version . '.';
}
if ( ! is_plugin_active( $plugin_file ) ) {
	$failures[] = 'The plugin lost its active state during the file update.';
}

$repository = new DestinX\AICommerce\Audit_Repository();
$summary    = $repository->get_summary();
if ( 1 !== $summary['scanned'] || 100 !== $summary['average_score'] || 'upgrade-preserved-snapshot' !== $repository->get_active_scan_id() ) {
	$failures[] = 'The completed snapshot was not preserved by the upgrade: ' . wp_json_encode( $summary ) . '.';
}
if ( DestinX\AICommerce\Database::VERSION !== get_option( DestinX\AICommerce\Database::VERSION_OPTION ) ) {
	$failures[] = 'The updated database schema was not marked current.';
}

$staging_scan_id = 'upgrade-interrupted-staging';
$product_id      = (int) get_option( 'dxaic_upgrade_fixture_product', 0 );
$repository->save(
	$staging_scan_id,
	$product_id,
	array(
		'score'         => 10,
		'status'        => 'at_risk',
		'issues'        => array(),
		'model_version' => DestinX\AICommerce\Product_Readiness_Evaluator::MODEL_VERSION,
	),
	'interrupted-fixture'
);

$state = array(
	'scan_id'          => $staging_scan_id,
	'status'           => 'running',
	'total'            => 2,
	'processed'        => 1,
	'next_page'        => 2,
	'current_page'     => 1,
	'retry_count'      => 0,
	'reconcile_count'  => 0,
	'heartbeat'        => time(),
	'catalog_revision' => '',
	'model_version'    => DestinX\AICommerce\Product_Readiness_Evaluator::MODEL_VERSION,
	'started_at'       => current_time( 'mysql', true ),
	'finished_at'      => '',
	'error'            => '',
);
update_option( DestinX\AICommerce\Background_Audit::STATE_OPTION, $state, false );
update_option( DestinX\AICommerce\Background_Audit::START_LOCK_OPTION, array( 'token' => 'upgrade-start-lock' ), false );
update_option( DestinX\AICommerce\Background_Audit::PROCESS_LOCK_OPTION, array( 'token' => 'upgrade-process-lock' ), false );
$scheduled_args = array( $staging_scan_id, 2, 0 );
wp_schedule_single_event( time() + HOUR_IN_SECONDS, DestinX\AICommerce\Background_Audit::ACTION_HOOK, $scheduled_args );
if ( function_exists( 'as_enqueue_async_action' ) ) {
	as_enqueue_async_action( DestinX\AICommerce\Background_Audit::ACTION_HOOK, $scheduled_args, DestinX\AICommerce\Background_Audit::ACTION_GROUP );
}

deactivate_plugins( $plugin_file, false, false );
if ( is_plugin_active( $plugin_file ) ) {
	$failures[] = 'WordPress did not deactivate the updated plugin.';
}
if ( 0 !== $repository->count_for_scan( $staging_scan_id ) ) {
	$failures[] = 'Deactivation left the incomplete staging snapshot behind.';
}

$preserved_summary = $repository->get_summary();
if ( 1 !== $preserved_summary['scanned'] || 100 !== $preserved_summary['average_score'] ) {
	$failures[] = 'Deactivation deleted or changed the last completed snapshot.';
}

$stopped_state = get_option( DestinX\AICommerce\Background_Audit::STATE_OPTION, array() );
if ( 'failed' !== $stopped_state['status'] || empty( $stopped_state['finished_at'] ) || empty( $stopped_state['error'] ) ) {
	$failures[] = 'Deactivation did not close the interrupted scan with an actionable state.';
}
if ( false !== get_option( DestinX\AICommerce\Background_Audit::START_LOCK_OPTION, false ) || false !== get_option( DestinX\AICommerce\Background_Audit::PROCESS_LOCK_OPTION, false ) ) {
	$failures[] = 'Deactivation left scan locks behind.';
}
if ( false !== wp_next_scheduled( DestinX\AICommerce\Background_Audit::ACTION_HOOK, $scheduled_args ) ) {
	$failures[] = 'Deactivation left the WP-Cron batch scheduled.';
}
if ( function_exists( 'as_has_scheduled_action' ) && as_has_scheduled_action( DestinX\AICommerce\Background_Audit::ACTION_HOOK, null, DestinX\AICommerce\Background_Audit::ACTION_GROUP ) ) {
	$failures[] = 'Deactivation left an Action Scheduler batch scheduled.';
}

$activation = activate_plugin( $plugin_file, '', false, false );
if ( is_wp_error( $activation ) ) {
	$failures[] = 'The updated plugin could not be reactivated: ' . $activation->get_error_message();
} elseif ( ! is_plugin_active( $plugin_file ) ) {
	$failures[] = 'The updated plugin is not active after reactivation.';
}

$reactivated_summary = $repository->get_summary();
if ( 1 !== $reactivated_summary['scanned'] || 100 !== $reactivated_summary['average_score'] ) {
	$failures[] = 'Reactivation did not retain the last completed snapshot.';
}

delete_option( 'dxaic_upgrade_fixture_product' );
delete_option( 'dxaic_upgrade_fixture_version' );

if ( ! empty( $failures ) ) {
	fwrite( STDERR, implode( PHP_EOL, $failures ) . PHP_EOL );
	exit( 1 );
}

echo 'Upgrade lifecycle smoke test passed: ' . $old_version . ' -> ' . $expected_version . ".\n";
