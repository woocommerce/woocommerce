<?php
namespace Wocommerce\Test;
/*
*
*/

class ShippingTest extends \WP_UnitTestCase {
	
	public function testValidShippingMethod() {
		
		$wc_shipping = \WC_Shipping::instance();
		
		$must_valid = $wc_shipping->is_valid_shipping_methods('WC_Shipping_Flat_Rate');
		$this->assertTrue( $must_valid, '> is_valid_shipping_methods return true if class extends WC_Shipping_Method' ); 
	}
	
	public function testInvalidShippingMethod() {
		$wc_shipping = \WC_Shipping::instance();
		
		$invalidShipping = $wc_shipping->is_valid_shipping_methods('WC_Gateway_Paypal');
		$otherinvalidShipping = $wc_shipping->is_valid_shipping_methods('WC_Shipping_Method');
		
		$this->assertFalse( $otherinvalidShipping, '> is_valid_shipping_methods return false if passed WC_Shipping_Method itself' ); 
		$this->assertFalse( $invalidShipping, '> is_valid_shipping_methods return false if class not extends WC_Shipping_Method' ); 
	}
	
	public function testValidInstance() {
		$wc_shipping 	= \WC_Shipping::instance();
		$flatRate		= new \WC_Shipping_Flat_Rate();
		
		$must_valid = $wc_shipping->is_valid_shipping_methods($flatRate);
		$this->assertTrue( $must_valid, '> is_valid_shipping_methods return true wheater is class name or instance passed' ); 
	}
	
	public function testmultipleParentValid() {
		
		$wc_shipping = \WC_Shipping::instance();
		//WC_Shipping_International_Delivery is extends WC_Shipping_Flat_Rate
		// and WC_Shipping_Flat_Rate extends WC_Shipping_Method, it must valid
		$must_valid = $wc_shipping->is_valid_shipping_methods('WC_Shipping_International_Delivery');
				
		$this->assertTrue( $must_valid, '> is_valid_shipping_methods return true although passed multiple parent' ); 
	}
	
} 