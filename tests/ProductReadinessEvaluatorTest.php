<?php

use ConfigCraft\AgenticCommerce\Product_Readiness_Evaluator;
use PHPUnit\Framework\TestCase;

final class ProductReadinessEvaluatorTest extends TestCase {
	public function test_complete_product_is_ready() {
		$evaluator = new Product_Readiness_Evaluator();
		$result    = $evaluator->evaluate( $this->complete_product() );

		$this->assertSame( 100, $result['score'] );
		$this->assertSame( 'ready', $result['status'] );
		$this->assertSame( array(), $result['issues'] );
	}

	public function test_missing_commerce_fields_reduce_score() {
		$evaluator             = new Product_Readiness_Evaluator();
		$product               = $this->complete_product();
		$product['price']      = '';
		$product['brand']      = '';
		$product['identifier'] = '';

		$result = $evaluator->evaluate( $product );
		$codes  = array_column( $result['issues'], 'code' );

		$this->assertSame( 64, $result['score'] );
		$this->assertSame( 'needs_work', $result['status'] );
		$this->assertContains( 'price_missing', $codes );
		$this->assertContains( 'brand_missing', $codes );
		$this->assertContains( 'identifier_missing', $codes );
	}

	public function test_empty_variable_product_is_at_risk() {
		$evaluator                  = new Product_Readiness_Evaluator();
		$product                    = $this->complete_product();
		$product['is_variable']     = true;
		$product['variation_count'] = 0;
		$product['price']           = '';
		$product['image_id']        = 0;
		$product['brand']           = '';

		$result = $evaluator->evaluate( $product );

		$this->assertSame( 44, $result['score'] );
		$this->assertSame( 'at_risk', $result['status'] );
	}

	private function complete_product() {
		return array(
			'name'            => 'Waterproof hiking shoe for mountain trails',
			'description'     => str_repeat( 'Detailed product information. ', 5 ),
			'price'           => '119.00',
			'image_id'        => 10,
			'category_ids'    => array( 2 ),
			'brand'           => 'Example Brand',
			'identifier'      => '1234567890123',
			'sku'             => 'SHOE-001',
			'attribute_count' => 3,
			'is_virtual'      => false,
			'is_downloadable' => false,
			'weight'          => '0.8',
			'length'          => '30',
			'width'           => '20',
			'height'          => '12',
			'is_variable'     => false,
			'variation_count' => 0,
		);
	}
}
