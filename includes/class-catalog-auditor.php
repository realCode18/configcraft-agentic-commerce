<?php
/**
 * Catalog audit orchestration.
 *
 * @package ConfigCraftAgenticCommerce
 */

namespace ConfigCraft\AgenticCommerce;

defined( 'ABSPATH' ) || exit;

/**
 * Loads WooCommerce products and aggregates their readiness results.
 */
final class Catalog_Auditor {
	/**
	 * WooCommerce product adapter.
	 *
	 * @var Product_Data_Extractor
	 */
	private $extractor;

	/**
	 * Product scoring service.
	 *
	 * @var Product_Readiness_Evaluator
	 */
	private $evaluator;

	/**
	 * Constructor.
	 *
	 * @param Product_Data_Extractor      $extractor Product adapter.
	 * @param Product_Readiness_Evaluator $evaluator Readiness rules.
	 */
	public function __construct( Product_Data_Extractor $extractor, Product_Readiness_Evaluator $evaluator ) {
		$this->extractor = $extractor;
		$this->evaluator = $evaluator;
	}

	/**
	 * Audit the first page of published products.
	 *
	 * @param int $limit Maximum number of products.
	 * @return array<string, mixed>
	 */
	public function audit( $limit = 25 ) {
		$limit = (int) apply_filters( 'configcraft_agentic_commerce_audit_limit', $limit );
		$limit = max( 1, min( 100, $limit ) );

		$query = wc_get_products(
			array(
				'limit'    => $limit,
				'page'     => 1,
				'status'   => 'publish',
				'orderby'  => 'ID',
				'order'    => 'DESC',
				'paginate' => true,
			)
		);

		$results     = array();
		$total_score = 0;
		$ready       = 0;
		$needs_work  = 0;
		$at_risk     = 0;

		foreach ( $query->products as $product ) {
			$evaluation   = $this->evaluator->evaluate( $this->extractor->extract( $product ) );
			$total_score += $evaluation['score'];

			if ( 'ready' === $evaluation['status'] ) {
				++$ready;
			}
			if ( 'needs_work' === $evaluation['status'] ) {
				++$needs_work;
			}
			if ( 'at_risk' === $evaluation['status'] ) {
				++$at_risk;
			}

			$results[] = array(
				'id'       => $product->get_id(),
				'name'     => $product->get_name(),
				'edit_url' => get_edit_post_link( $product->get_id(), 'raw' ),
				'score'    => $evaluation['score'],
				'status'   => $evaluation['status'],
				'issues'   => $evaluation['issues'],
			);
		}

		$scanned = count( $results );

		return array(
			'products' => $results,
			'summary'  => array(
				'total_products' => (int) $query->total,
				'scanned'        => $scanned,
				'average_score'  => $scanned ? (int) round( $total_score / $scanned ) : 0,
				'ready'          => $ready,
				'needs_work'     => $needs_work,
				'at_risk'        => $at_risk,
			),
		);
	}
}
