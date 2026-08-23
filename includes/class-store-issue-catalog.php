<?php
/**
 * Human-readable store readiness content.
 *
 * @package DestinXAICommerce
 */

namespace DestinX\AICommerce;

defined( 'ABSPATH' ) || exit;

/**
 * Maps store check codes to translated labels, guidance, and settings links.
 */
final class Store_Issue_Catalog {
	/**
	 * Return a translated check label.
	 *
	 * @param string $code Check code.
	 * @return string
	 */
	public static function label( $code ) {
		$labels = array(
			'https_enabled'                => __( 'HTTPS enabled', 'destinx-ai-commerce' ),
			'search_engine_visible'        => __( 'Search engine visibility', 'destinx-ai-commerce' ),
			'pretty_permalinks'            => __( 'Readable permalinks', 'destinx-ai-commerce' ),
			'store_country_configured'     => __( 'Store country configured', 'destinx-ai-commerce' ),
			'store_address_configured'     => __( 'Store address configured', 'destinx-ai-commerce' ),
			'currency_configured'          => __( 'Store currency configured', 'destinx-ai-commerce' ),
			'cart_page_published'          => __( 'Cart page published', 'destinx-ai-commerce' ),
			'checkout_page_published'      => __( 'Checkout page published', 'destinx-ai-commerce' ),
			'account_page_published'       => __( 'My account page published', 'destinx-ai-commerce' ),
			'privacy_page_published'       => __( 'Privacy policy published', 'destinx-ai-commerce' ),
			'terms_page_published'         => __( 'Terms and conditions published', 'destinx-ai-commerce' ),
			'returns_page_published'       => __( 'Refund and returns policy published', 'destinx-ai-commerce' ),
			'rest_api_available'           => __( 'WordPress REST API available', 'destinx-ai-commerce' ),
			'published_products_available' => __( 'Published products available', 'destinx-ai-commerce' ),
			'shipping_method_available'    => __( 'Shipping method available', 'destinx-ai-commerce' ),
		);

		return isset( $labels[ $code ] ) ? $labels[ $code ] : $code;
	}

	/**
	 * Return remediation guidance for a failed or warning check.
	 *
	 * @param string $code Check code.
	 * @return string
	 */
	public static function guidance( $code ) {
		$guidance = array(
			'https_enabled'                => __( 'Serve both the WordPress address and public site address over HTTPS with a valid certificate.', 'destinx-ai-commerce' ),
			'search_engine_visible'        => __( 'Allow search engines to index the site when the store is ready for public discovery.', 'destinx-ai-commerce' ),
			'pretty_permalinks'            => __( 'Choose any permalink structure other than Plain so public resources have stable readable URLs.', 'destinx-ai-commerce' ),
			'store_country_configured'     => __( 'Select the country or region where the WooCommerce store is based.', 'destinx-ai-commerce' ),
			'store_address_configured'     => __( 'Complete at least the store street address and city used for tax, shipping, and merchant information.', 'destinx-ai-commerce' ),
			'currency_configured'          => __( 'Select the currency used for product prices and checkout.', 'destinx-ai-commerce' ),
			'cart_page_published'          => __( 'Assign and publish the WooCommerce cart page.', 'destinx-ai-commerce' ),
			'checkout_page_published'      => __( 'Assign and publish the WooCommerce checkout page.', 'destinx-ai-commerce' ),
			'account_page_published'       => __( 'Assign and publish the WooCommerce My account page.', 'destinx-ai-commerce' ),
			'privacy_page_published'       => __( 'Create, review, and publish a privacy policy, then select it in WordPress settings.', 'destinx-ai-commerce' ),
			'terms_page_published'         => __( 'Create and publish store terms, then assign the page in WooCommerce advanced settings.', 'destinx-ai-commerce' ),
			'returns_page_published'       => __( 'Review and publish the WooCommerce Refund and Returns Policy page. This is a technical presence check, not legal advice.', 'destinx-ai-commerce' ),
			'rest_api_available'           => __( 'Check permalink and security-plugin settings that may disable the normal WordPress REST API.', 'destinx-ai-commerce' ),
			'published_products_available' => __( 'Publish at least one catalog-visible WooCommerce product.', 'destinx-ai-commerce' ),
			'shipping_method_available'    => __( 'Add and enable a method in at least one WooCommerce shipping zone for physical products.', 'destinx-ai-commerce' ),
		);

		return isset( $guidance[ $code ] ) ? $guidance[ $code ] : __( 'Review this store setting.', 'destinx-ai-commerce' );
	}

	/**
	 * Return the most relevant local settings URL.
	 *
	 * @param string $code Check code.
	 * @return string
	 */
	public static function action_url( $code ) {
		$woocommerce_general  = admin_url( 'admin.php?page=wc-settings&tab=general' );
		$woocommerce_advanced = admin_url( 'admin.php?page=wc-settings&tab=advanced' );
		$urls                 = array(
			'https_enabled'                => admin_url( 'options-general.php' ),
			'search_engine_visible'        => admin_url( 'options-reading.php' ),
			'pretty_permalinks'            => admin_url( 'options-permalink.php' ),
			'store_country_configured'     => $woocommerce_general,
			'store_address_configured'     => $woocommerce_general,
			'currency_configured'          => $woocommerce_general,
			'cart_page_published'          => $woocommerce_advanced,
			'checkout_page_published'      => $woocommerce_advanced,
			'account_page_published'       => $woocommerce_advanced,
			'privacy_page_published'       => admin_url( 'options-privacy.php' ),
			'terms_page_published'         => $woocommerce_advanced,
			'returns_page_published'       => admin_url( 'edit.php?post_type=page' ),
			'rest_api_available'           => admin_url( 'options-permalink.php' ),
			'published_products_available' => admin_url( 'edit.php?post_type=product' ),
			'shipping_method_available'    => admin_url( 'admin.php?page=wc-settings&tab=shipping' ),
		);

		return isset( $urls[ $code ] ) ? $urls[ $code ] : '';
	}

	/**
	 * Return a translated status label.
	 *
	 * @param string $status Check status.
	 * @return string
	 */
	public static function status_label( $status ) {
		$labels = array(
			'pass'           => __( 'Pass', 'destinx-ai-commerce' ),
			'warning'        => __( 'Review', 'destinx-ai-commerce' ),
			'fail'           => __( 'Action needed', 'destinx-ai-commerce' ),
			'not_applicable' => __( 'Not applicable', 'destinx-ai-commerce' ),
		);

		return isset( $labels[ $status ] ) ? $labels[ $status ] : $status;
	}
}
