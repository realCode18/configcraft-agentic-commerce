<?php
/**
 * Normalized product pricing metadata.
 *
 * @package DestinXAICommerce
 */

namespace DestinX\AICommerce;

defined( 'ABSPATH' ) || exit;

/**
 * Keeps WooCommerce and third-party pricing engines on one stable contract.
 */
final class Pricing_Context {
	/**
	 * Create the default context for a WooCommerce product price.
	 *
	 * @param mixed $price WooCommerce price value.
	 * @return array<string, mixed>
	 */
	public static function from_woocommerce_price( $price ) {
		return array(
			'mode'         => 'fixed',
			'source'       => 'woocommerce',
			'label'        => 'WooCommerce',
			'is_available' => '' !== trim( (string) $price ),
			'min_price'    => '',
			'max_price'    => '',
		);
	}

	/**
	 * Normalize pricing metadata supplied by core or an extension.
	 *
	 * Extensions can replace the `pricing` element through the
	 * `destinx_ai_commerce_product_data` filter. A dynamic, quote-based, or
	 * externally managed price suppresses the missing-price finding only when
	 * `is_available` is explicitly true.
	 *
	 * @param mixed $context        Candidate pricing context.
	 * @param mixed $fallback_price WooCommerce price used by the default context, or null when reading a stored context.
	 * @return array<string, mixed>
	 */
	public static function normalize( $context, $fallback_price = null ) {
		$default = self::from_woocommerce_price( $fallback_price );
		if ( ! is_array( $context ) ) {
			return $default;
		}

		$mode = isset( $context['mode'] ) ? self::key( $context['mode'] ) : $default['mode'];
		if ( ! in_array( $mode, array( 'fixed', 'dynamic', 'quote', 'external', 'not_applicable' ), true ) ) {
			$mode = $default['mode'];
		}

		$source = isset( $context['source'] ) ? self::key( $context['source'] ) : $default['source'];
		if ( '' === $source ) {
			$source = $default['source'];
		}

		$label = isset( $context['label'] ) ? trim( wp_strip_all_tags( (string) $context['label'] ) ) : $default['label'];
		$label = substr( $label, 0, 100 );
		if ( '' === $label ) {
			$label = 'woocommerce' === $source ? 'WooCommerce' : $source;
		}

		$is_available = array_key_exists( 'is_available', $context ) ? (bool) $context['is_available'] : $default['is_available'];
		if ( null !== $fallback_price && 'fixed' === $mode && 'woocommerce' === $source ) {
			$is_available = $default['is_available'];
		}
		if ( 'not_applicable' === $mode ) {
			$is_available = true;
		}

		return array(
			'mode'         => $mode,
			'source'       => substr( $source, 0, 100 ),
			'label'        => $label,
			'is_available' => $is_available,
			'min_price'    => self::amount( isset( $context['min_price'] ) ? $context['min_price'] : '' ),
			'max_price'    => self::amount( isset( $context['max_price'] ) ? $context['max_price'] : '' ),
		);
	}

	/**
	 * Whether native WooCommerce purchasability rules describe this price.
	 *
	 * @param array<string, mixed> $context Normalized pricing context.
	 * @return bool
	 */
	public static function uses_woocommerce_purchase_state( array $context ) {
		return 'fixed' === $context['mode'] && 'woocommerce' === $context['source'];
	}

	/**
	 * Human-readable pricing summary for local admin screens and exports.
	 *
	 * @param array<string, mixed> $context Normalized pricing context.
	 * @return string
	 */
	public static function display_label( array $context ) {
		$context    = self::normalize( $context );
		$labels     = array(
			'fixed'          => __( 'Fixed price', 'destinx-ai-commerce' ),
			'dynamic'        => __( 'Dynamic price', 'destinx-ai-commerce' ),
			'quote'          => __( 'Quote-based price', 'destinx-ai-commerce' ),
			'external'       => __( 'Managed externally', 'destinx-ai-commerce' ),
			'not_applicable' => __( 'Price not applicable', 'destinx-ai-commerce' ),
		);
		$mode_label = isset( $labels[ $context['mode'] ] ) ? $labels[ $context['mode'] ] : $labels['fixed'];

		/* translators: 1: pricing mode, 2: pricing provider label. */
		return sprintf( __( '%1$s · %2$s', 'destinx-ai-commerce' ), $mode_label, $context['label'] );
	}

	/**
	 * Sanitize a machine-readable key without requiring a particular WP version.
	 *
	 * @param mixed $value Candidate value.
	 * @return string
	 */
	private static function key( $value ) {
		$value = strtolower( (string) $value );
		return (string) preg_replace( '/[^a-z0-9_\-]/', '', $value );
	}

	/**
	 * Preserve a valid non-negative decimal amount or return an empty value.
	 *
	 * @param mixed $value Candidate amount.
	 * @return string
	 */
	private static function amount( $value ) {
		if ( '' === trim( (string) $value ) || ! is_numeric( $value ) || 0 > (float) $value ) {
			return '';
		}

		return (string) $value;
	}
}
