<?php
/**
 * Human-readable audit finding content.
 *
 * @package DestinXAICommerce
 */

namespace DestinX\AICommerce;

defined( 'ABSPATH' ) || exit;

/**
 * Maps stable machine codes to translated labels and remediation guidance.
 */
final class Issue_Catalog {
	/**
	 * Return the display label for a finding.
	 *
	 * @param string $code Finding code.
	 * @return string
	 */
	public static function label( $code ) {
		$labels = array(
			'title_too_short'        => __( 'Title too short', 'destinx-ai-commerce' ),
			'description_incomplete' => __( 'Description incomplete', 'destinx-ai-commerce' ),
			'price_missing'          => __( 'Price missing', 'destinx-ai-commerce' ),
			'image_missing'          => __( 'Image missing', 'destinx-ai-commerce' ),
			'category_missing'       => __( 'Category missing', 'destinx-ai-commerce' ),
			'brand_missing'          => __( 'Brand missing', 'destinx-ai-commerce' ),
			'identifier_missing'     => __( 'GTIN or identifier missing', 'destinx-ai-commerce' ),
			'sku_missing'            => __( 'SKU missing', 'destinx-ai-commerce' ),
			'attributes_missing'     => __( 'Attributes missing', 'destinx-ai-commerce' ),
			'shipping_data_missing'  => __( 'Weight or dimensions missing', 'destinx-ai-commerce' ),
			'variations_missing'     => __( 'No variations configured', 'destinx-ai-commerce' ),
		);

		return isset( $labels[ $code ] ) ? $labels[ $code ] : $code;
	}

	/**
	 * Return practical remediation guidance for a finding.
	 *
	 * @param string $code Finding code.
	 * @return string
	 */
	public static function guidance( $code ) {
		$guidance = array(
			'title_too_short'        => __( 'Use a specific title that identifies the product type and its most important distinguishing characteristic.', 'destinx-ai-commerce' ),
			'description_incomplete' => __( 'Add a clear description covering purpose, materials, important specifications, compatibility, and intended customer.', 'destinx-ai-commerce' ),
			'price_missing'          => __( 'Set a valid regular or sale price. Variable products also need a price on their purchasable variations.', 'destinx-ai-commerce' ),
			'image_missing'          => __( 'Choose a clear featured image that represents the product. Additional gallery images can provide useful context.', 'destinx-ai-commerce' ),
			'category_missing'       => __( 'Assign the product to the most specific relevant WooCommerce category.', 'destinx-ai-commerce' ),
			'brand_missing'          => __( 'Add a brand using the WooCommerce brand taxonomy, a pa_brand attribute, or a supported brand field.', 'destinx-ai-commerce' ),
			'identifier_missing'     => __( 'Add the manufacturer identifier when available, such as GTIN, EAN, UPC, ISBN, or another global unique ID.', 'destinx-ai-commerce' ),
			'sku_missing'            => __( 'Assign a stable internal SKU so the product can be referenced consistently across systems.', 'destinx-ai-commerce' ),
			'attributes_missing'     => __( 'Add structured attributes such as material, color, size, capacity, compatibility, or technical specifications.', 'destinx-ai-commerce' ),
			'shipping_data_missing'  => __( 'Add weight or complete dimensions so external systems can reason about fulfillment and shipping.', 'destinx-ai-commerce' ),
			'variations_missing'     => __( 'Create at least one purchasable variation with its required attributes, price, and availability.', 'destinx-ai-commerce' ),
		);

		return isset( $guidance[ $code ] ) ? $guidance[ $code ] : __( 'Review this product field and provide clear, structured information.', 'destinx-ai-commerce' );
	}

	/**
	 * Return the translated status label.
	 *
	 * @param string $status Status key.
	 * @return string
	 */
	public static function status_label( $status ) {
		$labels = array(
			'ready'      => __( 'Ready', 'destinx-ai-commerce' ),
			'needs_work' => __( 'Needs work', 'destinx-ai-commerce' ),
			'at_risk'    => __( 'At risk', 'destinx-ai-commerce' ),
		);

		return isset( $labels[ $status ] ) ? $labels[ $status ] : $status;
	}
}
