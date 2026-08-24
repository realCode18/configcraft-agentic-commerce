<?php
/**
 * Pricing adapter registry.
 *
 * @package DestinXAICommerce
 */

namespace DestinX\AICommerce;

defined( 'ABSPATH' ) || exit;

/**
 * Resolves third-party pricing contexts in a deterministic, failure-safe order.
 */
final class Pricing_Adapter_Registry {
	/**
	 * Registered adapters, keyed by stable ID.
	 *
	 * @var array<string, Pricing_Adapter>
	 */
	private $adapters = array();

	/**
	 * Create the registry used by the Free engine.
	 *
	 * @return Pricing_Adapter_Registry
	 */
	public static function with_defaults() {
		$adapters = array(
			new Name_Your_Price_Adapter(),
			new YITH_Request_A_Quote_Adapter(),
			new Call_For_Price_Adapter(),
			new Container_Pricing_Adapter(),
			new Measurement_Price_Calculator_Adapter(),
			new Product_Addons_Adapter(),
		);

		/**
		 * Filter the read-only pricing adapters available to the Free engine.
		 *
		 * Add-ons should append objects implementing Pricing_Adapter. Duplicate or
		 * invalid IDs are ignored, and one adapter failure cannot stop a scan.
		 *
		 * @param array<int, Pricing_Adapter> $adapters Default adapters.
		 */
		$adapters = apply_filters( 'destinx_ai_commerce_pricing_adapters', $adapters );

		$registry = new self();
		if ( ! is_array( $adapters ) ) {
			return $registry;
		}

		foreach ( $adapters as $adapter ) {
			if ( $adapter instanceof Pricing_Adapter ) {
				$registry->register( $adapter );
			}
		}

		return $registry;
	}

	/**
	 * Register one adapter unless its ID is empty or already owned.
	 *
	 * @param Pricing_Adapter $adapter Pricing adapter.
	 * @return bool Whether the adapter was registered.
	 */
	public function register( Pricing_Adapter $adapter ) {
		$id = $this->key( $adapter->get_id() );
		if ( '' === $id || isset( $this->adapters[ $id ] ) ) {
			return false;
		}

		$this->adapters[ $id ] = $adapter;
		return true;
	}

	/**
	 * Resolve the first verified product-specific pricing context.
	 *
	 * @param \WC_Product          $product WooCommerce product.
	 * @param array<string, mixed> $data    Normalized product data collected so far.
	 * @return array<string, mixed>
	 */
	public function resolve( $product, array $data ) {
		$adapters = array_values( $this->adapters );
		usort( $adapters, array( $this, 'compare' ) );

		foreach ( $adapters as $adapter ) {
			try {
				$context = $adapter->detect( $product, $data );
			} catch ( \Throwable $error ) {
				continue;
			}

			if ( ! is_array( $context ) || ! array_key_exists( 'is_available', $context ) ) {
				continue;
			}

			$context['adapter']    = $this->key( $adapter->get_id() );
			$context['confidence'] = 'verified';

			return Pricing_Context::normalize(
				$context,
				isset( $data['price'] ) ? $data['price'] : ''
			);
		}

		return Pricing_Context::normalize(
			isset( $data['pricing'] ) ? $data['pricing'] : array(),
			isset( $data['price'] ) ? $data['price'] : ''
		);
	}

	/**
	 * Return registered IDs for diagnostics and integration tests.
	 *
	 * @return array<int, string>
	 */
	public function get_ids() {
		return array_keys( $this->adapters );
	}

	/**
	 * Sort by priority, then stable ID for deterministic collision handling.
	 *
	 * @param Pricing_Adapter $left  First adapter.
	 * @param Pricing_Adapter $right Second adapter.
	 * @return int
	 */
	private function compare( Pricing_Adapter $left, Pricing_Adapter $right ) {
		$priority = (int) $right->get_priority() <=> (int) $left->get_priority();
		if ( 0 !== $priority ) {
			return $priority;
		}

		return strcmp( $this->key( $left->get_id() ), $this->key( $right->get_id() ) );
	}

	/**
	 * Sanitize an adapter ID without requiring a specific WordPress version.
	 *
	 * @param mixed $value Candidate ID.
	 * @return string
	 */
	private function key( $value ) {
		$value = strtolower( (string) $value );
		return substr( (string) preg_replace( '/[^a-z0-9_\-]/', '', $value ), 0, 100 );
	}

	/**
	 * Prevent direct construction outside named factories and tests.
	 */
	public function __construct() {}
}
