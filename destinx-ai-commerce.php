<?php
/**
 * Plugin Name:       DestinX AI Commerce for WooCommerce
 * Plugin URI:        https://github.com/realCode18/destinx-ai-commerce
 * Description:       Audits WooCommerce product catalogs for AI discovery and agentic commerce readiness.
 * Version:           0.11.1
 * Requires at least: 6.6
 * Requires PHP:      7.4
 * Requires Plugins:  woocommerce
 * WC requires at least: 8.2
 * WC tested up to:   11.0
 * Author:            DestinX
 * Author URI:        https://destinx.agency/
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       destinx-ai-commerce
 * Domain Path:       /languages
 *
 * @package DestinXAICommerce
 */

defined( 'ABSPATH' ) || exit;

define( 'DXAIC_VERSION', '0.11.1' );
define( 'DXAIC_FILE', __FILE__ );
define( 'DXAIC_PATH', plugin_dir_path( __FILE__ ) );
define( 'DXAIC_URL', plugin_dir_url( __FILE__ ) );

require_once DXAIC_PATH . 'includes/class-pricing-context.php';
require_once DXAIC_PATH . 'includes/interface-pricing-adapter.php';
require_once DXAIC_PATH . 'includes/class-name-your-price-adapter.php';
require_once DXAIC_PATH . 'includes/class-yith-request-a-quote-adapter.php';
require_once DXAIC_PATH . 'includes/class-call-for-price-adapter.php';
require_once DXAIC_PATH . 'includes/class-container-pricing-adapter.php';
require_once DXAIC_PATH . 'includes/class-measurement-price-calculator-adapter.php';
require_once DXAIC_PATH . 'includes/class-product-addons-adapter.php';
require_once DXAIC_PATH . 'includes/class-pricing-adapter-registry.php';
require_once DXAIC_PATH . 'includes/class-product-readiness-evaluator.php';
require_once DXAIC_PATH . 'includes/class-product-data-extractor.php';
require_once DXAIC_PATH . 'includes/class-store-readiness-evaluator.php';
require_once DXAIC_PATH . 'includes/class-store-data-extractor.php';
require_once DXAIC_PATH . 'includes/class-catalog-auditor.php';
require_once DXAIC_PATH . 'includes/class-database.php';
require_once DXAIC_PATH . 'includes/class-audit-repository.php';
require_once DXAIC_PATH . 'includes/class-catalog-csv-exporter.php';
require_once DXAIC_PATH . 'includes/class-scan-state.php';
require_once DXAIC_PATH . 'includes/class-background-audit.php';
require_once DXAIC_PATH . 'includes/class-issue-catalog.php';
require_once DXAIC_PATH . 'includes/class-store-issue-catalog.php';
require_once DXAIC_PATH . 'includes/class-product-meta-box.php';
require_once DXAIC_PATH . 'includes/class-admin-page.php';
require_once DXAIC_PATH . 'includes/class-plugin.php';

register_activation_hook( DXAIC_FILE, array( 'DestinX\\AICommerce\\Database', 'activate' ) );
register_deactivation_hook( DXAIC_FILE, array( 'DestinX\\AICommerce\\Database', 'deactivate' ) );
add_action( 'before_woocommerce_init', array( 'DestinX\\AICommerce\\Plugin', 'declare_woocommerce_compatibility' ) );
add_action( 'plugins_loaded', array( 'DestinX\\AICommerce\\Plugin', 'instance' ), 20 );
