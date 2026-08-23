<?php

use DestinX\AICommerce\Product_Readiness_Evaluator;
use PHPUnit\Framework\TestCase;

final class ProductReadinessEvaluatorTest extends TestCase {
	public function test_complete_product_is_ready() {
		$evaluator = new Product_Readiness_Evaluator();
		$result    = $evaluator->evaluate( $this->complete_product() );

		$this->assertSame( 100, $result['score'] );
		$this->assertSame( 'ready', $result['status'] );
		$this->assertSame( array(), $result['issues'] );
		$this->assertSame( '1.0.0', $result['model_version'] );
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

	public function test_high_severity_issue_prevents_ready_status() {
		$evaluator         = new Product_Readiness_Evaluator();
		$product           = $this->complete_product();
		$product['name']   = 'Shoe';

		$result = $evaluator->evaluate( $product );

		$this->assertSame( 85, $result['score'] );
		$this->assertSame( 'needs_work', $result['status'] );
		$this->assertSame( 'title_too_short', $result['issues'][0]['code'] );
	}

	public function test_variable_product_reports_purchasability_and_child_gaps() {
		$evaluator                                      = new Product_Readiness_Evaluator();
		$product                                        = $this->complete_product();
		$product['is_variable']                         = true;
		$product['variation_count']                     = 2;
		$product['purchasable_variation_count']         = 0;
		$product['variation_missing_price_count']       = 1;
		$product['variation_missing_attribute_count']   = 1;

		$result = $evaluator->evaluate( $product );
		$codes  = array_column( $result['issues'], 'code' );

		$this->assertSame( 66, $result['score'] );
		$this->assertSame( 'needs_work', $result['status'] );
		$this->assertSame( 'variations_not_purchasable', $codes[0] );
		$this->assertContains( 'variation_prices_missing', $codes );
		$this->assertContains( 'variation_attributes_incomplete', $codes );
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
			'purchasable_variation_count' => 0,
			'variation_missing_price_count' => 0,
			'variation_missing_attribute_count' => 0,
			'has_variation_shipping_data' => false,
			'all_variations_virtual_or_downloadable' => false,
			'is_purchasable' => true,
			'stock_status'   => 'instock',
		);
	}
}
