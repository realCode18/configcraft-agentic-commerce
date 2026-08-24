<?php
/**
 * WooCommerce admin audit page.
 *
 * @package DestinXAICommerce
 */

namespace DestinX\AICommerce;

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
	 * Store configuration adapter.
	 *
	 * @var Store_Data_Extractor
	 */
	private $store_extractor;

	/**
	 * Store readiness rules.
	 *
	 * @var Store_Readiness_Evaluator
	 */
	private $store_evaluator;

	/**
	 * Registered WordPress admin page hook.
	 *
	 * @var string
	 */
	private $hook_suffix = '';

	/**
	 * Constructor.
	 *
	 * @param Catalog_Auditor           $auditor         Preview audit service.
	 * @param Audit_Repository          $repository      Stored result service.
	 * @param Background_Audit          $background      Background scan service.
	 * @param Store_Data_Extractor      $store_extractor Store configuration adapter.
	 * @param Store_Readiness_Evaluator $store_evaluator Store readiness rules.
	 */
	public function __construct( Catalog_Auditor $auditor, Audit_Repository $repository, Background_Audit $background, Store_Data_Extractor $store_extractor, Store_Readiness_Evaluator $store_evaluator ) {
		$this->auditor         = $auditor;
		$this->repository      = $repository;
		$this->background      = $background;
		$this->store_extractor = $store_extractor;
		$this->store_evaluator = $store_evaluator;
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
			__( 'AI Commerce Readiness', 'destinx-ai-commerce' ),
			__( 'AI Commerce', 'destinx-ai-commerce' ),
			'manage_woocommerce',
			'destinx-ai-commerce',
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
			'destinx-ai-commerce-admin',
			DXAIC_URL . 'assets/admin.css',
			array(),
			DXAIC_VERSION
		);

		if ( $is_plugin_page ) {
			wp_enqueue_script(
				'destinx-ai-commerce-admin',
				DXAIC_URL . 'assets/admin.js',
				array(),
				DXAIC_VERSION,
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
			wp_die( esc_html__( 'You do not have permission to access this page.', 'destinx-ai-commerce' ) );
		}

		$page              = max( 1, (int) filter_input( INPUT_GET, 'dxaic_paged', FILTER_SANITIZE_NUMBER_INT ) );
		$per_page          = 20;
		$filters           = $this->get_request_filters();
		$state             = $this->background->get_state();
		$snapshot          = $this->repository->get_snapshot_metadata();
		$stored_summary    = $this->repository->get_summary();
		$store_readiness   = $this->store_evaluator->evaluate( $this->store_extractor->extract() );
		$has_full_snapshot = '' !== $snapshot['scan_id'];
		$filtered_total    = 0;

		if ( $has_full_snapshot ) {
			$filtered_total = $this->repository->count( $filters );
			$total_pages    = max( 1, (int) ceil( $filtered_total / $per_page ) );
			$page           = min( $page, $total_pages );
			$products       = $this->hydrate_stored_results( $this->repository->get_page( $page, $per_page, $filters ) );
			$summary        = array_merge(
				$stored_summary,
				array( 'total_products' => max( (int) $state['total'], $stored_summary['scanned'] ) )
			);
			$is_preview     = false;
		} else {
			$audit      = $this->auditor->audit();
			$products   = $audit['products'];
			$summary    = $audit['summary'];
			$is_preview = true;
		}
		?>
		<div class="wrap dxaic-wrap">
			<header class="dxaic-hero">
				<div class="dxaic-hero__copy">
					<p class="dxaic-eyebrow"><?php esc_html_e( 'DestinX AI Commerce', 'destinx-ai-commerce' ); ?></p>
					<h1><?php esc_html_e( 'AI Commerce Readiness', 'destinx-ai-commerce' ); ?></h1>
					<p class="dxaic-intro">
						<?php esc_html_e( 'Find catalog data that may prevent AI shopping assistants from understanding and recommending your products.', 'destinx-ai-commerce' ); ?>
					</p>
				</div>
				<div class="dxaic-hero__facts" aria-label="<?php esc_attr_e( 'Plugin service information', 'destinx-ai-commerce' ); ?>">
					<span class="dxaic-fact"><span class="dxaic-fact__dot" aria-hidden="true"></span><?php esc_html_e( 'Local catalog audit', 'destinx-ai-commerce' ); ?></span>
					<span class="dxaic-fact"><?php esc_html_e( 'No account or API key', 'destinx-ai-commerce' ); ?></span>
				</div>
			</header>

			<?php $this->render_notice(); ?>
			<?php $this->render_scan_control( $state, $snapshot ); ?>

			<div class="dxaic-summary" aria-label="<?php esc_attr_e( 'Catalog audit summary', 'destinx-ai-commerce' ); ?>">
				<?php $this->render_metric( __( 'Average score', 'destinx-ai-commerce' ), $summary['average_score'] . '/100', 'primary' ); ?>
				<?php $this->render_metric( __( 'Products scanned', 'destinx-ai-commerce' ), $summary['scanned'] . '/' . $summary['total_products'], 'neutral' ); ?>
				<?php $this->render_metric( __( 'Ready', 'destinx-ai-commerce' ), $summary['ready'], 'success' ); ?>
				<?php $this->render_metric( __( 'Needs work', 'destinx-ai-commerce' ), $summary['needs_work'], 'warning' ); ?>
				<?php $this->render_metric( __( 'At risk', 'destinx-ai-commerce' ), $summary['at_risk'], 'danger' ); ?>
			</div>

			<?php $this->render_store_readiness( $store_readiness ); ?>

			<?php if ( $is_preview && empty( $products ) ) : ?>
				<div class="notice notice-info inline"><p><?php esc_html_e( 'No published WooCommerce products were found.', 'destinx-ai-commerce' ); ?></p></div>
			<?php elseif ( $is_preview ) : ?>
				<div class="dxaic-panel">
					<h2><?php esc_html_e( 'Quick preview', 'destinx-ai-commerce' ); ?></h2>
					<p><?php esc_html_e( 'This preview scans the latest 25 products. Start a full catalog scan to persist, filter, and export all results.', 'destinx-ai-commerce' ); ?></p>
					<?php $this->render_table( $products ); ?>
				</div>
			<?php elseif ( 0 === $stored_summary['scanned'] ) : ?>
				<div class="notice notice-info inline"><p><?php esc_html_e( 'The latest full scan found no published WooCommerce products.', 'destinx-ai-commerce' ); ?></p></div>
			<?php else : ?>
				<div class="dxaic-panel dxaic-results-panel">
					<h2><?php esc_html_e( 'Catalog results', 'destinx-ai-commerce' ); ?></h2>
					<p><?php esc_html_e( 'Search by product or SKU, then narrow results by status, finding, or category.', 'destinx-ai-commerce' ); ?></p>
					<?php $this->render_catalog_filters( $filters, $filtered_total ); ?>
					<?php if ( empty( $products ) ) : ?>
						<div class="notice notice-info inline"><p><?php esc_html_e( 'No products match the current filters.', 'destinx-ai-commerce' ); ?></p></div>
					<?php else : ?>
						<?php $this->render_table( $products ); ?>
						<?php $this->render_pagination( $page, $per_page, $filtered_total, $filters ); ?>
					<?php endif; ?>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Render site-level technical readiness checks separately from product scoring.
	 *
	 * @param array<string, mixed> $readiness Store readiness result.
	 * @return void
	 */
	private function render_store_readiness( array $readiness ) {
		?>
		<div class="dxaic-panel dxaic-store-panel">
			<div class="dxaic-store-heading">
				<div>
					<h2><?php esc_html_e( 'Store readiness', 'destinx-ai-commerce' ); ?></h2>
					<p><?php esc_html_e( 'Technical store settings are checked separately and never change product scores.', 'destinx-ai-commerce' ); ?></p>
				</div>
				<p class="dxaic-store-summary">
					<span class="dxaic-check-status dxaic-check-status--pass">
						<?php /* translators: %d: number of store checks that passed. */ ?>
						<?php echo esc_html( sprintf( __( '%d passed', 'destinx-ai-commerce' ), $readiness['summary']['pass'] ) ); ?>
					</span>
					<span class="dxaic-check-status dxaic-check-status--warning">
						<?php /* translators: %d: number of store checks that need review. */ ?>
						<?php echo esc_html( sprintf( __( '%d to review', 'destinx-ai-commerce' ), $readiness['summary']['warning'] ) ); ?>
					</span>
					<span class="dxaic-check-status dxaic-check-status--fail">
						<?php /* translators: %d: number of store checks that need action. */ ?>
						<?php echo esc_html( sprintf( _n( '%d action needed', '%d actions needed', $readiness['summary']['fail'], 'destinx-ai-commerce' ), $readiness['summary']['fail'] ) ); ?>
					</span>
				</p>
			</div>
			<ul class="dxaic-store-checks">
				<?php foreach ( $readiness['checks'] as $check ) : ?>
					<?php
					$status      = sanitize_key( $check['status'] );
					$check_label = Store_Issue_Catalog::label( $check['code'] );
					$action_url  = Store_Issue_Catalog::action_url( $check['code'] );
					?>
					<li class="dxaic-store-check dxaic-store-check--<?php echo esc_attr( $status ); ?>">
						<div>
							<span class="dxaic-check-status dxaic-check-status--<?php echo esc_attr( $status ); ?>"><?php echo esc_html( Store_Issue_Catalog::status_label( $status ) ); ?></span>
							<strong><?php echo esc_html( $check_label ); ?></strong>
						</div>
						<?php if ( ! in_array( $status, array( 'pass', 'not_applicable' ), true ) ) : ?>
							<p>
								<?php echo esc_html( Store_Issue_Catalog::guidance( $check['code'] ) ); ?>
								<?php if ( $action_url ) : ?>
									<?php /* translators: %s: store readiness check label. */ ?>
									<a href="<?php echo esc_url( $action_url ); ?>" aria-label="<?php echo esc_attr( sprintf( __( 'Review setting: %s', 'destinx-ai-commerce' ), $check_label ) ); ?>"><?php esc_html_e( 'Review setting', 'destinx-ai-commerce' ); ?></a>
								<?php endif; ?>
							</p>
						<?php endif; ?>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>
		<?php
	}

	/**
	 * Render scan status and the start action.
	 *
	 * @param array<string, mixed> $state    Background scan state.
	 * @param array<string, mixed> $snapshot Visible snapshot metadata.
	 * @return void
	 */
	private function render_scan_control( array $state, array $snapshot ) {
		$is_running = in_array( $state['status'], array( 'queued', 'running' ), true );
		$progress   = $state['total'] ? (int) round( ( $state['processed'] / $state['total'] ) * 100 ) : 0;
		?>
		<div class="dxaic-scan-panel" data-dxaic-auto-refresh="<?php echo esc_attr( $is_running ? '1' : '0' ); ?>">
			<div>
				<h2><?php esc_html_e( 'Full catalog scan', 'destinx-ai-commerce' ); ?></h2>
				<p aria-live="polite"><?php echo esc_html( $this->scan_status_label( $state ) ); ?></p>
				<?php if ( ! empty( $snapshot['scanned_at'] ) ) : ?>
					<p class="dxaic-data-freshness">
						<?php
						$updated_at = get_date_from_gmt( $snapshot['scanned_at'], get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) );
						/* translators: 1: visible catalog data date and time, 2: scoring model version. */
						echo esc_html( sprintf( __( 'Visible catalog data updated on %1$s with scoring model %2$s.', 'destinx-ai-commerce' ), $updated_at, $snapshot['model_version'] ) );
						?>
					</p>
				<?php endif; ?>
				<?php if ( $is_running ) : ?>
					<progress aria-label="<?php esc_attr_e( 'Catalog scan progress', 'destinx-ai-commerce' ); ?>" value="<?php echo esc_attr( $state['processed'] ); ?>" max="<?php echo esc_attr( max( 1, $state['total'] ) ); ?>"><?php echo esc_html( $progress ); ?>%</progress>
					<span><?php echo esc_html( $progress ); ?>%</span>
					<p class="dxaic-auto-refresh-controls">
						<button
							type="button"
							class="button-link dxaic-auto-refresh-toggle"
							data-pause-label="<?php esc_attr_e( 'Pause automatic refresh', 'destinx-ai-commerce' ); ?>"
							data-resume-label="<?php esc_attr_e( 'Resume automatic refresh', 'destinx-ai-commerce' ); ?>"
							aria-pressed="false"
							hidden
						><?php esc_html_e( 'Pause automatic refresh', 'destinx-ai-commerce' ); ?></button>
					</p>
				<?php endif; ?>
				<?php if ( 'failed' === $state['status'] && $state['error'] ) : ?>
					<p class="dxaic-error"><?php echo esc_html( $state['error'] ); ?></p>
				<?php endif; ?>
			</div>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="dxaic_start_catalog_audit">
				<?php wp_nonce_field( 'dxaic_start_catalog_audit' ); ?>
				<button type="submit" class="button button-primary" <?php disabled( $is_running ); ?>>
					<?php echo esc_html( 'complete' === $state['status'] ? __( 'Scan again', 'destinx-ai-commerce' ) : __( 'Scan full catalog', 'destinx-ai-commerce' ) ); ?>
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
		$notice = sanitize_key( (string) filter_input( INPUT_GET, 'dxaic_notice', FILTER_SANITIZE_FULL_SPECIAL_CHARS ) );
		if ( 'started' === $notice ) {
			echo '<div class="notice notice-success inline"><p>' . esc_html__( 'The full catalog scan was queued successfully.', 'destinx-ai-commerce' ) . '</p></div>';
		}
		if ( 'already_running' === $notice ) {
			echo '<div class="notice notice-warning inline"><p>' . esc_html__( 'A catalog scan is already running.', 'destinx-ai-commerce' ) . '</p></div>';
		}
		if ( 'failed' === $notice ) {
			echo '<div class="notice notice-error inline"><p>' . esc_html__( 'The catalog scan could not be scheduled. Review the error below and try again.', 'destinx-ai-commerce' ) . '</p></div>';
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
				'sku'      => $product->get_sku(),
				'edit_url' => get_edit_post_link( $row['product_id'], 'raw' ),
				'score'    => $row['score'],
				'status'   => $row['status'],
				'issues'   => $row['issues'],
				'pricing'  => $row['pricing'],
			);
		}

		return $products;
	}

	/**
	 * Read and normalize the read-only result filters from the URL.
	 *
	 * @return array<string, int|string>
	 */
	private function get_request_filters() {
		return Audit_Repository::normalize_filters(
			array(
				'search'   => filter_input( INPUT_GET, 'dxaic_search', FILTER_UNSAFE_RAW ),
				'status'   => filter_input( INPUT_GET, 'dxaic_status', FILTER_UNSAFE_RAW ),
				'issue'    => filter_input( INPUT_GET, 'dxaic_issue', FILTER_UNSAFE_RAW ),
				'category' => filter_input( INPUT_GET, 'dxaic_category', FILTER_SANITIZE_NUMBER_INT ),
			)
		);
	}

	/**
	 * Render catalog search, filters, matching count, and protected export action.
	 *
	 * @param array<string, int|string> $filters        Active normalized filters.
	 * @param int                       $filtered_total Matching result count.
	 * @return void
	 */
	private function render_catalog_filters( array $filters, $filtered_total ) {
		$categories = get_terms(
			array(
				'taxonomy'   => 'product_cat',
				'hide_empty' => false,
			)
		);
		if ( is_wp_error( $categories ) ) {
			$categories = array();
		}
		$has_filters = (bool) array_filter( $filters );
		?>
		<form class="dxaic-filters" method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" aria-label="<?php esc_attr_e( 'Catalog result filters', 'destinx-ai-commerce' ); ?>">
			<input type="hidden" name="page" value="destinx-ai-commerce">
			<div class="dxaic-filter-fields">
				<label>
					<span><?php esc_html_e( 'Product or SKU', 'destinx-ai-commerce' ); ?></span>
					<input type="search" name="dxaic_search" value="<?php echo esc_attr( $filters['search'] ); ?>" maxlength="100" placeholder="<?php esc_attr_e( 'Search catalog', 'destinx-ai-commerce' ); ?>">
				</label>
				<label>
					<span><?php esc_html_e( 'Status', 'destinx-ai-commerce' ); ?></span>
					<select name="dxaic_status">
						<option value=""><?php esc_html_e( 'All statuses', 'destinx-ai-commerce' ); ?></option>
						<?php foreach ( array( 'ready', 'needs_work', 'at_risk' ) as $status ) : ?>
							<option value="<?php echo esc_attr( $status ); ?>" <?php selected( $filters['status'], $status ); ?>><?php echo esc_html( Issue_Catalog::status_label( $status ) ); ?></option>
						<?php endforeach; ?>
					</select>
				</label>
				<label>
					<span><?php esc_html_e( 'Finding', 'destinx-ai-commerce' ); ?></span>
					<select name="dxaic_issue">
						<option value=""><?php esc_html_e( 'All findings', 'destinx-ai-commerce' ); ?></option>
						<?php foreach ( Issue_Catalog::codes() as $issue_code ) : ?>
							<option value="<?php echo esc_attr( $issue_code ); ?>" <?php selected( $filters['issue'], $issue_code ); ?>><?php echo esc_html( Issue_Catalog::label( $issue_code ) ); ?></option>
						<?php endforeach; ?>
					</select>
				</label>
				<label>
					<span><?php esc_html_e( 'Category', 'destinx-ai-commerce' ); ?></span>
					<select name="dxaic_category">
						<option value="0"><?php esc_html_e( 'All categories', 'destinx-ai-commerce' ); ?></option>
						<?php foreach ( $categories as $category ) : ?>
							<option value="<?php echo esc_attr( $category->term_id ); ?>" <?php selected( (int) $filters['category'], (int) $category->term_id ); ?>><?php echo esc_html( $category->name ); ?></option>
						<?php endforeach; ?>
					</select>
				</label>
			</div>
			<div class="dxaic-filter-actions">
				<button type="submit" class="button button-primary"><?php esc_html_e( 'Apply filters', 'destinx-ai-commerce' ); ?></button>
				<?php if ( $has_filters ) : ?>
					<a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=destinx-ai-commerce' ) ); ?>"><?php esc_html_e( 'Clear filters', 'destinx-ai-commerce' ); ?></a>
				<?php endif; ?>
			</div>
		</form>
		<div class="dxaic-results-toolbar">
			<p aria-live="polite">
				<?php
				/* translators: %d: number of catalog products matching the current filters. */
				echo esc_html( sprintf( _n( '%d product matches', '%d products match', $filtered_total, 'destinx-ai-commerce' ), $filtered_total ) );
				?>
			</p>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" aria-label="<?php esc_attr_e( 'Export catalog results', 'destinx-ai-commerce' ); ?>">
				<input type="hidden" name="action" value="<?php echo esc_attr( Catalog_Csv_Exporter::ACTION ); ?>">
				<input type="hidden" name="dxaic_search" value="<?php echo esc_attr( $filters['search'] ); ?>">
				<input type="hidden" name="dxaic_status" value="<?php echo esc_attr( $filters['status'] ); ?>">
				<input type="hidden" name="dxaic_issue" value="<?php echo esc_attr( $filters['issue'] ); ?>">
				<input type="hidden" name="dxaic_category" value="<?php echo esc_attr( $filters['category'] ); ?>">
				<?php wp_nonce_field( Catalog_Csv_Exporter::NONCE_ACTION ); ?>
				<button type="submit" class="button" <?php disabled( 0 === $filtered_total ); ?>><?php esc_html_e( 'Export filtered CSV', 'destinx-ai-commerce' ); ?></button>
			</form>
		</div>
		<?php
	}

	/**
	 * Render catalog results.
	 *
	 * @param array<int, array<string, mixed>> $products Product results.
	 * @return void
	 */
	private function render_table( array $products ) {
		?>
		<div class="dxaic-table-scroll" tabindex="0" role="region" aria-label="<?php esc_attr_e( 'Scrollable catalog results', 'destinx-ai-commerce' ); ?>">
		<table class="widefat striped dxaic-table">
			<caption class="screen-reader-text"><?php esc_html_e( 'WooCommerce products and their AI commerce readiness findings', 'destinx-ai-commerce' ); ?></caption>
			<thead><tr>
				<th><?php esc_html_e( 'Product', 'destinx-ai-commerce' ); ?></th>
				<th><?php esc_html_e( 'Score', 'destinx-ai-commerce' ); ?></th>
				<th><?php esc_html_e( 'Status', 'destinx-ai-commerce' ); ?></th>
				<th><?php esc_html_e( 'Findings', 'destinx-ai-commerce' ); ?></th>
			</tr></thead>
			<tbody>
				<?php foreach ( $products as $product ) : ?>
					<tr>
						<td>
							<a href="<?php echo esc_url( $product['edit_url'] ); ?>"><strong><?php echo esc_html( $product['name'] ); ?></strong></a>
							<?php if ( '' !== $product['sku'] ) : ?>
								<span class="dxaic-product-sku">
									<?php /* translators: %s: product SKU. */ ?>
									<?php echo esc_html( sprintf( __( 'SKU: %s', 'destinx-ai-commerce' ), $product['sku'] ) ); ?>
								</span>
							<?php endif; ?>
							<?php if ( ! empty( $product['pricing'] ) ) : ?>
								<span class="dxaic-product-pricing dxaic-product-pricing--<?php echo esc_attr( $product['pricing']['mode'] ); ?>" title="<?php echo esc_attr( Pricing_Context::verification_label( $product['pricing'] ) ); ?>">
									<?php echo esc_html( Pricing_Context::display_label( $product['pricing'] ) ); ?>
								</span>
								<span class="dxaic-pricing-verification"><?php echo esc_html( Pricing_Context::verification_label( $product['pricing'] ) ); ?></span>
							<?php endif; ?>
						</td>
						<td><strong><?php echo esc_html( $product['score'] ); ?>/100</strong></td>
						<td><span class="dxaic-status dxaic-status--<?php echo esc_attr( $product['status'] ); ?>"><?php echo esc_html( Issue_Catalog::status_label( $product['status'] ) ); ?></span></td>
						<td><?php $this->render_issues( $product['issues'] ); ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
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
		<div class="dxaic-metric dxaic-metric--<?php echo esc_attr( $tone ); ?>">
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
			echo '<span class="dxaic-clear">' . esc_html__( 'No findings', 'destinx-ai-commerce' ) . '</span>';
			return;
		}

		$visible = array_slice( $issues, 0, 4 );
		foreach ( $visible as $issue ) {
			$issue_label    = Issue_Catalog::label( $issue['code'] );
			$issue_guidance = Issue_Catalog::guidance( $issue['code'] );
			printf(
				'<span class="dxaic-issue dxaic-issue--%1$s" title="%2$s" aria-label="%3$s">%4$s</span> ',
				esc_attr( $issue['severity'] ),
				esc_attr( $issue_guidance ),
				esc_attr( $issue_label . '. ' . $issue_guidance ),
				esc_html( $issue_label )
			);
		}

		$remaining = count( $issues ) - count( $visible );
		if ( 0 < $remaining ) {
			/* translators: %d: number of additional catalog findings. */
			$more_label = sprintf( _n( '+%d more', '+%d more', $remaining, 'destinx-ai-commerce' ), $remaining );
			printf( '<span class="dxaic-more">%s</span>', esc_html( $more_label ) );
		}
	}

	/**
	 * Render stored result pagination.
	 *
	 * @param int                       $page       Current page.
	 * @param int                       $per_page   Results per page.
	 * @param int                       $total_rows Total stored results.
	 * @param array<string, int|string> $filters    Active filters to preserve.
	 * @return void
	 */
	private function render_pagination( $page, $per_page, $total_rows, array $filters ) {
		$total_pages = (int) ceil( $total_rows / $per_page );
		if ( 2 > $total_pages ) {
			return;
		}

		$placeholder = 999999999;
		$query_args  = array_merge(
			array(
				'page'        => 'destinx-ai-commerce',
				'dxaic_paged' => $placeholder,
			),
			$this->filter_query_args( $filters )
		);
		$base        = str_replace(
			(string) $placeholder,
			'%#%',
			esc_url( add_query_arg( $query_args, admin_url( 'admin.php' ) ) )
		);
		$links       = paginate_links(
			array(
				'base'      => $base,
				'format'    => '',
				'current'   => $page,
				'total'     => $total_pages,
				'prev_text' => __( 'Previous', 'destinx-ai-commerce' ),
				'next_text' => __( 'Next', 'destinx-ai-commerce' ),
			)
		);

		if ( $links ) {
			echo '<div class="tablenav"><div class="tablenav-pages">' . wp_kses_post( $links ) . '</div></div>';
		}
	}

	/**
	 * Convert normalized filters into non-empty dashboard query arguments.
	 *
	 * @param array<string, int|string> $filters Normalized filters.
	 * @return array<string, int|string>
	 */
	private function filter_query_args( array $filters ) {
		$args = array();
		if ( '' !== $filters['search'] ) {
			$args['dxaic_search'] = $filters['search'];
		}
		if ( '' !== $filters['status'] ) {
			$args['dxaic_status'] = $filters['status'];
		}
		if ( '' !== $filters['issue'] ) {
			$args['dxaic_issue'] = $filters['issue'];
		}
		if ( 0 < $filters['category'] ) {
			$args['dxaic_category'] = $filters['category'];
		}

		return $args;
	}

	/**
	 * Return a human-readable scan state.
	 *
	 * @param array<string, mixed> $state Scan state.
	 * @return string
	 */
	private function scan_status_label( array $state ) {
		if ( 'queued' === $state['status'] ) {
			return __( 'The scan is queued and will start shortly.', 'destinx-ai-commerce' );
		}
		if ( 'running' === $state['status'] ) {
			/* translators: 1: processed products, 2: total products. */
			return sprintf( __( 'Scanning product %1$d of %2$d.', 'destinx-ai-commerce' ), $state['processed'], $state['total'] );
		}
		if ( 'complete' === $state['status'] ) {
			/* translators: %d: number of scanned products. */
			return sprintf( __( 'The last scan completed with %d products.', 'destinx-ai-commerce' ), $state['processed'] );
		}
		if ( 'failed' === $state['status'] ) {
			return __( 'The last scan stopped because of an error. You can start it again.', 'destinx-ai-commerce' );
		}

		return __( 'Run a full scan to analyze every published product in small background batches.', 'destinx-ai-commerce' );
	}
}
