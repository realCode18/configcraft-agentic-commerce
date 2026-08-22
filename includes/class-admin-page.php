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
	 * Quick preview audit service.
	 *
	 * @var Catalog_Auditor
	 */
	private $auditor;

	/**
	 * Persistent audit result storage.
	 *
	 * @var Audit_Repository
	 */
	private $repository;

	/**
	 * Full-catalog background scan service.
	 *
	 * @var Background_Audit
	 */
	private $background;

	/**
	 * Registered WordPress admin page hook.
	 *
	 * @var string
	 */
	private $hook_suffix = '';

	/**
	 * Constructor.
	 *
	 * @param Catalog_Auditor  $auditor    Preview audit service.
	 * @param Audit_Repository $repository Stored result service.
	 * @param Background_Audit $background Background scan service.
	 */
	public function __construct( Catalog_Auditor $auditor, Audit_Repository $repository, Background_Audit $background ) {
		$this->auditor    = $auditor;
		$this->repository = $repository;
		$this->background = $background;
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
	 * Load plugin styles on the dashboard and product editor.
	 *
	 * @param string $hook_suffix Current admin hook.
	 * @return void
	 */
	public function enqueue_assets( $hook_suffix ) {
		$screen         = get_current_screen();
		$is_product     = $screen && 'product' === $screen->post_type;
		$is_plugin_page = $this->hook_suffix === $hook_suffix;

		if ( ! $is_product && ! $is_plugin_page ) {
			return;
		}

		wp_enqueue_style(
			'configcraft-agentic-commerce-admin',
			CCAC_URL . 'assets/admin.css',
			array(),
			CCAC_VERSION
		);

		if ( $is_plugin_page ) {
			wp_enqueue_script(
				'configcraft-agentic-commerce-admin',
				CCAC_URL . 'assets/admin.js',
				array(),
				CCAC_VERSION,
				true
			);
		}
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

		$page           = max( 1, (int) filter_input( INPUT_GET, 'ccac_paged', FILTER_SANITIZE_NUMBER_INT ) );
		$per_page       = 20;
		$state          = $this->background->get_state();
		$stored_summary = $this->repository->get_summary();

		if ( 0 < $stored_summary['scanned'] ) {
			$products   = $this->hydrate_stored_results( $this->repository->get_page( $page, $per_page ) );
			$summary    = array_merge(
				$stored_summary,
				array( 'total_products' => max( (int) $state['total'], $stored_summary['scanned'] ) )
			);
			$is_preview = false;
		} else {
			$audit      = $this->auditor->audit();
			$products   = $audit['products'];
			$summary    = $audit['summary'];
			$is_preview = true;
		}
		?>
		<div class="wrap ccac-wrap">
			<h1><?php esc_html_e( 'AI Commerce Readiness', 'configcraft-agentic-commerce' ); ?></h1>
			<p class="ccac-intro">
				<?php esc_html_e( 'Find catalog data that may prevent AI shopping assistants from understanding and recommending your products.', 'configcraft-agentic-commerce' ); ?>
			</p>

			<?php $this->render_notice(); ?>
			<?php $this->render_scan_control( $state ); ?>

			<div class="ccac-summary" aria-label="<?php esc_attr_e( 'Catalog audit summary', 'configcraft-agentic-commerce' ); ?>">
				<?php $this->render_metric( __( 'Average score', 'configcraft-agentic-commerce' ), $summary['average_score'] . '/100', 'primary' ); ?>
				<?php $this->render_metric( __( 'Products scanned', 'configcraft-agentic-commerce' ), $summary['scanned'] . '/' . $summary['total_products'], 'neutral' ); ?>
				<?php $this->render_metric( __( 'Ready', 'configcraft-agentic-commerce' ), $summary['ready'], 'success' ); ?>
				<?php $this->render_metric( __( 'Needs work', 'configcraft-agentic-commerce' ), $summary['needs_work'], 'warning' ); ?>
				<?php $this->render_metric( __( 'At risk', 'configcraft-agentic-commerce' ), $summary['at_risk'], 'danger' ); ?>
			</div>

			<?php if ( empty( $products ) ) : ?>
				<div class="notice notice-info inline"><p><?php esc_html_e( 'No published WooCommerce products were found.', 'configcraft-agentic-commerce' ); ?></p></div>
			<?php else : ?>
				<div class="ccac-panel">
					<h2><?php echo esc_html( $is_preview ? __( 'Quick preview', 'configcraft-agentic-commerce' ) : __( 'Catalog results', 'configcraft-agentic-commerce' ) ); ?></h2>
					<p>
						<?php
						echo esc_html(
							$is_preview
								? __( 'This preview scans the latest 25 products. Start a full catalog scan to persist and paginate all results.', 'configcraft-agentic-commerce' )
								: __( 'Products with the lowest readiness score are shown first.', 'configcraft-agentic-commerce' )
						);
						?>
					</p>
					<?php $this->render_table( $products ); ?>
					<?php if ( ! $is_preview ) : ?>
						<?php $this->render_pagination( $page, $per_page, $stored_summary['scanned'] ); ?>
					<?php endif; ?>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Render scan status and the start action.
	 *
	 * @param array<string, mixed> $state Background scan state.
	 * @return void
	 */
	private function render_scan_control( array $state ) {
		$is_running = in_array( $state['status'], array( 'queued', 'running' ), true );
		$progress   = $state['total'] ? (int) round( ( $state['processed'] / $state['total'] ) * 100 ) : 0;
		?>
		<div class="ccac-scan-panel" data-ccac-auto-refresh="<?php echo esc_attr( $is_running ? '1' : '0' ); ?>">
			<div>
				<h2><?php esc_html_e( 'Full catalog scan', 'configcraft-agentic-commerce' ); ?></h2>
				<p><?php echo esc_html( $this->scan_status_label( $state ) ); ?></p>
				<?php if ( $is_running ) : ?>
					<progress value="<?php echo esc_attr( $state['processed'] ); ?>" max="<?php echo esc_attr( max( 1, $state['total'] ) ); ?>"><?php echo esc_html( $progress ); ?>%</progress>
					<span><?php echo esc_html( $progress ); ?>%</span>
				<?php endif; ?>
				<?php if ( 'failed' === $state['status'] && $state['error'] ) : ?>
					<p class="ccac-error"><?php echo esc_html( $state['error'] ); ?></p>
				<?php endif; ?>
			</div>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="ccac_start_catalog_audit">
				<?php wp_nonce_field( 'ccac_start_catalog_audit' ); ?>
				<button type="submit" class="button button-primary" <?php disabled( $is_running ); ?>>
					<?php echo esc_html( 'complete' === $state['status'] ? __( 'Scan again', 'configcraft-agentic-commerce' ) : __( 'Scan full catalog', 'configcraft-agentic-commerce' ) ); ?>
				</button>
			</form>
		</div>
		<?php
	}

	/**
	 * Render query-string action feedback.
	 *
	 * @return void
	 */
	private function render_notice() {
		$notice = sanitize_key( (string) filter_input( INPUT_GET, 'ccac_notice', FILTER_SANITIZE_FULL_SPECIAL_CHARS ) );
		if ( 'started' === $notice ) {
			echo '<div class="notice notice-success inline"><p>' . esc_html__( 'The full catalog scan was queued successfully.', 'configcraft-agentic-commerce' ) . '</p></div>';
		}
		if ( 'already_running' === $notice ) {
			echo '<div class="notice notice-warning inline"><p>' . esc_html__( 'A catalog scan is already running.', 'configcraft-agentic-commerce' ) . '</p></div>';
		}
	}

	/**
	 * Convert stored rows into live product display values.
	 *
	 * @param array<int, array<string, mixed>> $rows Stored rows.
	 * @return array<int, array<string, mixed>>
	 */
	private function hydrate_stored_results( array $rows ) {
		$products = array();
		foreach ( $rows as $row ) {
			$product = wc_get_product( $row['product_id'] );
			if ( ! $product ) {
				continue;
			}

			$products[] = array(
				'id'       => $row['product_id'],
				'name'     => $product->get_name(),
				'edit_url' => get_edit_post_link( $row['product_id'], 'raw' ),
				'score'    => $row['score'],
				'status'   => $row['status'],
				'issues'   => $row['issues'],
			);
		}

		return $products;
	}

	/**
	 * Render catalog results.
	 *
	 * @param array<int, array<string, mixed>> $products Product results.
	 * @return void
	 */
	private function render_table( array $products ) {
		?>
		<table class="widefat striped ccac-table">
			<thead><tr>
				<th><?php esc_html_e( 'Product', 'configcraft-agentic-commerce' ); ?></th>
				<th><?php esc_html_e( 'Score', 'configcraft-agentic-commerce' ); ?></th>
				<th><?php esc_html_e( 'Status', 'configcraft-agentic-commerce' ); ?></th>
				<th><?php esc_html_e( 'Findings', 'configcraft-agentic-commerce' ); ?></th>
			</tr></thead>
			<tbody>
				<?php foreach ( $products as $product ) : ?>
					<tr>
						<td><a href="<?php echo esc_url( $product['edit_url'] ); ?>"><strong><?php echo esc_html( $product['name'] ); ?></strong></a></td>
						<td><strong><?php echo esc_html( $product['score'] ); ?>/100</strong></td>
						<td><span class="ccac-status ccac-status--<?php echo esc_attr( $product['status'] ); ?>"><?php echo esc_html( Issue_Catalog::status_label( $product['status'] ) ); ?></span></td>
						<td><?php $this->render_issues( $product['issues'] ); ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
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
				'<span class="ccac-issue ccac-issue--%1$s" title="%2$s">%3$s</span> ',
				esc_attr( $issue['severity'] ),
				esc_attr( Issue_Catalog::guidance( $issue['code'] ) ),
				esc_html( Issue_Catalog::label( $issue['code'] ) )
			);
		}

		$remaining = count( $issues ) - count( $visible );
		if ( 0 < $remaining ) {
			/* translators: %d: number of additional catalog findings. */
			$more_label = sprintf( _n( '+%d more', '+%d more', $remaining, 'configcraft-agentic-commerce' ), $remaining );
			printf( '<span class="ccac-more">%s</span>', esc_html( $more_label ) );
		}
	}

	/**
	 * Render stored result pagination.
	 *
	 * @param int $page       Current page.
	 * @param int $per_page   Results per page.
	 * @param int $total_rows Total stored results.
	 * @return void
	 */
	private function render_pagination( $page, $per_page, $total_rows ) {
		$total_pages = (int) ceil( $total_rows / $per_page );
		if ( 2 > $total_pages ) {
			return;
		}

		$placeholder = 999999999;
		$base        = str_replace(
			(string) $placeholder,
			'%#%',
			esc_url( add_query_arg( 'ccac_paged', $placeholder, admin_url( 'admin.php?page=configcraft-agentic-commerce' ) ) )
		);
		$links       = paginate_links(
			array(
				'base'      => $base,
				'format'    => '',
				'current'   => $page,
				'total'     => $total_pages,
				'prev_text' => __( 'Previous', 'configcraft-agentic-commerce' ),
				'next_text' => __( 'Next', 'configcraft-agentic-commerce' ),
			)
		);

		if ( $links ) {
			echo '<div class="tablenav"><div class="tablenav-pages">' . wp_kses_post( $links ) . '</div></div>';
		}
	}

	/**
	 * Return a human-readable scan state.
	 *
	 * @param array<string, mixed> $state Scan state.
	 * @return string
	 */
	private function scan_status_label( array $state ) {
		if ( 'queued' === $state['status'] ) {
			return __( 'The scan is queued and will start shortly.', 'configcraft-agentic-commerce' );
		}
		if ( 'running' === $state['status'] ) {
			/* translators: 1: processed products, 2: total products. */
			return sprintf( __( 'Scanning product %1$d of %2$d.', 'configcraft-agentic-commerce' ), $state['processed'], $state['total'] );
		}
		if ( 'complete' === $state['status'] ) {
			/* translators: %d: number of scanned products. */
			return sprintf( __( 'The last scan completed with %d products.', 'configcraft-agentic-commerce' ), $state['processed'] );
		}
		if ( 'failed' === $state['status'] ) {
			return __( 'The last scan stopped because of an error. You can start it again.', 'configcraft-agentic-commerce' );
		}

		return __( 'Run a full scan to analyze every published product in small background batches.', 'configcraft-agentic-commerce' );
	}
}
