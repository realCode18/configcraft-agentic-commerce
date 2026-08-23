<?php
/**
 * WooCommerce product data adapter.
 *
 * @package DestinXAICommerce
 */

namespace DestinX\AICommerce;

defined( 'ABSPATH' ) || exit;

/**
 * Normalizes WooCommerce product objects for the readiness evaluator.
 */
final class Product_Data_Extractor {
	/**
	 * Convert a WooCommerce product into a stable audit payload.
	 *
	 * @param \WC_Product $product Product instance.
	 * @return array<string, mixed>
	 */
	public function extract( $product ) {
		$data = array(
			'id'                => $product->get_id(),
			'name'              => $product->get_name(),
			'description'       => $product->get_description(),
			'short_description' => $product->get_short_description(),
			'sku'               => $product->get_sku(),
			'identifier'        => $this->get_identifier( $product ),
			'brand'             => $this->get_brand( $product ),
			'price'             => $product->get_price(),
			'image_id'          => $product->get_image_id(),
			'category_ids'      => $product->get_category_ids(),
			'attribute_count'   => count( $product->get_attributes() ),
			'is_virtual'        => $product->is_virtual(),
			'is_downloadable'   => $product->is_downloadable(),
			'weight'            => $product->get_weight(),
			'length'            => $product->get_length(),
			'width'             => $product->get_width(),
			'height'            => $product->get_height(),
			'is_variable'       => $product->is_type( 'variable' ),
			'variation_count'   => $product->is_type( 'variable' ) ? count( $product->get_children() ) : 0,
			'is_purchasable'    => $product->is_purchasable(),
			'stock_status'      => $product->get_stock_status(),
		);

		/**
		 * Filter the normalized product data before readiness evaluation.
		 *
		 * @param array<string, mixed> $data    Audit data.
		 * @param \WC_Product          $product Product instance.
		 */
		return apply_filters( 'destinx_ai_commerce_product_data', $data, $product );
	}

	/**
	 * Find a product identifier without requiring a specific feed plugin.
	 *
	 * @param \WC_Product $product Product instance.
	 * @return string
	 */
	private function get_identifier( $product ) {
		if ( method_exists( $product, 'get_global_unique_id' ) ) {
			$identifier = (string) $product->get_global_unique_id();
			if ( '' !== $identifier ) {
				return $identifier;
			}
		}

		$keys = array( '_global_unique_id', '_gtin', '_ean', '_barcode' );
		foreach ( $keys as $key ) {
			$value = (string) $product->get_meta( $key, true );
			if ( '' !== trim( $value ) ) {
				return trim( $value );
			}
		}

		return '';
	}

	/**
	 * Find a brand from common attributes, taxonomies, or metadata.
	 *
	 * @param \WC_Product $product Product instance.
	 * @return string
	 */
	private function get_brand( $product ) {
		$attribute = trim( (string) $product->get_attribute( 'pa_brand' ) );
		if ( '' !== $attribute ) {
			return $attribute;
		}

		if ( taxonomy_exists( 'product_brand' ) ) {
			$brands = wc_get_product_terms(
				$product->get_id(),
				'product_brand',
				array( 'fields' => 'names' )
			);

			if ( ! is_wp_error( $brands ) && ! empty( $brands ) ) {
				return implode( ', ', $brands );
			}
		}

		$keys = array( '_brand', 'brand' );
		foreach ( $keys as $key ) {
			$value = trim( (string) $product->get_meta( $key, true ) );
			if ( '' !== $value ) {
				return $value;
			}
		}

		return '';
	}
}
