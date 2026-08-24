<?php
/**
 * WooCommerce Product Add-Ons pricing adapter.
 *
 * @package DestinXAICommerce
 */

namespace DestinX\AICommerce;

defined( 'ABSPATH' ) || exit;

/**
 * Recognizes products with price-bearing official WooCommerce add-ons.
 */
final class Product_Addons_Adapter implements Pricing_Adapter {
	/**
	 * {@inheritDoc}
	 */
	public function get_id() {
		return 'woocommerce_product_addons';
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_priority() {
		return 50;
	}

	/**
	 * Detect official product add-ons that can change the final price.
	 *
	 * @param \WC_Product          $product WooCommerce product.
	 * @param array<string, mixed> $data    Normalized product data.
	 * @return array<string, mixed>|null
	 */
	public function detect( $product, array $data ) {
		if ( ! class_exists( 'WC_Product_Addons_Helper' ) || ! method_exists( 'WC_Product_Addons_Helper', 'get_product_addons' ) ) {
			return null;
		}

		$addons = \WC_Product_Addons_Helper::get_product_addons( (int) $product->get_id() );
		if ( ! is_array( $addons ) || empty( $addons ) ) {
			return null;
		}

		$has_price_adjustment = false;
		$has_customer_price   = false;
		foreach ( $addons as $addon ) {
			if ( ! is_array( $addon ) ) {
				continue;
			}

			$type = isset( $addon['type'] ) ? (string) $addon['type'] : '';
			if ( 'custom_price' === $type ) {
				$has_customer_price = true;
			}
			if ( $has_customer_price || $this->has_price( $addon ) ) {
				$has_price_adjustment = true;
			}
		}

		if ( ! $has_price_adjustment ) {
			return null;
		}

		$base_price   = isset( $data['price'] ) ? (string) $data['price'] : '';
		$has_base     = '' !== trim( $base_price );
		$is_available = $has_base || ( $has_customer_price && ! empty( $data['is_purchasable'] ) );

		return array(
			'mode'                            => 'dynamic',
			'source'                          => 'woocommerce_product_addons',
			'label'                           => 'WooCommerce Product Add-Ons',
			'is_available'                    => $is_available,
			'min_price'                       => $has_base ? $base_price : '',
			'max_price'                       => '',
			'uses_woocommerce_purchase_state' => $has_base,
		);
	}

	/**
	 * Check direct and option-level price adjustments.
	 *
	 * @param array<string, mixed> $addon Add-on definition.
	 * @return bool
	 */
	private function has_price( array $addon ) {
		if ( isset( $addon['price'] ) && '' !== trim( (string) $addon['price'] ) && 0.0 !== (float) $addon['price'] ) {
			return true;
		}

		if ( empty( $addon['options'] ) || ! is_array( $addon['options'] ) ) {
			return false;
		}

		foreach ( $addon['options'] as $option ) {
			if ( is_array( $option ) && isset( $option['price'] ) && '' !== trim( (string) $option['price'] ) && 0.0 !== (float) $option['price'] ) {
				return true;
			}
		}

		return false;
	}
}
