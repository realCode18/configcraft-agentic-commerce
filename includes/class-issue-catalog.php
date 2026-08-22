<?php
/**
 * Human-readable audit finding content.
 *
 * @package ConfigCraftAgenticCommerce
 */

namespace ConfigCraft\AgenticCommerce;

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
			'title_too_short'        => __( 'Title too short', 'configcraft-agentic-commerce' ),
			'description_incomplete' => __( 'Description incomplete', 'configcraft-agentic-commerce' ),
			'price_missing'          => __( 'Price missing', 'configcraft-agentic-commerce' ),
			'image_missing'          => __( 'Image missing', 'configcraft-agentic-commerce' ),
			'category_missing'       => __( 'Category missing', 'configcraft-agentic-commerce' ),
			'brand_missing'          => __( 'Brand missing', 'configcraft-agentic-commerce' ),
			'identifier_missing'     => __( 'GTIN or identifier missing', 'configcraft-agentic-commerce' ),
			'sku_missing'            => __( 'SKU missing', 'configcraft-agentic-commerce' ),
			'attributes_missing'     => __( 'Attributes missing', 'configcraft-agentic-commerce' ),
			'shipping_data_missing'  => __( 'Weight or dimensions missing', 'configcraft-agentic-commerce' ),
			'variations_missing'     => __( 'No variations configured', 'configcraft-agentic-commerce' ),
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
			'title_too_short'        => __( 'Use a specific title that identifies the product type and its most important distinguishing characteristic.', 'configcraft-agentic-commerce' ),
			'description_incomplete' => __( 'Add a clear description covering purpose, materials, important specifications, compatibility, and intended customer.', 'configcraft-agentic-commerce' ),
			'price_missing'          => __( 'Set a valid regular or sale price. Variable products also need a price on their purchasable variations.', 'configcraft-agentic-commerce' ),
			'image_missing'          => __( 'Choose a clear featured image that represents the product. Additional gallery images can provide useful context.', 'configcraft-agentic-commerce' ),
			'category_missing'       => __( 'Assign the product to the most specific relevant WooCommerce category.', 'configcraft-agentic-commerce' ),
			'brand_missing'          => __( 'Add a brand using the WooCommerce brand taxonomy, a pa_brand attribute, or a supported brand field.', 'configcraft-agentic-commerce' ),
			'identifier_missing'     => __( 'Add the manufacturer identifier when available, such as GTIN, EAN, UPC, ISBN, or another global unique ID.', 'configcraft-agentic-commerce' ),
			'sku_missing'            => __( 'Assign a stable internal SKU so the product can be referenced consistently across systems.', 'configcraft-agentic-commerce' ),
			'attributes_missing'     => __( 'Add structured attributes such as material, color, size, capacity, compatibility, or technical specifications.', 'configcraft-agentic-commerce' ),
			'shipping_data_missing'  => __( 'Add weight or complete dimensions so external systems can reason about fulfillment and shipping.', 'configcraft-agentic-commerce' ),
			'variations_missing'     => __( 'Create at least one purchasable variation with its required attributes, price, and availability.', 'configcraft-agentic-commerce' ),
		);

		return isset( $guidance[ $code ] ) ? $guidance[ $code ] : __( 'Review this product field and provide clear, structured information.', 'configcraft-agentic-commerce' );
	}

	/**
	 * Return the translated status label.
	 *
	 * @param string $status Status key.
	 * @return string
	 */
	public static function status_label( $status ) {
		$labels = array(
			'ready'      => __( 'Ready', 'configcraft-agentic-commerce' ),
			'needs_work' => __( 'Needs work', 'configcraft-agentic-commerce' ),
			'at_risk'    => __( 'At risk', 'configcraft-agentic-commerce' ),
		);

		return isset( $labels[ $status ] ) ? $labels[ $status ] : $status;
	}
}
