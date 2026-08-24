<?php

use DestinX\AICommerce\Container_Pricing_Adapter;
use DestinX\AICommerce\Measurement_Price_Calculator_Adapter;
use DestinX\AICommerce\Name_Your_Price_Adapter;
use DestinX\AICommerce\Product_Addons_Adapter;
use DestinX\AICommerce\YITH_Request_A_Quote_Adapter;
use PHPUnit\Framework\TestCase;

if ( ! class_exists( 'WC_Name_Your_Price' ) ) {
	class WC_Name_Your_Price {}
}

if ( ! class_exists( 'WC_Price_Calculator_Product' ) ) {
	class WC_Price_Calculator_Product {
		public static $enabled_ids = array();

		public static function calculator_enabled( $product ) {
			return in_array( $product->get_id(), self::$enabled_ids, true );
		}
	}
}

if ( ! class_exists( 'WC_Product_Addons_Helper' ) ) {
	class WC_Product_Addons_Helper {
		public static $addons = array();

		public static function get_product_addons( $product_id ) {
			return isset( self::$addons[ $product_id ] ) ? self::$addons[ $product_id ] : array();
		}
	}
}

class PricingProductFixture {
	private $id;
	private $type;
	private $meta;
	private $children;

	public function __construct( $id, $type = 'simple', array $meta = array(), array $children = array() ) {
		$this->id       = $id;
		$this->type     = $type;
		$this->meta     = $meta;
		$this->children = $children;
	}

	public function get_id() {
		return $this->id;
	}

	public function get_type() {
		return $this->type;
	}

	public function is_type( $type ) {
		return $this->type === $type;
	}

	public function get_meta( $key, $single = true ) {
		return isset( $this->meta[ $key ] ) ? $this->meta[ $key ] : '';
	}

	public function get_children() {
		return $this->children;
	}
}

if ( ! class_exists( 'WC_Product_Composite' ) ) {
	class WC_Product_Composite extends PricingProductFixture {}
}

if ( ! defined( 'YITH_YWRAQ_VERSION' ) ) {
	define( 'YITH_YWRAQ_VERSION', 'test' );
}

final class PricingAdaptersTest extends TestCase {
	protected function setUp(): void {
		$GLOBALS['dxaic_test_options']            = array();
		$GLOBALS['dxaic_test_products']           = array();
		WC_Price_Calculator_Product::$enabled_ids = array();
		WC_Product_Addons_Helper::$addons         = array();
	}

	public function test_name_your_price_uses_verified_customer_range() {
		$product = new PricingProductFixture(
			101,
			'simple',
			array(
				'_nyp'           => 'yes',
				'_min_price'     => '5',
				'_maximum_price' => '500',
			)
		);

		$context = ( new Name_Your_Price_Adapter() )->detect( $product, array( 'price' => '' ) );

		$this->assertSame( 'dynamic', $context['mode'] );
		$this->assertTrue( $context['is_available'] );
		$this->assertSame( '5', $context['min_price'] );
		$this->assertSame( '500', $context['max_price'] );
		$this->assertFalse( $context['uses_woocommerce_purchase_state'] );
	}

	public function test_name_your_price_aggregates_enabled_variations() {
		$first                               = new PricingProductFixture(
			111,
			'variation',
			array(
				'_nyp'           => 'yes',
				'_min_price'     => '10',
				'_maximum_price' => '50',
			)
		);
		$second                              = new PricingProductFixture(
			112,
			'variation',
			array(
				'_nyp'           => 'yes',
				'_min_price'     => '20',
				'_maximum_price' => '90',
			)
		);
		$GLOBALS['dxaic_test_products'][111] = $first;
		$GLOBALS['dxaic_test_products'][112] = $second;
		$product                             = new PricingProductFixture( 110, 'variable', array(), array( 111, 112 ) );

		$context = ( new Name_Your_Price_Adapter() )->detect( $product, array( 'price' => '' ) );

		$this->assertSame( '10', $context['min_price'] );
		$this->assertSame( '90', $context['max_price'] );
		$this->assertFalse( $context['uses_woocommerce_purchase_state'] );
	}

	public function test_name_your_price_keeps_native_checks_for_mixed_variations() {
		$customer_priced                      = new PricingProductFixture(
			121,
			'variation',
			array(
				'_nyp'       => 'yes',
				'_min_price' => '15',
			)
		);
		$fixed_price                          = new PricingProductFixture( 122, 'variation' );
		$GLOBALS['dxaic_test_products'][121] = $customer_priced;
		$GLOBALS['dxaic_test_products'][122] = $fixed_price;
		$product                              = new PricingProductFixture( 120, 'variable', array(), array( 121, 122 ) );

		$context = ( new Name_Your_Price_Adapter() )->detect( $product, array( 'price' => '' ) );

		$this->assertTrue( $context['uses_woocommerce_purchase_state'] );
	}

	public function test_yith_quote_requires_blank_price_and_visible_product_action() {
		$GLOBALS['dxaic_test_options']['ywraq_show_btn_single_page'] = 'yes';
		$visible = new PricingProductFixture( 201 );
		$hidden  = new PricingProductFixture( 202, 'simple', array( '_ywraq_hide_quote_button' => '1' ) );
		$adapter = new YITH_Request_A_Quote_Adapter();

		$context = $adapter->detect( $visible, array( 'price' => '' ) );

		$this->assertSame( 'quote', $context['mode'] );
		$this->assertTrue( $context['is_available'] );
		$this->assertNull( $adapter->detect( $hidden, array( 'price' => '' ) ) );
		$this->assertNull( $adapter->detect( $visible, array( 'price' => '25' ) ) );
	}

	public function test_measurement_calculator_keeps_native_price_requirements() {
		$product                                  = new PricingProductFixture( 301 );
		WC_Price_Calculator_Product::$enabled_ids = array( 301 );
		$adapter                                  = new Measurement_Price_Calculator_Adapter();

		$valid   = $adapter->detect( $product, array( 'price' => '12.50' ) );
		$invalid = $adapter->detect( $product, array( 'price' => '' ) );

		$this->assertTrue( $valid['is_available'] );
		$this->assertTrue( $valid['uses_woocommerce_purchase_state'] );
		$this->assertFalse( $invalid['is_available'] );
	}

	public function test_product_addons_only_matches_price_bearing_fields() {
		$product                               = new PricingProductFixture( 401 );
		$adapter                               = new Product_Addons_Adapter();
		WC_Product_Addons_Helper::$addons[401] = array(
			array(
				'type'  => 'custom_text',
				'price' => '',
			),
		);

		$this->assertNull(
			$adapter->detect(
				$product,
				array(
					'price'          => '30',
					'is_purchasable' => true,
				)
			)
		);

		WC_Product_Addons_Helper::$addons[401] = array(
			array(
				'type'    => 'checkbox',
				'options' => array( array( 'price' => '7' ) ),
			),
		);
		$context                               = $adapter->detect(
			$product,
			array(
				'price'          => '30',
				'is_purchasable' => true,
			)
		);

		$this->assertSame( 'dynamic', $context['mode'] );
		$this->assertTrue( $context['is_available'] );
		$this->assertTrue( $context['uses_woocommerce_purchase_state'] );
	}

	public function test_blank_composite_price_is_valid_only_with_verified_purchasability() {
		$product = new WC_Product_Composite( 501, 'composite' );
		$adapter = new Container_Pricing_Adapter();

		$available = $adapter->detect(
			$product,
			array(
				'price'          => '',
				'is_purchasable' => true,
			)
		);
		$missing   = $adapter->detect(
			$product,
			array(
				'price'          => '',
				'is_purchasable' => false,
			)
		);

		$this->assertTrue( $available['is_available'] );
		$this->assertFalse( $available['uses_woocommerce_purchase_state'] );
		$this->assertFalse( $missing['is_available'] );
	}
}
