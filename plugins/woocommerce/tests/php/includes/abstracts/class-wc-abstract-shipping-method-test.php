<?php
declare( strict_types = 1 );

use Automattic\WooCommerce\Enums\ProductTaxStatus;

// phpcs:disable Squiz.Classes.ClassFileName.NoMatch, Squiz.Classes.ValidClassName.NotCamelCaps -- Backward compatibility.
/**
 * Tests for the WC_Shipping_Method class.
 */
class WC_Abstract_Shipping_Method_Test extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var WC_Shipping_Method
	 */
	private $sut;

	/**
	 * Tax rate ID created for the test.
	 *
	 * @var int
	 */
	private $tax_rate_id;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();

		update_option( 'woocommerce_calc_taxes', 'yes' );
		$this->tax_rate_id = WC_Tax::_insert_tax_rate(
			array(
				'tax_rate_country'  => '',
				'tax_rate_state'    => '',
				'tax_rate'          => '20.0000',
				'tax_rate_name'     => 'VAT',
				'tax_rate_priority' => '1',
				'tax_rate_compound' => '0',
				'tax_rate_shipping' => '1',
				'tax_rate_order'    => '1',
				'tax_rate_class'    => '',
			)
		);

		$this->sut = new class() extends WC_Shipping_Method {
			/**
			 * Constructor.
			 */
			public function __construct() {
				$this->id = 'test_method';
			}
		};
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		WC_Tax::_delete_tax_rate( $this->tax_rate_id );
		update_option( 'woocommerce_calc_taxes', 'no' );
		parent::tearDown();
	}

	/**
	 * Add a rate with the given args and return it.
	 *
	 * @param array $args Rate args.
	 * @return WC_Shipping_Rate
	 */
	private function add_rate( array $args ): WC_Shipping_Rate {
		$this->sut->add_rate( array_merge( array( 'label' => 'Test' ), $args ) );

		return current( $this->sut->rates );
	}

	/**
	 * @testdox Should store a zero tax for each shipping tax rate when a taxable rate costs nothing.
	 */
	public function test_zero_cost_taxable_rate_gets_zero_taxes(): void {
		$rate = $this->add_rate( array( 'cost' => 0 ) );

		$this->assertSame( array( $this->tax_rate_id ), array_keys( $rate->get_taxes() ), 'Zero cost rate should have an entry for the shipping tax rate' );
		$this->assertEquals( 0, $rate->get_shipping_tax(), 'Zero cost rate should have no tax amount' );
	}

	/**
	 * @testdox Should still calculate tax for a taxable rate with a cost.
	 */
	public function test_paid_taxable_rate_gets_taxes(): void {
		$rate = $this->add_rate( array( 'cost' => 10 ) );

		$this->assertEquals( array( $this->tax_rate_id => 2 ), $rate->get_taxes(), 'Rate should be taxed at 20%' );
	}

	/**
	 * @testdox Should not add taxes to a zero cost rate when the method is not taxable.
	 */
	public function test_zero_cost_non_taxable_rate_gets_no_taxes(): void {
		$this->sut->tax_status = ProductTaxStatus::NONE;

		$rate = $this->add_rate( array( 'cost' => 0 ) );

		$this->assertSame( array(), $rate->get_taxes(), 'Non taxable rate should have no taxes' );
	}

	/**
	 * @testdox Should not add taxes when tax calculation is turned off for the rate.
	 */
	public function test_rate_with_taxes_disabled_gets_no_taxes(): void {
		$rate = $this->add_rate(
			array(
				'cost'  => 0,
				'taxes' => false,
			)
		);

		$this->assertSame( array(), $rate->get_taxes(), 'Rate with taxes set to false should have no taxes' );
	}

	/**
	 * @testdox Should treat an empty cost as free and store a zero tax for each shipping tax rate.
	 */
	public function test_empty_cost_taxable_rate_gets_zero_taxes(): void {
		$rate = $this->add_rate( array( 'cost' => '' ) );

		$this->assertSame( array( $this->tax_rate_id ), array_keys( $rate->get_taxes() ), 'Empty cost rate should have an entry for the shipping tax rate' );
		$this->assertEquals( 0, $rate->get_shipping_tax(), 'Empty cost rate should have no tax amount' );
		$this->assertSame( '0', $rate->get_cost(), 'Empty cost should be stored as 0' );
	}

	/**
	 * @testdox Should store a zero tax for each shipping tax rate on free shipping rates.
	 */
	public function test_free_shipping_rate_gets_zero_taxes(): void {
		$free_shipping        = new WC_Shipping_Free_Shipping();
		$free_shipping->title = 'Free shipping';

		$free_shipping->calculate_shipping( array() );
		$rate = current( $free_shipping->rates );

		$this->assertSame( array( $this->tax_rate_id ), array_keys( $rate->get_taxes() ), 'Free shipping rate should have an entry for the shipping tax rate' );
		$this->assertEquals( 0, $rate->get_shipping_tax(), 'Free shipping rate should have no tax amount' );
	}

	/**
	 * @testdox Should not add taxes when the rate cost is negative.
	 */
	public function test_negative_cost_gets_no_taxes(): void {
		$rate = $this->add_rate( array( 'cost' => -5 ) );

		$this->assertSame( array(), $rate->get_taxes(), 'Rate with a negative cost should have no taxes' );
	}
}
