<?php
/**
 * WooCommerce Name Your Price pricing adapter.
 *
 * @package DestinXAICommerce
 */

namespace DestinX\AICommerce;

defined( 'ABSPATH' ) || exit;

/**
 * Recognizes products whose amount is supplied by the customer.
 */
final class Name_Your_Price_Adapter implements Pricing_Adapter {
	/**
	 * {@inheritDoc}
	 */
	public function get_id() {
		return 'woocommerce_name_your_price';
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_priority() {
		return 100;
	}

	/**
	 * Detect customer-controlled pricing on a product or its variations.
	 *
	 * @param \WC_Product          $product WooCommerce product.
	 * @param array<string, mixed> $data    Normalized product data.
	 * @return array<string, mixed>|null
	 */
	public function detect( $product, array $data ) {
		if ( ! class_exists( 'WC_Name_Your_Price' ) && ! class_exists( 'WC_Name_Your_Price_Helpers' ) ) {
			return null;
		}

		$products = array( $product );
		if ( $product->is_type( 'variable' ) ) {
			$products = array();
			foreach ( $product->get_children() as $variation_id ) {
				$variation = wc_get_product( $variation_id );
				if ( $variation ) {
					$products[] = $variation;
				}
			}
		}

		$minimums      = array();
		$maximums      = array();
		$unbounded     = false;
		$enabled_count = 0;
		foreach ( $products as $candidate ) {
			if ( 'yes' !== (string) $candidate->get_meta( '_nyp', true ) ) {
				continue;
			}

			++$enabled_count;
			$minimum    = $this->amount( $candidate->get_meta( '_min_price', true ) );
			$maximum    = $this->amount( $candidate->get_meta( '_maximum_price', true ) );
			$minimums[] = '' === $minimum ? 0.0 : (float) $minimum;
			if ( '' === $maximum ) {
				$unbounded = true;
			} else {
				$maximums[] = (float) $maximum;
			}
		}

		if ( 0 === $enabled_count ) {
			return null;
		}

		return array(
			'mode'                            => 'dynamic',
			'source'                          => 'woocommerce_name_your_price',
			'label'                           => 'WooCommerce Name Your Price',
			'is_available'                    => true,
			'min_price'                       => (string) min( $minimums ),
			'max_price'                       => $unbounded || empty( $maximums ) ? '' : (string) max( $maximums ),
			// Mixed variable products still need native checks for fixed-price variations.
			'uses_woocommerce_purchase_state' => $enabled_count < count( $products ),
		);
	}

	/**
	 * Accept a non-negative product-meta amount.
	 *
	 * @param mixed $value Candidate amount.
	 * @return string
	 */
	private function amount( $value ) {
		if ( '' === trim( (string) $value ) || ! is_numeric( $value ) || 0 > (float) $value ) {
			return '';
		}

		return (string) $value;
	}
}
