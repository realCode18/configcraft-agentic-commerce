<?php

use DestinX\AICommerce\Pricing_Adapter;
use DestinX\AICommerce\Pricing_Adapter_Registry;
use PHPUnit\Framework\TestCase;

final class PricingAdapterFixture implements Pricing_Adapter {
	private $id;
	private $priority;
	private $context;
	private $throws;

	public function __construct( $id, $priority, $context, $throws = false ) {
		$this->id       = $id;
		$this->priority = $priority;
		$this->context  = $context;
		$this->throws   = $throws;
	}

	public function get_id() {
		return $this->id;
	}

	public function get_priority() {
		return $this->priority;
	}

	public function detect( $product, array $data ) {
		if ( $this->throws ) {
			throw new RuntimeException( 'Adapter failure.' );
		}

		return $this->context;
	}
}

final class PricingAdapterRegistryTest extends TestCase {
	public function test_highest_priority_verified_adapter_wins() {
		$registry = new Pricing_Adapter_Registry();
		$registry->register( new PricingAdapterFixture( 'low', 10, $this->context( 'low' ) ) );
		$registry->register( new PricingAdapterFixture( 'high', 90, $this->context( 'high' ) ) );

		$result = $registry->resolve( new stdClass(), $this->product_data() );

		$this->assertSame( 'high', $result['source'] );
		$this->assertSame( 'high', $result['adapter'] );
		$this->assertSame( 'verified', $result['confidence'] );
	}

	public function test_duplicate_ids_and_unverified_results_are_ignored() {
		$registry = new Pricing_Adapter_Registry();
		$this->assertTrue( $registry->register( new PricingAdapterFixture( 'same', 10, array( 'mode' => 'dynamic' ) ) ) );
		$this->assertFalse( $registry->register( new PricingAdapterFixture( 'same', 100, $this->context( 'duplicate' ) ) ) );

		$result = $registry->resolve( new stdClass(), $this->product_data() );

		$this->assertSame( 'woocommerce', $result['source'] );
		$this->assertSame( 'native', $result['confidence'] );
	}

	public function test_one_broken_adapter_cannot_stop_catalog_resolution() {
		$registry = new Pricing_Adapter_Registry();
		$registry->register( new PricingAdapterFixture( 'broken', 100, null, true ) );
		$registry->register( new PricingAdapterFixture( 'working', 10, $this->context( 'working' ) ) );

		$result = $registry->resolve( new stdClass(), $this->product_data() );

		$this->assertSame( 'working', $result['source'] );
		$this->assertTrue( $result['is_available'] );
	}

	private function context( $source ) {
		return array(
			'mode'                            => 'dynamic',
			'source'                          => $source,
			'label'                           => ucfirst( $source ),
			'is_available'                    => true,
			'uses_woocommerce_purchase_state' => false,
		);
	}

	private function product_data() {
		return array(
			'price'   => '10',
			'pricing' => array(
				'mode'         => 'fixed',
				'source'       => 'woocommerce',
				'label'        => 'WooCommerce',
				'is_available' => true,
			),
		);
	}
}
