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
		$this->assertSame( '1.2.1', $result['model_version'] );
		$this->assertTrue( $result['pricing']['is_available'] );
		$this->assertTrue( $result['pricing']['uses_woocommerce_purchase_state'] );
		$this->assertSame( 'native', $result['pricing']['confidence'] );
	}

	public function test_external_dynamic_price_is_available_without_native_woocommerce_price() {
		$evaluator                 = new Product_Readiness_Evaluator();
		$product                   = $this->complete_product();
		$product['price']          = '';
		$product['pricing']        = array(
			'mode'         => 'dynamic',
			'source'       => 'configcraft',
			'label'        => 'ConfigCraft',
			'is_available' => true,
		);
		$product['is_purchasable'] = false;

		$result = $evaluator->evaluate( $product );
		$codes  = array_column( $result['issues'], 'code' );

		$this->assertSame( 100, $result['score'] );
		$this->assertSame( 'ready', $result['status'] );
		$this->assertNotContains( 'price_missing', $codes );
		$this->assertNotContains( 'product_not_purchasable', $codes );
		$this->assertSame( 'dynamic', $result['pricing']['mode'] );
		$this->assertSame( 'configcraft', $result['pricing']['source'] );
	}

	public function test_dynamic_mode_without_explicit_price_availability_still_reports_missing_price() {
		$evaluator          = new Product_Readiness_Evaluator();
		$product            = $this->complete_product();
		$product['price']   = '';
		$product['pricing'] = array(
			'mode'   => 'dynamic',
			'source' => 'custom_engine',
			'label'  => 'Custom engine',
		);

		$result = $evaluator->evaluate( $product );

		$this->assertContains( 'price_missing', array_column( $result['issues'], 'code' ) );
	}

	public function test_dynamic_modifier_can_keep_native_purchasability_checks() {
		$evaluator                 = new Product_Readiness_Evaluator();
		$product                   = $this->complete_product();
		$product['is_purchasable'] = false;
		$product['pricing']        = array(
			'mode'                            => 'dynamic',
			'source'                          => 'measurement_engine',
			'label'                           => 'Measurement engine',
			'is_available'                    => true,
			'uses_woocommerce_purchase_state' => true,
		);

		$result = $evaluator->evaluate( $product );

		$this->assertContains( 'product_not_purchasable', array_column( $result['issues'], 'code' ) );
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
		$evaluator       = new Product_Readiness_Evaluator();
		$product         = $this->complete_product();
		$product['name'] = 'Shoe';

		$result = $evaluator->evaluate( $product );

		$this->assertSame( 85, $result['score'] );
		$this->assertSame( 'needs_work', $result['status'] );
		$this->assertSame( 'title_too_short', $result['issues'][0]['code'] );
	}

	public function test_variable_product_reports_purchasability_and_child_gaps() {
		$evaluator                                    = new Product_Readiness_Evaluator();
		$product                                      = $this->complete_product();
		$product['is_variable']                       = true;
		$product['variation_count']                   = 2;
		$product['purchasable_variation_count']       = 0;
		$product['variation_missing_price_count']     = 1;
		$product['variation_missing_attribute_count'] = 1;

		$result = $evaluator->evaluate( $product );
		$codes  = array_column( $result['issues'], 'code' );

		$this->assertSame( 66, $result['score'] );
		$this->assertSame( 'needs_work', $result['status'] );
		$this->assertSame( 'variations_not_purchasable', $codes[0] );
		$this->assertContains( 'variation_prices_missing', $codes );
		$this->assertContains( 'variation_attributes_incomplete', $codes );
	}

	public function test_virtual_and_downloadable_products_do_not_require_shipping_data() {
		$evaluator                  = new Product_Readiness_Evaluator();
		$product                    = $this->complete_product();
		$product['is_virtual']      = true;
		$product['is_downloadable'] = true;
		$product['weight']          = '';
		$product['length']          = '';
		$product['width']           = '';
		$product['height']          = '';

		$result = $evaluator->evaluate( $product );

		$this->assertSame( 100, $result['score'] );
		$this->assertNotContains( 'shipping_data_missing', array_column( $result['issues'], 'code' ) );
	}

	public function test_external_and_grouped_products_do_not_receive_direct_purchase_or_shipping_findings() {
		$evaluator = new Product_Readiness_Evaluator();

		foreach ( array( 'external', 'grouped' ) as $product_type ) {
			$product                   = $this->complete_product();
			$product['product_type']   = $product_type;
			$product['is_purchasable'] = false;
			$product['weight']         = '';
			$product['length']         = '';
			$product['width']          = '';
			$product['height']         = '';

			$result = $evaluator->evaluate( $product );
			$codes  = array_column( $result['issues'], 'code' );

			$this->assertSame( 100, $result['score'], $product_type );
			$this->assertNotContains( 'product_not_purchasable', $codes, $product_type );
			$this->assertNotContains( 'shipping_data_missing', $codes, $product_type );
		}
	}

	public function test_physical_product_without_weight_or_complete_dimensions_has_shipping_finding() {
		$evaluator         = new Product_Readiness_Evaluator();
		$product           = $this->complete_product();
		$product['weight'] = '';
		$product['length'] = '30';
		$product['width']  = '';
		$product['height'] = '12';

		$result = $evaluator->evaluate( $product );

		$this->assertSame( 96, $result['score'] );
		$this->assertSame( 'ready', $result['status'] );
		$this->assertContains( 'shipping_data_missing', array_column( $result['issues'], 'code' ) );
	}

	public function test_non_purchasable_configuration_is_distinct_from_normal_out_of_stock_state() {
		$evaluator                 = new Product_Readiness_Evaluator();
		$product                   = $this->complete_product();
		$product['is_purchasable'] = false;
		$not_purchasable           = $evaluator->evaluate( $product );
		$product['stock_status']   = 'outofstock';
		$normal_out_of_stock       = $evaluator->evaluate( $product );

		$this->assertContains( 'product_not_purchasable', array_column( $not_purchasable['issues'], 'code' ) );
		$this->assertNotContains( 'product_not_purchasable', array_column( $normal_out_of_stock['issues'], 'code' ) );
		$this->assertSame( 100, $normal_out_of_stock['score'] );
	}

	public function test_unknown_stock_status_is_reported_and_score_is_bounded() {
		$evaluator               = new Product_Readiness_Evaluator();
		$product                 = $this->complete_product();
		$product['stock_status'] = 'custom-state';
		$unknown_stock           = $evaluator->evaluate( $product );

		$this->assertSame( 96, $unknown_stock['score'] );
		$this->assertContains( 'stock_status_unknown', array_column( $unknown_stock['issues'], 'code' ) );

		$empty_product = array_fill_keys( array_keys( $this->complete_product() ), '' );
		$bounded       = $evaluator->evaluate( $empty_product );
		$this->assertGreaterThanOrEqual( 0, $bounded['score'] );
		$this->assertLessThanOrEqual( 100, $bounded['score'] );
	}

	private function complete_product() {
		return array(
			'product_type'                           => 'simple',
			'name'                                   => 'Waterproof hiking shoe for mountain trails',
			'description'                            => str_repeat( 'Detailed product information. ', 5 ),
			'price'                                  => '119.00',
			'pricing'                                => array(
				'mode'         => 'fixed',
				'source'       => 'woocommerce',
				'label'        => 'WooCommerce',
				'is_available' => true,
			),
			'image_id'                               => 10,
			'category_ids'                           => array( 2 ),
			'brand'                                  => 'Example Brand',
			'identifier'                             => '1234567890123',
			'sku'                                    => 'SHOE-001',
			'attribute_count'                        => 3,
			'is_virtual'                             => false,
			'is_downloadable'                        => false,
			'weight'                                 => '0.8',
			'length'                                 => '30',
			'width'                                  => '20',
			'height'                                 => '12',
			'is_variable'                            => false,
			'variation_count'                        => 0,
			'purchasable_variation_count'            => 0,
			'variation_missing_price_count'          => 0,
			'variation_missing_attribute_count'      => 0,
			'has_variation_shipping_data'            => false,
			'all_variations_virtual_or_downloadable' => false,
			'is_purchasable'                         => true,
			'stock_status'                           => 'instock',
		);
	}
}
