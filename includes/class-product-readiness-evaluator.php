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

		if ( '' === trim( isset( $data['price'] ) ? (string) $data['price'] : '' ) ) {
			$issues[] = $this->issue( 'price_missing', 'high', 20 );
		}

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

		$is_physical = empty( $data['is_virtual'] ) && empty( $data['is_downloadable'] );
		$has_size    = ! empty( $data['length'] ) && ! empty( $data['width'] ) && ! empty( $data['height'] );
		if ( $is_physical && empty( $data['weight'] ) && ! $has_size ) {
			$issues[] = $this->issue( 'shipping_data_missing', 'low', 4 );
		}

		if ( ! empty( $data['is_variable'] ) && empty( $data['variation_count'] ) ) {
			$issues[] = $this->issue( 'variations_missing', 'high', 20 );
		}

		$issues = apply_filters( 'destinx_ai_commerce_product_issues', $issues, $data );
		$score  = 100;
		foreach ( $issues as $issue ) {
			$score -= isset( $issue['penalty'] ) ? (int) $issue['penalty'] : 0;
		}
		$score = max( 0, min( 100, $score ) );

		return array(
			'score'  => $score,
			'status' => $this->get_status( $score ),
			'issues' => array_values( $issues ),
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
	 * @param int $score Readiness score.
	 * @return string
	 */
	private function get_status( $score ) {
		if ( 80 <= $score ) {
			return 'ready';
		}

		if ( 50 <= $score ) {
			return 'needs_work';
		}

		return 'at_risk';
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
