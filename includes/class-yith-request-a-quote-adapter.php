<?php
/**
 * YITH Request a Quote pricing adapter.
 *
 * @package DestinXAICommerce
 */

namespace DestinX\AICommerce;

defined( 'ABSPATH' ) || exit;

/**
 * Recognizes blank-price products with a verified public quote action.
 */
final class YITH_Request_A_Quote_Adapter implements Pricing_Adapter {
	/**
	 * {@inheritDoc}
	 */
	public function get_id() {
		return 'yith_request_a_quote';
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_priority() {
		return 90;
	}

	/**
	 * Detect a product-specific public quote action.
	 *
	 * @param \WC_Product          $product WooCommerce product.
	 * @param array<string, mixed> $data    Normalized product data.
	 * @return array<string, mixed>|null
	 */
	public function detect( $product, array $data ) {
		if ( ! defined( 'YITH_YWRAQ_VERSION' ) || '' !== trim( isset( $data['price'] ) ? (string) $data['price'] : '' ) ) {
			return null;
		}

		if ( 'yes' !== (string) get_option( 'ywraq_show_btn_single_page', 'no' ) ) {
			return null;
		}

		$hidden = $product->get_meta( '_ywraq_hide_quote_button', true );
		if ( in_array( (string) $hidden, array( '1', 'yes' ), true ) ) {
			return null;
		}

		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- This is YITH's public product-level visibility hook.
		if ( ! apply_filters( 'yith_ywraq_before_print_button', true, $product ) ) {
			return null;
		}

		return array(
			'mode'                            => 'quote',
			'source'                          => 'yith_request_a_quote',
			'label'                           => 'YITH Request a Quote',
			'is_available'                    => true,
			'min_price'                       => '',
			'max_price'                       => '',
			'uses_woocommerce_purchase_state' => false,
		);
	}
}
