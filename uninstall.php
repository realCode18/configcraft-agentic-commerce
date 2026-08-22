<?php
/**
 * Plugin uninstall cleanup.
 *
 * @package ConfigCraftAgenticCommerce
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

require_once __DIR__ . '/includes/class-background-audit.php';
require_once __DIR__ . '/includes/class-database.php';

ConfigCraft\AgenticCommerce\Database::uninstall();
