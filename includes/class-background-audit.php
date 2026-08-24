<?php
/**
 * Full-catalog background scanning.
 *
 * @package DestinXAICommerce
 */

namespace DestinX\AICommerce;

defined( 'ABSPATH' ) || exit;

/**
 * Processes catalog audits in small WooCommerce-friendly batches.
 */
final class Background_Audit {
	const ACTION_HOOK             = 'destinx_ai_commerce_process_batch';
	const ACTION_GROUP            = 'destinx-ai-commerce';
	const STATE_OPTION            = 'dxaic_background_audit_state';
	const START_LOCK_OPTION       = 'dxaic_background_audit_start_lock';
	const PROCESS_LOCK_OPTION     = 'dxaic_background_audit_process_lock';
	const CATALOG_REVISION_OPTION = 'dxaic_catalog_revision_token';
	const DEFAULT_STALE_AFTER     = 900;
	const MAX_RETRIES             = 3;

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
		add_action( 'admin_post_dxaic_start_catalog_audit', array( $this, 'handle_start_request' ) );
		add_action( self::ACTION_HOOK, array( $this, 'process_batch' ), 10, 3 );
		add_action( 'save_post_product', array( $this, 'touch_catalog_revision' ) );
		add_action( 'save_post_product_variation', array( $this, 'touch_catalog_revision' ) );
		add_action( 'before_delete_post', array( $this, 'touch_catalog_revision' ) );
	}

	/**
	 * Record a catalog mutation using a collision-resistant token.
	 *
	 * @param int $post_id Product or variation post ID.
	 * @return void
	 */
	public function touch_catalog_revision( $post_id ) {
		$post_type = get_post_type( (int) $post_id );
		if ( ! in_array( $post_type, array( 'product', 'product_variation' ), true ) ) {
			return;
		}

		update_option( self::CATALOG_REVISION_OPTION, wp_generate_uuid4(), false );
	}

	/**
	 * Start a scan from the authenticated admin action.
	 *
	 * @return void
	 */
	public function handle_start_request() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You do not have permission to start a catalog audit.', 'destinx-ai-commerce' ) );
		}

		check_admin_referer( 'dxaic_start_catalog_audit' );
		$started = $this->start( true );
		$state   = $this->get_state();
		$notice  = $started ? 'started' : 'already_running';
		if ( $started && 'complete' === $state['status'] ) {
			$notice = 'completed';
		}
		if ( $started && 'failed' === $state['status'] ) {
			$notice = 'failed';
		}

		$url = add_query_arg(
			'dxaic_notice',
			$notice,
			admin_url( 'admin.php?page=destinx-ai-commerce' )
		);

		wp_safe_redirect( $url );
		exit;
	}

	/**
	 * Initialize a new full-catalog scan.
	 *
	 * @param bool $process_first_batch Whether to process the first bounded batch immediately.
	 * @return bool Whether a new scan was initialized.
	 */
	public function start( $process_first_batch = false ) {
		$start_token = $this->acquire_option_lock( self::START_LOCK_OPTION, 'start', 30 );
		if ( '' === $start_token ) {
			return false;
		}

		try {
			$state = $this->get_state();
			if ( Scan_State::is_active( $state ) ) {
				return false;
			}

			$scan_id = wp_generate_uuid4();
			$total   = $this->get_total_products();
			$now     = time();
			$state   = array(
				'scan_id'          => $scan_id,
				'status'           => $total ? 'queued' : 'complete',
				'total'            => $total,
				'processed'        => 0,
				'next_page'        => 1,
				'current_page'     => 0,
				'retry_count'      => 0,
				'reconcile_count'  => 0,
				'heartbeat'        => $now,
				'catalog_revision' => $this->get_catalog_revision(),
				'model_version'    => Product_Readiness_Evaluator::MODEL_VERSION,
				'started_at'       => current_time( 'mysql', true ),
				'finished_at'      => $total ? '' : current_time( 'mysql', true ),
				'error'            => '',
			);

			$this->save_state( $state );

			if ( ! $total ) {
				if ( ! $this->repository->activate_scan( $scan_id ) ) {
					$this->fail_scan( $state, __( 'The empty catalog snapshot could not be activated.', 'destinx-ai-commerce' ) );
				}

				return true;
			}

			if ( $process_first_batch ) {
				$this->process_batch( $scan_id, 1, 0 );
				return true;
			}

			if ( ! $this->schedule_batch( $scan_id, 1, 0 ) ) {
				$this->fail_scan( $state, __( 'The first catalog audit batch could not be scheduled.', 'destinx-ai-commerce' ) );
			}

			return true;
		} finally {
			$this->release_option_lock( self::START_LOCK_OPTION, $start_token );
		}
	}

	/**
	 * Process one product page and schedule the next one.
	 *
	 * @param string $scan_id Scan identifier.
	 * @param int    $page    Product query page.
	 * @param int    $attempt Retry attempt.
	 * @throws \RuntimeException When persistence or scheduling fails before retry handling.
	 * @return void
	 */
	public function process_batch( $scan_id = '', $page = 1, $attempt = 0 ) {
		$scan_id = substr( sanitize_key( (string) $scan_id ), 0, 40 );
		$page    = max( 1, (int) $page );
		$attempt = max( 0, (int) $attempt );
		$state   = $this->get_state();

		if ( ! $this->is_current_active_scan( $state, $scan_id ) ) {
			return;
		}

		$expected_page = max( 1, (int) $state['next_page'] );
		if ( $page < $expected_page ) {
			return;
		}
		if ( $page > $expected_page ) {
			$this->schedule_batch( $scan_id, $expected_page, (int) $state['retry_count'] );
			return;
		}

		$lock_owner = $scan_id . ':' . $page;
		$lock_token = $this->acquire_option_lock( self::PROCESS_LOCK_OPTION, $lock_owner, self::DEFAULT_STALE_AFTER );
		if ( '' === $lock_token ) {
			return;
		}

		try {
			$state = $this->load_state();
			if ( ! $this->is_current_active_scan( $state, $scan_id ) || $page !== (int) $state['next_page'] ) {
				return;
			}

			$state['status']       = 'running';
			$state['current_page'] = $page;
			$state['heartbeat']    = time();
			$state['error']        = '';
			$this->save_state( $state );

			$batch_size = (int) apply_filters( 'destinx_ai_commerce_batch_size', 25 );
			$batch_size = max( 5, min( 100, $batch_size ) );
			$query      = wc_get_products(
				array(
					'limit'    => $batch_size,
					'page'     => $page,
					'status'   => 'publish',
					'orderby'  => 'ID',
					'order'    => 'ASC',
					'paginate' => true,
				)
			);

			foreach ( $query->products as $product ) {
				$product_data = $this->extractor->extract( $product );
				$evaluation   = $this->evaluator->evaluate( $product_data );
				$product_hash = hash( 'sha256', (string) wp_json_encode( $product_data ) );

				if ( ! $this->repository->save( $scan_id, $product->get_id(), $evaluation, $product_hash ) ) {
					throw new \RuntimeException( __( 'The product audit result could not be saved.', 'destinx-ai-commerce' ) );
				}
			}

			$state = $this->load_state();
			if ( ! $this->is_current_active_scan( $state, $scan_id ) ) {
				return;
			}

			$state['processed']   = $this->repository->count_for_scan( $scan_id );
			$state['heartbeat']   = time();
			$state['retry_count'] = 0;

			if ( $page < (int) $query->max_num_pages ) {
				$state['status']    = 'running';
				$state['next_page'] = $page + 1;
				$this->save_state( $state );

				if ( ! $this->schedule_batch( $scan_id, $page + 1, 0 ) ) {
					throw new \RuntimeException( __( 'The next catalog audit batch could not be scheduled.', 'destinx-ai-commerce' ) );
				}

				return;
			}

			if ( ! $this->repository->prune_unpublished( $scan_id ) ) {
				throw new \RuntimeException( __( 'Products that are no longer published could not be removed from the snapshot.', 'destinx-ai-commerce' ) );
			}

			$current_revision = $this->get_catalog_revision();
			if ( ! hash_equals( (string) $state['catalog_revision'], $current_revision ) && 1 > (int) $state['reconcile_count'] ) {
				$this->repository->discard_scan( $scan_id );
				$state['status']           = 'queued';
				$state['total']            = $this->get_total_products();
				$state['processed']        = 0;
				$state['next_page']        = 1;
				$state['current_page']     = 0;
				$state['retry_count']      = 0;
				$state['reconcile_count']  = (int) $state['reconcile_count'] + 1;
				$state['catalog_revision'] = $current_revision;
				$state['heartbeat']        = time();
				$this->save_state( $state );

				if ( ! $this->schedule_batch( $scan_id, 1, 0 ) ) {
					throw new \RuntimeException( __( 'The catalog changed during the scan and the reconciliation pass could not be scheduled.', 'destinx-ai-commerce' ) );
				}

				return;
			}

			$state['processed'] = $this->repository->count_for_scan( $scan_id );
			if ( ! $this->repository->activate_scan( $scan_id ) ) {
				throw new \RuntimeException( __( 'The completed catalog snapshot could not be activated.', 'destinx-ai-commerce' ) );
			}

			$state['status']      = 'complete';
			$state['total']       = $state['processed'];
			$state['next_page']   = $page;
			$state['finished_at'] = current_time( 'mysql', true );
			$state['error']       = '';
			$this->save_state( $state );
		} catch ( \Throwable $exception ) {
			$this->handle_batch_failure( $scan_id, $page, $attempt, $exception );
		} finally {
			$this->release_option_lock( self::PROCESS_LOCK_OPTION, $lock_token );
		}
	}

	/**
	 * Return the current scan state and recover an expired scan.
	 *
	 * @return array<string, mixed>
	 */
	public function get_state() {
		$state         = $this->load_state();
		$stale_seconds = (int) apply_filters( 'destinx_ai_commerce_stale_scan_seconds', self::DEFAULT_STALE_AFTER );
		$stale_seconds = max( 300, $stale_seconds );

		if ( Scan_State::is_stale( $state, time(), $stale_seconds ) ) {
			$this->fail_scan( $state, __( 'The scan stopped responding and was released. Start a new scan to try again.', 'destinx-ai-commerce' ) );
			$state = $this->load_state();
		}

		return $state;
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
	 * Return a compact revision for the published catalog.
	 *
	 * @return string
	 */
	private function get_catalog_revision() {
		global $wpdb;

		// Aggregate metadata detects product additions, removals, and edits without loading the catalog.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT COUNT(ID) AS product_count, COALESCE(MAX(post_modified_gmt), '') AS last_modified
				FROM %i WHERE post_type = 'product' AND post_status = 'publish'",
				$wpdb->posts
			),
			ARRAY_A
		);

		$count         = isset( $row['product_count'] ) ? (int) $row['product_count'] : 0;
		$last_modified = isset( $row['last_modified'] ) ? (string) $row['last_modified'] : '';

		$revision_token = sanitize_key( get_option( self::CATALOG_REVISION_OPTION, '' ) );

		return hash( 'sha256', $count . '|' . $last_modified . '|' . $revision_token );
	}

	/**
	 * Handle one failed batch with bounded retries.
	 *
	 * @param string     $scan_id   Scan identifier.
	 * @param int        $page      Failed page.
	 * @param int        $attempt   Current attempt.
	 * @param \Throwable $exception Failure.
	 * @return void
	 */
	private function handle_batch_failure( $scan_id, $page, $attempt, \Throwable $exception ) {
		$state = $this->load_state();
		if ( ! $this->is_current_active_scan( $state, $scan_id ) ) {
			return;
		}

		$max_retries  = (int) apply_filters( 'destinx_ai_commerce_max_retries', self::MAX_RETRIES );
		$max_retries  = max( 0, min( 5, $max_retries ) );
		$next_attempt = max( (int) $attempt + 1, (int) $state['retry_count'] + 1 );
		$error        = sanitize_text_field( $exception->getMessage() );

		if ( $next_attempt <= $max_retries ) {
			$retry_page            = max( 1, (int) $state['next_page'] );
			$state['status']       = 'queued';
			$state['current_page'] = (int) $page;
			$state['retry_count']  = $next_attempt;
			$state['heartbeat']    = time();
			$state['error']        = $error;
			$this->save_state( $state );

			if ( $this->schedule_batch( $scan_id, $retry_page, $next_attempt, Scan_State::retry_delay( $next_attempt ) ) ) {
				return;
			}

			$error = __( 'The retry batch could not be scheduled.', 'destinx-ai-commerce' );
		}

		$this->fail_scan( $state, $error );
	}

	/**
	 * Mark one staging scan as failed without changing the active snapshot.
	 *
	 * @param array<string, mixed> $state Scan state.
	 * @param string               $error Safe error message.
	 * @return void
	 */
	private function fail_scan( array $state, $error ) {
		$scan_id              = isset( $state['scan_id'] ) ? sanitize_key( $state['scan_id'] ) : '';
		$state['status']      = 'failed';
		$state['heartbeat']   = time();
		$state['finished_at'] = current_time( 'mysql', true );
		$state['error']       = sanitize_text_field( $error );

		$this->repository->discard_scan( $scan_id );
		$this->clear_process_lock_for_scan( $scan_id );
		$this->save_state( $state );
	}

	/**
	 * Queue a batch through Action Scheduler or WordPress Cron.
	 *
	 * @param string $scan_id       Scan identifier.
	 * @param int    $page          Product query page.
	 * @param int    $attempt       Retry attempt.
	 * @param int    $delay_seconds Delay before processing.
	 * @return bool
	 */
	private function schedule_batch( $scan_id, $page, $attempt, $delay_seconds = 0 ) {
		$args = array( sanitize_key( $scan_id ), (int) $page, (int) $attempt );

		if ( function_exists( 'as_enqueue_async_action' ) ) {
			if ( function_exists( 'as_has_scheduled_action' ) && as_has_scheduled_action( self::ACTION_HOOK, $args, self::ACTION_GROUP ) ) {
				return true;
			}

			if ( 0 < $delay_seconds && function_exists( 'as_schedule_single_action' ) ) {
				return 0 < (int) as_schedule_single_action( time() + (int) $delay_seconds, self::ACTION_HOOK, $args, self::ACTION_GROUP );
			}

			return 0 < (int) as_enqueue_async_action( self::ACTION_HOOK, $args, self::ACTION_GROUP );
		}

		if ( wp_next_scheduled( self::ACTION_HOOK, $args ) ) {
			return true;
		}

		$result = wp_schedule_single_event( time() + max( 1, (int) $delay_seconds ), self::ACTION_HOOK, $args );

		return false !== $result && ! is_wp_error( $result );
	}

	/**
	 * Load a normalized state without triggering recovery.
	 *
	 * @return array<string, mixed>
	 */
	private function load_state() {
		$defaults = array(
			'scan_id'          => '',
			'status'           => 'idle',
			'total'            => 0,
			'processed'        => 0,
			'next_page'        => 1,
			'current_page'     => 0,
			'retry_count'      => 0,
			'reconcile_count'  => 0,
			'heartbeat'        => 0,
			'catalog_revision' => '',
			'model_version'    => Product_Readiness_Evaluator::MODEL_VERSION,
			'started_at'       => '',
			'finished_at'      => '',
			'error'            => '',
		);
		$state    = get_option( self::STATE_OPTION, array() );

		return wp_parse_args( is_array( $state ) ? $state : array(), $defaults );
	}

	/**
	 * Whether a state belongs to the scheduled unfinished scan.
	 *
	 * @param array<string, mixed> $state   Scan state.
	 * @param string               $scan_id Scan identifier.
	 * @return bool
	 */
	private function is_current_active_scan( array $state, $scan_id ) {
		return '' !== $scan_id
			&& Scan_State::is_active( $state )
			&& isset( $state['scan_id'] )
			&& hash_equals( (string) $state['scan_id'], (string) $scan_id );
	}

	/**
	 * Acquire an atomic option-backed lock with an expiry.
	 *
	 * @param string $option_name Lock option.
	 * @param string $owner       Lock owner.
	 * @param int    $ttl         Lifetime in seconds.
	 * @return string Lock token, or an empty string when busy.
	 */
	private function acquire_option_lock( $option_name, $owner, $ttl ) {
		$token   = wp_generate_uuid4();
		$payload = array(
			'token'      => $token,
			'owner'      => sanitize_text_field( $owner ),
			'expires_at' => time() + max( 1, (int) $ttl ),
		);

		if ( add_option( $option_name, $payload, '', false ) ) {
			return $token;
		}

		$current = get_option( $option_name, array() );
		if ( ! is_array( $current ) || ! isset( $current['expires_at'] ) || (int) $current['expires_at'] <= time() ) {
			delete_option( $option_name );
			if ( add_option( $option_name, $payload, '', false ) ) {
				return $token;
			}
		}

		return '';
	}

	/**
	 * Release only the lock created by the current process.
	 *
	 * @param string $option_name Lock option.
	 * @param string $token       Lock token.
	 * @return void
	 */
	private function release_option_lock( $option_name, $token ) {
		$current = get_option( $option_name, array() );
		if ( is_array( $current ) && isset( $current['token'] ) && hash_equals( (string) $current['token'], (string) $token ) ) {
			delete_option( $option_name );
		}
	}

	/**
	 * Remove an expired process lock belonging to a failed scan.
	 *
	 * @param string $scan_id Scan identifier.
	 * @return void
	 */
	private function clear_process_lock_for_scan( $scan_id ) {
		$current = get_option( self::PROCESS_LOCK_OPTION, array() );
		if ( is_array( $current ) && isset( $current['owner'] ) && 0 === strpos( (string) $current['owner'], $scan_id . ':' ) ) {
			delete_option( self::PROCESS_LOCK_OPTION );
		}
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
