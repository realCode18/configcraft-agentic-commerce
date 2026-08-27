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
	 * Public read-only extension API.
	 *
	 * @var Public_API|null
	 */
	private $api = null;

	/**
	 * Declare compatibility only for WooCommerce features the plugin actually touches.
	 *
	 * @return void
	 */
	public static function declare_woocommerce_compatibility() {
		if ( ! class_exists( '\\Automattic\\WooCommerce\\Utilities\\FeaturesUtil' ) ) {
			return;
		}

		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', DXAIC_FILE, true );
	}

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
	 * Return the public extension API after a successful WooCommerce bootstrap.
	 *
	 * @return Public_API|null
	 */
	public function api() {
		return $this->api;
	}

	/**
	 * Register plugin services.
	 *
	 * @return void
	 */
	private function boot() {
		Database::maybe_upgrade();
		add_action( 'wp_initialize_site', array( 'DestinX\\AICommerce\\Database', 'initialize_site' ), 200 );
		add_filter( 'plugin_action_links_' . plugin_basename( DXAIC_FILE ), array( $this, 'add_plugin_action_links' ) );

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

		$this->api = new Public_API( $repository, $background );

		/**
		 * Fires after the Free engine and its public read-only API are ready.
		 *
		 * @param Public_API $api Stable extension API.
		 */
		do_action( 'destinx_ai_commerce_loaded', $this->api );
	}

	/**
	 * Add a direct, non-disruptive entry point from the Plugins screen.
	 *
	 * @param array<int, string> $links Existing plugin action links.
	 * @return array<int, string>
	 */
	public function add_plugin_action_links( array $links ) {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return $links;
		}

		$dashboard_link = sprintf(
			'<a href="%1$s" aria-label="%2$s">%3$s</a>',
			esc_url( admin_url( 'admin.php?page=destinx-ai-commerce' ) ),
			esc_attr__( 'Open the DestinX AI Commerce setup guide', 'destinx-ai-commerce' ),
			esc_html__( 'Open AI Commerce', 'destinx-ai-commerce' )
		);

		array_unshift( $links, $dashboard_link );

		return $links;
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
