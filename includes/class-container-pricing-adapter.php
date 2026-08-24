<?php
/**
 * Configurable container product pricing adapter.
 *
 * @package DestinXAICommerce
 */

namespace DestinX\AICommerce;

defined( 'ABSPATH' ) || exit;

/**
 * Recognizes official bundle, composite, and mix-and-match product types.
 */
final class Container_Pricing_Adapter implements Pricing_Adapter {
	/**
	 * Supported product types and their runtime evidence.
	 *
	 * @var array<string, array<string, string>>
	 */
	private $types = array(
		'composite'     => array(
			'class'  => 'WC_Product_Composite',
			'source' => 'woocommerce_composite_products',
			'label'  => 'WooCommerce Composite Products',
		),
		'bundle'        => array(
			'class'  => 'WC_Product_Bundle',
			'source' => 'woocommerce_product_bundles',
			'label'  => 'WooCommerce Product Bundles',
		),
		'mix-and-match' => array(
			'class'  => 'WC_Product_Mix_and_Match',
			'source' => 'woocommerce_mix_and_match',
			'label'  => 'WooCommerce Mix and Match',
		),
	);

	/**
	 * {@inheritDoc}
	 */
	public function get_id() {
		return 'woocommerce_container_products';
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_priority() {
		return 70;
	}

	/**
	 * Detect a configurable container with a valid pricing path.
	 *
	 * @param \WC_Product          $product WooCommerce product.
	 * @param array<string, mixed> $data    Normalized product data.
	 * @return array<string, mixed>|null
	 */
	public function detect( $product, array $data ) {
		$type = (string) $product->get_type();
		if ( ! isset( $this->types[ $type ] ) ) {
			return null;
		}

		$definition = $this->types[ $type ];
		if ( ! class_exists( $definition['class'] ) || ! is_a( $product, $definition['class'] ) ) {
			return null;
		}

		$has_base_price = '' !== trim( isset( $data['price'] ) ? (string) $data['price'] : '' );
		$is_available   = $has_base_price || ( isset( $data['is_purchasable'] ) && (bool) $data['is_purchasable'] );

		return array(
			'mode'                            => 'dynamic',
			'source'                          => $definition['source'],
			'label'                           => $definition['label'],
			'is_available'                    => $is_available,
			'min_price'                       => $has_base_price ? (string) $data['price'] : '',
			'max_price'                       => '',
			'uses_woocommerce_purchase_state' => $has_base_price,
		);
	}
}
