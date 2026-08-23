<?php
/**
 * Main plugin bootstrap.
 *
 * @package DestinXAICommerce
 */

namespace DestinX\AICommerce;

defined( 'ABSPATH' ) || exit;

/**
 * Boots the plugin after WordPress has loaded its dependencies.
 */
final class Plugin {
	/**
	 * Plugin instance.
	 *
	 * @var Plugin|null
	 */
	private static $instance = null;

	/**
	 * Return the shared plugin instance.
	 *
	 * @return Plugin
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
			self::$instance->boot();
		}

		return self::$instance;
	}

	/**
	 * Register plugin services.
	 *
	 * @return void
	 */
	private function boot() {
		Database::maybe_upgrade();

		if ( ! class_exists( 'WooCommerce' ) ) {
			add_action( 'admin_notices', array( $this, 'render_woocommerce_notice' ) );
			return;
		}

		$evaluator  = new Product_Readiness_Evaluator();
		$extractor  = new Product_Data_Extractor();
		$auditor    = new Catalog_Auditor( $extractor, $evaluator );
		$repository = new Audit_Repository();
		$background = new Background_Audit( $repository, $extractor, $evaluator );
		$background->hooks();
		$csv_exporter = new Catalog_Csv_Exporter( $repository );
		$csv_exporter->hooks();
		$meta_box = new Product_Meta_Box( $extractor, $evaluator );
		$meta_box->hooks();

		$store_extractor = new Store_Data_Extractor();
		$store_evaluator = new Store_Readiness_Evaluator();
		$admin_page      = new Admin_Page( $auditor, $repository, $background, $store_extractor, $store_evaluator );
		$admin_page->hooks();
	}

	/**
	 * Explain the missing WooCommerce dependency.
	 *
	 * @return void
	 */
	public function render_woocommerce_notice() {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}
		?>
		<div class="notice notice-error">
			<p>
				<?php esc_html_e( 'DestinX AI Commerce for WooCommerce requires WooCommerce to be installed and active.', 'destinx-ai-commerce' ); ?>
			</p>
		</div>
		<?php
	}

	/**
	 * Prevent direct construction.
	 */
	private function __construct() {}
}
