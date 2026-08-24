<?php
/**
 * Pricing adapter contract.
 *
 * @package DestinXAICommerce
 */

namespace DestinX\AICommerce;

defined( 'ABSPATH' ) || exit;

/**
 * Detects one product-specific pricing context without changing store data.
 */
interface Pricing_Adapter {
	/**
	 * Return a stable machine-readable adapter ID.
	 *
	 * @return string
	 */
	public function get_id();

	/**
	 * Higher-priority adapters are evaluated first.
	 *
	 * @return int
	 */
	public function get_priority();

	/**
	 * Return a pricing context when this adapter manages the product.
	 *
	 * Returning null leaves the product available to the next adapter. A matched
	 * context must explicitly include `is_available`; the registry never assumes
	 * that an active plugin makes every product valid.
	 *
	 * @param \WC_Product          $product WooCommerce product.
	 * @param array<string, mixed> $data    Normalized product data collected so far.
	 * @return array<string, mixed>|null
	 */
	public function detect( $product, array $data );
}
