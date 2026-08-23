<?php
/**
 * Pure store readiness rules.
 *
 * @package DestinXAICommerce
 */

namespace DestinX\AICommerce;

/**
 * Evaluates normalized WordPress and WooCommerce store configuration.
 */
final class Store_Readiness_Evaluator {
	const MODEL_VERSION = '1.0.0';

	/**
	 * Evaluate a normalized store payload.
	 *
	 * @param array<string, mixed> $data Store data.
	 * @return array<string, mixed>
	 */
	public function evaluate( array $data ) {
		$checks = array(
			$this->boolean_check( 'https_enabled', ! empty( $data['https_enabled'] ), 'fail' ),
			$this->boolean_check( 'search_engine_visible', ! empty( $data['search_engine_visible'] ), 'fail' ),
			$this->boolean_check( 'pretty_permalinks', ! empty( $data['pretty_permalinks'] ), 'fail' ),
			$this->boolean_check( 'store_country_configured', ! empty( $data['store_country_configured'] ), 'fail' ),
			$this->boolean_check( 'store_address_configured', ! empty( $data['store_address_configured'] ), 'warning' ),
			$this->boolean_check( 'currency_configured', ! empty( $data['currency_configured'] ), 'fail' ),
			$this->boolean_check( 'cart_page_published', ! empty( $data['cart_page_published'] ), 'fail' ),
			$this->boolean_check( 'checkout_page_published', ! empty( $data['checkout_page_published'] ), 'fail' ),
			$this->boolean_check( 'account_page_published', ! empty( $data['account_page_published'] ), 'fail' ),
			$this->boolean_check( 'privacy_page_published', ! empty( $data['privacy_page_published'] ), 'warning' ),
			$this->boolean_check( 'terms_page_published', ! empty( $data['terms_page_published'] ), 'warning' ),
			$this->boolean_check( 'returns_page_published', ! empty( $data['returns_page_published'] ), 'warning' ),
			$this->boolean_check( 'rest_api_available', ! empty( $data['rest_api_available'] ), 'warning' ),
			$this->boolean_check( 'published_products_available', ! empty( $data['has_published_products'] ), 'fail' ),
		);

		if ( empty( $data['has_physical_products'] ) ) {
			$checks[] = $this->check( 'shipping_method_available', 'not_applicable' );
		} else {
			$checks[] = $this->boolean_check( 'shipping_method_available', ! empty( $data['has_shipping_method'] ), 'warning' );
		}

		$summary = array(
			'pass'           => 0,
			'warning'        => 0,
			'fail'           => 0,
			'not_applicable' => 0,
		);
		foreach ( $checks as $check ) {
			if ( isset( $summary[ $check['status'] ] ) ) {
				++$summary[ $check['status'] ];
			}
		}

		return array(
			'model_version' => self::MODEL_VERSION,
			'checks'        => $checks,
			'summary'       => $summary,
		);
	}

	/**
	 * Create a pass or problem check.
	 *
	 * @param string $code           Stable check code.
	 * @param bool   $passes         Whether the check passes.
	 * @param string $failure_status Warning or fail.
	 * @return array<string, string>
	 */
	private function boolean_check( $code, $passes, $failure_status ) {
		return $this->check( $code, $passes ? 'pass' : $failure_status );
	}

	/**
	 * Create one normalized store check.
	 *
	 * @param string $code   Stable check code.
	 * @param string $status Check status.
	 * @return array<string, string>
	 */
	private function check( $code, $status ) {
		return array(
			'code'   => $code,
			'status' => $status,
		);
	}
}
