<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Customers;

use Automattic\WooCommerce\Internal\Customers\ShippingDestination;
use WC_Customer;
use WC_Unit_Test_Case;

/**
 * Tests for the ShippingDestination class.
 */
class ShippingDestinationTest extends WC_Unit_Test_Case {
	/**
	 * A billing address in San Francisco.
	 */
	private const BILLING = array(
		'address_1' => '1 Billing Street',
		'address_2' => '',
		'city'      => 'San Francisco',
		'state'     => 'CA',
		'postcode'  => '94103',
		'country'   => 'US',
	);

	/**
	 * The System Under Test.
	 *
	 * @var ShippingDestination
	 */
	private $sut;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut = new ShippingDestination();
		update_option( 'woocommerce_ship_to_destination', 'billing' );
	}

	/**
	 * @testdox is_same_location ignores names, surrounding spaces and postcode formatting, but not the street.
	 */
	public function test_is_same_location_compares_only_the_location(): void {
		$same_place = array_merge(
			self::BILLING,
			array(
				'first_name' => 'Someone else',
				'city'       => ' San Francisco ',
				'postcode'   => '94 103',
			)
		);

		$this->assertTrue( $this->sut->is_same_location( self::BILLING, $same_place ) );
		$this->assertFalse( $this->sut->is_same_location( self::BILLING, array_merge( self::BILLING, array( 'address_1' => '2 Other Street' ) ) ) );
	}

	/**
	 * @testdox is_same_address also compares the recipient, but only the recipient fields both addresses have.
	 */
	public function test_is_same_address_compares_the_recipient_fields_both_addresses_have(): void {
		$billing = array_merge(
			self::BILLING,
			array(
				'first_name' => 'Pat',
				'phone'      => '5555550100',
			)
		);

		$this->assertFalse( $this->sut->is_same_address( $billing, array_merge( $billing, array( 'first_name' => 'Gift' ) ) ), 'Another recipient is another address.' );
		$this->assertFalse( $this->sut->is_same_address( $billing, array_merge( $billing, array( 'phone' => '5555550111' ) ) ), 'Another phone is another address.' );
		$this->assertTrue( $this->sut->is_same_address( $billing, array_diff_key( $billing, array( 'phone' => true ) ) ), 'A field one checkout does not post should not count.' );
	}

	/**
	 * @testdox keeps_saved_shipping_address is $expected when the saved shipping street is "$saved_street".
	 *
	 * @testWith [ "2 Shipping Avenue", true ]
	 *           [ "1 Billing Street", false ]
	 *           [ "", false ]
	 *
	 * @param string $saved_street Shipping street saved to the account; empty means no saved shipping address.
	 * @param bool   $expected     Whether the saved shipping address should be kept.
	 */
	public function test_keeps_saved_shipping_address_only_when_a_different_one_is_saved( string $saved_street, bool $expected ): void {
		$customer = new WC_Customer();
		if ( '' !== $saved_street ) {
			$customer->set_shipping_address_1( $saved_street );
			$customer->set_shipping_city( 'San Francisco' );
			$customer->set_shipping_postcode( '94103' );
			$customer->set_shipping_state( 'CA' );
			$customer->set_shipping_country( 'US' );
		}

		$this->assertSame( $expected, $this->sut->keeps_saved_shipping_address( self::BILLING, self::BILLING, $customer ) );
	}

	/**
	 * @testdox keeps_saved_shipping_address is false when the order ships elsewhere, or the setting uses the shipping address.
	 */
	public function test_keeps_saved_shipping_address_is_false_when_not_shipping_to_billing(): void {
		$customer = new WC_Customer();
		$customer->set_shipping_address_1( '2 Shipping Avenue' );

		$this->assertFalse(
			$this->sut->keeps_saved_shipping_address( self::BILLING, array_merge( self::BILLING, array( 'address_1' => '3 Seattle Road' ) ), $customer ),
			'An order shipped to another address should be saved as the shipping address.'
		);

		update_option( 'woocommerce_ship_to_destination', 'shipping' );

		$this->assertFalse( $this->sut->keeps_saved_shipping_address( self::BILLING, self::BILLING, $customer ), 'The "shipping" setting should keep the previous behaviour.' );
	}
}
