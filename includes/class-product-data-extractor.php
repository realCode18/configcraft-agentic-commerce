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
	 * Third-party pricing compatibility registry.
	 *
	 * @var Pricing_Adapter_Registry
	 */
	private $pricing_adapters;

	/**
	 * Constructor.
	 *
	 * @param Pricing_Adapter_Registry|null $pricing_adapters Optional registry for tests or extensions.
	 */
	public function __construct( ?Pricing_Adapter_Registry $pricing_adapters = null ) {
		$this->pricing_adapters = $pricing_adapters ? $pricing_adapters : Pricing_Adapter_Registry::with_defaults();
	}

	/**
	 * Convert a WooCommerce product into a stable audit payload.
	 *
	 * @param \WC_Product $product Product instance.
	 * @return array<string, mixed>
	 */
	public function extract( $product ) {
		$variation_summary = $this->get_variation_summary( $product );
		$price             = $product->get_price();
		$data              = array(
			'id'                                     => $product->get_id(),
			'product_type'                           => $product->get_type(),
			'name'                                   => $product->get_name(),
			'description'                            => $product->get_description(),
			'short_description'                      => $product->get_short_description(),
			'sku'                                    => $product->get_sku(),
			'identifier'                             => $this->get_identifier( $product ),
			'brand'                                  => $this->get_brand( $product ),
			'price'                                  => $price,
			'pricing'                                => Pricing_Context::from_woocommerce_price( $price ),
			'image_id'                               => $product->get_image_id(),
			'category_ids'                           => $product->get_category_ids(),
			'attribute_count'                        => count( $product->get_attributes() ),
			'is_virtual'                             => $product->is_virtual(),
			'is_downloadable'                        => $product->is_downloadable(),
			'weight'                                 => $product->get_weight(),
			'length'                                 => $product->get_length(),
			'width'                                  => $product->get_width(),
			'height'                                 => $product->get_height(),
			'is_variable'                            => $product->is_type( 'variable' ),
			'variation_count'                        => $variation_summary['count'],
			'purchasable_variation_count'            => $variation_summary['purchasable_count'],
			'variation_missing_price_count'          => $variation_summary['missing_price_count'],
			'variation_missing_attribute_count'      => $variation_summary['missing_attribute_count'],
			'has_variation_shipping_data'            => $variation_summary['has_shipping_data'],
			'all_variations_virtual_or_downloadable' => $variation_summary['all_virtual_or_downloadable'],
			'is_purchasable'                         => $product->is_purchasable(),
			'stock_status'                           => $product->get_stock_status(),
		);
		$data['pricing']   = $this->pricing_adapters->resolve( $product, $data );

		/**
		 * Filter the normalized product data before readiness evaluation.
		 *
		 * @param array<string, mixed> $data    Audit data.
		 * @param \WC_Product          $product Product instance.
		 */
		return apply_filters( 'destinx_ai_commerce_product_data', $data, $product );
	}

	/**
	 * Summarize child variations without exposing WooCommerce objects to the evaluator.
	 *
	 * @param \WC_Product $product Product instance.
	 * @return array<string, mixed>
	 */
	private function get_variation_summary( $product ) {
		$summary = array(
			'count'                       => 0,
			'purchasable_count'           => 0,
			'missing_price_count'         => 0,
			'missing_attribute_count'     => 0,
			'has_shipping_data'           => false,
			'all_virtual_or_downloadable' => false,
		);
		if ( ! $product->is_type( 'variable' ) ) {
			return $summary;
		}

		$children                               = method_exists( $product, 'get_visible_children' ) ? $product->get_visible_children() : $product->get_children();
		$summary['count']                       = count( $children );
		$summary['all_virtual_or_downloadable'] = ! empty( $children );
		foreach ( $children as $variation_id ) {
			$variation = wc_get_product( $variation_id );
			if ( ! $variation ) {
				++$summary['missing_price_count'];
				++$summary['missing_attribute_count'];
				$summary['all_virtual_or_downloadable'] = false;
				continue;
			}

			if ( $variation->is_purchasable() ) {
				++$summary['purchasable_count'];
			}
			if ( '' === trim( (string) $variation->get_price() ) ) {
				++$summary['missing_price_count'];
			}
			$attributes = array_filter(
				$variation->get_variation_attributes(),
				static function ( $value ) {
					return '' !== trim( (string) $value );
				}
			);
			if ( empty( $attributes ) ) {
				++$summary['missing_attribute_count'];
			}

			$is_physical = ! $variation->is_virtual() && ! $variation->is_downloadable();
			if ( $is_physical ) {
				$summary['all_virtual_or_downloadable'] = false;
				$has_size                               = '' !== $variation->get_length() && '' !== $variation->get_width() && '' !== $variation->get_height();
				if ( '' !== $variation->get_weight() || $has_size ) {
					$summary['has_shipping_data'] = true;
				}
			}
		}

		return $summary;
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
