<?php
/**
 * Tests for discounting a shopper's own stale stock holds.
 *
 * Intended location:
 *   plugins/woocommerce/tests/php/src/Checkout/Helpers/ReserveStockOwnHoldsTest.php
 *
 * The contract under test, in order of importance:
 *
 *   1. A hold belonging to ANOTHER shopper always blocks, however old it is.
 *      That is the oversell invariant and every other case is a refinement of it.
 *   2. The shopper's OWN hold blocks inside the grace window and is discounted
 *      after it.
 *   3. Ownership never comes from anything the shopper can type.
 *   4. With no session — admin, WP-CLI, cron — nothing is discounted and the
 *      current user is never consulted.
 *   5. The woocommerce_query_for_reserved_stock signature is unchanged.
 *
 * Holds are written straight into wc_reserved_stock rather than through
 * reserve_stock_for_order() so that `timestamp` can be aged exactly, and so that
 * a hold can be created without also entering the session list. The one test
 * that exercises the session list calls wc_reserve_stock_for_order() for real.
 */

declare(strict_types=1);

namespace Automattic\WooCommerce\Tests\Checkout\Helpers;

use Automattic\WooCommerce\Enums\OrderStatus;
use WC_Customer;
use WC_Helper_Order;
use WC_Helper_Product;
use WC_Product_Simple;
use WC_Session_Handler;
use WC_Unit_Test_Case;

/**
 * Class ReserveStockOwnHoldsTest.
 *
 * Exercises the grace window that stops a shopper being blocked by their own
 * stale, unpaid stock hold. The full contract is in the file docblock above.
 */
class ReserveStockOwnHoldsTest extends WC_Unit_Test_Case {

	/**
	 * How long the reservation itself lasts, well beyond any grace window used here.
	 */
	private const HOLD_MINUTES = 2880;

	/**
	 * The session the bootstrap set up, put back in tearDown.
	 *
	 * @var \WC_Session|null
	 */
	private $original_session;

	/**
	 * The customer the bootstrap set up, put back in tearDown.
	 *
	 * @var WC_Customer|null
	 */
	private $original_customer;

	/**
	 * $_COOKIE as it was before the test, put back in tearDown.
	 *
	 * @var array
	 */
	private $original_cookies;

	/**
	 * Set up a shopper session and a store that manages stock.
	 */
	public function setUp(): void {
		parent::setUp();

		update_option( 'woocommerce_manage_stock', 'yes' );
		update_option( 'woocommerce_schema_version', 430 );
		update_option( 'woocommerce_hold_stock_minutes', self::HOLD_MINUTES );

		$this->original_session  = WC()->session;
		$this->original_customer = WC()->customer;

		// Earlier suites can leave a valid session cookie in $_COOKIE (the test
		// framework never resets it), and WC_Session_Handler::init() would then
		// give every session in these tests that cookie's customer id instead of
		// minting distinct ones.
		$this->original_cookies = $_COOKIE;

		/** This filter is documented in includes/class-wc-session-handler.php */
		unset( $_COOKIE[ (string) apply_filters( 'woocommerce_cookie', 'wp_woocommerce_session_' . COOKIEHASH ) ] );

		WC()->session = new WC_Session_Handler();
		WC()->session->init();
	}

	/**
	 * Leave no session, user or reservation behind.
	 *
	 * The session and customer singletons are restored rather than nulled: this
	 * suite runs in one process, and a null WC()->session fatals later classes.
	 */
	public function tearDown(): void {
		global $wpdb;

		$wpdb->query( "DELETE FROM {$wpdb->wc_reserved_stock}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		wp_set_current_user( 0 );

		$_COOKIE       = $this->original_cookies;
		WC()->session  = $this->original_session;
		WC()->customer = $this->original_customer;

		parent::tearDown();
	}

	// ---------------------------------------------------------------------
	// 1. The oversell invariant.
	// ---------------------------------------------------------------------

	/**
	 * Another shopper's hold blocks however stale it is.
	 */
	public function test_another_shoppers_stale_hold_still_blocks() {
		$product = $this->create_stock_managed_product( 1 );

		// Signed in, with a session, and owning nothing that holds stock.
		$this->create_signed_in_shopper();
		$other = $this->create_shopper_with_unpaid_order( $product );

		$this->hold_stock( $other, $product->get_id(), 1, 120 );

		$this->assertSame( 1, wc_get_held_stock_quantity( $product, 0 ) );
	}

	/**
	 * A stale hold on an order the shopper does not own is not discounted just
	 * because the shopper's own list happens to be non empty.
	 */
	public function test_own_stale_hold_does_not_release_another_shoppers_hold() {
		$product = $this->create_stock_managed_product( 2 );
		$shopper = $this->create_signed_in_shopper();
		$mine    = $this->create_unpaid_order_for( $shopper, $product );
		$theirs  = $this->create_shopper_with_unpaid_order( $product );

		$this->hold_stock( $mine, $product->get_id(), 1, 120 );
		$this->hold_stock( $theirs, $product->get_id(), 1, 120 );

		$this->assertSame( 1, wc_get_held_stock_quantity( $product, 0 ) );
	}

	// ---------------------------------------------------------------------
	// 2. The grace window.
	// ---------------------------------------------------------------------

	/**
	 * The shopper's own hold still blocks inside the default 10 minute window.
	 */
	public function test_own_fresh_hold_still_blocks() {
		$product = $this->create_stock_managed_product( 1 );
		$shopper = $this->create_signed_in_shopper();
		$order   = $this->create_unpaid_order_for( $shopper, $product );

		$this->hold_stock( $order, $product->get_id(), 1, 9 );

		$this->assertSame( 1, wc_get_held_stock_quantity( $product, 0 ) );
	}

	/**
	 * Past the window the shopper stops being blocked by their own attempt.
	 */
	public function test_own_stale_hold_is_discounted() {
		$product = $this->create_stock_managed_product( 1 );
		$shopper = $this->create_signed_in_shopper();
		$order   = $this->create_unpaid_order_for( $shopper, $product );

		$this->hold_stock( $order, $product->get_id(), 1, 11 );

		$this->assertSame( 0, wc_get_held_stock_quantity( $product, 0 ) );
	}

	/**
	 * A grace window of zero restores the behaviour from before this change.
	 */
	public function test_zero_grace_window_keeps_own_hold_blocking() {
		$product = $this->create_stock_managed_product( 1 );
		$shopper = $this->create_signed_in_shopper();
		$order   = $this->create_unpaid_order_for( $shopper, $product );

		$this->hold_stock( $order, $product->get_id(), 1, 2000 );

		add_filter( 'woocommerce_own_reserved_stock_grace_minutes', '__return_zero' );
		try {
			$held = wc_get_held_stock_quantity( $product, 0 );
		} finally {
			remove_filter( 'woocommerce_own_reserved_stock_grace_minutes', '__return_zero' );
		}

		$this->assertSame( 1, $held );
	}

	// ---------------------------------------------------------------------
	// 3. Ownership sources.
	// ---------------------------------------------------------------------

	/**
	 * The reported flow: a guest on the block checkout whose draft pointer has
	 * already moved on to a new order.
	 */
	public function test_session_list_covers_a_guest_whose_draft_pointer_moved_on() {
		$product = $this->create_stock_managed_product( 1 );
		$order   = $this->create_unpaid_order_for( 0, $product );

		// The real reservation path, which is what records the order in the session.
		wc_reserve_stock_for_order( wc_get_order( $order ) );
		$this->age_hold( $order, 11 );

		// The Store API mints a new draft and repoints the session at it.
		WC()->session->set( 'store_api_draft_order', $this->create_unpaid_order_for( 0, $product ) );

		$remembered = WC()->session->get( 'stock_holding_orders', array() );

		$this->assertContains( $order, $remembered['order_ids'] );
		$this->assertSame( 0, wc_get_held_stock_quantity( $product, 0 ) );
	}

	/**
	 * A session list that arrived from somebody else's session confers nothing.
	 *
	 * WC_Session_Handler hands one shopper's session data to another in two places:
	 * clone_session_data() copies everything but `customer` into a freshly minted
	 * session when a cart token is presented, and
	 * migrate_guest_session_to_user_session() moves a guest session onto whichever
	 * account next signs in on that browser. Both change the session customer id
	 * first, which is what the stamp detects.
	 */
	public function test_borrowed_session_list_confers_no_ownership() {
		$product = $this->create_stock_managed_product( 1 );
		$order   = $this->create_unpaid_order_for( 0, $product );

		wc_reserve_stock_for_order( wc_get_order( $order ) );
		$this->age_hold( $order, 11 );

		$borrowed = WC()->session->get( 'stock_holding_orders', array() );

		$this->assertContains( $order, $borrowed['order_ids'], 'Precondition: the owner recorded the hold.' );

		// A second shopper, whose session carries the first shopper's list verbatim
		// but under their own, different customer id.
		WC()->session = new WC_Session_Handler();
		WC()->session->init();
		WC()->session->set( 'stock_holding_orders', $borrowed );

		$this->assertNotSame(
			$borrowed['customer'],
			(string) WC()->session->get_customer_id(),
			'Precondition: the borrowing session has a different customer id.'
		);
		$this->assertSame( 1, wc_get_held_stock_quantity( $product, 0 ) );
	}

	/**
	 * A billing email is not an ownership claim.
	 *
	 * The attacker types the victim's address into their own checkout. The
	 * victim's in-flight hold must keep blocking.
	 */
	public function test_billing_email_confers_no_ownership() {
		$product      = $this->create_stock_managed_product( 1 );
		$victim       = $this->create_signed_in_shopper( 'victim@example.com' );
		$order        = $this->create_unpaid_order_for( $victim, $product );
		$victim_order = wc_get_order( $order );
		$victim_order->set_billing_email( 'victim@example.com' );
		$victim_order->save();

		$this->hold_stock( $order, $product->get_id(), 1, 120 );

		// Now a different, unrelated shopper claiming the same address.
		wp_set_current_user( 0 );
		WC()->session = new WC_Session_Handler();
		WC()->session->init();
		WC()->customer = new WC_Customer();
		WC()->customer->set_billing_email( 'victim@example.com' );

		$this->assertSame( 1, wc_get_held_stock_quantity( $product, 0 ) );
	}

	// ---------------------------------------------------------------------
	// 4. Contexts with no shopper.
	// ---------------------------------------------------------------------

	/**
	 * With no session — the admin, WP-CLI, cron — nothing is discounted, even for
	 * a signed in user who happens to have unpaid orders of their own.
	 */
	public function test_no_session_leaves_the_total_unchanged() {
		$product = $this->create_stock_managed_product( 1 );
		$shopper = $this->create_signed_in_shopper();
		$order   = $this->create_unpaid_order_for( $shopper, $product );

		$this->hold_stock( $order, $product->get_id(), 1, 120 );

		WC()->session = null;

		$this->assertSame( 1, wc_get_held_stock_quantity( $product, 0 ) );
	}

	// ---------------------------------------------------------------------
	// 5. The public filter contract.
	// ---------------------------------------------------------------------

	/**
	 * woocommerce_query_for_reserved_stock still receives a scalar order id, and
	 * still receives the complete core query.
	 */
	public function test_filter_signature_is_unchanged() {
		$product = $this->create_stock_managed_product( 1 );
		$shopper = $this->create_signed_in_shopper();
		$order   = $this->create_unpaid_order_for( $shopper, $product );

		$this->hold_stock( $order, $product->get_id(), 1, 120 );

		$seen = array();

		$capture = function ( $query, $product_id, $exclude_order_id ) use ( &$seen ) {
			$seen = array(
				'query'            => $query,
				'product_id'       => $product_id,
				'exclude_order_id' => $exclude_order_id,
			);

			return $query;
		};

		add_filter( 'woocommerce_query_for_reserved_stock', $capture, 10, 3 );
		try {
			wc_get_held_stock_quantity( $product, 4242 );
		} finally {
			remove_filter( 'woocommerce_query_for_reserved_stock', $capture, 10 );
		}

		$this->assertIsInt( $seen['exclude_order_id'] );
		$this->assertSame( 4242, $seen['exclude_order_id'] );
		$this->assertSame( $product->get_id(), $seen['product_id'] );
		$this->assertStringContainsString( 'AND NOT (', $seen['query'], 'The filter sees the complete core query.' );
	}

	// ---------------------------------------------------------------------
	// Helpers.
	// ---------------------------------------------------------------------

	/**
	 * A simple product managing a fixed quantity of stock.
	 *
	 * @param int $quantity Stock quantity.
	 * @return WC_Product_Simple
	 */
	private function create_stock_managed_product( int $quantity ): WC_Product_Simple {
		$product = WC_Helper_Product::create_simple_product();
		$product->set_manage_stock( true );
		$product->set_stock_quantity( $quantity );
		$product->save();

		return $product;
	}

	/**
	 * A signed in shopper. A fresh user per test, because the customer lookup is
	 * memoised for the life of the request.
	 *
	 * @param string $email Optional email.
	 * @return int User ID.
	 */
	private function create_signed_in_shopper( string $email = '' ): int {
		$user_id = wp_insert_user(
			array(
				'user_login' => uniqid( 'shopper_' ),
				'user_pass'  => wp_generate_password(),
				'user_email' => '' !== $email ? $email : uniqid( 'shopper_' ) . '@example.com',
				'role'       => 'customer',
			)
		);

		wp_set_current_user( $user_id );

		return (int) $user_id;
	}

	/**
	 * An unpaid order for a customer, holding one unit of the product.
	 *
	 * The line item is rebuilt rather than left as WC_Helper_Order::create_order()
	 * makes it, because that helper hard-codes a quantity of 4
	 * (class-wc-helper-order.php:73). A test that then reserves for real would ask
	 * for 4 units of a 1-unit product and throw ReserveStockException. Same approach
	 * as create_order_holding_stock() in class-wc-cart-test.php.
	 *
	 * The save() between removing and adding is required, not cosmetic: without it
	 * the replacement line item never reaches wc_order_items, so the order reloads
	 * with no items and reserves nothing.
	 *
	 * @param int               $customer_id Customer ID, 0 for a guest.
	 * @param WC_Product_Simple $product     Product to add.
	 * @return int Order ID.
	 */
	private function create_unpaid_order_for( int $customer_id, WC_Product_Simple $product ): int {
		$order = WC_Helper_Order::create_order( $customer_id );
		$order->remove_order_items();
		$order->save();
		$order->add_product( wc_get_product( $product->get_id() ), 1 );
		$order->set_status( OrderStatus::PENDING );
		$order->save();

		return $order->get_id();
	}

	/**
	 * An unpaid order belonging to somebody who is not the current user, and whose
	 * id never enters the current session.
	 *
	 * @param WC_Product_Simple $product Product to add.
	 * @return int Order ID.
	 */
	private function create_shopper_with_unpaid_order( WC_Product_Simple $product ): int {
		$current = get_current_user_id();
		$other   = $this->create_signed_in_shopper();
		$order   = $this->create_unpaid_order_for( $other, $product );

		wp_set_current_user( $current );

		return $order;
	}

	/**
	 * Write a hold directly, with an exact age.
	 *
	 * @param int $order_id    Order holding the stock.
	 * @param int $product_id  Product held.
	 * @param int $quantity    Quantity held.
	 * @param int $age_minutes How long ago the hold was placed.
	 */
	private function hold_stock( int $order_id, int $product_id, int $quantity, int $age_minutes ): void {
		global $wpdb;

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$wpdb->query(
			$wpdb->prepare(
				"
				INSERT INTO {$wpdb->wc_reserved_stock} ( `order_id`, `product_id`, `stock_quantity`, `timestamp`, `expires` )
				VALUES ( %d, %d, %d, ( NOW() - INTERVAL %d MINUTE ), ( NOW() + INTERVAL %d MINUTE ) )
				",
				$order_id,
				$product_id,
				$quantity,
				$age_minutes,
				self::HOLD_MINUTES
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}

	/**
	 * Backdate a hold that was created by the real reservation path.
	 *
	 * @param int $order_id    Order holding the stock.
	 * @param int $age_minutes How long ago the hold should read as placed.
	 */
	private function age_hold( int $order_id, int $age_minutes ): void {
		global $wpdb;

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$wpdb->query(
			$wpdb->prepare(
				"UPDATE {$wpdb->wc_reserved_stock} SET `timestamp` = ( NOW() - INTERVAL %d MINUTE ) WHERE `order_id` = %d",
				$age_minutes,
				$order_id
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}
}
