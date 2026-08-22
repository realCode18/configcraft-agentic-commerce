<?php
/**
 * WooCommerce admin audit page.
 *
 * @package ConfigCraftAgenticCommerce
 */

namespace ConfigCraft\AgenticCommerce;

defined( 'ABSPATH' ) || exit;

/**
 * Registers and renders the WooCommerce catalog audit screen.
 */
final class Admin_Page {
	/**
	 * Catalog audit service.
	 *
	 * @var Catalog_Auditor
	 */
	private $auditor;

	/**
	 * WordPress admin page hook.
	 *
	 * @var string
	 */
	private $hook_suffix = '';

	/**
	 * Constructor.
	 *
	 * @param Catalog_Auditor $auditor Catalog audit service.
	 */
	public function __construct( Catalog_Auditor $auditor ) {
		$this->auditor = $auditor;
	}

	/**
	 * Register WordPress hooks.
	 *
	 * @return void
	 */
	public function hooks() {
		add_action( 'admin_menu', array( $this, 'register_page' ), 60 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	/**
	 * Register the WooCommerce submenu page.
	 *
	 * @return void
	 */
	public function register_page() {
		$this->hook_suffix = add_submenu_page(
			'woocommerce',
			__( 'AI Commerce Readiness', 'configcraft-agentic-commerce' ),
			__( 'AI Commerce', 'configcraft-agentic-commerce' ),
			'manage_woocommerce',
			'configcraft-agentic-commerce',
			array( $this, 'render' )
		);
	}

	/**
	 * Load the admin stylesheet only on the plugin page.
	 *
	 * @param string $hook_suffix Current admin hook.
	 * @return void
	 */
	public function enqueue_assets( $hook_suffix ) {
		if ( $this->hook_suffix !== $hook_suffix ) {
			return;
		}

		wp_enqueue_style(
			'configcraft-agentic-commerce-admin',
			CCAC_URL . 'assets/admin.css',
			array(),
			CCAC_VERSION
		);
	}

	/**
	 * Render the audit page.
	 *
	 * @return void
	 */
	public function render() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'configcraft-agentic-commerce' ) );
		}

		$audit   = $this->auditor->audit();
		$summary = $audit['summary'];
		?>
		<div class="wrap ccac-wrap">
			<h1><?php esc_html_e( 'AI Commerce Readiness', 'configcraft-agentic-commerce' ); ?></h1>
			<p class="ccac-intro">
				<?php esc_html_e( 'Find catalog data that may prevent AI shopping assistants from understanding and recommending your products.', 'configcraft-agentic-commerce' ); ?>
			</p>

			<div class="ccac-summary" aria-label="<?php esc_attr_e( 'Catalog audit summary', 'configcraft-agentic-commerce' ); ?>">
				<?php $this->render_metric( __( 'Average score', 'configcraft-agentic-commerce' ), $summary['average_score'] . '/100', 'primary' ); ?>
				<?php $this->render_metric( __( 'Products scanned', 'configcraft-agentic-commerce' ), $summary['scanned'] . '/' . $summary['total_products'], 'neutral' ); ?>
				<?php $this->render_metric( __( 'Ready', 'configcraft-agentic-commerce' ), $summary['ready'], 'success' ); ?>
				<?php $this->render_metric( __( 'At risk', 'configcraft-agentic-commerce' ), $summary['at_risk'], 'danger' ); ?>
			</div>

			<?php if ( empty( $audit['products'] ) ) : ?>
				<div class="notice notice-info inline"><p><?php esc_html_e( 'No published WooCommerce products were found.', 'configcraft-agentic-commerce' ); ?></p></div>
			<?php else : ?>
				<div class="ccac-panel">
					<h2><?php esc_html_e( 'Latest products', 'configcraft-agentic-commerce' ); ?></h2>
					<p><?php esc_html_e( 'The initial MVP scans the latest 25 published products. Full-catalog background scans are planned for a later milestone.', 'configcraft-agentic-commerce' ); ?></p>
					<table class="widefat striped ccac-table">
						<thead>
							<tr>
								<th><?php esc_html_e( 'Product', 'configcraft-agentic-commerce' ); ?></th>
								<th><?php esc_html_e( 'Score', 'configcraft-agentic-commerce' ); ?></th>
								<th><?php esc_html_e( 'Status', 'configcraft-agentic-commerce' ); ?></th>
								<th><?php esc_html_e( 'Findings', 'configcraft-agentic-commerce' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $audit['products'] as $product ) : ?>
								<tr>
									<td><a href="<?php echo esc_url( $product['edit_url'] ); ?>"><strong><?php echo esc_html( $product['name'] ); ?></strong></a></td>
									<td><strong><?php echo esc_html( $product['score'] ); ?>/100</strong></td>
									<td><span class="ccac-status ccac-status--<?php echo esc_attr( $product['status'] ); ?>"><?php echo esc_html( $this->status_label( $product['status'] ) ); ?></span></td>
									<td><?php $this->render_issues( $product['issues'] ); ?></td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Render a summary metric.
	 *
	 * @param string     $label Label.
	 * @param int|string $value Value.
	 * @param string     $tone  Visual tone.
	 * @return void
	 */
	private function render_metric( $label, $value, $tone ) {
		?>
		<div class="ccac-metric ccac-metric--<?php echo esc_attr( $tone ); ?>">
			<span><?php echo esc_html( $label ); ?></span>
			<strong><?php echo esc_html( $value ); ?></strong>
		</div>
		<?php
	}

	/**
	 * Render a compact list of product findings.
	 *
	 * @param array<int, array<string, mixed>> $issues Product issues.
	 * @return void
	 */
	private function render_issues( array $issues ) {
		if ( empty( $issues ) ) {
			echo '<span class="ccac-clear">' . esc_html__( 'No findings', 'configcraft-agentic-commerce' ) . '</span>';
			return;
		}

		$visible = array_slice( $issues, 0, 4 );
		foreach ( $visible as $issue ) {
			printf(
				'<span class="ccac-issue ccac-issue--%1$s">%2$s</span> ',
				esc_attr( $issue['severity'] ),
				esc_html( $this->issue_label( $issue['code'] ) )
			);
		}

		$remaining = count( $issues ) - count( $visible );
		if ( 0 < $remaining ) {
			/* translators: %d: number of additional catalog findings. */
			$more_label = sprintf( _n( '+%d more', '+%d more', $remaining, 'configcraft-agentic-commerce' ), $remaining );
			printf(
				'<span class="ccac-more">%s</span>',
				esc_html( $more_label )
			);
		}
	}

	/**
	 * Translate an issue code for display.
	 *
	 * @param string $code Issue code.
	 * @return string
	 */
	private function issue_label( $code ) {
		$labels = array(
			'title_too_short'        => __( 'Title too short', 'configcraft-agentic-commerce' ),
			'description_incomplete' => __( 'Description incomplete', 'configcraft-agentic-commerce' ),
			'price_missing'          => __( 'Price missing', 'configcraft-agentic-commerce' ),
			'image_missing'          => __( 'Image missing', 'configcraft-agentic-commerce' ),
			'category_missing'       => __( 'Category missing', 'configcraft-agentic-commerce' ),
			'brand_missing'          => __( 'Brand missing', 'configcraft-agentic-commerce' ),
			'identifier_missing'     => __( 'GTIN or identifier missing', 'configcraft-agentic-commerce' ),
			'sku_missing'            => __( 'SKU missing', 'configcraft-agentic-commerce' ),
			'attributes_missing'     => __( 'Attributes missing', 'configcraft-agentic-commerce' ),
			'shipping_data_missing'  => __( 'Weight or dimensions missing', 'configcraft-agentic-commerce' ),
			'variations_missing'     => __( 'No variations configured', 'configcraft-agentic-commerce' ),
		);

		return isset( $labels[ $code ] ) ? $labels[ $code ] : $code;
	}

	/**
	 * Translate an audit status.
	 *
	 * @param string $status Status key.
	 * @return string
	 */
	private function status_label( $status ) {
		$labels = array(
			'ready'      => __( 'Ready', 'configcraft-agentic-commerce' ),
			'needs_work' => __( 'Needs work', 'configcraft-agentic-commerce' ),
			'at_risk'    => __( 'At risk', 'configcraft-agentic-commerce' ),
		);

		return isset( $labels[ $status ] ) ? $labels[ $status ] : $status;
	}
}
