<?php
/**
 * Full-catalog background scanning.
 *
 * @package ConfigCraftAgenticCommerce
 */

namespace ConfigCraft\AgenticCommerce;

defined( 'ABSPATH' ) || exit;

/**
 * Processes catalog audits in small WooCommerce-friendly batches.
 */
final class Background_Audit {
	const ACTION_HOOK  = 'configcraft_agentic_commerce_process_batch';
	const ACTION_GROUP = 'configcraft-agentic-commerce';
	const STATE_OPTION = 'ccac_background_audit_state';

	/**
	 * Persistent audit result storage.
	 *
	 * @var Audit_Repository
	 */
	private $repository;

	/**
	 * WooCommerce product data adapter.
	 *
	 * @var Product_Data_Extractor
	 */
	private $extractor;

	/**
	 * Product readiness scoring service.
	 *
	 * @var Product_Readiness_Evaluator
	 */
	private $evaluator;

	/**
	 * Constructor.
	 *
	 * @param Audit_Repository            $repository Result storage.
	 * @param Product_Data_Extractor      $extractor  Product adapter.
	 * @param Product_Readiness_Evaluator $evaluator  Product scoring service.
	 */
	public function __construct( Audit_Repository $repository, Product_Data_Extractor $extractor, Product_Readiness_Evaluator $evaluator ) {
		$this->repository = $repository;
		$this->extractor  = $extractor;
		$this->evaluator  = $evaluator;
	}

	/**
	 * Register action handlers.
	 *
	 * @return void
	 */
	public function hooks() {
		add_action( 'admin_post_ccac_start_catalog_audit', array( $this, 'handle_start_request' ) );
		add_action( self::ACTION_HOOK, array( $this, 'process_batch' ), 10, 1 );
	}

	/**
	 * Start a scan from the authenticated admin action.
	 *
	 * @return void
	 */
	public function handle_start_request() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You do not have permission to start a catalog audit.', 'configcraft-agentic-commerce' ) );
		}

		check_admin_referer( 'ccac_start_catalog_audit' );
		$started = $this->start();
		$url     = add_query_arg(
			'ccac_notice',
			$started ? 'started' : 'already_running',
			admin_url( 'admin.php?page=configcraft-agentic-commerce' )
		);

		wp_safe_redirect( $url );
		exit;
	}

	/**
	 * Initialize a new full-catalog scan.
	 *
	 * @return bool Whether a new scan was queued.
	 */
	public function start() {
		$state = $this->get_state();
		if ( in_array( $state['status'], array( 'queued', 'running' ), true ) ) {
			return false;
		}

		$total = $this->get_total_products();
		$this->repository->clear();
		$this->save_state(
			array(
				'status'      => $total ? 'queued' : 'complete',
				'total'       => $total,
				'processed'   => 0,
				'started_at'  => current_time( 'mysql', true ),
				'finished_at' => $total ? '' : current_time( 'mysql', true ),
				'error'       => '',
			)
		);

		if ( $total ) {
			$this->schedule_batch( 1 );
		}

		return true;
	}

	/**
	 * Process one product page and schedule the next one.
	 *
	 * @param int $page Product query page.
	 * @throws \RuntimeException When an audit result cannot be persisted.
	 * @return void
	 */
	public function process_batch( $page = 1 ) {
		$state = $this->get_state();
		if ( ! in_array( $state['status'], array( 'queued', 'running' ), true ) ) {
			return;
		}

		$batch_size = (int) apply_filters( 'configcraft_agentic_commerce_batch_size', 25 );
		$batch_size = max( 5, min( 100, $batch_size ) );

		try {
			$this->save_state( array_merge( $state, array( 'status' => 'running' ) ) );
			$query = wc_get_products(
				array(
					'limit'    => $batch_size,
					'page'     => max( 1, (int) $page ),
					'status'   => 'publish',
					'orderby'  => 'ID',
					'order'    => 'ASC',
					'paginate' => true,
				)
			);

			foreach ( $query->products as $product ) {
				$evaluation = $this->evaluator->evaluate( $this->extractor->extract( $product ) );
				if ( ! $this->repository->save( $product->get_id(), $evaluation ) ) {
					throw new \RuntimeException( __( 'The product audit result could not be saved.', 'configcraft-agentic-commerce' ) );
				}
			}

			$state              = $this->get_state();
			$state['processed'] = min( $state['total'], $state['processed'] + count( $query->products ) );

			if ( (int) $page < (int) $query->max_num_pages ) {
				$state['status'] = 'running';
				$this->save_state( $state );
				$this->schedule_batch( (int) $page + 1 );
				return;
			}

			$state['status']      = 'complete';
			$state['processed']   = count( $query->products ) ? $state['processed'] : $state['total'];
			$state['finished_at'] = current_time( 'mysql', true );
			$this->save_state( $state );
		} catch ( \Throwable $exception ) {
			$state['status'] = 'failed';
			$state['error']  = sanitize_text_field( $exception->getMessage() );
			$this->save_state( $state );
		}
	}

	/**
	 * Return the current scan state.
	 *
	 * @return array<string, mixed>
	 */
	public function get_state() {
		$defaults = array(
			'status'      => 'idle',
			'total'       => 0,
			'processed'   => 0,
			'started_at'  => '',
			'finished_at' => '',
			'error'       => '',
		);
		$state    = get_option( self::STATE_OPTION, array() );

		return wp_parse_args( is_array( $state ) ? $state : array(), $defaults );
	}

	/**
	 * Count published products without loading the full catalog.
	 *
	 * @return int
	 */
	private function get_total_products() {
		$query = wc_get_products(
			array(
				'limit'    => 1,
				'page'     => 1,
				'status'   => 'publish',
				'return'   => 'ids',
				'paginate' => true,
			)
		);

		return (int) $query->total;
	}

	/**
	 * Queue a batch through Action Scheduler or WordPress Cron.
	 *
	 * @param int $page Product query page.
	 * @return void
	 */
	private function schedule_batch( $page ) {
		if ( function_exists( 'as_enqueue_async_action' ) ) {
			as_enqueue_async_action( self::ACTION_HOOK, array( (int) $page ), self::ACTION_GROUP );
			return;
		}

		wp_schedule_single_event( time() + 1, self::ACTION_HOOK, array( (int) $page ) );
	}

	/**
	 * Persist the current scan state.
	 *
	 * @param array<string, mixed> $state Scan state.
	 * @return void
	 */
	private function save_state( array $state ) {
		update_option( self::STATE_OPTION, $state, false );
	}
}
