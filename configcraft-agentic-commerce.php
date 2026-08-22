<?php
/**
 * Plugin Name:       ConfigCraft Agentic Commerce
 * Plugin URI:        https://www.configcraftsuite.com/
 * Description:       Audits WooCommerce product catalogs for AI discovery and agentic commerce readiness.
 * Version:           0.1.0
 * Requires at least: 6.6
 * Requires PHP:      7.4
 * Requires Plugins:  woocommerce
 * Author:            ConfigCraft Suite by Destin X
 * Author URI:        https://www.configcraftsuite.com/
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       configcraft-agentic-commerce
 * Domain Path:       /languages
 *
 * @package ConfigCraftAgenticCommerce
 */

defined( 'ABSPATH' ) || exit;

define( 'CCAC_VERSION', '0.1.0' );
define( 'CCAC_FILE', __FILE__ );
define( 'CCAC_PATH', plugin_dir_path( __FILE__ ) );
define( 'CCAC_URL', plugin_dir_url( __FILE__ ) );

require_once CCAC_PATH . 'includes/class-product-readiness-evaluator.php';
require_once CCAC_PATH . 'includes/class-product-data-extractor.php';
require_once CCAC_PATH . 'includes/class-catalog-auditor.php';
require_once CCAC_PATH . 'includes/class-admin-page.php';
require_once CCAC_PATH . 'includes/class-plugin.php';

add_action( 'plugins_loaded', array( 'ConfigCraft\\AgenticCommerce\\Plugin', 'instance' ), 20 );
