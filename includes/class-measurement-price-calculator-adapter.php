<?php
/**
 * WooCommerce Measurement Price Calculator pricing adapter.
 *
 * @package DestinXAICommerce
 */

namespace DestinX\AICommerce;

defined( 'ABSPATH' ) || exit;

/**
 * Recognizes measurement-enabled products through the extension's public API.
 */
final class Measurement_Price_Calculator_Adapter implements Pricing_Adapter {
	/**
	 * {@inheritDoc}
	 */
	public function get_id() {
		return 'woocommerce_measurement_price_calculator';
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_priority() {
		return 60;
	}

	/**
	 * Detect a measurement-enabled product through the public extension API.
	 *
	 * @param \WC_Product          $product WooCommerce product.
	 * @param array<string, mixed> $data    Normalized product data.
	 * @return array<string, mixed>|null
	 */
	public function detect( $product, array $data ) {
		if ( ! class_exists( 'WC_Price_Calculator_Product' ) || ! method_exists( 'WC_Price_Calculator_Product', 'calculator_enabled' ) ) {
			return null;
		}

		if ( ! \WC_Price_Calculator_Product::calculator_enabled( $product ) ) {
			return null;
		}

		$price = isset( $data['price'] ) ? (string) $data['price'] : '';
		return array(
			'mode'                            => 'dynamic',
			'source'                          => 'woocommerce_measurement_price_calculator',
			'label'                           => 'WooCommerce Measurement Price Calculator',
			'is_available'                    => '' !== trim( $price ),
			'min_price'                       => '' !== trim( $price ) ? $price : '',
			'max_price'                       => '',
			'uses_woocommerce_purchase_state' => true,
		);
	}
}
