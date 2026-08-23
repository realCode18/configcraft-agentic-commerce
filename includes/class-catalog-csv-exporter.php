<?php
/**
 * Secure catalog result CSV export.
 *
 * @package DestinXAICommerce
 */

namespace DestinX\AICommerce;

defined( 'ABSPATH' ) || exit;

/**
 * Streams the visible catalog snapshot without using temporary public files.
 */
final class Catalog_Csv_Exporter {
	const ACTION       = 'dxaic_export_catalog';
	const NONCE_ACTION = 'dxaic_export_catalog';
	const BATCH_SIZE   = 250;

	/**
	 * Stored result service.
	 *
	 * @var Audit_Repository
	 */
	private $repository;

	/**
	 * Constructor.
	 *
	 * @param Audit_Repository $repository Stored result service.
	 */
	public function __construct( Audit_Repository $repository ) {
		$this->repository = $repository;
	}

	/**
	 * Register the authenticated download handler.
	 *
	 * @return void
	 */
	public function hooks() {
		add_action( 'admin_post_' . self::ACTION, array( $this, 'handle_request' ) );
	}

	/**
	 * Validate the request and stream the CSV response.
	 *
	 * @return void
	 */
	public function handle_request() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You do not have permission to export catalog audit results.', 'destinx-ai-commerce' ) );
		}

		check_admin_referer( self::NONCE_ACTION );
		$filters = Audit_Repository::normalize_filters(
			array(
				'search'   => isset( $_POST['dxaic_search'] ) ? sanitize_text_field( wp_unslash( $_POST['dxaic_search'] ) ) : '',
				'status'   => isset( $_POST['dxaic_status'] ) ? sanitize_key( wp_unslash( $_POST['dxaic_status'] ) ) : '',
				'issue'    => isset( $_POST['dxaic_issue'] ) ? sanitize_key( wp_unslash( $_POST['dxaic_issue'] ) ) : '',
				'category' => isset( $_POST['dxaic_category'] ) ? absint( wp_unslash( $_POST['dxaic_category'] ) ) : 0,
			)
		);

		if ( '' === $this->repository->get_active_scan_id() ) {
			wp_die( esc_html__( 'Run a full catalog scan before exporting results.', 'destinx-ai-commerce' ) );
		}

		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'X-Content-Type-Options: nosniff' );
		$filename = sanitize_file_name( 'destinx-ai-commerce-' . current_time( 'Y-m-d-His' ) . '.csv' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );

		$stream = fopen( 'php://output', 'w' );
		if ( false === $stream ) {
			wp_die( esc_html__( 'The CSV output stream could not be opened.', 'destinx-ai-commerce' ) );
		}

		$this->export( $stream, $filters );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- This closes an HTTP response stream, not a file.
		fclose( $stream );
		exit;
	}

	/**
	 * Write matching rows to a stream. Public for integration testing.
	 *
	 * @param resource             $stream  Writable stream.
	 * @param array<string, mixed> $filters Optional filters.
	 * @return int Exported product count.
	 */
	public function export( $stream, array $filters = array() ) {
		$filters = Audit_Repository::normalize_filters( $filters );
		$count   = 0;
		$page    = 1;

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite -- CSV is streamed directly to the authenticated response.
		fwrite( $stream, "\xEF\xBB\xBF" );
		$this->write_row(
			$stream,
			array(
				__( 'Product ID', 'destinx-ai-commerce' ),
				__( 'Product', 'destinx-ai-commerce' ),
				__( 'SKU', 'destinx-ai-commerce' ),
				__( 'Pricing', 'destinx-ai-commerce' ),
				__( 'Score', 'destinx-ai-commerce' ),
				__( 'Status', 'destinx-ai-commerce' ),
				__( 'Finding codes', 'destinx-ai-commerce' ),
				__( 'Findings', 'destinx-ai-commerce' ),
				__( 'Categories', 'destinx-ai-commerce' ),
				__( 'Edit URL', 'destinx-ai-commerce' ),
				__( 'Scanned at', 'destinx-ai-commerce' ),
				__( 'Score model', 'destinx-ai-commerce' ),
			)
		);

		do {
			$rows        = $this->repository->get_page( $page, self::BATCH_SIZE, $filters );
			$batch_count = count( $rows );
			foreach ( $rows as $row ) {
				$product = wc_get_product( $row['product_id'] );
				if ( ! $product ) {
					continue;
				}

				$issue_codes  = array();
				$issue_labels = array();
				foreach ( $row['issues'] as $issue ) {
					if ( empty( $issue['code'] ) ) {
						continue;
					}
					$issue_codes[]  = sanitize_key( $issue['code'] );
					$issue_labels[] = Issue_Catalog::label( $issue['code'] );
				}

				$categories = wp_get_post_terms( $row['product_id'], 'product_cat', array( 'fields' => 'names' ) );
				if ( is_wp_error( $categories ) ) {
					$categories = array();
				}

				$this->write_row(
					$stream,
					array(
						$row['product_id'],
						$product->get_name(),
						$product->get_sku(),
						Pricing_Context::display_label( $row['pricing'] ),
						$row['score'],
						Issue_Catalog::status_label( $row['status'] ),
						implode( ' | ', $issue_codes ),
						implode( ' | ', $issue_labels ),
						implode( ' | ', $categories ),
						get_edit_post_link( $row['product_id'], 'raw' ),
						$row['scanned_at'],
						$row['model_version'],
					)
				);
				++$count;
			}
			++$page;
		} while ( self::BATCH_SIZE === $batch_count );

		return $count;
	}

	/**
	 * Neutralize spreadsheet formulas and write one RFC 4180-style row.
	 *
	 * @param resource          $stream Writable stream.
	 * @param array<int, mixed> $values Cell values.
	 * @return void
	 */
	private function write_row( $stream, array $values ) {
		$safe_values = array_map( array( __CLASS__, 'spreadsheet_safe_value' ), $values );
		fputcsv( $stream, $safe_values, ',', '"', '' );
	}

	/**
	 * Prevent CSV cells from being interpreted as spreadsheet formulas.
	 *
	 * @param mixed $value Raw value.
	 * @return string
	 */
	public static function spreadsheet_safe_value( $value ) {
		$value = str_replace( "\0", '', (string) $value );
		if ( preg_match( '/^[\x01-\x20]*[=+\-@]/', $value ) ) {
			return "'" . $value;
		}

		return $value;
	}
}
