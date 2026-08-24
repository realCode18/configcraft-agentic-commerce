<?php
/**
 * Call for Price for WooCommerce pricing adapter.
 *
 * @package DestinXAICommerce
 */

namespace DestinX\AICommerce;

defined( 'ABSPATH' ) || exit;

/**
 * Recognizes products handled by Tyche Softwares Call for Price.
 */
final class Call_For_Price_Adapter implements Pricing_Adapter {
	/**
	 * Third-party compatibility helper class.
	 */
	const COMPATIBILITY_CLASS = 'TycheSoftwares\\CallForPrice\\Lite\\Compatibility';

	/**
	 * {@inheritDoc}
	 */
	public function get_id() {
		return 'woocommerce_call_for_price';
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_priority() {
		return 80;
	}

	/**
	 * Detect an effective public Call for Price route.
	 *
	 * @param \WC_Product          $product WooCommerce product.
	 * @param array<string, mixed> $data    Normalized product data.
	 * @return array<string, mixed>|null
	 */
	public function detect( $product, array $data ) {
		if ( ! class_exists( self::COMPATIBILITY_CLASS ) || '' !== trim( isset( $data['price'] ) ? (string) $data['price'] : '' ) ) {
			return null;
		}

		$class = self::COMPATIBILITY_CLASS;
		if ( ! $this->enabled( $class::get_setting( 'general', 'enabled', true ) ) ) {
			return null;
		}

		$roles = (array) $class::get_setting( 'general', 'logged_in_only', array() );
		if ( ! empty( $roles ) && ! in_array( 'guest', $roles, true ) ) {
			return null;
		}

		$type = $product->is_type( 'variation' ) ? 'variable' : (string) $product->get_type();
		if ( ! in_array( $type, array( 'simple', 'variable', 'grouped', 'external' ), true ) ) {
			return null;
		}

		$product_meta = $class::get_product_meta( (int) $product->get_id() );
		$per_product  = $this->enabled( $class::get_setting( 'general', 'per_product_enabled', false ) )
			&& isset( $product_meta['enabled'] )
			&& 'yes' === (string) $product_meta['enabled'];
		if ( ! $per_product ) {
			if ( ! $this->enabled( $class::get_setting( $type, 'enabled', true ) ) ) {
				return null;
			}
			if ( ! $this->enabled( $class::get_setting( $type, array( 'views', 'single', 'enabled' ), true ) ) ) {
				return null;
			}
		}

		return array(
			'mode'                            => 'quote',
			'source'                          => 'woocommerce_call_for_price',
			'label'                           => 'Call for Price for WooCommerce',
			'is_available'                    => true,
			'min_price'                       => '',
			'max_price'                       => '',
			'uses_woocommerce_purchase_state' => false,
		);
	}

	/**
	 * Normalize booleans stored by different plugin generations.
	 *
	 * @param mixed $value Candidate setting.
	 * @return bool
	 */
	private function enabled( $value ) {
		return true === $value || 1 === $value || in_array( strtolower( (string) $value ), array( '1', 'yes', 'true', 'on' ), true );
	}
}
