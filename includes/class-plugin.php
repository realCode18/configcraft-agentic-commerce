<?php
/**
 * Main plugin bootstrap.
 *
 * @package ConfigCraftAgenticCommerce
 */

namespace ConfigCraft\AgenticCommerce;

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
		if ( ! class_exists( 'WooCommerce' ) ) {
			add_action( 'admin_notices', array( $this, 'render_woocommerce_notice' ) );
			return;
		}

		$evaluator = new Product_Readiness_Evaluator();
		$extractor = new Product_Data_Extractor();
		$auditor   = new Catalog_Auditor( $extractor, $evaluator );

		$admin_page = new Admin_Page( $auditor );
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
				<?php esc_html_e( 'ConfigCraft Agentic Commerce requires WooCommerce to be installed and active.', 'configcraft-agentic-commerce' ); ?>
			</p>
		</div>
		<?php
	}

	/**
	 * Prevent direct construction.
	 */
	private function __construct() {}
}
