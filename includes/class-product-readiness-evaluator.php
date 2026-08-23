<?php
/**
 * Pure product readiness rules.
 *
 * @package DestinXAICommerce
 */

namespace DestinX\AICommerce;

/**
 * Applies deterministic readiness rules to normalized product data.
 */
final class Product_Readiness_Evaluator {
	const MODEL_VERSION = '1.1.0';

	/**
	 * Evaluate normalized product data.
	 *
	 * @param array<string, mixed> $data Product data.
	 * @return array<string, mixed>
	 */
	public function evaluate( array $data ) {
		$issues = array();

		if ( 10 > $this->text_length( isset( $data['name'] ) ? $data['name'] : '' ) ) {
			$issues[] = $this->issue( 'title_too_short', 'high', 15 );
		}

		$description = trim(
			wp_strip_all_tags(
				isset( $data['description'] ) ? (string) $data['description'] : ''
			)
		);
		if ( 80 > $this->text_length( $description ) ) {
			$issues[] = $this->issue( 'description_incomplete', 'medium', 8 );
		}

		$pricing   = Pricing_Context::normalize(
			isset( $data['pricing'] ) ? $data['pricing'] : array(),
			isset( $data['price'] ) ? $data['price'] : ''
		);
		$has_price = ! empty( $pricing['is_available'] );
		if ( ! $has_price ) {
			$issues[] = $this->issue( 'price_missing', 'high', 20 );
		}
		$uses_woocommerce_purchase_state = Pricing_Context::uses_woocommerce_purchase_state( $pricing );

		if ( empty( $data['image_id'] ) ) {
			$issues[] = $this->issue( 'image_missing', 'medium', 8 );
		}

		if ( empty( $data['category_ids'] ) ) {
			$issues[] = $this->issue( 'category_missing', 'medium', 8 );
		}

		if ( '' === trim( isset( $data['brand'] ) ? (string) $data['brand'] : '' ) ) {
			$issues[] = $this->issue( 'brand_missing', 'medium', 8 );
		}

		if ( '' === trim( isset( $data['identifier'] ) ? (string) $data['identifier'] : '' ) ) {
			$issues[] = $this->issue( 'identifier_missing', 'medium', 8 );
		}

		if ( '' === trim( isset( $data['sku'] ) ? (string) $data['sku'] : '' ) ) {
			$issues[] = $this->issue( 'sku_missing', 'low', 4 );
		}

		if ( empty( $data['attribute_count'] ) ) {
			$issues[] = $this->issue( 'attributes_missing', 'low', 4 );
		}

		$is_variable = ! empty( $data['is_variable'] );
		$is_physical = empty( $data['is_virtual'] ) && empty( $data['is_downloadable'] );
		if ( $is_variable && ! empty( $data['all_variations_virtual_or_downloadable'] ) ) {
			$is_physical = false;
		}
		$has_size          = ! empty( $data['length'] ) && ! empty( $data['width'] ) && ! empty( $data['height'] );
		$has_shipping_data = ! empty( $data['weight'] ) || $has_size || ! empty( $data['has_variation_shipping_data'] );
		if ( $is_physical && ! $has_shipping_data ) {
			$issues[] = $this->issue( 'shipping_data_missing', 'low', 4 );
		}

		if ( $is_variable ) {
			$variation_count = isset( $data['variation_count'] ) ? (int) $data['variation_count'] : 0;
			if ( 0 === $variation_count ) {
				$issues[] = $this->issue( 'variations_missing', 'high', 20 );
			} else {
				$purchasable_count  = isset( $data['purchasable_variation_count'] ) ? (int) $data['purchasable_variation_count'] : 0;
				$missing_prices     = isset( $data['variation_missing_price_count'] ) ? (int) $data['variation_missing_price_count'] : 0;
				$missing_attributes = isset( $data['variation_missing_attribute_count'] ) ? (int) $data['variation_missing_attribute_count'] : 0;

				if ( $has_price && $uses_woocommerce_purchase_state && 0 === $purchasable_count ) {
					$issues[] = $this->issue( 'variations_not_purchasable', 'high', 20 );
				}
				if ( $has_price && $uses_woocommerce_purchase_state && 0 < $missing_prices ) {
					$issues[] = $this->issue( 'variation_prices_missing', 'medium', 8 );
				}
				if ( 0 < $missing_attributes ) {
					$issues[] = $this->issue( 'variation_attributes_incomplete', 'medium', 6 );
				}
			}
		} elseif ( $has_price && $uses_woocommerce_purchase_state && isset( $data['is_purchasable'] ) && ! $data['is_purchasable'] && 'outofstock' !== ( isset( $data['stock_status'] ) ? $data['stock_status'] : '' ) ) {
			$issues[] = $this->issue( 'product_not_purchasable', 'high', 20 );
		}

		$known_stock_statuses = array( 'instock', 'outofstock', 'onbackorder' );
		if ( isset( $data['stock_status'] ) && ! in_array( $data['stock_status'], $known_stock_statuses, true ) ) {
			$issues[] = $this->issue( 'stock_status_unknown', 'low', 4 );
		}

		$issues = apply_filters( 'destinx_ai_commerce_product_issues', $issues, $data );
		usort( $issues, array( $this, 'compare_issues' ) );
		$score = 100;
		foreach ( $issues as $issue ) {
			$score -= isset( $issue['penalty'] ) ? (int) $issue['penalty'] : 0;
		}
		$score = max( 0, min( 100, $score ) );

		return array(
			'model_version' => self::MODEL_VERSION,
			'score'         => $score,
			'status'        => $this->get_status( $score, $issues ),
			'issues'        => array_values( $issues ),
			'pricing'       => $pricing,
		);
	}

	/**
	 * Create a consistent issue payload.
	 *
	 * @param string $code     Machine-readable code.
	 * @param string $severity Severity level.
	 * @param int    $penalty  Score penalty.
	 * @return array<string, mixed>
	 */
	private function issue( $code, $severity, $penalty ) {
		return array(
			'code'     => $code,
			'severity' => $severity,
			'penalty'  => $penalty,
		);
	}

	/**
	 * Convert a numeric score into a stable status.
	 *
	 * @param int                              $score  Readiness score.
	 * @param array<int, array<string, mixed>> $issues Product issues.
	 * @return string
	 */
	private function get_status( $score, array $issues ) {
		if ( 50 > $score ) {
			return 'at_risk';
		}

		foreach ( $issues as $issue ) {
			if ( isset( $issue['severity'] ) && 'high' === $issue['severity'] ) {
				return 'needs_work';
			}
		}

		if ( 80 <= $score ) {
			return 'ready';
		}

		return 'needs_work';
	}

	/**
	 * Sort severe, high-penalty findings first with a deterministic tie-breaker.
	 *
	 * @param array<string, mixed> $left  First issue.
	 * @param array<string, mixed> $right Second issue.
	 * @return int
	 */
	private function compare_issues( array $left, array $right ) {
		$severity_weights = array(
			'high'   => 3,
			'medium' => 2,
			'low'    => 1,
		);
		$left_key         = isset( $left['severity'] ) ? (string) $left['severity'] : '';
		$right_key        = isset( $right['severity'] ) ? (string) $right['severity'] : '';
		$left_severity    = isset( $severity_weights[ $left_key ] ) ? $severity_weights[ $left_key ] : 0;
		$right_severity   = isset( $severity_weights[ $right_key ] ) ? $severity_weights[ $right_key ] : 0;
		if ( $left_severity !== $right_severity ) {
			return $right_severity <=> $left_severity;
		}

		$left_penalty  = isset( $left['penalty'] ) ? (int) $left['penalty'] : 0;
		$right_penalty = isset( $right['penalty'] ) ? (int) $right['penalty'] : 0;
		if ( $left_penalty !== $right_penalty ) {
			return $right_penalty <=> $left_penalty;
		}

		return strcmp( isset( $left['code'] ) ? (string) $left['code'] : '', isset( $right['code'] ) ? (string) $right['code'] : '' );
	}

	/**
	 * Get a text length with or without the mbstring extension.
	 *
	 * @param string $text Text to measure.
	 * @return int
	 */
	private function text_length( $text ) {
		$text = (string) $text;
		return function_exists( 'mb_strlen' ) ? mb_strlen( $text ) : strlen( $text );
	}
}
