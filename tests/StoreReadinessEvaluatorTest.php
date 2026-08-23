<?php

use DestinX\AICommerce\Store_Readiness_Evaluator;
use PHPUnit\Framework\TestCase;

final class StoreReadinessEvaluatorTest extends TestCase {
	public function test_ready_digital_store_does_not_require_shipping() {
		$evaluator = new Store_Readiness_Evaluator();
		$result    = $evaluator->evaluate( $this->ready_store() );

		$this->assertSame( '1.0.0', $result['model_version'] );
		$this->assertSame( 14, $result['summary']['pass'] );
		$this->assertSame( 0, $result['summary']['warning'] );
		$this->assertSame( 0, $result['summary']['fail'] );
		$this->assertSame( 1, $result['summary']['not_applicable'] );
		$this->assertSame( 'not_applicable', $this->status_for( $result['checks'], 'shipping_method_available' ) );
	}

	public function test_incomplete_physical_store_separates_failures_and_warnings() {
		$evaluator = new Store_Readiness_Evaluator();
		$data      = array_fill_keys( array_keys( $this->ready_store() ), false );
		$data['has_physical_products'] = true;
		$result = $evaluator->evaluate( $data );

		$this->assertSame( 0, $result['summary']['pass'] );
		$this->assertSame( 6, $result['summary']['warning'] );
		$this->assertSame( 9, $result['summary']['fail'] );
		$this->assertSame( 'fail', $this->status_for( $result['checks'], 'checkout_page_published' ) );
		$this->assertSame( 'warning', $this->status_for( $result['checks'], 'returns_page_published' ) );
		$this->assertSame( 'warning', $this->status_for( $result['checks'], 'shipping_method_available' ) );
	}

	public function test_ready_physical_store_passes_shipping_check() {
		$evaluator                       = new Store_Readiness_Evaluator();
		$data                            = $this->ready_store();
		$data['has_physical_products']   = true;
		$data['has_shipping_method']     = true;
		$result                          = $evaluator->evaluate( $data );

		$this->assertSame( 15, $result['summary']['pass'] );
		$this->assertSame( 'pass', $this->status_for( $result['checks'], 'shipping_method_available' ) );
	}

	private function ready_store() {
		return array(
			'https_enabled'             => true,
			'search_engine_visible'     => true,
			'pretty_permalinks'         => true,
			'store_country_configured'  => true,
			'store_address_configured'  => true,
			'currency_configured'       => true,
			'cart_page_published'       => true,
			'checkout_page_published'   => true,
			'account_page_published'    => true,
			'privacy_page_published'    => true,
			'terms_page_published'      => true,
			'returns_page_published'    => true,
			'rest_api_available'        => true,
			'has_published_products'    => true,
			'has_physical_products'     => false,
			'has_shipping_method'       => false,
		);
	}

	private function status_for( array $checks, $code ) {
		foreach ( $checks as $check ) {
			if ( $code === $check['code'] ) {
				return $check['status'];
			}
		}

		return '';
	}
}
