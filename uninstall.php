<?php
/**
 * Plugin uninstall cleanup.
 *
 * @package DestinXAICommerce
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

require_once __DIR__ . '/includes/class-background-audit.php';
require_once __DIR__ . '/includes/class-database.php';

DestinX\AICommerce\Database::uninstall();
