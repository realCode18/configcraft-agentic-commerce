<?php
/**
 * WordPress and WooCommerce store configuration adapter.
 *
 * @package DestinXAICommerce
 */

namespace DestinX\AICommerce;

defined( 'ABSPATH' ) || exit;

/**
 * Normalizes store settings for the pure readiness evaluator.
 */
final class Store_Data_Extractor {
	/**
	 * Build the normalized store payload.
	 *
	 * @return array<string, mixed>
	 */
	public function extract() {
		$published_products = wc_get_products(
			array(
				'limit'    => 1,
				'status'   => 'publish',
				'return'   => 'ids',
				'paginate' => true,
			)
		);
		$physical_products  = wc_get_products(
			array(
				'limit'   => 1,
				'status'  => 'publish',
				'virtual' => false,
				'return'  => 'ids',
			)
		);
		$home_scheme        = wp_parse_url( home_url( '/' ), PHP_URL_SCHEME );
		$site_scheme        = wp_parse_url( site_url( '/' ), PHP_URL_SCHEME );

		$data = array(
			'https_enabled'            => 'https' === $home_scheme && 'https' === $site_scheme,
			'search_engine_visible'    => '0' !== (string) get_option( 'blog_public', '1' ),
			'pretty_permalinks'        => '' !== trim( (string) get_option( 'permalink_structure', '' ) ),
			'store_country_configured' => '' !== trim( (string) get_option( 'woocommerce_default_country', '' ) ),
			'store_address_configured' => $this->has_store_address(),
			'currency_configured'      => '' !== trim( (string) get_option( 'woocommerce_currency', '' ) ),
			'cart_page_published'      => $this->is_published_page( wc_get_page_id( 'cart' ) ),
			'checkout_page_published'  => $this->is_published_page( wc_get_page_id( 'checkout' ) ),
			'account_page_published'   => $this->is_published_page( wc_get_page_id( 'myaccount' ) ),
			'privacy_page_published'   => $this->is_published_page( (int) get_option( 'wp_page_for_privacy_policy', 0 ) ),
			'terms_page_published'     => $this->is_published_page( wc_get_page_id( 'terms' ) ),
			'returns_page_published'   => $this->is_published_page( (int) get_option( 'woocommerce_refund_returns_page_id', 0 ) ),
			'rest_api_available'       => function_exists( 'rest_url' ) && '' !== (string) rest_url(),
			'has_published_products'   => 0 < (int) $published_products->total,
			'has_physical_products'    => ! empty( $physical_products ),
			'has_shipping_method'      => $this->has_enabled_shipping_method(),
		);

		/**
		 * Filter normalized store data before readiness evaluation.
		 *
		 * @param array<string, mixed> $data Store audit data.
		 */
		return apply_filters( 'destinx_ai_commerce_store_data', $data );
	}

	/**
	 * Whether the base address has the minimum useful fields.
	 *
	 * @return bool
	 */
	private function has_store_address() {
		$address = trim( (string) get_option( 'woocommerce_store_address', '' ) );
		$city    = trim( (string) get_option( 'woocommerce_store_city', '' ) );

		return '' !== $address && '' !== $city;
	}

	/**
	 * Whether a configured page exists and is public.
	 *
	 * @param int $page_id Page ID.
	 * @return bool
	 */
	private function is_published_page( $page_id ) {
		return 0 < (int) $page_id && 'publish' === get_post_status( (int) $page_id );
	}

	/**
	 * Whether any WooCommerce shipping zone has an enabled method.
	 *
	 * @return bool
	 */
	private function has_enabled_shipping_method() {
		$zones   = \WC_Shipping_Zones::get_zones();
		$zones[] = array(
			'shipping_methods' => \WC_Shipping_Zones::get_zone( 0 )->get_shipping_methods( true ),
		);

		foreach ( $zones as $zone ) {
			if ( empty( $zone['shipping_methods'] ) ) {
				continue;
			}
			foreach ( $zone['shipping_methods'] as $method ) {
				if ( isset( $method->enabled ) && 'yes' === $method->enabled ) {
					return true;
				}
			}
		}

		return false;
	}
}
