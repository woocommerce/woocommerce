<?php
/**
 * Unit tests for wc-product-functions.php.
 *
 * @package WooCommerce\Tests\Functions\Stock
 */

declare( strict_types = 1 );

use Automattic\WooCommerce\Testing\Tools\CodeHacking\Hacks\FunctionsMockerHack;
use Automattic\WooCommerce\Testing\Tools\CodeHacking\Hacks\StaticMockerHack;

// phpcs:disable Squiz.Classes.ClassFileName.NoMatch, Squiz.Classes.ValidClassName.NotCamelCaps -- Backward compatibility.
/**
 * Class WC_Stock_Functions_Tests.
 */
class WC_Product_Functions_Tests extends \WC_Unit_Test_Case {
	/**
	 * The original product reviews setting.
	 *
	 * @var mixed
	 */
	private $original_reviews_setting;

	/**
	 * Whether the current test changed the product reviews setting.
	 *
	 * @var bool
	 */
	private $reviews_setting_changed = false;

	/**
	 * Restore settings modified by tests.
	 */
	public function tearDown(): void {
		if ( $this->reviews_setting_changed ) {
			delete_option( 'woocommerce_product_lookup_table_is_generating' );
			as_unschedule_all_actions( '', array(), 'wc_update_product_lookup_tables' );

			if ( null === $this->original_reviews_setting ) {
				delete_option( 'woocommerce_enable_reviews' );
			} else {
				update_option( 'woocommerce_enable_reviews', $this->original_reviews_setting );
			}
		}

		parent::tearDown();
	}

	/**
	 * @testdox If 'wc_get_price_excluding_tax' gets an order as argument, it passes the order customer to 'WC_Tax::get_rates'.
	 *
	 * @testWith [true, 1, true]
	 *           [true, 1, false]
	 *           [true, 0, true]
	 *           [true, 0, false]
	 *           [false, null, true]
	 *           [false, null, false]
	 *
	 * @param bool     $pass_order Whether an order is passed to 'wc_get_price_excluding_tax' or not.
	 * @param int|null $customer_id Id of the customer associated to the order.
	 * @param bool     $set_filter Whether the 'woocommerce_adjust_non_base_location_prices' filter should be set to return false.
	 */
	public function test_wc_get_price_excluding_tax_passes_order_customer_to_get_rates_if_order_is_available( $pass_order, $customer_id, $set_filter ) {
		$customer_passed_to_get_rates                  = false;
		$get_base_rates_invoked                        = false;
		$customer_id_passed_to_wc_customer_constructor = false;

		if ( $set_filter ) {
			add_filter( 'woocommerce_adjust_non_base_location_prices', '__return_false' );
		}

		FunctionsMockerHack::add_function_mocks(
			array(
				'wc_prices_include_tax' => '__return_true',
			)
		);

		StaticMockerHack::add_method_mocks(
			array(
				'WC_Tax' =>
				array(
					'get_rates'          => function ( $tax_class, $customer ) use ( &$customer_passed_to_get_rates ) {
						$customer_passed_to_get_rates = $customer;
					},
					'get_base_tax_rates' => function () use ( &$get_base_rates_invoked ) {
						$get_base_rates_invoked = true;
						return 0;
					},
					'calc_tax'           => function () {
						return array( 0 );
					},
				),
			)
		);

		// phpcs:disable Squiz.Commenting

		$product = new class() extends WC_Product {
			public function get_price( $context = 'view' ) {
				return 0;
			}

			public function is_taxable() {
				return true;
			}

			public function get_tax_class( $context = 'view' ) {
				return '';
			}
		};

		$customer = new stdClass();
		$this->register_legacy_proxy_class_mocks(
			array(
				'WC_Customer' => function ( $customer_id ) use ( &$customer_id_passed_to_wc_customer_constructor, $customer ) {
					$customer_id_passed_to_wc_customer_constructor = $customer_id;
					return $customer;
				},
			)
		);

		if ( $pass_order ) {
			$order = new class( $customer_id ) {
				private $customer_id;

				public function __construct( $customer_id ) {
					$this->customer_id = $customer_id;
				}

				public function get_customer_id() {
					return $this->customer_id;
				}
			};

			wc_get_price_excluding_tax( $product, array( 'order' => $order ) );

			if ( $customer_id && $set_filter ) {
				$this->assertEquals( $order->get_customer_id(), $customer_id_passed_to_wc_customer_constructor );
				$this->assertFalse( $get_base_rates_invoked );
				$this->assertSame( $customer, $customer_passed_to_get_rates );
			} elseif ( ! $customer_id && $set_filter ) {
				$this->assertFalse( $customer_id_passed_to_wc_customer_constructor );
				$this->assertNull( $customer_passed_to_get_rates );
				$this->assertFalse( $get_base_rates_invoked );
			} else {
				$this->assertFalse( $customer_id_passed_to_wc_customer_constructor );
				$this->assertFalse( $customer_passed_to_get_rates );
				$this->assertTrue( $get_base_rates_invoked );
			}
		} else {
			wc_get_price_excluding_tax( $product );

			$this->assertFalse( $customer_id_passed_to_wc_customer_constructor );
			$this->assertEquals( $set_filter ? null : false, $customer_passed_to_get_rates );
			$this->assertEquals( ! $set_filter, $get_base_rates_invoked );
		}

		// phpcs:enable Squiz.Commenting

		if ( $set_filter ) {
			remove_filter( 'woocommerce_adjust_non_base_location_prices', '__return_false' );
		}
	}

	/**
	 * @testdox Test 'wc_get_price_including_tax'.
	 *
	 * @testWith [true, true]
	 *           [true, false]
	 *           [false, true]
	 *           [false, false]
	 *
	 * @param bool $prices_include_tax Whether entered prices are inclusive of tax.
	 * @param bool $is_vat_exempt      Whether the VAT is exempted for customer.
	 */
	public function test_wc_get_price_including_tax( $prices_include_tax, $is_vat_exempt ) {
		// Set VAT exempt and Mock prices_include_tax.
		WC()->customer->set_is_vat_exempt( $is_vat_exempt );
		FunctionsMockerHack::add_function_mocks(
			array(
				'wc_prices_include_tax' => $prices_include_tax ? '__return_true' : '__return_false',
			)
		);

		// Add dummy tax-rate.
		$tax_rate    = array(
			'tax_rate_country'  => '',
			'tax_rate_state'    => '',
			'tax_rate'          => '20.0000',
			'tax_rate_name'     => 'VAT',
			'tax_rate_priority' => '1',
			'tax_rate_compound' => '0',
			'tax_rate_shipping' => '1',
			'tax_rate_order'    => '1',
			'tax_rate_class'    => '',
		);
		$tax_rate_id = WC_Tax::_insert_tax_rate( $tax_rate );

		$wc_tax_enabled = wc_tax_enabled();
		if ( ! $wc_tax_enabled ) {
			update_option( 'woocommerce_calc_taxes', 'yes' );
		}

		$product         = WC_Helper_Product::create_simple_product();
		$expected_prices = array(
			'10'  => array( 8.33, 10, 10, 12 ),
			'50'  => array( 41.67, 50, 50, 60 ),
			'100' => array( 83.33, 100, 100, 120 ),
		);

		foreach ( $expected_prices as $price => $value ) {
			$product->set_price( $price );
			$product->save();
			if ( $prices_include_tax && $is_vat_exempt ) {
				$this->assertEquals( $value[0], wc_get_price_including_tax( $product ) );
			} elseif ( $prices_include_tax && ! $is_vat_exempt ) {
				$this->assertEquals( $value[1], wc_get_price_including_tax( $product ) );
			} elseif ( ! $prices_include_tax && $is_vat_exempt ) {
				$this->assertEquals( $value[2], wc_get_price_including_tax( $product ) );
			} elseif ( ! $prices_include_tax && ! $is_vat_exempt ) {
				$this->assertEquals( $value[3], wc_get_price_including_tax( $product ) );
			}
		}

		// Test clean up.
		WC()->customer->set_is_vat_exempt( false );
		WC_Tax::_delete_tax_rate( $tax_rate_id );
		WC_Helper_Product::delete_product( $product->get_id() );
		if ( ! $wc_tax_enabled ) {
			update_option( 'woocommerce_calc_taxes', 'no' );
		}
	}

	/**
	 * @testDox Sales price is applied when scheduled sale starts.
	 */
	public function test_wc_scheduled_sales_sale_start() {
		$product = WC_Helper_Product::create_simple_product();
		$product->set_price( 100 );
		$product->set_regular_price( 100 );
		$product->set_sale_price( 50 );
		$product->set_date_on_sale_from( gmdate( 'Y-m-d H:i:s', time() + 10 ) );
		$product->save();

		// Bypass product after save hook to prevent price change on save.
		update_post_meta( $product->get_id(), '_sale_price_dates_from', time() - 5 );

		// The stored _price stays stale until the cron runs (the display heal makes get_price() correct sooner).
		$this->assertEquals( 100, get_post_meta( $product->get_id(), '_price', true ) );

		wc_scheduled_sales();

		$this->assertEquals( 50, wc_get_product( $product->get_id() )->get_price() );
	}

	/**
	 * @testDox Sales price is removed when scheduled sale ends.
	 */
	public function test_wc_scheduled_sales_sale_end() {
		$product = WC_Helper_Product::create_simple_product();
		$product->set_price( 100 );
		$product->set_regular_price( 100 );
		$product->set_sale_price( 50 );
		$product->set_date_on_sale_to( gmdate( 'Y-m-d H:i:s', time() + 10 ) );
		$product->save();

		// Bypass product after save hook to prevent price change on save.
		update_post_meta( $product->get_id(), '_sale_price_dates_to', time() - 5 );

		// The stored _price stays stale until the cron runs (the display heal makes get_price() correct sooner).
		$this->assertEquals( 50, get_post_meta( $product->get_id(), '_price', true ) );

		wc_scheduled_sales();

		$this->assertEquals( 100, wc_get_product( $product->get_id() )->get_price() );
	}

	/**
	 * @testdox A completed scheduled sale is not returned by get_starting_sales().
	 */
	public function test_get_starting_sales_excludes_completed_sales(): void {
		$product = WC_Helper_Product::create_simple_product();
		$product->set_regular_price( 100 );
		$product->set_sale_price( 50 );
		$product->save();

		update_post_meta( $product->get_id(), '_price', 100 );
		update_post_meta( $product->get_id(), '_sale_price_dates_from', time() - 300 );
		update_post_meta( $product->get_id(), '_sale_price_dates_to', time() - 100 );

		$data_store = WC_Data_Store::load( 'product' );

		$this->assertNotContains(
			(string) $product->get_id(),
			$data_store->get_starting_sales(),
			'A sale that already ended must never be started by the daily safety net.'
		);
		$this->assertNotContains(
			(string) $product->get_id(),
			$data_store->get_ending_sales(),
			'A completed sale already at the regular price has nothing left to end.'
		);
	}

	/**
	 * @testdox get_starting_sales() still returns sales that have started and not yet ended.
	 */
	public function test_get_starting_sales_includes_open_and_future_ending_sales(): void {
		$data_store = WC_Data_Store::load( 'product' );

		$open_ended = WC_Helper_Product::create_simple_product();
		$open_ended->set_regular_price( 100 );
		$open_ended->set_sale_price( 50 );
		$open_ended->save();
		update_post_meta( $open_ended->get_id(), '_price', 100 );
		update_post_meta( $open_ended->get_id(), '_sale_price_dates_from', time() - 100 );
		delete_post_meta( $open_ended->get_id(), '_sale_price_dates_to' );

		$still_running = WC_Helper_Product::create_simple_product();
		$still_running->set_regular_price( 100 );
		$still_running->set_sale_price( 50 );
		$still_running->save();
		update_post_meta( $still_running->get_id(), '_price', 100 );
		update_post_meta( $still_running->get_id(), '_sale_price_dates_from', time() - 100 );
		update_post_meta( $still_running->get_id(), '_sale_price_dates_to', time() + 3600 );

		// A direct writer leaves the row present but empty, which the `> 0` term tolerates.
		$empty_end_date = WC_Helper_Product::create_simple_product();
		$empty_end_date->set_regular_price( 100 );
		$empty_end_date->set_sale_price( 50 );
		$empty_end_date->save();
		update_post_meta( $empty_end_date->get_id(), '_price', 100 );
		update_post_meta( $empty_end_date->get_id(), '_sale_price_dates_from', time() - 100 );
		update_post_meta( $empty_end_date->get_id(), '_sale_price_dates_to', '' );

		$starting = $data_store->get_starting_sales();

		$this->assertContains( (string) $open_ended->get_id(), $starting, 'An open-ended sale must still start.' );
		$this->assertContains( (string) $still_running->get_id(), $starting, 'A sale whose end is in the future must still start.' );
		$this->assertContains( (string) $empty_end_date->get_id(), $starting, 'An empty end-date row means no end date, so the sale must still start.' );
	}

	/**
	 * @testdox The new exclusion reads date meta the same way get_ending_sales() does.
	 */
	public function test_get_starting_sales_matches_ending_sales_on_non_numeric_dates(): void {
		// A date string from an importer. Year 9999 sorts above any timestamp this code sees,
		// so both queries must read it as not-yet-ended.
		$far_future = '9999-12-31';

		// At the regular price, so only the date can exclude it from starting.
		$not_started = WC_Helper_Product::create_simple_product();
		$not_started->set_regular_price( 100 );
		$not_started->set_sale_price( 50 );
		$not_started->save();
		update_post_meta( $not_started->get_id(), '_price', 100 );
		update_post_meta( $not_started->get_id(), '_sale_price_dates_from', time() - 300 );
		update_post_meta( $not_started->get_id(), '_sale_price_dates_to', $far_future );

		// At the sale price, so it clears the ending query's price predicate and only the date
		// can exclude it. Without that the assertion would pass either way.
		$not_ended = WC_Helper_Product::create_simple_product();
		$not_ended->set_regular_price( 100 );
		$not_ended->set_sale_price( 50 );
		$not_ended->save();
		update_post_meta( $not_ended->get_id(), '_price', 50 );
		update_post_meta( $not_ended->get_id(), '_sale_price_dates_from', time() - 300 );
		update_post_meta( $not_ended->get_id(), '_sale_price_dates_to', $far_future );

		$data_store = WC_Data_Store::load( 'product' );

		$this->assertContains(
			(string) $not_started->get_id(),
			$data_store->get_starting_sales(),
			'A non-numeric end date must not read as ended in the starting query.'
		);
		$this->assertNotContains(
			(string) $not_ended->get_id(),
			$data_store->get_ending_sales(),
			'The ending query must read the same value the same way, or the two disagree.'
		);
	}

	/**
	 * End-date values the query still returns, in shapes a PHP-side check reads as ended.
	 *
	 * The query returns both, so the consumer has to write the price to settle them. Anything
	 * deciding "ended" for itself skips that write and re-queues the product forever.
	 * '0000-00-00' is the one value where the query's numeric `> 0` disagrees with a PHP
	 * `$v > 0`; '999999999' is a plain past timestamp that still sorts above the current one.
	 *
	 * Calendar forms such as '2020-01-01' were dropped: they age out in 2034 once `time()`
	 * renders as '20...', and no clock-derived replacement survives, since a fixture must read
	 * as past for the checks it catches yet sort above a decimal timestamp for the query.
	 *
	 * @return array<string, array{string}>
	 */
	public function provider_end_dates_the_query_still_returns(): array {
		return array(
			'short numeric' => array( '999999999' ),
			'zero date'     => array( '0000-00-00' ),
		);
	}

	/**
	 * @testdox A sale the query still returns is started once and stops being re-queued.
	 *
	 * @dataProvider provider_end_dates_the_query_still_returns
	 *
	 * @param string $date_to Stored `_sale_price_dates_to` value.
	 */
	public function test_wc_scheduled_sales_settles_a_sale_the_query_still_returns( string $date_to ): void {
		// A full cycle, not just the query: the consumer's price write is what drains the queue.
		// This shows it drains, not that the price is right. It is not, the product ends at the
		// sale price with a past end date, which predates this fix.
		$product = WC_Helper_Product::create_simple_product();
		$product->set_regular_price( 100 );
		$product->set_sale_price( 50 );
		$product->save();
		update_post_meta( $product->get_id(), '_price', 100 );
		update_post_meta( $product->get_id(), '_sale_price_dates_from', time() - 300 );
		update_post_meta( $product->get_id(), '_sale_price_dates_to', $date_to );

		$data_store = WC_Data_Store::load( 'product' );

		// Precondition, not a result: the rest only means anything while the query returns it.
		$this->assertContains(
			(string) $product->get_id(),
			$data_store->get_starting_sales(),
			"get_starting_sales() no longer returns the fixture '{$date_to}'. Either the query "
				. 'changed, or this value aged out and needs replacing (see the provider docblock).'
		);

		$started = array();
		add_action(
			'wc_before_products_starting_sales',
			function ( $ids ) use ( &$started ) {
				$started = array_merge( $started, $ids );
			}
		);

		wc_scheduled_sales();
		$this->assertContains( (string) $product->get_id(), $started, "The first run should start the sale: {$date_to}." );

		$started = array();
		wc_scheduled_sales();
		$this->assertNotContains( (string) $product->get_id(), $started, "The product must settle instead of being queued again: {$date_to}." );

		$this->assertNotContains( (string) $product->get_id(), $data_store->get_starting_sales() );
	}

	/**
	 * @testdox An end date of exactly now still leaves the product in one of the two queues.
	 */
	public function test_an_end_date_of_exactly_now_lands_in_one_queue(): void {
		// Asserting that some queue claims it, rather than which one, keeps this deterministic
		// without freezing the clock. Neither queue is the failure, and a `<=` on one side alone
		// is what produces it, which nothing else here catches.
		$product = WC_Helper_Product::create_simple_product();
		$product->set_regular_price( 100 );
		$product->set_sale_price( 50 );
		$product->save();

		// A third price, so neither query's price predicate can be what excludes it.
		update_post_meta( $product->get_id(), '_price', 75 );
		update_post_meta( $product->get_id(), '_sale_price_dates_from', time() - 300 );
		update_post_meta( $product->get_id(), '_sale_price_dates_to', time() );

		$data_store = WC_Data_Store::load( 'product' );
		$id         = (string) $product->get_id();

		$starting = in_array( $id, $data_store->get_starting_sales(), true );
		$ending   = in_array( $id, $data_store->get_ending_sales(), true );

		$this->assertTrue(
			$starting || $ending,
			'An end date of exactly now left the product in neither queue, so nothing will ever '
				. 'settle its price. The two queries have stopped reading the date the same way.'
		);
	}

	/**
	 * @testdox Duplicate end-date rows exclude on the ended one and never duplicate the product.
	 */
	public function test_duplicate_end_date_rows_are_handled(): void {
		// add_post_meta(): direct writers can leave several rows for one key.
		$expired_and_open = WC_Helper_Product::create_simple_product();
		$expired_and_open->set_regular_price( 100 );
		$expired_and_open->set_sale_price( 50 );
		$expired_and_open->save();
		update_post_meta( $expired_and_open->get_id(), '_price', 100 );
		update_post_meta( $expired_and_open->get_id(), '_sale_price_dates_from', time() - 300 );
		delete_post_meta( $expired_and_open->get_id(), '_sale_price_dates_to' );
		add_post_meta( $expired_and_open->get_id(), '_sale_price_dates_to', time() - 100 );
		add_post_meta( $expired_and_open->get_id(), '_sale_price_dates_to', time() + 3600 );

		$both_open = WC_Helper_Product::create_simple_product();
		$both_open->set_regular_price( 100 );
		$both_open->set_sale_price( 50 );
		$both_open->save();
		update_post_meta( $both_open->get_id(), '_price', 100 );
		update_post_meta( $both_open->get_id(), '_sale_price_dates_from', time() - 300 );
		delete_post_meta( $both_open->get_id(), '_sale_price_dates_to' );
		add_post_meta( $both_open->get_id(), '_sale_price_dates_to', time() + 3600 );
		add_post_meta( $both_open->get_id(), '_sale_price_dates_to', time() + 7200 );

		$starting = WC_Data_Store::load( 'product' )->get_starting_sales();

		$this->assertNotContains(
			(string) $expired_and_open->get_id(),
			$starting,
			'One ended row is enough to exclude, however many other rows sit beside it.'
		);
		$this->assertSame(
			1,
			count( array_keys( $starting, (string) $both_open->get_id(), true ) ),
			'Two open rows must not multiply the product into the result set.'
		);
	}

	/**
	 * @testdox A product left at an expired sale price is repaired once and then goes inert.
	 */
	public function test_wc_scheduled_sales_repairs_expired_price_once(): void {
		$product = WC_Helper_Product::create_simple_product();
		$product->set_regular_price( 100 );
		$product->set_sale_price( 50 );
		$product->save();

		// A missed end: _price still holds the sale price after the window closed.
		update_post_meta( $product->get_id(), '_price', 50 );
		update_post_meta( $product->get_id(), '_sale_price_dates_from', time() - 300 );
		update_post_meta( $product->get_id(), '_sale_price_dates_to', time() - 100 );

		$started = array();
		$ended   = array();
		add_action(
			'wc_before_products_starting_sales',
			function ( $ids ) use ( &$started ) {
				$started = array_merge( $started, $ids );
			}
		);
		add_action(
			'wc_before_products_ending_sales',
			function ( $ids ) use ( &$ended ) {
				$ended = array_merge( $ended, $ids );
			}
		);

		wc_scheduled_sales();

		$this->assertNotContains( (string) $product->get_id(), $started, 'An expired sale must not be reported as starting.' );
		$this->assertContains( (string) $product->get_id(), $ended, 'The safety net should end it once.' );
		$this->assertEquals( 100, get_post_meta( $product->get_id(), '_price', true ) );

		$started = array();
		$ended   = array();
		wc_scheduled_sales();

		$this->assertNotContains( (string) $product->get_id(), $started, 'The churn must not resume on the next run.' );
		$this->assertNotContains( (string) $product->get_id(), $ended, 'The churn must not resume on the next run.' );
		$this->assertEquals( 100, get_post_meta( $product->get_id(), '_price', true ) );
	}

	/**
	 * @testdox An ended scheduled sale displays the regular price before the AS event runs.
	 */
	public function test_scheduled_sale_active_price_heals_ended_sale_to_regular(): void {
		$product = WC_Helper_Product::create_simple_product();
		$product->set_regular_price( 50 );
		$product->set_sale_price( 20 );
		$product->set_date_on_sale_from( gmdate( 'Y-m-d H:i:s', time() - HOUR_IN_SECONDS ) );
		$product->set_date_on_sale_to( gmdate( 'Y-m-d H:i:s', time() + HOUR_IN_SECONDS ) );
		$product->save();

		// End time elapses without the AS event running, so _price stays stale.
		update_post_meta( $product->get_id(), '_sale_price_dates_to', time() - 5 );
		clean_post_cache( $product->get_id() );

		$reread = wc_get_product( $product->get_id() );
		$this->assertEquals( 50, $reread->get_price(), 'An ended sale should display the regular price.' );
		$this->assertEquals( 20, get_post_meta( $product->get_id(), '_price', true ), 'The stored _price stays stale until the AS event/cron runs.' );
		$this->assertStringNotContainsString( '<del', $reread->get_price_html(), 'An ended sale should not render a strikethrough.' );
	}

	/**
	 * @testdox A started scheduled sale displays the sale price before the AS event runs.
	 */
	public function test_scheduled_sale_active_price_heals_started_sale_to_sale_price(): void {
		$product = WC_Helper_Product::create_simple_product();
		$product->set_regular_price( 50 );
		$product->set_sale_price( 20 );
		$product->set_date_on_sale_from( gmdate( 'Y-m-d H:i:s', time() + HOUR_IN_SECONDS ) );
		$product->set_date_on_sale_to( gmdate( 'Y-m-d H:i:s', time() + 2 * HOUR_IN_SECONDS ) );
		$product->save();

		// Start time elapses without the AS event running, so _price stays at the regular price.
		update_post_meta( $product->get_id(), '_sale_price_dates_from', time() - 5 );
		clean_post_cache( $product->get_id() );

		$reread = wc_get_product( $product->get_id() );
		$this->assertEquals( 20, $reread->get_price(), 'A started sale should display the sale price.' );
		$this->assertStringContainsString( '<del', $reread->get_price_html(), 'A started sale should render a strikethrough.' );
	}

	/**
	 * @testdox A started scheduled sale with no end date heals to the sale price.
	 */
	public function test_scheduled_sale_active_price_heals_started_sale_with_no_end_date(): void {
		$product = WC_Helper_Product::create_simple_product();
		$product->set_regular_price( 50 );
		$product->set_sale_price( 20 );
		$product->set_date_on_sale_from( gmdate( 'Y-m-d H:i:s', time() + HOUR_IN_SECONDS ) );
		$product->save();

		// Start time elapses without the AS event running, so _price stays at the regular price.
		update_post_meta( $product->get_id(), '_sale_price_dates_from', time() - 5 );
		clean_post_cache( $product->get_id() );

		$this->assertEquals( 20, wc_get_product( $product->get_id() )->get_price(), 'A started sale with only a start date should display the sale price.' );
	}

	/**
	 * @testdox An ended scheduled sale with no start date heals to the regular price.
	 */
	public function test_scheduled_sale_active_price_heals_ended_sale_with_no_start_date(): void {
		$product = WC_Helper_Product::create_simple_product();
		$product->set_regular_price( 50 );
		$product->set_sale_price( 20 );
		$product->set_date_on_sale_to( gmdate( 'Y-m-d H:i:s', time() + HOUR_IN_SECONDS ) );
		$product->save();

		// End time elapses without the AS event running, so _price stays at the sale price.
		update_post_meta( $product->get_id(), '_sale_price_dates_to', time() - 5 );
		clean_post_cache( $product->get_id() );

		$this->assertEquals( 50, wc_get_product( $product->get_id() )->get_price(), 'An ended sale with only an end date should display the regular price.' );
	}

	/**
	 * @testdox An active scheduled sale leaves the sale price unchanged.
	 */
	public function test_scheduled_sale_active_price_leaves_active_sale_unchanged(): void {
		$product = WC_Helper_Product::create_simple_product();
		$product->set_regular_price( 50 );
		$product->set_sale_price( 20 );
		$product->set_date_on_sale_from( gmdate( 'Y-m-d H:i:s', time() - HOUR_IN_SECONDS ) );
		$product->set_date_on_sale_to( gmdate( 'Y-m-d H:i:s', time() + HOUR_IN_SECONDS ) );
		$product->save();

		$this->assertEquals( 20, wc_get_product( $product->get_id() )->get_price(), 'An active sale should display the sale price.' );
	}

	/**
	 * @testdox A future scheduled sale leaves the regular price unchanged.
	 */
	public function test_scheduled_sale_active_price_leaves_future_sale_unchanged(): void {
		$product = WC_Helper_Product::create_simple_product();
		$product->set_regular_price( 50 );
		$product->set_sale_price( 20 );
		$product->set_date_on_sale_from( gmdate( 'Y-m-d H:i:s', time() + HOUR_IN_SECONDS ) );
		$product->set_date_on_sale_to( gmdate( 'Y-m-d H:i:s', time() + 2 * HOUR_IN_SECONDS ) );
		$product->save();

		$this->assertEquals( 50, wc_get_product( $product->get_id() )->get_price(), 'A future sale should display the regular price.' );
	}

	/**
	 * @testdox A custom active price unrelated to the sale or regular price is left untouched.
	 */
	public function test_scheduled_sale_active_price_leaves_custom_price_untouched(): void {
		$product = WC_Helper_Product::create_simple_product();
		$product->set_regular_price( 50 );
		$product->set_sale_price( 20 );
		$product->set_date_on_sale_from( gmdate( 'Y-m-d H:i:s', time() - HOUR_IN_SECONDS ) );
		$product->set_date_on_sale_to( gmdate( 'Y-m-d H:i:s', time() + HOUR_IN_SECONDS ) );
		$product->save();

		// A third party set a custom active price unrelated to the sale or regular price.
		update_post_meta( $product->get_id(), '_price', 37 );
		clean_post_cache( $product->get_id() );

		$this->assertEquals( 37, wc_get_product( $product->get_id() )->get_price(), 'A custom price not equal to sale or regular is left untouched.' );
	}

	/**
	 * @testdox A sale with no scheduled dates is never reconciled.
	 */
	public function test_scheduled_sale_active_price_ignores_unscheduled_sale(): void {
		$product = WC_Helper_Product::create_simple_product();
		$product->set_regular_price( 50 );
		$product->set_sale_price( 20 );
		$product->save();

		// Stored price disagrees with the on-sale state, but there is no schedule to drift against.
		update_post_meta( $product->get_id(), '_price', 50 );
		clean_post_cache( $product->get_id() );

		$this->assertEquals( 50, wc_get_product( $product->get_id() )->get_price(), 'Without sale dates the reconciliation does not run.' );
	}

	/**
	 * @testdox A price already changed by another plugin is not overridden.
	 */
	public function test_scheduled_sale_active_price_does_not_clobber_third_party_price(): void {
		$product = WC_Helper_Product::create_simple_product();
		$product->set_regular_price( 50 );
		$product->set_sale_price( 20 );
		$product->set_date_on_sale_from( gmdate( 'Y-m-d H:i:s', time() - HOUR_IN_SECONDS ) );
		$product->set_date_on_sale_to( gmdate( 'Y-m-d H:i:s', time() + HOUR_IN_SECONDS ) );
		$product->save();

		$product_id = $product->get_id();
		// Membership-style plugin charges the regular price during an active sale.
		$callback = function ( $price, $filtered_product ) use ( $product_id ) {
			return ( $filtered_product instanceof WC_Product && $filtered_product->get_id() === $product_id ) ? '50' : $price;
		};
		add_filter( 'woocommerce_product_get_price', $callback, 50, 2 );

		$this->assertEquals( 50, wc_get_product( $product_id )->get_price(), 'A deliberate third-party price is respected, not reverted to the sale price.' );

		remove_filter( 'woocommerce_product_get_price', $callback, 50 );
	}

	/**
	 * @testdox A third-party price wins over the heal even when the heal would otherwise fire.
	 */
	public function test_scheduled_sale_active_price_respects_third_party_price_during_started_gap(): void {
		$product = WC_Helper_Product::create_simple_product();
		$product->set_regular_price( 50 );
		$product->set_sale_price( 20 );
		// Future sale, so the saved _price is the regular price (50).
		$product->set_date_on_sale_from( gmdate( 'Y-m-d H:i:s', time() + HOUR_IN_SECONDS ) );
		$product->set_date_on_sale_to( gmdate( 'Y-m-d H:i:s', time() + 2 * HOUR_IN_SECONDS ) );
		$product->save();

		// Start time elapses without the AS event running: the sale is active but _price is
		// still 50, so the heal would reconcile 50 -> 20 absent a third-party override.
		update_post_meta( $product->get_id(), '_sale_price_dates_from', time() - 5 );
		clean_post_cache( $product->get_id() );

		$product_id = $product->get_id();
		// Third party sets a deliberate price that is neither the stored value nor the heal target.
		$callback = function ( $price, $filtered_product ) use ( $product_id ) {
			return ( $filtered_product instanceof WC_Product && $filtered_product->get_id() === $product_id ) ? '42' : $price;
		};
		add_filter( 'woocommerce_product_get_price', $callback, 50, 2 );

		$this->assertEquals(
			42,
			wc_get_product( $product_id )->get_price(),
			'The third-party price wins over the scheduled-sale heal (which would otherwise return 20).'
		);

		remove_filter( 'woocommerce_product_get_price', $callback, 50 );
	}

	/**
	 * @testdox An external product reconciles like a simple product.
	 */
	public function test_scheduled_sale_active_price_heals_external_product(): void {
		$product = new WC_Product_External();
		$product->set_name( 'External sale product' );
		$product->set_regular_price( 50 );
		$product->set_sale_price( 20 );
		$product->set_date_on_sale_from( gmdate( 'Y-m-d H:i:s', time() - HOUR_IN_SECONDS ) );
		$product->set_date_on_sale_to( gmdate( 'Y-m-d H:i:s', time() + HOUR_IN_SECONDS ) );
		$product->save();

		update_post_meta( $product->get_id(), '_sale_price_dates_to', time() - 5 );
		clean_post_cache( $product->get_id() );

		$this->assertEquals( 50, wc_get_product( $product->get_id() )->get_price(), 'External products reconcile like simple products.' );
	}

	/**
	 * @testdox Variations self-heal at read time and need no reconciliation filter.
	 */
	public function test_scheduled_sale_variation_self_heals_on_read(): void {
		$parent = new WC_Product_Variable();
		$parent->set_name( 'Variation parent' );
		$parent->save();

		$variation = new WC_Product_Variation();
		$variation->set_parent_id( $parent->get_id() );
		$variation->set_regular_price( '50' );
		$variation->set_sale_price( '20' );
		$variation->set_date_on_sale_from( gmdate( 'Y-m-d H:i:s', time() - HOUR_IN_SECONDS ) );
		$variation->set_date_on_sale_to( gmdate( 'Y-m-d H:i:s', time() + HOUR_IN_SECONDS ) );
		$variation->save();

		// The variation data store re-derives the price from is_on_sale() on every read, so a
		// fresh read after the sale ends already reflects the regular price (no filter needed).
		update_post_meta( $variation->get_id(), '_sale_price_dates_to', time() - 5 );
		clean_post_cache( $variation->get_id() );

		$this->assertEquals( 50, wc_get_product( $variation->get_id() )->get_price(), 'A freshly read variation reflects the ended sale without the reconciliation filter.' );
	}

	/**
	 * @testdox An ended scheduled sale charges the regular price in the cart.
	 */
	public function test_scheduled_sale_active_price_charges_regular_in_cart_for_ended_sale(): void {
		$product = WC_Helper_Product::create_simple_product();
		$product->set_regular_price( 50 );
		$product->set_sale_price( 20 );
		$product->set_date_on_sale_from( gmdate( 'Y-m-d H:i:s', time() - HOUR_IN_SECONDS ) );
		$product->set_date_on_sale_to( gmdate( 'Y-m-d H:i:s', time() + HOUR_IN_SECONDS ) );
		$product->save();

		update_post_meta( $product->get_id(), '_sale_price_dates_to', time() - 5 );
		clean_post_cache( $product->get_id() );

		WC()->cart->empty_cart();
		WC()->cart->add_to_cart( $product->get_id() );
		WC()->cart->calculate_totals();
		$total = WC()->cart->get_cart_contents_total();
		WC()->cart->empty_cart();

		$this->assertEquals( 50, $total, 'The cart charges the regular price once the sale has ended.' );
	}

	/**
	 * @testdox Lookup table is refreshed when scheduled sale starts.
	 */
	public function test_wc_scheduled_sales_sale_start_updates_lookup_table(): void {
		global $wpdb;

		$product = WC_Helper_Product::create_simple_product();
		$product->set_price( 100 );
		$product->set_regular_price( 100 );
		$product->set_sale_price( 50 );
		$product->set_date_on_sale_from( gmdate( 'Y-m-d H:i:s', time() + 10 ) );
		$product->save();

		// Bypass product after save hook to prevent price change on save.
		update_post_meta( $product->get_id(), '_sale_price_dates_from', time() - 5 );

		$lookup_before = $wpdb->get_row(
			$wpdb->prepare( "SELECT onsale, min_price, max_price FROM {$wpdb->prefix}wc_product_meta_lookup WHERE product_id = %d", $product->get_id() )
		);
		$this->assertEquals( 0, (int) $lookup_before->onsale, 'Product should not be on sale before scheduled sale starts' );

		wc_scheduled_sales();

		$lookup_after = $wpdb->get_row(
			$wpdb->prepare( "SELECT onsale, min_price, max_price FROM {$wpdb->prefix}wc_product_meta_lookup WHERE product_id = %d", $product->get_id() )
		);
		$this->assertEquals( 1, (int) $lookup_after->onsale, 'Lookup table onsale flag should be updated after sale starts' );
		$this->assertEquals( 50, (float) $lookup_after->min_price, 'Lookup table min_price should reflect sale price' );
	}

	/**
	 * @testdox Lookup table is refreshed when scheduled sale ends.
	 */
	public function test_wc_scheduled_sales_sale_end_updates_lookup_table(): void {
		global $wpdb;

		$product = WC_Helper_Product::create_simple_product();
		$product->set_price( 50 );
		$product->set_regular_price( 100 );
		$product->set_sale_price( 50 );
		$product->set_date_on_sale_to( gmdate( 'Y-m-d H:i:s', time() + 10 ) );
		$product->save();

		// Bypass product after save hook to prevent price change on save.
		update_post_meta( $product->get_id(), '_sale_price_dates_to', time() - 5 );

		$lookup_before = $wpdb->get_row(
			$wpdb->prepare( "SELECT onsale, min_price, max_price FROM {$wpdb->prefix}wc_product_meta_lookup WHERE product_id = %d", $product->get_id() )
		);
		$this->assertEquals( 1, (int) $lookup_before->onsale, 'Product should be on sale before scheduled sale ends' );

		wc_scheduled_sales();

		$lookup_after = $wpdb->get_row(
			$wpdb->prepare( "SELECT onsale, min_price, max_price FROM {$wpdb->prefix}wc_product_meta_lookup WHERE product_id = %d", $product->get_id() )
		);
		$this->assertEquals( 0, (int) $lookup_after->onsale, 'Lookup table onsale flag should be updated after sale ends' );
		$this->assertEquals( 100, (float) $lookup_after->min_price, 'Lookup table min_price should reflect regular price' );
	}

	/**
	 * @testdox Product lookup table regeneration schedules review data updates only when reviews are enabled.
	 * @testWith ["yes", true]
	 *           ["no", false]
	 *
	 * @param string $reviews_setting       The product reviews setting.
	 * @param bool   $expect_review_updates Whether review data updates should be scheduled.
	 */
	public function test_wc_update_product_lookup_tables_schedules_review_data_only_when_reviews_are_enabled( string $reviews_setting, bool $expect_review_updates ): void {
		$this->set_reviews_setting_for_lookup_table_test( $reviews_setting );

		wc_update_product_lookup_tables();

		$average_rating_scheduled = as_has_scheduled_action(
			'wc_update_product_lookup_tables_column',
			array( 'column' => 'average_rating' ),
			'wc_update_product_lookup_tables'
		);
		$rating_count_scheduled   = as_has_scheduled_action(
			'wc_update_product_lookup_tables_rating_count_batch',
			array(
				'offset' => 0,
				'limit'  => 50,
			),
			'wc_update_product_lookup_tables'
		);

		$this->assertSame( $expect_review_updates, (bool) $average_rating_scheduled, 'Average rating regeneration should follow the product reviews setting.' );
		$this->assertSame( $expect_review_updates, (bool) $rating_count_scheduled, 'Rating count regeneration should follow the product reviews setting.' );
		$this->assertTrue(
			(bool) as_has_scheduled_action(
				'wc_update_product_lookup_tables_column',
				array( 'column' => 'min_max_price' ),
				'wc_update_product_lookup_tables'
			),
			'Unrelated lookup table columns should still be scheduled.'
		);
	}

	/**
	 * @testdox Queued average rating updates do not run when reviews are disabled.
	 * @testWith ["yes", 4.5]
	 *           ["no", 1.0]
	 *
	 * @param string $reviews_setting The product reviews setting.
	 * @param float  $expected_rating The expected lookup table rating.
	 */
	public function test_wc_update_product_lookup_tables_column_respects_reviews_setting( string $reviews_setting, float $expected_rating ): void {
		global $wpdb;

		$product = WC_Helper_Product::create_simple_product();
		update_post_meta( $product->get_id(), '_wc_average_rating', '4.50' );
		$wpdb->update(
			$wpdb->wc_product_meta_lookup,
			array( 'average_rating' => 1.0 ),
			array( 'product_id' => $product->get_id() )
		);
		$this->set_reviews_setting_for_lookup_table_test( $reviews_setting );

		wc_update_product_lookup_tables_column( 'average_rating' );

		$actual_rating = (float) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT average_rating FROM {$wpdb->wc_product_meta_lookup} WHERE product_id = %d",
				$product->get_id()
			)
		);
		$this->assertSame( $expected_rating, $actual_rating, 'Average rating lookup data should only update when reviews are enabled.' );
	}

	/**
	 * @testdox Queued rating count batches do not run when reviews are disabled.
	 * @testWith ["yes", 3, true]
	 *           ["no", 1, false]
	 *
	 * @param string $reviews_setting  The product reviews setting.
	 * @param int    $expected_count   The expected lookup table rating count.
	 * @param bool   $expect_follow_up Whether another batch should be scheduled.
	 */
	public function test_wc_update_product_lookup_tables_rating_count_batch_respects_reviews_setting( string $reviews_setting, int $expected_count, bool $expect_follow_up ): void {
		global $wpdb;

		$product = WC_Helper_Product::create_simple_product();
		update_post_meta( $product->get_id(), '_wc_rating_count', array( 5 => 3 ) );
		$wpdb->update(
			$wpdb->wc_product_meta_lookup,
			array( 'rating_count' => 1 ),
			array( 'product_id' => $product->get_id() )
		);
		$this->set_reviews_setting_for_lookup_table_test( $reviews_setting );

		wc_update_product_lookup_tables_rating_count_batch( 0, 50 );

		$actual_count        = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT rating_count FROM {$wpdb->wc_product_meta_lookup} WHERE product_id = %d",
				$product->get_id()
			)
		);
		$follow_up_scheduled = as_has_scheduled_action(
			'wc_update_product_lookup_tables_rating_count_batch',
			array(
				'offset' => 50,
				'limit'  => 50,
			),
			'wc_update_product_lookup_tables'
		);

		$this->assertSame( $expected_count, $actual_count, 'Rating count lookup data should only update when reviews are enabled.' );
		$this->assertSame( $expect_follow_up, (bool) $follow_up_scheduled, 'Follow-up rating count batches should only be scheduled when reviews are enabled.' );
	}

	/**
	 * Set the product reviews option and reset lookup table actions for a test.
	 *
	 * @param string $reviews_setting The product reviews setting.
	 */
	private function set_reviews_setting_for_lookup_table_test( string $reviews_setting ): void {
		$this->original_reviews_setting = get_option( 'woocommerce_enable_reviews', null );
		$this->reviews_setting_changed  = true;
		as_unschedule_all_actions( '', array(), 'wc_update_product_lookup_tables' );
		update_option( 'woocommerce_enable_reviews', $reviews_setting );
	}

	/**
	 * @testDox Action Scheduler events are scheduled when product with sale dates is saved.
	 */
	public function test_wc_schedule_product_sale_events_on_save() {
		$future_start = time() + 3600;
		// 1 hour from now.
		$future_end = time() + 86400;
		// 24 hours from now.

		$product = WC_Helper_Product::create_simple_product();
		$product->set_price( 100 );
		$product->set_regular_price( 100 );
		$product->set_sale_price( 50 );
		$product->set_date_on_sale_from( gmdate( 'Y-m-d H:i:s', $future_start ) );
		$product->set_date_on_sale_to( gmdate( 'Y-m-d H:i:s', $future_end ) );
		$product->save();

		// Check that AS actions were scheduled.
		$start_action = as_next_scheduled_action(
			'wc_product_start_scheduled_sale',
			array( 'product_id' => $product->get_id() ),
			'woocommerce-sales'
		);
		$end_action   = as_next_scheduled_action(
			'wc_product_end_scheduled_sale',
			array( 'product_id' => $product->get_id() ),
			'woocommerce-sales'
		);

		$this->assertNotFalse( $start_action, 'Start sale action should be scheduled' );
		$this->assertNotFalse( $end_action, 'End sale action should be scheduled' );
	}

	/**
	 * @testDox Existing AS events are cleared when product sale dates change.
	 */
	public function test_wc_schedule_product_sale_events_clears_existing() {
		$future_start = time() + 3600;
		$future_end   = time() + 86400;

		$product = WC_Helper_Product::create_simple_product();
		$product->set_price( 100 );
		$product->set_regular_price( 100 );
		$product->set_sale_price( 50 );
		$product->set_date_on_sale_from( gmdate( 'Y-m-d H:i:s', $future_start ) );
		$product->set_date_on_sale_to( gmdate( 'Y-m-d H:i:s', $future_end ) );
		$product->save();

		$original_start = as_next_scheduled_action(
			'wc_product_start_scheduled_sale',
			array( 'product_id' => $product->get_id() ),
			'woocommerce-sales'
		);

		// Update the sale dates.
		$new_start = time() + 7200;
		// 2 hours from now.
		$product->set_date_on_sale_from( gmdate( 'Y-m-d H:i:s', $new_start ) );
		$product->save();

		$new_start_action = as_next_scheduled_action(
			'wc_product_start_scheduled_sale',
			array( 'product_id' => $product->get_id() ),
			'woocommerce-sales'
		);

		// The timestamp should have changed.
		$this->assertNotEquals( $original_start, $new_start_action, 'Start action should be rescheduled with new time' );
	}

	/**
	 * @testDox Identical pending sale events are not scheduled twice when scheduling runs concurrently.
	 */
	public function test_wc_schedule_product_sale_events_skips_identical_pending_actions() {
		$future_start = time() + 3600;
		$future_end   = time() + 86400;

		$product = WC_Helper_Product::create_simple_product();
		$product->set_price( 100 );
		$product->set_regular_price( 100 );
		$product->set_sale_price( 50 );
		$product->set_date_on_sale_from( gmdate( 'Y-m-d H:i:s', $future_start ) );
		$product->set_date_on_sale_to( gmdate( 'Y-m-d H:i:s', $future_end ) );
		$product->save();

		// Simulate a concurrent process that already passed the unschedule-all step
		// by invoking the scheduling function directly, without unscheduling first.
		wc_schedule_product_sale_events( wc_get_product( $product->get_id() ) );

		$this->assertSame(
			1,
			$this->count_pending_sale_actions( 'wc_product_start_scheduled_sale', $product->get_id() ),
			'Only one start sale action should be pending after concurrent scheduling'
		);
		$this->assertSame(
			1,
			$this->count_pending_sale_actions( 'wc_product_end_scheduled_sale', $product->get_id() ),
			'Only one end sale action should be pending after concurrent scheduling'
		);
	}

	/**
	 * @testDox An identical pending sale event is not scheduled twice even when an earlier action is also pending.
	 */
	public function test_wc_schedule_product_sale_events_skips_identical_pending_action_behind_an_earlier_one() {
		$product = WC_Helper_Product::create_simple_product();
		$product->set_price( 100 );
		$product->set_regular_price( 100 );
		$product->set_sale_price( 50 );
		$product->save();

		$earlier_ts = time() + 3600;
		$target_ts  = time() + 7200;

		// Simulate a stale action left behind by a concurrent process that saw older sale dates.
		as_schedule_single_action( $earlier_ts, 'wc_product_start_scheduled_sale', array( 'product_id' => $product->get_id() ), 'woocommerce-sales' );

		// Not saved on purpose: saving would trigger the unschedule-all step and remove the stale action.
		$product->set_date_on_sale_from( gmdate( 'Y-m-d H:i:s', $target_ts ) );

		wc_schedule_product_sale_events( $product );
		wc_schedule_product_sale_events( $product );

		$this->assertSame(
			2,
			$this->count_pending_sale_actions( 'wc_product_start_scheduled_sale', $product->get_id() ),
			'The action at the new time should be scheduled exactly once alongside the stale earlier action'
		);
	}

	/**
	 * @testDox A sale event pending for a different time does not prevent scheduling at the new time.
	 */
	public function test_wc_schedule_product_sale_events_schedules_when_pending_action_has_different_timestamp() {
		$future_start = time() + 3600;

		$product = WC_Helper_Product::create_simple_product();
		$product->set_price( 100 );
		$product->set_regular_price( 100 );
		$product->set_sale_price( 50 );
		$product->set_date_on_sale_from( gmdate( 'Y-m-d H:i:s', $future_start ) );
		$product->save();

		$product = wc_get_product( $product->get_id() );
		$product->set_date_on_sale_from( gmdate( 'Y-m-d H:i:s', $future_start + 3600 ) );
		wc_schedule_product_sale_events( $product );

		$this->assertSame(
			2,
			$this->count_pending_sale_actions( 'wc_product_start_scheduled_sale', $product->get_id() ),
			'A start sale action with a different timestamp should still be scheduled'
		);
	}

	/**
	 * Count pending Action Scheduler sale actions for a product.
	 *
	 * @param string $hook       Action hook name.
	 * @param int    $product_id Product ID.
	 * @return int
	 */
	private function count_pending_sale_actions( string $hook, int $product_id ): int {
		$actions = as_get_scheduled_actions(
			array(
				'hook'     => $hook,
				'args'     => array( 'product_id' => $product_id ),
				'group'    => 'woocommerce-sales',
				'status'   => \ActionScheduler_Store::STATUS_PENDING,
				'per_page' => -1,
			),
			'ids'
		);

		return count( $actions );
	}

	/**
	 * @testDox Action Scheduler events are scheduled when sale date meta is written directly via update_post_meta.
	 */
	public function test_wc_schedule_product_sale_events_on_direct_meta_write() {
		$future_start = time() + 3600;
		$future_end   = time() + 86400;

		$product = WC_Helper_Product::create_simple_product();
		$product->set_price( 100 );
		$product->set_regular_price( 100 );
		$product->set_sale_price( 50 );
		$product->save();

		// Verify no sale events are scheduled yet.
		$this->assertFalse(
			as_next_scheduled_action( 'wc_product_start_scheduled_sale', array( 'product_id' => $product->get_id() ), 'woocommerce-sales' ),
			'No start action should be scheduled before meta write'
		);

		// Write sale date meta directly, bypassing WooCommerce CRUD.
		update_post_meta( $product->get_id(), '_sale_price_dates_from', $future_start );
		update_post_meta( $product->get_id(), '_sale_price_dates_to', $future_end );

		// Check that AS actions were scheduled via the meta hook.
		$start_action = as_next_scheduled_action(
			'wc_product_start_scheduled_sale',
			array( 'product_id' => $product->get_id() ),
			'woocommerce-sales'
		);
		$end_action   = as_next_scheduled_action(
			'wc_product_end_scheduled_sale',
			array( 'product_id' => $product->get_id() ),
			'woocommerce-sales'
		);

		$this->assertNotFalse( $start_action, 'Start sale action should be scheduled after direct meta write' );
		$this->assertNotFalse( $end_action, 'End sale action should be scheduled after direct meta write' );
	}

	/**
	 * @testDox Action Scheduler events are scheduled for product variations when sale date meta is written directly.
	 */
	public function test_wc_schedule_product_sale_events_on_direct_meta_write_for_variation() {
		$future_start = time() + 3600;
		$future_end   = time() + 86400;

		$product      = WC_Helper_Product::create_variation_product();
		$variations   = $product->get_children();
		$variation_id = $variations[0];

		$variation = wc_get_product( $variation_id );
		$variation->set_sale_price( 5 );
		$variation->save();

		// Write sale date meta directly on the variation.
		update_post_meta( $variation_id, '_sale_price_dates_from', $future_start );
		update_post_meta( $variation_id, '_sale_price_dates_to', $future_end );

		$start_action = as_next_scheduled_action(
			'wc_product_start_scheduled_sale',
			array( 'product_id' => $variation_id ),
			'woocommerce-sales'
		);

		$this->assertNotFalse( $start_action, 'Start sale action should be scheduled for variation after direct meta write' );
	}

	/**
	 * @testDox Scheduled sale events are cleared when sale date meta is deleted.
	 */
	public function test_wc_schedule_product_sale_events_cleared_on_meta_delete() {
		$future_start = time() + 3600;
		$future_end   = time() + 86400;

		$product = WC_Helper_Product::create_simple_product();
		$product->set_price( 100 );
		$product->set_regular_price( 100 );
		$product->set_sale_price( 50 );
		$product->set_date_on_sale_from( gmdate( 'Y-m-d H:i:s', $future_start ) );
		$product->set_date_on_sale_to( gmdate( 'Y-m-d H:i:s', $future_end ) );
		$product->save();

		// Sanity check: events are scheduled.
		$this->assertNotFalse(
			as_next_scheduled_action( 'wc_product_start_scheduled_sale', array( 'product_id' => $product->get_id() ), 'woocommerce-sales' ),
			'Start action should be scheduled after save'
		);

		// Delete sale date meta directly, bypassing WooCommerce CRUD.
		delete_post_meta( $product->get_id(), '_sale_price_dates_from' );
		delete_post_meta( $product->get_id(), '_sale_price_dates_to' );

		$this->assertFalse(
			as_next_scheduled_action( 'wc_product_start_scheduled_sale', array( 'product_id' => $product->get_id() ), 'woocommerce-sales' ),
			'Start action should be cleared after sale date meta is deleted'
		);
		$this->assertFalse(
			as_next_scheduled_action( 'wc_product_end_scheduled_sale', array( 'product_id' => $product->get_id() ), 'woocommerce-sales' ),
			'End action should be cleared after sale date meta is deleted'
		);
	}

	/**
	 * @testDox Meta hook does not reschedule when sale date meta is written from inside the AS sale start handler.
	 */
	public function test_wc_schedule_sale_events_meta_hook_skips_when_inside_as_start_handler() {
		$product = WC_Helper_Product::create_simple_product();

		$writer = function ( $pid ) {
			update_post_meta( $pid, '_sale_price_dates_from', time() + 3600 );
		};
		add_action( 'wc_product_start_scheduled_sale', $writer, 1, 1 );

		do_action( 'wc_product_start_scheduled_sale', $product->get_id() );

		remove_action( 'wc_product_start_scheduled_sale', $writer, 1 );

		$this->assertFalse(
			as_next_scheduled_action( 'wc_product_start_scheduled_sale', array( 'product_id' => $product->get_id() ), 'woocommerce-sales' ),
			'Meta-hook scheduling should be suppressed while inside the AS sale start handler'
		);
	}

	/**
	 * @testDox Meta hook does not reschedule when sale date meta is written from inside the AS sale end handler.
	 */
	public function test_wc_schedule_sale_events_meta_hook_skips_when_inside_as_end_handler() {
		$product = WC_Helper_Product::create_simple_product();

		$writer = function ( $pid ) {
			update_post_meta( $pid, '_sale_price_dates_to', time() + 3600 );
		};
		add_action( 'wc_product_end_scheduled_sale', $writer, 1, 1 );

		do_action( 'wc_product_end_scheduled_sale', $product->get_id() );

		remove_action( 'wc_product_end_scheduled_sale', $writer, 1 );

		$this->assertFalse(
			as_next_scheduled_action( 'wc_product_end_scheduled_sale', array( 'product_id' => $product->get_id() ), 'woocommerce-sales' ),
			'Meta-hook scheduling should be suppressed while inside the AS sale end handler'
		);
	}

	/**
	 * @testDox Direct meta write on non-product post types does not schedule sale events.
	 */
	public function test_wc_schedule_sale_events_ignores_non_product_post_types() {
		$future_start = time() + 3600;

		$post_id = wp_insert_post(
			array(
				'post_title'  => 'Not a product',
				'post_type'   => 'post',
				'post_status' => 'publish',
			)
		);

		update_post_meta( $post_id, '_sale_price_dates_from', $future_start );

		$this->assertFalse(
			as_next_scheduled_action( 'wc_product_start_scheduled_sale', array( 'product_id' => $post_id ), 'woocommerce-sales' ),
			'Sale events should not be scheduled for non-product post types'
		);
	}

	/**
	 * @testdox Guest order uses billing address tax rate when woocommerce_adjust_non_base_location_prices is false.
	 */
	public function test_wc_get_price_excluding_tax_guest_order_uses_billing_address() {
		// Enable taxes.
		$wc_tax_enabled = wc_tax_enabled();
		if ( ! $wc_tax_enabled ) {
			update_option( 'woocommerce_calc_taxes', 'yes' );
		}

		// Set prices to include tax.
		$original_prices_include_tax = get_option( 'woocommerce_prices_include_tax' );
		update_option( 'woocommerce_prices_include_tax', 'yes' );

		// Set base country to Germany.
		$original_base_country = get_option( 'woocommerce_default_country' );
		update_option( 'woocommerce_default_country', 'DE' );

		// Create German tax rate (19%) - this is the base/shop rate.
		$german_tax_rate_id = WC_Tax::_insert_tax_rate(
			array(
				'tax_rate_country'  => 'DE',
				'tax_rate_state'    => '',
				'tax_rate'          => '19.0000',
				'tax_rate_name'     => 'German VAT',
				'tax_rate_priority' => '1',
				'tax_rate_compound' => '0',
				'tax_rate_shipping' => '1',
				'tax_rate_order'    => '1',
				'tax_rate_class'    => '',
			)
		);

		// Create French tax rate (20%) - this is where the customer is.
		$french_tax_rate_id = WC_Tax::_insert_tax_rate(
			array(
				'tax_rate_country'  => 'FR',
				'tax_rate_state'    => '',
				'tax_rate'          => '20.0000',
				'tax_rate_name'     => 'French VAT',
				'tax_rate_priority' => '1',
				'tax_rate_compound' => '0',
				'tax_rate_shipping' => '1',
				'tax_rate_order'    => '1',
				'tax_rate_class'    => '',
			)
		);

		// Create a product priced at 100 (including tax).
		$product = WC_Helper_Product::create_simple_product();
		$product->set_price( 100 );
		$product->set_tax_status( 'taxable' );
		$product->save();

		// Create a guest order with French billing address.
		$order = wc_create_order();
		$order->set_customer_id( 0 );
		// Guest order.
		$order->set_billing_country( 'FR' );
		$order->set_billing_city( 'Paris' );
		$order->set_billing_postcode( '75001' );
		$order->save();

		// Enable "same price everywhere" mode.
		add_filter( 'woocommerce_adjust_non_base_location_prices', '__return_false' );

		// Calculate the price excluding tax.
		$price_excluding_tax = wc_get_price_excluding_tax( $product, array( 'order' => $order ) );

		// With filter=false and French customer (20% VAT):
		// €100 / 1.20 = €83.33 (net price)
		// Later: €83.33 * 1.20 = €100 (customer pays €100).
		//
		// If the bug were present (using base rate instead):
		// €100 / 1.19 = €84.03 (wrong net price)
		// Later: €84.03 * 1.20 = €100.84 (customer pays more than €100).
		$this->assertEquals( 83.33, round( $price_excluding_tax, 2 ), 'Price should use French tax rate (20%) to calculate net, not German base rate (19%)' );

		// Clean up.
		remove_filter( 'woocommerce_adjust_non_base_location_prices', '__return_false' );
		WC_Tax::_delete_tax_rate( $german_tax_rate_id );
		WC_Tax::_delete_tax_rate( $french_tax_rate_id );
		WC_Helper_Product::delete_product( $product->get_id() );
		$order->delete( true );
		update_option( 'woocommerce_prices_include_tax', $original_prices_include_tax );
		update_option( 'woocommerce_default_country', $original_base_country );
		if ( ! $wc_tax_enabled ) {
			update_option( 'woocommerce_calc_taxes', 'no' );
		}
	}

	/**
	 * @testDox Test 'wc_get_related_products' with actual related products.
	 */
	public function test_wc_get_related_products_with_actual_related_products() {
		$main_product = WC_Helper_Product::create_simple_product();

		// Create related products.
		$related_product1 = WC_Helper_Product::create_simple_product();
		$related_product2 = WC_Helper_Product::create_simple_product();
		$related_product3 = WC_Helper_Product::create_simple_product();

		// Set up relationships - products can be related by category.
		$category_term = wp_insert_term( 'Test Category', 'product_cat' );
		wp_set_object_terms( $main_product->get_id(), $category_term['term_id'], 'product_cat' );
		wp_set_object_terms( $related_product1->get_id(), $category_term['term_id'], 'product_cat' );
		wp_set_object_terms( $related_product2->get_id(), $category_term['term_id'], 'product_cat' );
		wp_set_object_terms( $related_product3->get_id(), $category_term['term_id'], 'product_cat' );

		// Save all products.
		$main_product->save();
		$related_product1->save();
		$related_product2->save();
		$related_product3->save();

		// Get related products with a limit of 2.
		$related_products = wc_get_related_products( $main_product->get_id(), 2 );

		// Test that we got related products (limited to 2).
		$this->assertCount( 2, $related_products );

		$related_products_numeric = wc_get_related_products( $main_product->get_id(), '2' );
		$this->assertCount( 2, $related_products_numeric );

		// Test with a larger limit to get all related products.
		$all_related_products = wc_get_related_products( $main_product->get_id(), 10 );
		$this->assertCount( 3, $all_related_products );

		$empty_related_products = wc_get_related_products( $main_product->get_id(), 'non-numeric-limit' );
		$this->assertEquals( array(), $empty_related_products );

		// Clean up.
		WC_Helper_Product::delete_product( $main_product->get_id() );
		WC_Helper_Product::delete_product( $related_product1->get_id() );
		WC_Helper_Product::delete_product( $related_product2->get_id() );
		WC_Helper_Product::delete_product( $related_product3->get_id() );
	}

	/**
	 * @testdox Product category list preserves WordPress term-list order by default.
	 */
	public function test_wc_get_product_category_list_preserves_default_order(): void {
		$suffix               = wp_unique_id();
		$root                 = wp_insert_term( 'Default Root ' . $suffix, 'product_cat' );
		$child                = wp_insert_term( 'Default Child ' . $suffix, 'product_cat', array( 'parent' => $root['term_id'] ) );
		$product              = WC_Helper_Product::create_simple_product();
		$get_the_terms_filter = null;
		$sanitize_key_filter  = null;

		try {
			wp_set_object_terms( $product->get_id(), array( $root['term_id'], $child['term_id'] ), 'product_cat' );

			$expected            = get_the_term_list( $product->get_id(), 'product_cat', 'Before ', ' > ', ' After' );
			$sanitize_key_calls  = 0;
			$sanitize_key_filter = static function ( $sanitized_key, $key ) use ( &$sanitize_key_calls ) {
				if ( '' === $key ) {
					++$sanitize_key_calls;

					return 'breadcrumb';
				}

				return $sanitized_key;
			};
			add_filter( 'sanitize_key', $sanitize_key_filter, 10, 2 );

			$actual = wc_get_product_category_list( $product->get_id(), ' > ', 'Before ', ' After' );

			remove_filter( 'sanitize_key', $sanitize_key_filter );
			$sanitize_key_filter = null;

			$this->assertSame( $expected, $actual, 'Default helper output should remain identical to WordPress term-list output.' );
			$this->assertSame( 0, $sanitize_key_calls, 'Default helper calls should not introduce ordering-mode sanitization hooks.' );

			$get_the_terms_calls  = 0;
			$get_the_terms_filter = static function ( $terms, $post_id, $taxonomy ) use ( &$get_the_terms_calls, $product ) {
				if ( $product->get_id() === $post_id && 'product_cat' === $taxonomy ) {
					++$get_the_terms_calls;
				}

				return $terms;
			};
			add_filter( 'get_the_terms', $get_the_terms_filter, 10, 3 );

			$this->setExpectedIncorrectUsage( 'wc_get_product_category_list' );

			$this->assertSame(
				$expected,
				wc_get_product_category_list( $product->get_id(), ' > ', 'Before ', ' After', 'hierarchy' ),
				'Unsupported ordering modes should fall back to WordPress term-list output.'
			);
			$this->assertSame( 1, $get_the_terms_calls, 'Unsupported ordering modes should invoke the WordPress term-list path only once.' );
		} finally {
			if ( null !== $sanitize_key_filter ) {
				remove_filter( 'sanitize_key', $sanitize_key_filter );
			}
			if ( null !== $get_the_terms_filter ) {
				remove_filter( 'get_the_terms', $get_the_terms_filter );
			}
			WC_Helper_Product::delete_product( $product->get_id() );
			wp_delete_term( $child['term_id'], 'product_cat' );
			wp_delete_term( $root['term_id'], 'product_cat' );
		}
	}

	/**
	 * @testdox Product category list warns about an unsupported ordering mode and still falls back to WordPress order.
	 */
	public function test_wc_get_product_category_list_warns_on_unsupported_orderby(): void {
		$suffix   = wp_unique_id();
		$category = wp_insert_term( 'Unsupported orderby ' . $suffix, 'product_cat' );
		$product  = WC_Helper_Product::create_simple_product();

		try {
			wp_set_object_terms( $product->get_id(), array( $category['term_id'] ), 'product_cat' );

			$expected = get_the_term_list( $product->get_id(), 'product_cat', '', ', ', '' );

			$this->setExpectedIncorrectUsage( 'wc_get_product_category_list' );

			$this->assertSame(
				$expected,
				wc_get_product_category_list( $product->get_id(), ', ', '', '', 'menu_order' ),
				'An unsupported ordering mode should still return the WordPress term-list output.'
			);
		} finally {
			WC_Helper_Product::delete_product( $product->get_id() );
			wp_delete_term( $category['term_id'], 'product_cat' );
		}
	}

	/**
	 * @testdox Product category list can render assigned terms in breadcrumb order.
	 */
	public function test_wc_get_product_category_list_can_render_breadcrumb_order(): void {
		$suffix    = wp_unique_id();
		$root_name = 'Breadcrumb Root ' . $suffix;
		$mid_name  = 'Breadcrumb Mid ' . $suffix;
		$leaf_name = 'Breadcrumb Leaf ' . $suffix;
		$root      = wp_insert_term( $root_name, 'product_cat' );
		$mid       = wp_insert_term( $mid_name, 'product_cat', array( 'parent' => $root['term_id'] ) );
		$leaf      = wp_insert_term( $leaf_name, 'product_cat', array( 'parent' => $mid['term_id'] ) );
		$product   = WC_Helper_Product::create_simple_product();

		try {
			wp_set_object_terms( $product->get_id(), array( $leaf['term_id'], $root['term_id'], $mid['term_id'] ), 'product_cat' );

			$actual = wp_strip_all_tags( wc_get_product_category_list( $product->get_id(), ' > ', '', '', 'breadcrumb' ) );

			$this->assertSame( "{$root_name} > {$mid_name} > {$leaf_name}", $actual );
		} finally {
			WC_Helper_Product::delete_product( $product->get_id() );
			wp_delete_term( $leaf['term_id'], 'product_cat' );
			wp_delete_term( $mid['term_id'], 'product_cat' );
			wp_delete_term( $root['term_id'], 'product_cat' );
		}
	}

	/**
	 * @testdox Product category list breadcrumb ordering follows depth even when term IDs run against it.
	 */
	public function test_wc_get_product_category_list_breadcrumb_order_ignores_term_id_sequence(): void {
		$suffix    = wp_unique_id();
		$leaf_name = 'Aaa descendant ' . $suffix;
		$root_name = 'Zzz ancestor ' . $suffix;

		/*
		 * Create the descendant first, so its term ID is lower than its eventual ancestor's, and
		 * name it so that alphabetical order also runs against the hierarchy.
		 */
		$leaf    = wp_insert_term( $leaf_name, 'product_cat' );
		$root    = wp_insert_term( $root_name, 'product_cat' );
		$product = WC_Helper_Product::create_simple_product();

		try {
			wp_update_term( $leaf['term_id'], 'product_cat', array( 'parent' => $root['term_id'] ) );

			$this->assertGreaterThan(
				$leaf['term_id'],
				$root['term_id'],
				'The fixture is only meaningful while the ancestor carries the higher term ID.'
			);

			wp_set_object_terms( $product->get_id(), array( $leaf['term_id'], $root['term_id'] ), 'product_cat' );

			$actual = wp_strip_all_tags( wc_get_product_category_list( $product->get_id(), ' > ', '', '', 'breadcrumb' ) );

			$this->assertSame( "{$root_name} > {$leaf_name}", $actual );
		} finally {
			WC_Helper_Product::delete_product( $product->get_id() );
			wp_delete_term( $leaf['term_id'], 'product_cat' );
			wp_delete_term( $root['term_id'], 'product_cat' );
		}
	}

	/**
	 * @testdox Product category list breadcrumb ordering batches ancestor loading and ignores the order of ancestors it does not render.
	 */
	public function test_wc_get_product_category_list_breadcrumb_order_batches_ancestors(): void {
		global $wpdb;

		$suffix              = wp_unique_id();
		$first_branch_name   = 'First branch child ' . $suffix;
		$second_sibling_name = 'Zulu ordered first ' . $suffix;
		$third_sibling_name  = 'Alpha ordered second ' . $suffix;
		$first_root          = wp_insert_term( 'First branch root ' . $suffix, 'product_cat' );
		$second_root         = wp_insert_term( 'Second branch root ' . $suffix, 'product_cat' );
		$first_middle        = wp_insert_term( 'First branch middle ' . $suffix, 'product_cat', array( 'parent' => $first_root['term_id'] ) );
		$second_middle       = wp_insert_term( 'Second branch middle ' . $suffix, 'product_cat', array( 'parent' => $second_root['term_id'] ) );
		$first_branch        = wp_insert_term( $first_branch_name, 'product_cat', array( 'parent' => $first_middle['term_id'] ) );
		$third_sibling       = wp_insert_term( $third_sibling_name, 'product_cat', array( 'parent' => $second_middle['term_id'] ) );
		$second_sibling      = wp_insert_term( $second_sibling_name, 'product_cat', array( 'parent' => $second_middle['term_id'] ) );
		$product             = WC_Helper_Product::create_simple_product();
		$query_filter        = null;

		try {
			update_term_meta( $first_root['term_id'], 'order', 2 );
			update_term_meta( $second_root['term_id'], 'order', 1 );
			update_term_meta( $second_sibling['term_id'], 'order', 1 );
			update_term_meta( $third_sibling['term_id'], 'order', 2 );
			wp_set_object_terms( $product->get_id(), array( $first_branch['term_id'], $third_sibling['term_id'], $second_sibling['term_id'] ), 'product_cat' );

			get_the_terms( $product->get_id(), 'product_cat' );
			$ancestor_ids = array( $first_root['term_id'], $second_root['term_id'], $first_middle['term_id'], $second_middle['term_id'] );

			foreach ( $ancestor_ids as $ancestor_id ) {
				wp_cache_delete( $ancestor_id, 'terms' );
				wp_cache_delete( $ancestor_id, 'term_meta' );
			}

			// Match ancestor term IDs in captured SQL. Word boundaries avoid partial matches within larger numeric IDs.
			$ancestor_id_pattern = '/\b(?:' . implode( '|', $ancestor_ids ) . ')\b/';

			/*
			 * Match the term-join clause regardless of run-length whitespace. WP_Term_Query builds
			 * "FROM $wpdb->terms AS t $join" and $join already opens with a space, so core emits
			 * this clause with both one and two spaces. A literal single-space match silently sees
			 * only half of the queries.
			 */
			$term_join_pattern = '/FROM\s+' . preg_quote( $wpdb->terms, '/' ) . '\s+AS\s+t\s+INNER\s+JOIN\s+' . preg_quote( $wpdb->term_taxonomy, '/' ) . '\s+AS\s+tt/i';

			$ancestor_term_queries = 0;
			$ancestor_meta_queries = 0;
			$query_filter          = static function ( $query ) use ( &$ancestor_meta_queries, &$ancestor_term_queries, $ancestor_id_pattern, $term_join_pattern, $wpdb ) {
				if ( preg_match( $ancestor_id_pattern, $query ) ) {
					if ( preg_match( $term_join_pattern, $query ) ) {
						++$ancestor_term_queries;
					} elseif ( false !== strpos( $query, "FROM {$wpdb->termmeta}" ) ) {
						++$ancestor_meta_queries;
					}
				}

				return $query;
			};
			add_filter( 'query', $query_filter );

			try {
				$actual = wp_strip_all_tags( wc_get_product_category_list( $product->get_id(), ' > ', '', '', 'breadcrumb' ) );
			} finally {
				remove_filter( 'query', $query_filter );
				$query_filter = null;
			}

			/*
			 * The two roots carry deliberately contradictory `order` metas (2 and 1) and neither is
			 * assigned to the product. The rendered order must come from the three assigned terms'
			 * own `order` metas -- 0, 1 and 2 -- and never from their invisible roots.
			 */
			$this->assertSame( "{$first_branch_name} > {$second_sibling_name} > {$third_sibling_name}", $actual );

			/*
			 * Two ancestor levels, and core spends two queries on each: one WP_Term_Query for the
			 * ids and one _prime_term_caches() follow-up for the rows. The bound is what guards
			 * against regressing to one query per ancestor; it is deliberately not assertSame(),
			 * because the 2-per-level split is a core implementation detail.
			 */
			$this->assertLessThanOrEqual( 4, $ancestor_term_queries, 'Ancestor terms should be loaded in a bounded number of batched queries per hierarchy level, not one query per ancestor.' );
			$this->assertLessThanOrEqual( 1, $ancestor_meta_queries, 'Ancestor metadata should be primed in one query.' );
		} finally {
			if ( null !== $query_filter ) {
				remove_filter( 'query', $query_filter );
			}
			WC_Helper_Product::delete_product( $product->get_id() );
			wp_delete_term( $second_sibling['term_id'], 'product_cat' );
			wp_delete_term( $third_sibling['term_id'], 'product_cat' );
			wp_delete_term( $first_branch['term_id'], 'product_cat' );
			wp_delete_term( $second_middle['term_id'], 'product_cat' );
			wp_delete_term( $first_middle['term_id'], 'product_cat' );
			wp_delete_term( $second_root['term_id'], 'product_cat' );
			wp_delete_term( $first_root['term_id'], 'product_cat' );
		}
	}

	/**
	 * @testdox Product category list breadcrumb ordering never ranks by a category it does not render.
	 */
	public function test_wc_get_product_category_list_breadcrumb_order_ignores_unrendered_ancestors(): void {
		$suffix       = wp_unique_id();
		$parent_name  = 'Apparel ' . $suffix;
		$child_name   = 'Zebra shirts ' . $suffix;
		$sibling_name = 'Books ' . $suffix;
		$parent       = wp_insert_term( $parent_name, 'product_cat' );
		$child        = wp_insert_term( $child_name, 'product_cat', array( 'parent' => $parent['term_id'] ) );
		$sibling      = wp_insert_term( $sibling_name, 'product_cat' );
		$product      = WC_Helper_Product::create_simple_product();

		try {
			// Only the leaf and the independent root are assigned; the parent is never rendered.
			wp_set_object_terms( $product->get_id(), array( $child['term_id'], $sibling['term_id'] ), 'product_cat' );

			$actual = wp_strip_all_tags( wc_get_product_category_list( $product->get_id(), ' > ', '', '', 'breadcrumb' ) );

			$this->assertSame(
				"{$sibling_name} > {$child_name}",
				$actual,
				'An ancestor that is not rendered must not decide the order of the categories that are.'
			);
		} finally {
			WC_Helper_Product::delete_product( $product->get_id() );
			wp_delete_term( $child['term_id'], 'product_cat' );
			wp_delete_term( $sibling['term_id'], 'product_cat' );
			wp_delete_term( $parent['term_id'], 'product_cat' );
		}
	}

	/**
	 * @testdox Product category list breadcrumb ordering keeps ancestor loading flat as branch count grows.
	 */
	public function test_wc_get_product_category_list_breadcrumb_order_keeps_ancestor_queries_flat(): void {
		global $wpdb;

		$suffix       = wp_unique_id();
		$branch_count = 6;
		$created      = array();
		$leaves       = array();
		$ancestor_ids = array();
		$product      = WC_Helper_Product::create_simple_product();
		$query_filter = null;

		/*
		 * Six branches rather than the two the sibling test uses. Ancestor loading is batched per
		 * hierarchy level, so its cost tracks depth and stays flat as branches are added; resolving
		 * one ancestor at a time instead would grow with the branch count. Two branches is too few
		 * to tell those apart -- both cost the same there.
		 */
		try {
			for ( $branch = 0; $branch < $branch_count; $branch++ ) {
				$parent = 0;

				for ( $depth = 0; $depth < 3; $depth++ ) {
					$term      = wp_insert_term( "Flat b{$branch} d{$depth} {$suffix}", 'product_cat', $parent ? array( 'parent' => $parent ) : array() );
					$created[] = $term['term_id'];
					$parent    = $term['term_id'];

					if ( $depth < 2 ) {
						$ancestor_ids[] = $term['term_id'];
					}
				}

				$leaves[] = $parent;
			}

			wp_set_object_terms( $product->get_id(), $leaves, 'product_cat' );
			get_the_terms( $product->get_id(), 'product_cat' );

			foreach ( $ancestor_ids as $ancestor_id ) {
				wp_cache_delete( $ancestor_id, 'terms' );
				wp_cache_delete( $ancestor_id, 'term_meta' );
			}

			$ancestor_id_pattern = '/\b(?:' . implode( '|', $ancestor_ids ) . ')\b/';
			$term_join_pattern   = '/FROM\s+' . preg_quote( $wpdb->terms, '/' ) . '\s+AS\s+t\s+INNER\s+JOIN\s+' . preg_quote( $wpdb->term_taxonomy, '/' ) . '\s+AS\s+tt/i';

			$ancestor_term_queries = 0;
			$query_filter          = static function ( $query ) use ( &$ancestor_term_queries, $ancestor_id_pattern, $term_join_pattern ) {
				if ( preg_match( $ancestor_id_pattern, $query ) && preg_match( $term_join_pattern, $query ) ) {
					++$ancestor_term_queries;
				}

				return $query;
			};
			add_filter( 'query', $query_filter );

			try {
				wc_get_product_category_list( $product->get_id(), ' > ', '', '', 'breadcrumb' );
			} finally {
				remove_filter( 'query', $query_filter );
				$query_filter = null;
			}

			/*
			 * Two levels of ancestors, two queries each. The bound is per level, so it must not move
			 * when branch_count does -- that is the property under test.
			 */
			$this->assertLessThanOrEqual(
				4,
				$ancestor_term_queries,
				"Ancestor loading should stay flat across {$branch_count} branches, not grow with them."
			);
		} finally {
			if ( null !== $query_filter ) {
				remove_filter( 'query', $query_filter );
			}
			WC_Helper_Product::delete_product( $product->get_id() );

			foreach ( array_reverse( $created ) as $term_id ) {
				wp_delete_term( $term_id, 'product_cat' );
			}
		}
	}

	/**
	 * @testdox Product category list breadcrumb ordering survives a get_terms filter returning an unexpected shape.
	 */
	public function test_wc_get_product_category_list_breadcrumb_order_survives_hostile_get_terms_filter(): void {
		$suffix     = wp_unique_id();
		$root_name  = 'Hostile root ' . $suffix;
		$leaf_name  = 'Hostile alpha leaf ' . $suffix;
		$other_name = 'Hostile zulu other ' . $suffix;
		$root       = wp_insert_term( $root_name, 'product_cat' );
		$leaf       = wp_insert_term( $leaf_name, 'product_cat', array( 'parent' => $root['term_id'] ) );
		$other      = wp_insert_term( $other_name, 'product_cat' );
		$product    = WC_Helper_Product::create_simple_product();

		/*
		 * The prime asks for 'id=>parent'. Any plugin filtering get_terms can hand back WP_Term
		 * objects instead, and casting one of those to int yields 1, which would build a fabricated
		 * parent chain out of whatever term happens to hold that ID.
		 */
		$object_shape_filter = static function ( $terms, $taxonomies, $args ) use ( $root ) {
			if ( 'id=>parent' === ( $args['fields'] ?? '' ) ) {
				return array( get_term( $root['term_id'], 'product_cat' ) );
			}

			return $terms;
		};

		try {
			/*
			 * The root is deliberately left unassigned. Priming only runs for ancestors that are not
			 * themselves rendered, so assigning it would leave the frontier empty and skip the code
			 * under test entirely.
			 */
			wp_set_object_terms( $product->get_id(), array( $leaf['term_id'], $other['term_id'] ), 'product_cat' );

			$expected = wp_strip_all_tags( wc_get_product_category_list( $product->get_id(), ' > ', '', '', 'breadcrumb' ) );

			add_filter( 'get_terms', $object_shape_filter, 10, 3 );

			$diagnostics = array();
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_set_error_handler -- The object-to-int conversion notice is what is being asserted on; PHPUnit would convert it to an exception and hide the ordering result.
			set_error_handler(
				static function ( $errno, $errstr ) use ( &$diagnostics ) {
					$diagnostics[] = $errstr;

					return true;
				},
				E_DEPRECATED | E_WARNING | E_NOTICE
			);

			try {
				$actual = wp_strip_all_tags( wc_get_product_category_list( $product->get_id(), ' > ', '', '', 'breadcrumb' ) );
			} finally {
				restore_error_handler();
				remove_filter( 'get_terms', $object_shape_filter, 10 );
			}

			$this->assertSame( "{$leaf_name} > {$other_name}", $expected );
			$this->assertSame( $expected, $actual, 'A get_terms filter returning term objects must not reorder the rendered categories.' );
			$this->assertSame( array(), $diagnostics, 'Priming should reject an unexpected get_terms shape rather than trying to convert it.' );
		} finally {
			WC_Helper_Product::delete_product( $product->get_id() );
			wp_delete_term( $leaf['term_id'], 'product_cat' );
			wp_delete_term( $other['term_id'], 'product_cat' );
			wp_delete_term( $root['term_id'], 'product_cat' );
		}
	}

	/**
	 * @testdox Product category list breadcrumb ordering terminates when a get_terms filter never stops yielding ancestors.
	 */
	public function test_wc_get_product_category_list_breadcrumb_order_terminates_on_endless_ancestry(): void {
		$suffix       = wp_unique_id();
		$root_name    = 'Endless root ' . $suffix;
		$leaf_name    = 'Endless alpha leaf ' . $suffix;
		$other_name   = 'Endless zulu other ' . $suffix;
		$root         = wp_insert_term( $root_name, 'product_cat' );
		$leaf         = wp_insert_term( $leaf_name, 'product_cat', array( 'parent' => $root['term_id'] ) );
		$other        = wp_insert_term( $other_name, 'product_cat' );
		$product      = WC_Helper_Product::create_simple_product();
		$rounds       = 0;
		$next_term_id = 900000;

		/*
		 * Yields a parent nobody has seen on every round, so the frontier never empties on its own,
		 * then gives up well past the implementation's ceiling. The give-up point is what keeps a
		 * removed ceiling a clean assertion failure instead of a hung test run.
		 */
		$endless_filter = static function ( $terms, $taxonomies, $args ) use ( &$rounds, &$next_term_id ) {
			if ( 'id=>parent' !== ( $args['fields'] ?? '' ) || $rounds >= 500 ) {
				return $terms;
			}

			++$rounds;
			++$next_term_id;

			return array( $next_term_id => $next_term_id + 1 );
		};

		try {
			// As above, the root stays unassigned so the walk has an ancestor to chase at all.
			wp_set_object_terms( $product->get_id(), array( $leaf['term_id'], $other['term_id'] ), 'product_cat' );
			add_filter( 'get_terms', $endless_filter, 10, 3 );

			try {
				$actual = wp_strip_all_tags( wc_get_product_category_list( $product->get_id(), ' > ', '', '', 'breadcrumb' ) );
			} finally {
				remove_filter( 'get_terms', $endless_filter, 10 );
			}

			$this->assertGreaterThan( 0, $rounds, 'The fixture is only meaningful while the ancestor walk actually runs.' );
			$this->assertLessThanOrEqual( 100, $rounds, 'The ancestor walk must be bounded rather than trusting a filter to end it.' );
			$this->assertSame( "{$leaf_name} > {$other_name}", $actual, 'A bounded walk should still render the rendered terms in order.' );
		} finally {
			WC_Helper_Product::delete_product( $product->get_id() );
			wp_delete_term( $leaf['term_id'], 'product_cat' );
			wp_delete_term( $other['term_id'], 'product_cat' );
			wp_delete_term( $root['term_id'], 'product_cat' );
		}
	}

	/**
	 * @testdox Product category list breadcrumb ordering honors filtered category order after priming term metadata.
	 */
	public function test_wc_get_product_category_list_breadcrumb_order_honors_filtered_category_order(): void {
		$suffix = wp_unique_id();

		/*
		 * Named so the expected order runs against alphabetical order, and against the stored `order`
		 * metas below. Without that, an implementation that ignored category order entirely would
		 * still produce this exact string from the name tiebreak, and the test could not fail.
		 */
		$filtered_first_name   = 'Zulu filtered first ' . $suffix;
		$filtered_second_name  = 'Alpha filtered second ' . $suffix;
		$filtered_first        = wp_insert_term( $filtered_first_name, 'product_cat' );
		$filtered_second       = wp_insert_term( $filtered_second_name, 'product_cat' );
		$product               = WC_Helper_Product::create_simple_product();
		$metadata_cache_filter = static function () {
			return true;
		};
		$term_metadata_filter  = static function ( $value, $object_id, $meta_key ) use ( $filtered_first, $filtered_second ) {
			if ( 'order' !== $meta_key ) {
				return $value;
			}

			if ( $filtered_first['term_id'] === $object_id ) {
				return 1;
			}

			return $filtered_second['term_id'] === $object_id ? 2 : $value;
		};

		try {
			update_term_meta( $filtered_first['term_id'], 'order', 2 );
			update_term_meta( $filtered_second['term_id'], 'order', 1 );
			wp_set_object_terms( $product->get_id(), array( $filtered_second['term_id'], $filtered_first['term_id'] ), 'product_cat' );
			add_filter( 'get_term_metadata', $term_metadata_filter, 10, 3 );

			$actual = wp_strip_all_tags( wc_get_product_category_list( $product->get_id(), ' > ', '', '', 'breadcrumb' ) );

			$this->assertSame( "{$filtered_first_name} > {$filtered_second_name}", $actual );

			wp_cache_delete( $filtered_first['term_id'], 'term_meta' );
			wp_cache_delete( $filtered_second['term_id'], 'term_meta' );
			add_filter( 'update_term_metadata_cache', $metadata_cache_filter );

			$actual = wp_strip_all_tags( wc_get_product_category_list( $product->get_id(), ' > ', '', '', 'breadcrumb' ) );

			$this->assertSame( "{$filtered_first_name} > {$filtered_second_name}", $actual, 'Filtered category order should remain authoritative when cache priming is short-circuited.' );
		} finally {
			remove_filter( 'update_term_metadata_cache', $metadata_cache_filter );
			remove_filter( 'get_term_metadata', $term_metadata_filter );
			WC_Helper_Product::delete_product( $product->get_id() );
			wp_delete_term( $filtered_second['term_id'], 'product_cat' );
			wp_delete_term( $filtered_first['term_id'], 'product_cat' );
		}
	}

	/**
	 * @testdox Product category list breadcrumb ordering reflects category order updates after term metadata is primed.
	 */
	public function test_wc_get_product_category_list_breadcrumb_order_reflects_category_order_updates(): void {
		$first_name  = 'Cache order first ' . wp_unique_id();
		$second_name = 'Cache order second ' . wp_unique_id();
		$first       = wp_insert_term( $first_name, 'product_cat' );
		$second      = wp_insert_term( $second_name, 'product_cat' );
		$product     = WC_Helper_Product::create_simple_product();

		try {
			update_term_meta( $first['term_id'], 'order', 1 );
			update_term_meta( $second['term_id'], 'order', 2 );
			wp_set_object_terms( $product->get_id(), array( $second['term_id'], $first['term_id'] ), 'product_cat' );

			$initial = wp_strip_all_tags( wc_get_product_category_list( $product->get_id(), ' > ', '', '', 'breadcrumb' ) );

			update_term_meta( $first['term_id'], 'order', 2 );
			update_term_meta( $second['term_id'], 'order', 1 );

			$updated = wp_strip_all_tags( wc_get_product_category_list( $product->get_id(), ' > ', '', '', 'breadcrumb' ) );

			$this->assertSame( "{$first_name} > {$second_name}", $initial );
			$this->assertSame( "{$second_name} > {$first_name}", $updated );
		} finally {
			WC_Helper_Product::delete_product( $product->get_id() );
			wp_delete_term( $second['term_id'], 'product_cat' );
			wp_delete_term( $first['term_id'], 'product_cat' );
		}
	}

	/**
	 * @testdox Product category list breadcrumb ordering treats invalid filtered category order as zero.
	 */
	public function test_wc_get_product_category_list_breadcrumb_order_handles_invalid_filtered_category_order(): void {
		$suffix               = wp_unique_id();
		$invalid_order_name   = 'Alpha invalid order ' . $suffix;
		$zero_order_name      = 'Zulu zero order ' . $suffix;
		$invalid_order        = wp_insert_term( $invalid_order_name, 'product_cat' );
		$zero_order           = wp_insert_term( $zero_order_name, 'product_cat' );
		$product              = WC_Helper_Product::create_simple_product();
		$term_metadata_filter = static function ( $value, $object_id, $meta_key ) use ( $invalid_order, $zero_order ) {
			if ( 'order' !== $meta_key ) {
				return $value;
			}

			if ( $invalid_order['term_id'] === $object_id ) {
				return new stdClass();
			}

			return $zero_order['term_id'] === $object_id ? 0 : $value;
		};

		try {
			wp_set_object_terms( $product->get_id(), array( $zero_order['term_id'], $invalid_order['term_id'] ), 'product_cat' );
			add_filter( 'get_term_metadata', $term_metadata_filter, 10, 3 );

			$actual = wp_strip_all_tags( wc_get_product_category_list( $product->get_id(), ' > ', '', '', 'breadcrumb' ) );

			$this->assertSame( "{$invalid_order_name} > {$zero_order_name}", $actual );
		} finally {
			remove_filter( 'get_term_metadata', $term_metadata_filter );
			WC_Helper_Product::delete_product( $product->get_id() );
			wp_delete_term( $zero_order['term_id'], 'product_cat' );
			wp_delete_term( $invalid_order['term_id'], 'product_cat' );
		}
	}

	/**
	 * @testdox Product category list breadcrumb ordering treats a term with a missing ancestor as a root term.
	 */
	public function test_wc_get_product_category_list_breadcrumb_order_handles_missing_ancestor(): void {
		$suffix           = wp_unique_id();
		$missing_root     = wp_insert_term( 'Missing root ' . $suffix, 'product_cat' );
		$orphan_name      = 'Zulu orphan ' . $suffix;
		$independent_name = 'Alpha independent ' . $suffix;
		$orphan           = wp_insert_term( $orphan_name, 'product_cat', array( 'parent' => $missing_root['term_id'] ) );
		$independent      = wp_insert_term( $independent_name, 'product_cat' );
		$product          = WC_Helper_Product::create_simple_product();
		$ancestors_filter = static function ( $ancestors, $object_id, $object_type ) use ( &$orphan ) {
			return 'product_cat' === $object_type && (int) $orphan['term_id'] === (int) $object_id ? array() : $ancestors;
		};

		try {
			update_term_meta( $independent['term_id'], 'order', 1 );
			update_term_meta( $orphan['term_id'], 'order', 2 );
			wp_set_object_terms( $product->get_id(), array( $orphan['term_id'], $independent['term_id'] ), 'product_cat' );

			$resolved = wp_strip_all_tags( wc_get_product_category_list( $product->get_id(), ' > ', '', '', 'breadcrumb' ) );

			add_filter( 'get_ancestors', $ancestors_filter, 10, 3 );

			$unresolved = wp_strip_all_tags( wc_get_product_category_list( $product->get_id(), ' > ', '', '', 'breadcrumb' ) );

			$this->assertSame( "{$independent_name} > {$orphan_name}", $resolved );
			$this->assertSame( $resolved, $unresolved, 'A term whose ancestry cannot be resolved should rank exactly as a root term does.' );
		} finally {
			remove_filter( 'get_ancestors', $ancestors_filter, 10 );
			WC_Helper_Product::delete_product( $product->get_id() );
			wp_delete_term( $orphan['term_id'], 'product_cat' );
			wp_delete_term( $independent['term_id'], 'product_cat' );
			wp_delete_term( $missing_root['term_id'], 'product_cat' );
		}
	}

	/**
	 * @testdox Product category list breadcrumb ordering terminates safely when ancestry is cyclic.
	 */
	public function test_wc_get_product_category_list_breadcrumb_order_handles_cyclic_ancestry(): void {
		$suffix           = wp_unique_id();
		$cyclic_root      = wp_insert_term( 'Cyclic root ' . $suffix, 'product_cat' );
		$cyclic_name      = 'Cyclic child ' . $suffix;
		$independent_name = 'Independent root ' . $suffix;
		$cyclic_child     = wp_insert_term( $cyclic_name, 'product_cat', array( 'parent' => $cyclic_root['term_id'] ) );
		$independent      = wp_insert_term( $independent_name, 'product_cat' );
		$product          = WC_Helper_Product::create_simple_product();
		$get_term_filter  = static function ( $term, $taxonomy ) use ( $cyclic_root, $cyclic_child ) {
			if ( 'product_cat' === $taxonomy && $term instanceof \WP_Term && (int) $cyclic_root['term_id'] === (int) $term->term_id ) {
				/*
				 * Clone before mutating: get_term() hands back the cached instance, and mutating it in
				 * place would corrupt the term cache for every later test in the run.
				 */
				$term         = clone $term;
				$term->parent = (int) $cyclic_child['term_id'];
			}

			return $term;
		};

		try {
			/*
			 * The invisible root is left at order 0 on purpose: if it still steered the sort, the
			 * cyclic child would render first.
			 */
			update_term_meta( $independent['term_id'], 'order', 1 );
			update_term_meta( $cyclic_child['term_id'], 'order', 3 );
			wp_set_object_terms( $product->get_id(), array( $cyclic_child['term_id'], $independent['term_id'] ), 'product_cat' );
			add_filter( 'get_term', $get_term_filter, 10, 2 );

			$actual = wp_strip_all_tags( wc_get_product_category_list( $product->get_id(), ' > ', '', '', 'breadcrumb' ) );

			$this->assertSame( "{$independent_name} > {$cyclic_name}", $actual );
		} finally {
			remove_filter( 'get_term', $get_term_filter, 10 );
			WC_Helper_Product::delete_product( $product->get_id() );
			wp_delete_term( $cyclic_child['term_id'], 'product_cat' );
			wp_delete_term( $independent['term_id'], 'product_cat' );
			wp_delete_term( $cyclic_root['term_id'], 'product_cat' );
		}
	}

	/**
	 * @testdox Product category list can render assigned terms alphabetically by name.
	 */
	public function test_wc_get_product_category_list_can_render_name_order(): void {
		$suffix            = wp_unique_id();
		$second_name       = 'Natural Category 2 ' . $suffix;
		$tenth_name        = 'Natural Category 10 ' . $suffix;
		$tenth             = wp_insert_term( $tenth_name, 'product_cat' );
		$second            = wp_insert_term( $second_name, 'product_cat' );
		$product           = WC_Helper_Product::create_simple_product();
		$term_links_filter = static function ( $links ) {
			return array_map( static fn( $link ) => 'Filtered ' . $link, $links );
		};

		try {
			update_term_meta( $second['term_id'], 'order', 2 );
			update_term_meta( $tenth['term_id'], 'order', 1 );
			wp_set_object_terms( $product->get_id(), array( $tenth['term_id'], $second['term_id'] ), 'product_cat' );
			add_filter( 'term_links-product_cat', $term_links_filter );

			$actual = wp_strip_all_tags( wc_get_product_category_list( $product->get_id(), ' > ', '', '', 'name' ) );

			$this->assertSame( "Filtered {$second_name} > Filtered {$tenth_name}", $actual );
		} finally {
			remove_filter( 'term_links-product_cat', $term_links_filter );
			WC_Helper_Product::delete_product( $product->get_id() );
			wp_delete_term( $second['term_id'], 'product_cat' );
			wp_delete_term( $tenth['term_id'], 'product_cat' );
		}
	}

	/**
	 * @testdox Product category list name ordering tolerates a term with no name.
	 */
	public function test_wc_get_product_category_list_name_order_tolerates_missing_names(): void {
		$suffix         = wp_unique_id();
		$named          = wp_insert_term( 'Named category ' . $suffix, 'product_cat' );
		$unnamed        = wp_insert_term( 'Unnamed category ' . $suffix, 'product_cat' );
		$product        = WC_Helper_Product::create_simple_product();
		$unnamed_filter = static function ( $terms ) use ( $unnamed ) {
			if ( ! is_array( $terms ) ) {
				return $terms;
			}

			/*
			 * Clone before mutating: these objects come from the term cache, and blanking a name in
			 * place would leak into every later test in the run.
			 */
			return array_map(
				static function ( $term ) use ( $unnamed ) {
					if ( $term instanceof \WP_Term && (int) $unnamed['term_id'] === (int) $term->term_id ) {
						$term       = clone $term;
						$term->name = null;
					}

					return $term;
				},
				$terms
			);
		};

		try {
			wp_set_object_terms( $product->get_id(), array( $named['term_id'], $unnamed['term_id'] ), 'product_cat' );
			add_filter( 'get_the_terms', $unnamed_filter, 99 );

			$comparison_diagnostics = array();

			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_set_error_handler -- Capturing the diagnostic is the assertion; PHPUnit would otherwise convert it to an exception and hide which operand it came from.
			set_error_handler(
				static function ( $errno, $errstr ) use ( &$comparison_diagnostics ) {
					if ( false !== stripos( $errstr, 'strnatcasecmp' ) ) {
						$comparison_diagnostics[] = $errstr;
					}

					return true;
				},
				E_DEPRECATED | E_WARNING | E_NOTICE
			);

			try {
				wc_get_product_category_list( $product->get_id(), ' > ', '', '', 'name' );
			} finally {
				restore_error_handler();
			}

			$this->assertSame(
				array(),
				$comparison_diagnostics,
				'Name ordering should not raise comparison diagnostics for a term with no name.'
			);
		} finally {
			remove_filter( 'get_the_terms', $unnamed_filter, 99 );
			WC_Helper_Product::delete_product( $product->get_id() );
			wp_delete_term( $named['term_id'], 'product_cat' );
			wp_delete_term( $unnamed['term_id'], 'product_cat' );
		}
	}

	/**
	 * @testdox Product category list ordered modes validate values returned by the term-links filter.
	 */
	public function test_wc_get_product_category_list_ordered_mode_validates_term_links_filter_result(): void {
		$suffix                    = wp_unique_id();
		$category                  = wp_insert_term( 'Filter result category ' . $suffix, 'product_cat' );
		$product                   = WC_Helper_Product::create_simple_product();
		$filter_error              = new WP_Error( 'category-link-filter-error' );
		$term_links_error_filter   = static function () use ( $filter_error ) {
			return $filter_error;
		};
		$term_links_invalid_filter = static function () {
			return 'invalid-filter-result';
		};

		try {
			wp_set_object_terms( $product->get_id(), array( $category['term_id'] ), 'product_cat' );
			add_filter( 'term_links-product_cat', $term_links_error_filter );

			$this->assertSame( $filter_error, wc_get_product_category_list( $product->get_id(), ', ', '', '', 'name' ) );

			remove_filter( 'term_links-product_cat', $term_links_error_filter );
			add_filter( 'term_links-product_cat', $term_links_invalid_filter );

			$actual = wc_get_product_category_list( $product->get_id(), ', ', '', '', 'name' );

			$this->assertFalse( $actual );
		} finally {
			remove_filter( 'term_links-product_cat', $term_links_error_filter );
			remove_filter( 'term_links-product_cat', $term_links_invalid_filter );
			WC_Helper_Product::delete_product( $product->get_id() );
			wp_delete_term( $category['term_id'], 'product_cat' );
		}
	}

	/**
	 * @testdox Product permalink should use deepest category, not the one with highest parent term ID.
	 */
	public function test_wc_product_post_type_link_uses_deepest_category() {
		/*
		 * Reproduce the bug from WOOPLUG-5957:
		 * Create categories "out of sequence" so term_ids don't match hierarchy depth.
		 * Per the issue: "Level 2 ID should be higher than all other levels."
		 *
		 * We create Level 2 LAST so it has the highest term_id. Then we update parent
		 * relationships. This means Level 3's parent (Level 2) has a higher term_id
		 * than Level 4's parent (Level 3).
		 *
		 * Old buggy code sorted by parent DESC, so it would select Level 3 (parent=Level 2
		 * with high term_id) instead of Level 4 (the actual deepest category).
		 */

		// Create Level 1 first (gets lowest term_id).
		$level1_term = wp_insert_term( 'Level 1', 'product_cat' );

		// Create Level 3 and Level 4 without parents initially.
		$level3_term = wp_insert_term( 'Level 3', 'product_cat' );
		$level4_term = wp_insert_term( 'Level 4 Deepest', 'product_cat' );

		// Create Level 2 LAST (gets highest term_id).
		$level2_term = wp_insert_term( 'Level 2', 'product_cat' );

		// Set up hierarchy: Level 1 > Level 2 > Level 3 > Level 4.
		wp_update_term( $level2_term['term_id'], 'product_cat', array( 'parent' => $level1_term['term_id'] ) );
		wp_update_term( $level3_term['term_id'], 'product_cat', array( 'parent' => $level2_term['term_id'] ) );
		wp_update_term( $level4_term['term_id'], 'product_cat', array( 'parent' => $level3_term['term_id'] ) );

		// Assign product to all categories.
		$product = WC_Helper_Product::create_simple_product();
		wp_set_object_terms(
			$product->get_id(),
			array(
				$level1_term['term_id'],
				$level2_term['term_id'],
				$level3_term['term_id'],
				$level4_term['term_id'],
			),
			'product_cat'
		);

		// Set up permalink structure to include product_cat.
		update_option( 'woocommerce_permalinks', array( 'product_base' => '/shop/%product_cat%' ) );
		$product_post = get_post( $product->get_id() );

		// Call wc_product_post_type_link directly to test the category selection.
		$permalink = wc_product_post_type_link( '/shop/%product_cat%/' . $product_post->post_name . '/', $product_post );

		// Get slugs for assertions.
		$level1_slug = get_term( $level1_term['term_id'], 'product_cat' )->slug;
		$level2_slug = get_term( $level2_term['term_id'], 'product_cat' )->slug;
		$level3_slug = get_term( $level3_term['term_id'], 'product_cat' )->slug;
		$level4_slug = get_term( $level4_term['term_id'], 'product_cat' )->slug;

		// The permalink should contain the full hierarchical path of the deepest category (level 4).
		// The old buggy code would select Level 3 (parent=Level 2 with high term_id) instead of Level 4.
		$expected_path = $level1_slug . '/' . $level2_slug . '/' . $level3_slug . '/' . $level4_slug;
		$this->assertStringContainsString(
			$expected_path,
			$permalink,
			'Permalink should contain the full path of the deepest category (level 4)'
		);

		// Clean up (delete children before parents).
		WC_Helper_Product::delete_product( $product->get_id() );
		wp_delete_term( $level4_term['term_id'], 'product_cat' );
		wp_delete_term( $level3_term['term_id'], 'product_cat' );
		wp_delete_term( $level2_term['term_id'], 'product_cat' );
		wp_delete_term( $level1_term['term_id'], 'product_cat' );
	}

	/**
	 * @testdox Product permalink uses first root category when product has only root-level categories.
	 */
	public function test_wc_product_post_type_link_with_only_root_categories() {
		// Create multiple root categories - first one (lowest term_id) should be selected.
		$root1_term = wp_insert_term( 'Root Category One', 'product_cat' );
		$root2_term = wp_insert_term( 'Root Category Two', 'product_cat' );
		$root3_term = wp_insert_term( 'Root Category Three', 'product_cat' );

		$product = WC_Helper_Product::create_simple_product();
		wp_set_object_terms(
			$product->get_id(),
			array( $root1_term['term_id'], $root2_term['term_id'], $root3_term['term_id'] ),
			'product_cat'
		);

		update_option( 'woocommerce_permalinks', array( 'product_base' => '/shop/%product_cat%' ) );
		$product_post = get_post( $product->get_id() );

		$permalink = wc_product_post_type_link( '/shop/%product_cat%/' . $product_post->post_name . '/', $product_post );

		// First root category (lowest term_id) should be used.
		$root1_slug = get_term( $root1_term['term_id'], 'product_cat' )->slug;
		$this->assertStringContainsString(
			'/' . $root1_slug . '/',
			$permalink,
			'Permalink should contain the first root category slug'
		);

		WC_Helper_Product::delete_product( $product->get_id() );
		wp_delete_term( $root3_term['term_id'], 'product_cat' );
		wp_delete_term( $root2_term['term_id'], 'product_cat' );
		wp_delete_term( $root1_term['term_id'], 'product_cat' );
	}

	/**
	 * @testdox Product permalink skips category processing when permalink structure has no category placeholders.
	 */
	public function test_wc_product_post_type_link_skips_category_when_not_in_permalink() {
		// Create a category to ensure it's not fetched unnecessarily.
		$category_term = wp_insert_term( 'Test Category', 'product_cat' );

		$product = WC_Helper_Product::create_simple_product();
		wp_set_object_terms( $product->get_id(), array( $category_term['term_id'] ), 'product_cat' );

		$product_post = get_post( $product->get_id() );

		// Test with %post_id% placeholder but no category placeholder.
		$permalink = wc_product_post_type_link( '/product/%post_id%/' . $product_post->post_name . '/', $product_post );

		$this->assertStringContainsString(
			'/product/' . $product->get_id() . '/',
			$permalink,
			'Permalink should have the post ID replaced'
		);
		$this->assertStringNotContainsString(
			'%post_id%',
			$permalink,
			'Permalink should not contain unreplaced %post_id% placeholder'
		);
		$this->assertStringNotContainsString(
			'%category%',
			$permalink,
			'Permalink should not contain %category% placeholder'
		);

		WC_Helper_Product::delete_product( $product->get_id() );
		wp_delete_term( $category_term['term_id'], 'product_cat' );
	}

	/**
	 * @testdox Product permalink handles non-sequential array keys from get_the_terms filters.
	 */
	public function test_wc_product_post_type_link_handles_non_sequential_term_array_keys() {
		// Create categories.
		$category1_term = wp_insert_term( 'Category One', 'product_cat' );
		$category2_term = wp_insert_term( 'Category Two', 'product_cat' );

		$product = WC_Helper_Product::create_simple_product();
		wp_set_object_terms(
			$product->get_id(),
			array( $category1_term['term_id'], $category2_term['term_id'] ),
			'product_cat'
		);

		$original_permalinks = get_option( 'woocommerce_permalinks' );
		$filter_callback     = null;

		try {
			update_option( 'woocommerce_permalinks', array( 'product_base' => '/shop/%product_cat%' ) );
			$product_post = get_post( $product->get_id() );

			// Simulate a plugin filter that removes a term without re-indexing the array.
			// This creates non-sequential keys (e.g., key 0 is removed, leaving only key 1).
			$filter_callback = function ( $terms, $post_id, $taxonomy ) use ( $category1_term, $product ) {
				if ( 'product_cat' !== $taxonomy || ! is_array( $terms ) || $post_id !== $product->get_id() ) {
					return $terms;
				}
				foreach ( $terms as $key => $term ) {
					if ( $term->term_id === $category1_term['term_id'] ) {
						unset( $terms[ $key ] );
						// Intentionally don't re-index.
						break;
					}
				}
				return $terms;
				// Returns array with non-sequential keys.
			};
			add_filter( 'get_the_terms', $filter_callback, 10, 3 );

			// This should not trigger PHP warnings about undefined array key 0.
			$permalink = wc_product_post_type_link( '/shop/%product_cat%/' . $product_post->post_name . '/', $product_post );

			// Should use the remaining category (Category Two).
			$category2_slug = get_term( $category2_term['term_id'], 'product_cat' )->slug;
			$this->assertStringContainsString(
				'/' . $category2_slug . '/',
				$permalink,
				'Permalink should contain the remaining category slug after filter removes one'
			);
		} finally {
			if ( null !== $filter_callback ) {
				remove_filter( 'get_the_terms', $filter_callback, 10 );
			}
			update_option( 'woocommerce_permalinks', $original_permalinks );
			WC_Helper_Product::delete_product( $product->get_id() );
			wp_delete_term( $category2_term['term_id'], 'product_cat' );
			wp_delete_term( $category1_term['term_id'], 'product_cat' );
		}
	}

	/**
	 * Helper to run wc_product_canonical_redirect() under wp_redirect guard.
	 *
	 * @param callable $callback The callback that triggers wc_product_canonical_redirect() when executed.
	 */
	private function with_wc_product_canonical_redirect_guard( callable $callback ) {
		$redirect_attempted = false;
		$redirected_to      = '';
		$redirect_status    = 0;

		$redirect_callback = function ( $location = '', $status = 302 ) use ( &$redirect_attempted, &$redirected_to, &$redirect_status ) {
			$redirect_attempted = true;
			$redirected_to      = $location;
			$redirect_status    = $status;
			throw new \WPAjaxDieContinueException();
		};

		add_filter( 'wp_redirect', $redirect_callback, 10, 2 );

		try {
			$callback();
		} catch ( \WPAjaxDieContinueException $e ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
			// Expected for redirects, or failure path to be asserted.
		} finally {
			remove_filter( 'wp_redirect', $redirect_callback, 10, 2 );
		}

		return array( $redirect_attempted, $redirected_to, $redirect_status );
	}

	/**
	 * @testdox Product canonical redirect is skipped for non-product requests.
	 */
	public function test_wc_product_canonical_redirect_skips_non_product_requests() {
		$this->go_to( home_url( '/' ) );

		list( $redirect_attempted ) = $this->with_wc_product_canonical_redirect_guard( 'wc_product_canonical_redirect' );

		$this->assertFalse( $redirect_attempted );
	}

	/**
	 * @testdox Product canonical redirect ignores invalid non-string product_cat query var.
	 */
	public function test_wc_product_canonical_redirect_ignores_invalid_product_cat_query_var() {
		$product = WC_Helper_Product::create_simple_product();
		$this->go_to( get_permalink( $product->get_id() ) );

		// Force non-string query var to cover the guard condition.
		set_query_var( 'product_cat', array() );

		list( $redirect_attempted ) = $this->with_wc_product_canonical_redirect_guard( 'wc_product_canonical_redirect' );

		$this->assertFalse( $redirect_attempted );

		WC_Helper_Product::delete_product( $product->get_id() );
	}

	/**
	 * @testdox Product canonical redirect skips redirect when requested product_cat equals expected slug.
	 */
	public function test_wc_product_canonical_redirect_ignores_matching_category_slug() {
		$category = wp_insert_term( 'Matching Category', 'product_cat' );
		$product  = WC_Helper_Product::create_simple_product();
		wp_set_object_terms( $product->get_id(), (int) $category['term_id'], 'product_cat' );
		$product->save();

		$this->go_to( add_query_arg( 'product_cat', get_term( $category['term_id'], 'product_cat' )->slug, get_permalink( $product->get_id() ) ) );

		list( $redirect_attempted ) = $this->with_wc_product_canonical_redirect_guard( 'wc_product_canonical_redirect' );

		$this->assertFalse( $redirect_attempted );

		WC_Helper_Product::delete_product( $product->get_id() );
		wp_delete_term( $category['term_id'], 'product_cat' );
	}

	/**
	 * @testdox Product canonical redirect sends 301 when requested category slug differs from expected.
	 */
	public function test_wc_product_canonical_redirect_redirects_when_category_slug_mismatch() {
		$category = wp_insert_term( 'Redirect Category', 'product_cat' );
		$product  = WC_Helper_Product::create_simple_product();
		wp_set_object_terms( $product->get_id(), (int) $category['term_id'], 'product_cat' );
		$product->save();

		$query_args = array(
			'product_cat' => 'wrong-slug',
			'foo'         => 'bar',
		);

		$this->go_to( add_query_arg( $query_args, get_permalink( $product->get_id() ) ) );

		list( $redirect_attempted, $redirected_to, $redirected_code ) = $this->with_wc_product_canonical_redirect_guard( 'wc_product_canonical_redirect' );

		$this->assertTrue( $redirect_attempted );
		$this->assertSame( 301, $redirected_code );
		$this->assertStringContainsString( wc_get_product( $product->get_id() )->get_permalink(), $redirected_to );
		$this->assertStringContainsString( 'foo=bar', $redirected_to );

		WC_Helper_Product::delete_product( $product->get_id() );
		wp_delete_term( $category['term_id'], 'product_cat' );
	}

	/**
	 * @testdox Product canonical redirect ignores empty product_cat query value.
	 */
	public function test_wc_product_canonical_redirect_ignores_empty_product_cat_slug() {
		$product = WC_Helper_Product::create_simple_product();
		$this->go_to( add_query_arg( 'product_cat', '', get_permalink( $product->get_id() ) ) );

		list( $redirect_attempted ) = $this->with_wc_product_canonical_redirect_guard( 'wc_product_canonical_redirect' );

		$this->assertFalse( $redirect_attempted );

		WC_Helper_Product::delete_product( $product->get_id() );
	}

	/**
	 * @testdox Product canonical redirect skips when global wp_rewrite is not WP_Rewrite.
	 */
	public function test_wc_product_canonical_redirect_skips_when_wp_rewrite_not_valid() {
		global $wp_rewrite;

		$product = WC_Helper_Product::create_simple_product();
		$this->go_to( get_permalink( $product->get_id() ) );

		$old_wp_rewrite = $wp_rewrite;
		$wp_rewrite     = null; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

		try {
			list( $redirect_attempted ) = $this->with_wc_product_canonical_redirect_guard( 'wc_product_canonical_redirect' );

			$this->assertFalse( $redirect_attempted );
		} finally {
			$wp_rewrite = $old_wp_rewrite; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		}

		WC_Helper_Product::delete_product( $product->get_id() );
	}

	/**
	 * @testdox Variable add-to-cart attaches a pristine gallery snapshot to the variation script.
	 */
	public function test_woocommerce_variable_add_to_cart_attaches_gallery_snapshot() {
		$inline_js = $this->capture_variable_add_to_cart_inline_js();

		$this->assertStringContainsString( 'wc_variation_gallery_defaults', $inline_js );

		// Extract the JSON snapshot from the inline JS and verify it contains gallery markup.
		preg_match( '/\)\[\d+\]\s*=\s*("(?:\\\\.|[^"])*");/', $inline_js, $matches );
		$this->assertNotEmpty( $matches, 'Inline JS should expose a JSON-encoded snapshot.' );
		$decoded_snapshot = json_decode( $matches[1] );
		$this->assertIsString( $decoded_snapshot );
		$this->assertStringContainsString( 'woocommerce-product-gallery', $decoded_snapshot );
	}

	/**
	 * Render the variable add-to-cart template and return the inline JS
	 * attached to the variation script.
	 */
	private function capture_variable_add_to_cart_inline_js(): string {
		$product = WC_Helper_Product::create_variation_product();

		WC_Frontend_Scripts::load_scripts();

		$wp_scripts = wp_scripts();
		if ( isset( $wp_scripts->registered['wc-add-to-cart-variation'] ) ) {
			unset( $wp_scripts->registered['wc-add-to-cart-variation']->extra['before'] );
		}

		$previous_product   = $GLOBALS['product'] ?? null;
		$GLOBALS['product'] = $product; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

		ob_start();
		woocommerce_variable_add_to_cart();
		ob_end_clean();

		$before_data = $wp_scripts->registered['wc-add-to-cart-variation']->extra['before'] ?? array();

		$GLOBALS['product'] = $previous_product; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		WC_Helper_Product::delete_product( $product->get_id() );

		return implode( "\n", (array) $before_data );
	}

	/**
	 * @testdox Does not attach a featured image via SKU match when the current user cannot edit the product.
	 */
	public function test_wc_product_attach_featured_image_requires_edit_product_capability(): void {
		update_option( 'woocommerce_product_match_featured_image_by_sku', 'yes' );

		$sku     = 'TEST-SKU-ATTACH-' . wp_generate_password( 8, false );
		$product = WC_Helper_Product::create_simple_product();
		$product->set_sku( $sku );
		$product->set_image_id( '' );
		$product->save();

		$attachment_id = wp_insert_attachment(
			array(
				'post_title'     => $sku,
				'post_status'    => 'inherit',
				'post_mime_type' => 'image/jpeg',
			)
		);

		$subscriber_id = self::factory()->user->create( array( 'role' => 'author' ) );

		try {
			wp_set_current_user( $subscriber_id );

			wc_product_attach_featured_image( $attachment_id );

			$product = wc_get_product( $product->get_id() );
			$this->assertEmpty(
				$product->get_image_id(),
				'Featured image should not be attached when the user lacks edit_product capability'
			);
		} finally {
			wp_set_current_user( 0 );
			wp_delete_attachment( $attachment_id, true );
			WC_Helper_Product::delete_product( $product->get_id() );
			wp_delete_user( $subscriber_id );
			delete_option( 'woocommerce_product_match_featured_image_by_sku' );
		}
	}

	/**
	 * @testdox Every product is processed when the backlog spans more than one batch.
	 */
	public function test_wc_scheduled_sales_processes_every_product_across_batches(): void {
		// One more than the chunk size, so the backlog spans two batches and a product
		// dropped at the boundary would show up as an unprocessed price.
		$ids = array();
		for ( $i = 0; $i < 51; $i++ ) {
			$product = WC_Helper_Product::create_simple_product();
			$product->set_regular_price( 100 );
			$product->set_sale_price( 50 );
			$product->save();

			// A missed end: the window closed but _price still holds the sale price.
			update_post_meta( $product->get_id(), '_price', 50 );
			update_post_meta( $product->get_id(), '_sale_price_dates_from', time() - 300 );
			update_post_meta( $product->get_id(), '_sale_price_dates_to', time() - 100 );

			$ids[] = $product->get_id();
		}

		$payloads = array();
		add_action(
			'wc_before_products_ending_sales',
			function ( $hook_ids ) use ( &$payloads ) {
				$payloads[] = $hook_ids;
			}
		);

		wc_scheduled_sales();

		foreach ( $ids as $id ) {
			$this->assertEquals(
				100,
				get_post_meta( $id, '_price', true ),
				"Product {$id} was not processed, so a batch boundary dropped it."
			);
		}

		// Batching must not fragment the hook payload: extensions receive the whole set
		// once, exactly as they did before the loop was chunked.
		$this->assertCount( 1, $payloads, 'The ending hook must fire once, not once per batch.' );
		$this->assertCount( 51, $payloads[0], 'The hook payload must carry the whole backlog.' );
	}

	/**
	 * @testdox Priming happens per batch, not once for the whole backlog.
	 */
	public function test_wc_scheduled_sales_primes_one_batch_at_a_time(): void {
		// The point of the change: a backlog larger than one batch must never be primed in
		// full, or the run dies before its first save on the stores this exists for. Checked
		// from inside the run, because by the end every batch has been primed and released.
		$ids = array();

		for ( $i = 0; $i < 51; $i++ ) {
			$product = WC_Helper_Product::create_simple_product();
			$product->set_regular_price( 100 );
			$product->set_sale_price( 50 );
			$product->save();
			update_post_meta( $product->get_id(), '_price', 50 );
			update_post_meta( $product->get_id(), '_sale_price_dates_from', time() - 300 );
			update_post_meta( $product->get_id(), '_sale_price_dates_to', time() - 100 );
			$ids[] = $product->get_id();
		}

		$queued = array();
		add_action(
			'wc_before_products_ending_sales',
			static function ( $product_ids ) use ( &$queued ) {
				$queued = $product_ids;
			}
		);

		// Sampled on the first save, while the run is still inside its first batch.
		$last_primed_during_first_save = null;
		add_action(
			'woocommerce_update_product',
			static function () use ( &$queued, &$last_primed_during_first_save ) {
				if ( null !== $last_primed_during_first_save || ! $queued ) {
					return;
				}

				$last_primed_during_first_save = false !== wp_cache_get( (int) end( $queued ), 'posts' );
			}
		);

		// Creating the fixtures warmed every one of them, which a real cron request would
		// not have. Without this the probe reads leftover fixture state, not the priming.
		wp_cache_flush();

		wc_scheduled_sales();

		$this->assertCount( 51, $queued, 'Fixture precondition: the backlog must exceed one batch.' );
		$this->assertFalse(
			$last_primed_during_first_save,
			'The last product in the queue must not be primed while the first batch is still processing.'
		);
	}

	/**
	 * @testdox An external object cache keeps the persistent-capable groups out of the release.
	 */
	public function test_wc_scheduled_sales_leaves_shared_groups_alone_on_an_external_cache(): void {
		// products and term-queries are persistent-capable, so the loop releases them only
		// when the cache lives in this request. Under an external cache they must survive,
		// or the run evicts entries other requests are using.
		$product = WC_Helper_Product::create_simple_product();
		$product->set_regular_price( 100 );
		$product->set_sale_price( 50 );
		$product->save();
		update_post_meta( $product->get_id(), '_price', 50 );
		update_post_meta( $product->get_id(), '_sale_price_dates_from', time() - 300 );
		update_post_meta( $product->get_id(), '_sale_price_dates_to', time() - 100 );

		wp_cache_set( 'sentinel', 'keep me', 'products' );
		wp_cache_set( 'sentinel', 'keep me', 'term-queries' );

		// Cast: with no drop-in loaded nothing ever assigns the global, so the previous
		// value is null, and wp_using_ext_object_cache( null ) is a read rather than a
		// write. Restoring that verbatim would leave every later test on this process
		// believing an external cache is in use.
		$was_external = (bool) wp_using_ext_object_cache( true );

		try {
			wc_scheduled_sales();
		} finally {
			wp_using_ext_object_cache( $was_external );
		}

		$this->assertSame(
			'keep me',
			wp_cache_get( 'sentinel', 'products' ),
			'The products group must survive when an external object cache is in use.'
		);
		$this->assertSame(
			'keep me',
			wp_cache_get( 'sentinel', 'term-queries' ),
			'The term-queries group must survive when an external object cache is in use.'
		);
		$this->assertEquals(
			100,
			get_post_meta( $product->get_id(), '_price', true ),
			'The product must still settle with the shared groups left alone.'
		);
	}

	/**
	 * @testdox A request-local cache has the flushed groups released after the run.
	 */
	public function test_wc_scheduled_sales_releases_the_flushed_groups_on_a_request_local_cache(): void {
		// The mirror of the external-cache test: on the default object cache the loop must
		// flush products, term-queries and product_objects, or the relief the batching
		// exists for never happens. Nothing else pins those three calls.
		if ( ! wp_cache_supports( 'flush_group' ) || wp_using_ext_object_cache() ) {
			$this->markTestSkipped( 'Requires a request-local object cache with flush_group support.' );
		}

		$product = WC_Helper_Product::create_simple_product();
		$product->set_regular_price( 100 );
		$product->set_sale_price( 50 );
		$product->save();
		update_post_meta( $product->get_id(), '_price', 50 );
		update_post_meta( $product->get_id(), '_sale_price_dates_from', time() - 300 );
		update_post_meta( $product->get_id(), '_sale_price_dates_to', time() - 100 );

		wp_cache_set( 'sentinel', 'release me', 'products' );
		wp_cache_set( 'sentinel', 'release me', 'term-queries' );
		wp_cache_set( 'sentinel', 'release me', 'product_objects' );

		wc_scheduled_sales();

		$this->assertFalse(
			wp_cache_get( 'sentinel', 'products' ),
			'The products group must be flushed when the cache is request-local.'
		);
		$this->assertFalse(
			wp_cache_get( 'sentinel', 'term-queries' ),
			'The term-queries group must be flushed when the cache is request-local.'
		);
		$this->assertFalse(
			wp_cache_get( 'sentinel', 'product_objects' ),
			'The product_objects group must be flushed after each batch.'
		);
		$this->assertEquals(
			100,
			get_post_meta( $product->get_id(), '_price', true ),
			'Fixture precondition: the product should have been processed.'
		);
	}

	/**
	 * @testdox A batch holding both a product and a variation leaves no term relationship cached.
	 */
	public function test_wc_scheduled_sales_releases_term_caches_for_a_mixed_batch(): void {
		// _prime_post_caches() resolves the batch's post types, then caches every ID against
		// the union of their taxonomies, writing an empty entry where an ID has no terms in
		// one. A variation therefore gets the product-only groups too, and releasing each ID
		// under only its own type would strand exactly those.
		$parent = new WC_Product_Variable();
		$parent->set_name( 'Mixed batch parent' );
		$parent->set_regular_price( 100 );
		$parent->set_sale_price( 50 );
		$parent->save();

		$variation = new WC_Product_Variation();
		$variation->set_parent_id( $parent->get_id() );
		$variation->set_regular_price( 100 );
		$variation->set_sale_price( 50 );
		$variation->save();

		foreach ( array( $parent->get_id(), $variation->get_id() ) as $id ) {
			update_post_meta( $id, '_price', 50 );
			update_post_meta( $id, '_sale_price_dates_from', time() - 300 );
			update_post_meta( $id, '_sale_price_dates_to', time() - 100 );
		}

		$taxonomies = array_unique(
			array_merge( get_object_taxonomies( 'product' ), get_object_taxonomies( 'product_variation' ) )
		);

		// Precondition: without this the assertions below pass just as well against a run
		// that never primed anything.
		wp_cache_flush();
		_prime_post_caches( array( $parent->get_id(), $variation->get_id() ) );
		$this->assertNotFalse(
			wp_cache_get( $variation->get_id(), 'product_cat_relationships' ),
			'Fixture precondition: priming must cache the product-only groups for the variation.'
		);

		wc_scheduled_sales();

		foreach ( array( $parent->get_id(), $variation->get_id() ) as $id ) {
			foreach ( $taxonomies as $taxonomy ) {
				$this->assertFalse(
					wp_cache_get( $id, "{$taxonomy}_relationships" ),
					"Post {$id} should hold no {$taxonomy} relationship cache after the run."
				);
			}
		}
	}

	/**
	 * @testdox Releasing a batch does not evict cache entries belonging to other posts.
	 */
	public function test_wc_scheduled_sales_leaves_unrelated_post_caches_intact(): void {
		// The loop shares `posts` and `post_meta` with every other post type and every
		// other job in the same WP-Cron request, so it releases its own IDs rather than
		// flushing those groups whole. A page primed before the run must survive it.
		$page_id = self::factory()->post->create( array( 'post_type' => 'page' ) );
		update_post_meta( $page_id, '_unrelated', 'keep me' );
		get_post( $page_id );
		get_post_meta( $page_id );
		$this->assertNotFalse(
			wp_cache_get( $page_id, 'posts' ),
			'Fixture precondition: the page should be primed before the cron runs.'
		);
		$this->assertNotFalse(
			wp_cache_get( $page_id, 'post_meta' ),
			'Fixture precondition: the page meta should be primed before the cron runs.'
		);

		$product = WC_Helper_Product::create_simple_product();
		$product->set_regular_price( 100 );
		$product->set_sale_price( 50 );
		$product->save();
		update_post_meta( $product->get_id(), '_price', 50 );
		update_post_meta( $product->get_id(), '_sale_price_dates_from', time() - 300 );
		update_post_meta( $product->get_id(), '_sale_price_dates_to', time() - 100 );

		wc_scheduled_sales();

		// Assert the release before anything below re-primes the product: get_post_meta()
		// in the processing check would repopulate post_meta and mask a missing release.
		$this->assertFalse(
			wp_cache_get( $product->get_id(), 'posts' ),
			'The batch did not release its own post cache entry.'
		);
		$this->assertFalse(
			wp_cache_get( $product->get_id(), 'post_meta' ),
			'The batch did not release its own post meta cache entry.'
		);

		$this->assertEquals(
			100,
			get_post_meta( $product->get_id(), '_price', true ),
			'Fixture precondition: the product should have been processed.'
		);
		$this->assertNotFalse(
			wp_cache_get( $page_id, 'posts' ),
			'The cron released an unrelated post from the shared posts cache.'
		);
		$this->assertNotFalse(
			wp_cache_get( $page_id, 'post_meta' ),
			'The cron released unrelated meta from the shared post_meta cache.'
		);
	}

	/**
	 * @testdox Ids a replaced data store returns in any form wc_get_product() accepts still settle.
	 */
	public function test_wc_scheduled_sales_settles_ids_in_every_form_wc_get_product_accepts(): void {
		// The loop resolves each row rather than screening it, because wc_get_product()
		// accepts more than a canonical decimal string and these all named a real product
		// before the batching went in. A row dropped here settles nothing, says nothing, and
		// is picked up and dropped again on every later run.
		$ids = array();

		for ( $i = 0; $i < 6; $i++ ) {
			$product = WC_Helper_Product::create_simple_product();
			$product->set_regular_price( 100 );
			$product->set_sale_price( 50 );
			$product->save();
			update_post_meta( $product->get_id(), '_price', 50 );
			update_post_meta( $product->get_id(), '_sale_price_dates_from', time() - 300 );
			update_post_meta( $product->get_id(), '_sale_price_dates_to', time() - 100 );
			$ids[] = $product->get_id();
		}

		// Zero padded, leading space, signed, decimal string, float, and a WP_Post, which
		// get_product_id() reads through ->ID.
		$rows = array(
			'0' . $ids[0],
			' ' . $ids[1],
			'+' . $ids[2],
			$ids[3] . '.0',
			(float) $ids[4],
			get_post( $ids[5] ),
		);

		$store = new class( $rows ) extends WC_Product_Data_Store_CPT {
			/**
			 * Rows to report from the ending-sales query.
			 *
			 * @var array
			 */
			private $rows;

			/**
			 * Constructor.
			 *
			 * @param array $rows Rows to report.
			 */
			public function __construct( $rows ) {
				$this->rows = $rows;
			}

			/**
			 * Report the rows in the forms a replaced store might return them.
			 *
			 * @return array
			 */
			public function get_ending_sales() {
				return $this->rows;
			}
		};

		add_filter( 'woocommerce_product_data_store', fn() => $store );

		wc_scheduled_sales();

		foreach ( $ids as $index => $id ) {
			$this->assertEquals(
				100,
				get_post_meta( $id, '_price', true ),
				"Product {$id}, supplied as " . wp_json_encode( $rows[ $index ] ) . ', should have settled.'
			);
		}
	}

	/**
	 * @testdox A data store returning malformed IDs does not fatal the run or evict unrelated posts.
	 */
	public function test_wc_scheduled_sales_survives_malformed_ids_from_a_replaced_data_store(): void {
		// woocommerce_product_data_store accepts an object, and nothing enforces the int[]
		// the query methods document. A malformed row must not end a run that has already
		// saved work, and must not take an unrelated post's cache down with it.
		$product = WC_Helper_Product::create_simple_product();
		$product->set_regular_price( 100 );
		$product->set_sale_price( 50 );
		$product->save();
		update_post_meta( $product->get_id(), '_price', 50 );
		update_post_meta( $product->get_id(), '_sale_price_dates_from', time() - 300 );
		update_post_meta( $product->get_id(), '_sale_price_dates_to', time() - 100 );

		// A cast would turn "<id>abc" into this page's own id, so priming it here makes a
		// looser release predicate show up as a real eviction rather than a harmless no-op.
		$decoy_id  = self::factory()->post->create( array( 'post_type' => 'page' ) );
		$malformed = $decoy_id . 'abc';
		get_post( $decoy_id );
		$this->assertNotFalse(
			wp_cache_get( $decoy_id, 'posts' ),
			'Fixture precondition: the decoy page should be primed before the cron runs.'
		);

		$store = new class( $product->get_id(), $malformed ) extends WC_Product_Data_Store_CPT {
			/**
			 * Real product id to report alongside the malformed rows.
			 *
			 * @var int
			 */
			private $real_id;

			/**
			 * Malformed row that casts onto an unrelated post id.
			 *
			 * @var string
			 */
			private $malformed;

			/**
			 * Constructor.
			 *
			 * @param int    $real_id   Real product id.
			 * @param string $malformed Malformed row.
			 */
			public function __construct( $real_id, $malformed ) {
				$this->real_id   = $real_id;
				$this->malformed = $malformed;
			}

			/**
			 * Return a mix of one real id and rows that violate the int[] contract.
			 *
			 * @return array
			 */
			public function get_ending_sales() {
				// Objects are resolved rather than rejected now, on the same terms
				// wc_get_product() accepts, so one here would name a real product instead of
				// exercising the malformed path this fixture is for.
				return array( $this->real_id, array( 9 ), true, $this->malformed, null );
			}
		};

		add_filter( 'woocommerce_product_data_store', fn() => $store );
		WC_Data_Store::load( 'product' );

		// No setExpectedIncorrectUsage here on purpose: the malformed rows are screened out
		// before priming, so core never sees them and never reports the usage. Adding the
		// expectation back would fail, which is the point.

		wc_scheduled_sales();

		$this->assertEquals(
			100,
			get_post_meta( $product->get_id(), '_price', true ),
			'A malformed id stopped the run before the real product settled.'
		);

		$this->assertNotFalse(
			wp_cache_get( $decoy_id, 'posts' ),
			"The release cast '{$malformed}' onto post {$decoy_id} and evicted an unrelated page."
		);
	}

	/**
	 * @testdox An id the data store returns twice in one batch is processed once.
	 */
	public function test_wc_scheduled_sales_processes_a_duplicated_id_once(): void {
		// The sales queries join postmeta without a row-identity constraint, so a product
		// carrying two rows for one of the joined keys comes back twice. Before batching
		// that meant two saves; the batch now de-duplicates its IDs, and this pins it.
		$product = WC_Helper_Product::create_simple_product();
		$product->set_regular_price( 100 );
		$product->set_sale_price( 50 );
		$product->save();
		update_post_meta( $product->get_id(), '_price', 50 );
		update_post_meta( $product->get_id(), '_sale_price_dates_from', time() - 300 );
		update_post_meta( $product->get_id(), '_sale_price_dates_to', time() - 100 );

		$store = new class( $product->get_id() ) extends WC_Product_Data_Store_CPT {
			/**
			 * Product id to report twice.
			 *
			 * @var int
			 */
			private $id;

			/**
			 * Constructor.
			 *
			 * @param int $id Product id to report twice.
			 */
			public function __construct( $id ) {
				$this->id = $id;
			}

			/**
			 * Report the same product as both an int and the string $wpdb->get_col() returns.
			 *
			 * @return array
			 */
			public function get_ending_sales() {
				return array( $this->id, (string) $this->id );
			}
		};

		add_filter( 'woocommerce_product_data_store', fn() => $store );

		$saves = 0;
		add_action(
			'woocommerce_update_product',
			static function ( $updated_id ) use ( $product, &$saves ) {
				if ( (int) $updated_id === $product->get_id() ) {
					++$saves;
				}
			}
		);

		wc_scheduled_sales();

		$this->assertSame( 1, $saves, 'A duplicated id must settle with a single save, not one per row.' );
		$this->assertEquals(
			100,
			get_post_meta( $product->get_id(), '_price', true ),
			'Fixture precondition: the product should have been processed.'
		);
	}
}
