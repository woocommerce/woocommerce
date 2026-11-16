<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Utilities;

use Automattic\WooCommerce\Utilities\ShippingUtil;
use WC_Unit_Test_Case;

/**
 * Tests for the ShippingUtil class.
 */
class ShippingUtilTest extends WC_Unit_Test_Case {

	/**
	 * Most runs of the package filter before a test stops the code it runs from the filter.
	 */
	private const PACKAGE_FILTER_RUN_CAP = 5;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->reset_cart_package_keys();
		WC()->shipping()->unregister_shipping_methods();
		foreach ( array( 'flat_rate', 'free_shipping', 'international_delivery', 'local_delivery', 'local_pickup' ) as $legacy_method ) {
			delete_option( 'woocommerce_' . $legacy_method . '_settings' );
		}
		\WC_Cache_Helper::get_transient_version( 'shipping', true );
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		try {
			$this->reset_cart_package_keys();
		} finally {
			parent::tearDown();
		}
	}

	/**
	 * Forget the package keys kept from the last build, so each test starts without them.
	 */
	private function reset_cart_package_keys(): void {
		$property = new \ReflectionProperty( ShippingUtil::class, 'cart_package_keys' );
		$property->setAccessible( true );
		$property->setValue( null, null );
	}

	/**
	 * Count the runs of the `woocommerce_cart_shipping_packages` filter after all other callbacks.
	 *
	 * @param int $calls Variable that receives the number of runs.
	 */
	private function count_package_builds( int &$calls ): void {
		add_filter(
			'woocommerce_cart_shipping_packages',
			function ( $packages ) use ( &$calls ) {
				++$calls;
				return $packages;
			},
			PHP_INT_MAX
		);
	}

	/**
	 * Run a callable from the `woocommerce_cart_shipping_packages` filter and count the runs.
	 *
	 * The callable does not run after the cap, so a build that repeats itself ends in a failed
	 * assertion instead of an exhausted stack.
	 *
	 * @param callable $inner Code to run from the filter.
	 * @param int      $calls Variable that receives the number of filter runs.
	 */
	private function run_in_package_filter( callable $inner, int &$calls ): void {
		add_filter(
			'woocommerce_cart_shipping_packages',
			function ( $packages ) use ( $inner, &$calls ) {
				++$calls;
				if ( $calls < self::PACKAGE_FILTER_RUN_CAP ) {
					$inner();
				}
				return $packages;
			}
		);
	}

	/**
	 * @testdox is_shipping_rate_listed only accepts a rate listed for the same package key.
	 *
	 * @testWith [ 0, "flat_rate:1", true ]
	 *           [ 0, "local_pickup:2", false ]
	 *           [ 1, "flat_rate:1", false ]
	 *           [ 0, "123", true ]
	 *           [ 0, 123, false ]
	 *
	 * @param int|string $package_key Package key to check.
	 * @param mixed      $rate_id     Rate id to check.
	 * @param bool       $expected    Expected result.
	 */
	public function test_is_shipping_rate_listed( $package_key, $rate_id, bool $expected ): void {
		WC()->session->set( 'previous_shipping_methods', array( 0 => array( 'flat_rate:1', 123 ) ) );

		$this->assertSame( $expected, ShippingUtil::is_shipping_rate_listed( $package_key, $rate_id ) );
	}

	/**
	 * @testdox is_shipping_rate_listed returns false when no rates were listed.
	 */
	public function test_is_shipping_rate_listed_without_listed_rates(): void {
		WC()->session->set( 'previous_shipping_methods', null );

		$this->assertFalse( ShippingUtil::is_shipping_rate_listed( 0, 'flat_rate:1' ) );
	}

	/**
	 * @testdox filter_chosen_shipping_methods drops choices whose rate was not listed for their key.
	 */
	public function test_filter_chosen_shipping_methods_drops_unlisted_rates(): void {
		WC()->session->set( 'previous_shipping_methods', array( 0 => array( 'flat_rate:1' ) ) );

		$this->assertSame(
			array( 0 => 'flat_rate:1' ),
			ShippingUtil::filter_chosen_shipping_methods(
				array(
					0       => 'flat_rate:1',
					'extra' => 'flat_rate:1',
				)
			),
			'A key with no listed rates must be dropped.'
		);
		$this->assertSame(
			array(),
			ShippingUtil::filter_chosen_shipping_methods( array( 0 => 'local_pickup:2' ) ),
			'A rate that was not listed for the key must be dropped.'
		);
	}

	/**
	 * @testdox filter_chosen_shipping_methods keeps a listed pickup rate only for a shipping package of the cart.
	 */
	public function test_filter_chosen_shipping_methods_checks_package_key_for_pickup(): void {
		WC()->session->set(
			'previous_shipping_methods',
			array(
				0       => array( 'local_pickup:2' ),
				'other' => array( 'local_pickup:2', 'flat_rate:1' ),
			)
		);

		$this->assertSame(
			array( 0 => 'local_pickup:2' ),
			ShippingUtil::filter_chosen_shipping_methods(
				array(
					0       => 'local_pickup:2',
					'other' => 'local_pickup:2',
				)
			),
			'A pickup rate for a key that is not a cart package must be dropped.'
		);
		$this->assertSame(
			array( 'other' => 'flat_rate:1' ),
			ShippingUtil::filter_chosen_shipping_methods( array( 'other' => 'flat_rate:1' ) ),
			'A listed rate that is not pickup does not need a cart package.'
		);
	}

	/**
	 * @testdox filter_chosen_shipping_methods builds the cart packages one time for the same cart state.
	 */
	public function test_filter_chosen_shipping_methods_builds_packages_once_for_same_cart_state(): void {
		WC()->session->set( 'previous_shipping_methods', array( 0 => array( 'local_pickup:2' ) ) );
		$chosen = array(
			0       => 'local_pickup:2',
			'other' => 'local_pickup:2',
		);
		$calls  = 0;
		$this->count_package_builds( $calls );

		for ( $i = 0; $i < 3; $i++ ) {
			$this->assertSame( array( 0 => 'local_pickup:2' ), ShippingUtil::filter_chosen_shipping_methods( $chosen ) );
		}

		$this->assertSame( 1, $calls, 'The packages must be built one time for three calls with the same cart state.' );
	}

	/**
	 * @testdox filter_chosen_shipping_methods builds the cart packages one time when a package filter reads the taxable address.
	 */
	public function test_filter_chosen_shipping_methods_builds_packages_once_when_package_filter_reads_taxable_address(): void {
		update_option( 'woocommerce_default_country', 'US:CA' );
		update_option( 'woocommerce_tax_based_on', 'shipping' );
		WC()->customer->set_shipping_country( 'GB' );
		WC()->session->set( 'previous_shipping_methods', array( 0 => array( 'local_pickup:2' ) ) );
		WC()->session->set( 'chosen_shipping_methods', array( 0 => 'local_pickup:2' ) );
		$calls   = 0;
		$address = null;
		$this->run_in_package_filter(
			function () use ( &$address ) {
				$address = WC()->customer->get_taxable_address();
			},
			$calls
		);

		$result = ShippingUtil::filter_chosen_shipping_methods( array( 0 => 'local_pickup:2' ) );

		$this->assertSame( 1, $calls, 'The packages must be built one time when a package filter reads the taxable address.' );
		$this->assertSame( array( 0 => 'local_pickup:2' ), $result, 'The listed pickup rate must be kept.' );
		$this->assertSame( 'US', $address[0], 'While the packages are built, the chosen pickup must still give the store address.' );
	}

	/**
	 * @testdox filter_chosen_shipping_methods builds the cart packages one time when a package filter reads a taxed price.
	 */
	public function test_filter_chosen_shipping_methods_builds_packages_once_when_package_filter_reads_taxed_price(): void {
		update_option( 'woocommerce_calc_taxes', 'yes' );
		update_option( 'woocommerce_default_country', 'US:CA' );
		update_option( 'woocommerce_tax_based_on', 'shipping' );
		WC()->customer->set_shipping_country( 'GB' );
		\WC_Tax::_insert_tax_rate(
			array(
				'tax_rate_country' => 'US',
				'tax_rate'         => '10.0000',
				'tax_rate_name'    => 'US',
			)
		);
		\WC_Tax::_insert_tax_rate(
			array(
				'tax_rate_country' => 'GB',
				'tax_rate'         => '20.0000',
				'tax_rate_name'    => 'GB',
			)
		);
		$product = \WC_Helper_Product::create_simple_product( true, array( 'regular_price' => 10 ) );
		WC()->cart->add_to_cart( $product->get_id() );
		WC()->session->set( 'previous_shipping_methods', array( 0 => array( 'local_pickup:2' ) ) );
		WC()->session->set( 'chosen_shipping_methods', array( 0 => 'local_pickup:2' ) );
		$calls = 0;
		$price = null;
		$this->run_in_package_filter(
			function () use ( $product, &$price ) {
				$price = wc_get_price_including_tax( $product );
			},
			$calls
		);

		$result = ShippingUtil::filter_chosen_shipping_methods( array( 0 => 'local_pickup:2' ) );

		$this->assertSame( 1, $calls, 'The packages must be built one time when a package filter reads a taxed price.' );
		$this->assertSame( array( 0 => 'local_pickup:2' ), $result, 'The listed pickup rate must be kept.' );
		$this->assertSame( 11.0, $price, 'While the packages are built, the chosen pickup must still give the store tax rate.' );
	}

	/**
	 * @testdox filter_chosen_shipping_methods checks the package key again once the cart packages are built.
	 */
	public function test_filter_chosen_shipping_methods_checks_package_key_once_packages_are_built(): void {
		WC()->session->set(
			'previous_shipping_methods',
			array(
				0       => array( 'local_pickup:2' ),
				'other' => array( 'local_pickup:2' ),
			)
		);
		$chosen = array(
			0       => 'local_pickup:2',
			'other' => 'local_pickup:2',
		);
		$calls  = 0;
		$nested = null;
		$this->run_in_package_filter(
			function () use ( $chosen, &$nested ) {
				$nested = ShippingUtil::filter_chosen_shipping_methods( $chosen );
			},
			$calls
		);

		$result = ShippingUtil::filter_chosen_shipping_methods( $chosen );

		$this->assertSame( 1, $calls );
		$this->assertSame( $chosen, $nested, 'While the packages are built, every listed pickup must be kept.' );
		$this->assertSame( array( 0 => 'local_pickup:2' ), $result, 'The outer call must drop the pickup for a key that is not a cart package.' );
		$this->assertSame( array( 0 => 'local_pickup:2' ), ShippingUtil::filter_chosen_shipping_methods( $chosen ), 'A later call must drop the pickup for a key that is not a cart package.' );
	}

	/**
	 * @testdox filter_chosen_shipping_methods forgets the cart state when the package build throws.
	 */
	public function test_filter_chosen_shipping_methods_forgets_cart_state_when_package_build_throws(): void {
		WC()->session->set(
			'previous_shipping_methods',
			array(
				0       => array( 'local_pickup:2' ),
				'other' => array( 'local_pickup:2' ),
			)
		);
		$chosen = array(
			0       => 'local_pickup:2',
			'other' => 'local_pickup:2',
		);
		$fail   = true;
		add_filter(
			'woocommerce_cart_shipping_packages',
			function ( $packages ) use ( &$fail ) {
				if ( $fail ) {
					throw new \RuntimeException( 'Package filter failed.' );
				}
				return $packages;
			}
		);

		$caught = null;
		try {
			ShippingUtil::filter_chosen_shipping_methods( $chosen );
		} catch ( \RuntimeException $e ) {
			$caught = $e;
		}

		$this->assertInstanceOf( \RuntimeException::class, $caught, 'The exception from the package filter must reach the caller.' );
		$property = new \ReflectionProperty( ShippingUtil::class, 'cart_package_keys' );
		$property->setAccessible( true );
		$this->assertNull( $property->getValue(), 'A failed build must not leave an entry for the cart state.' );

		$fail = false;
		$this->assertSame( array( 0 => 'local_pickup:2' ), ShippingUtil::filter_chosen_shipping_methods( $chosen ), 'The next call must build the packages and check the package key.' );
	}

	/**
	 * @testdox filter_chosen_shipping_methods builds the cart packages again when the cart items change.
	 */
	public function test_filter_chosen_shipping_methods_rebuilds_packages_when_cart_items_change(): void {
		WC()->session->set( 'previous_shipping_methods', array( 0 => array( 'local_pickup:2' ) ) );
		$chosen = array( 0 => 'local_pickup:2' );
		$calls  = 0;
		$this->count_package_builds( $calls );

		ShippingUtil::filter_chosen_shipping_methods( $chosen );
		$this->assertSame( 1, $calls );

		$product  = \WC_Helper_Product::create_simple_product();
		$item_key = WC()->cart->add_to_cart( $product->get_id() );
		$calls    = 0;
		$this->assertSame( $chosen, ShippingUtil::filter_chosen_shipping_methods( $chosen ) );
		$this->assertSame( 1, $calls, 'The packages must be built again after an item is added.' );
		ShippingUtil::filter_chosen_shipping_methods( $chosen );
		$this->assertSame( 1, $calls, 'The packages must not be built again for the same items.' );

		WC()->cart->set_quantity( $item_key, 2, false );
		$calls = 0;
		$this->assertSame( $chosen, ShippingUtil::filter_chosen_shipping_methods( $chosen ) );
		$this->assertSame( 1, $calls, 'The packages must be built again after a quantity changes.' );
	}

	/**
	 * @testdox filter_chosen_shipping_methods builds the cart packages again when the shipping address changes.
	 */
	public function test_filter_chosen_shipping_methods_rebuilds_packages_when_shipping_address_changes(): void {
		// Other test classes leave their postcode on the shared customer, so start from a known one.
		WC()->customer->set_shipping_postcode( '90001' );
		WC()->session->set( 'previous_shipping_methods', array( 0 => array( 'local_pickup:2' ) ) );
		$chosen = array( 0 => 'local_pickup:2' );
		$calls  = 0;
		$this->count_package_builds( $calls );

		ShippingUtil::filter_chosen_shipping_methods( $chosen );
		$this->assertSame( 1, $calls );

		WC()->customer->set_shipping_postcode( '90210' );
		$calls = 0;
		$this->assertSame( $chosen, ShippingUtil::filter_chosen_shipping_methods( $chosen ) );
		$this->assertSame( 1, $calls, 'The packages must be built again after the shipping address changes.' );
		ShippingUtil::filter_chosen_shipping_methods( $chosen );
		$this->assertSame( 1, $calls, 'The packages must not be built again for the same address.' );
	}

	/**
	 * @testdox filter_chosen_shipping_methods builds the cart packages again for another cart object.
	 */
	public function test_filter_chosen_shipping_methods_rebuilds_packages_for_another_cart_object(): void {
		WC()->session->set( 'previous_shipping_methods', array( 0 => array( 'local_pickup:2' ) ) );
		$chosen = array( 0 => 'local_pickup:2' );
		$calls  = 0;
		$this->count_package_builds( $calls );

		ShippingUtil::filter_chosen_shipping_methods( $chosen );
		$this->assertSame( 1, $calls );

		$original_cart = WC()->cart;
		try {
			WC()->cart = clone $original_cart;
			$calls     = 0;
			$this->assertSame( $chosen, ShippingUtil::filter_chosen_shipping_methods( $chosen ) );
			$this->assertSame( 1, $calls, 'The packages must be built again for another cart object.' );
		} finally {
			WC()->cart = $original_cart;
		}
	}

	/**
	 * @testdox filter_chosen_shipping_methods builds the cart packages again for each totals calculation.
	 */
	public function test_filter_chosen_shipping_methods_rebuilds_packages_for_each_totals_calculation(): void {
		$recurring_key = '2026_11_30_monthly_0';
		$use_recurring = false;
		add_filter(
			'woocommerce_cart_shipping_packages',
			function ( $packages ) use ( &$use_recurring, $recurring_key ) {
				return $use_recurring ? array( $recurring_key => $packages[0] ) : $packages;
			}
		);
		$listed = array(
			0              => array( 'local_pickup:2' ),
			$recurring_key => array( 'local_pickup:2' ),
		);
		$chosen = array(
			0              => 'local_pickup:2',
			$recurring_key => 'local_pickup:2',
		);
		WC()->session->set( 'previous_shipping_methods', $listed );

		$this->assertSame(
			array( 0 => 'local_pickup:2' ),
			ShippingUtil::filter_chosen_shipping_methods( $chosen ),
			'Before the recurring calculation, only the main package key must be kept.'
		);

		$use_recurring = true;
		do_action( 'woocommerce_before_calculate_totals', WC()->cart );
		$this->assertSame(
			array( $recurring_key => 'local_pickup:2' ),
			ShippingUtil::filter_chosen_shipping_methods( $chosen ),
			'During the recurring calculation, only the recurring package key must be kept.'
		);

		$use_recurring = false;
		do_action( 'woocommerce_after_calculate_totals', WC()->cart );
		// WC_Cart_Session::set_session() runs on this action and clears the listed rates for a cart without shippable items.
		WC()->session->set( 'previous_shipping_methods', $listed );
		$this->assertSame(
			array( 0 => 'local_pickup:2' ),
			ShippingUtil::filter_chosen_shipping_methods( $chosen ),
			'After the recurring calculation, only the main package key must be kept.'
		);
	}

	/**
	 * @testdox filter_chosen_shipping_methods returns an empty array for a value that is not an array.
	 */
	public function test_filter_chosen_shipping_methods_with_invalid_input(): void {
		$this->assertSame( array(), ShippingUtil::filter_chosen_shipping_methods( 'local_pickup:2' ) );
	}
}
