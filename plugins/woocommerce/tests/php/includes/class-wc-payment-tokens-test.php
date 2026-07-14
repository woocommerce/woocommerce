<?php
declare( strict_types = 1 );

/**
 * @package WooCommerce\Tests\PaymentTokens
 */

/**
 * Class WC_Payment_Tokens_Test.
 */
class WC_Payment_Tokens_Test extends WC_Unit_Test_Case {

	/**
	 * ID of the customer used in the tests.
	 *
	 * @var int
	 */
	private $user_id;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->user_id = self::factory()->user->create( array( 'role' => 'customer' ) );
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		remove_all_filters( 'woocommerce_get_customer_payment_tokens_limit' );
		remove_all_filters( 'pre_option_posts_per_page' );
		update_option( 'posts_per_page', 10 );
		parent::tearDown();
	}

	/**
	 * Create a number of credit card tokens for the test customer.
	 *
	 * @param int $count Number of tokens to create.
	 */
	private function create_tokens_for_user( int $count ): void {
		for ( $i = 0; $i < $count; $i++ ) {
			WC_Helper_Payment_Token::create_cc_token( $this->user_id );
		}
	}

	/**
	 * @testdox get_customer_tokens should not be limited by the posts_per_page option (issue 25025).
	 */
	public function test_get_customer_tokens_is_not_limited_by_posts_per_page(): void {
		update_option( 'posts_per_page', 1 );
		$this->create_tokens_for_user( 3 );

		$this->assertCount(
			3,
			WC_Payment_Tokens::get_customer_tokens( $this->user_id ),
			'All customer tokens should be returned regardless of the posts_per_page option'
		);
	}

	/**
	 * @testdox Data store get_tokens should not consult the posts_per_page option even when it is empty.
	 */
	public function test_data_store_get_tokens_ignores_empty_posts_per_page(): void {
		add_filter( 'pre_option_posts_per_page', '__return_empty_string' );
		$this->create_tokens_for_user( 3 );

		$data_store = WC_Data_Store::load( 'payment-token' );

		// Regression guard: the data store used to fall back to posts_per_page, and an empty value produced LIMIT 0 (zero rows).
		$this->assertCount(
			3,
			$data_store->get_tokens( array( 'user_id' => $this->user_id ) ),
			'Token queries must not be affected by the posts_per_page option'
		);
	}

	/**
	 * @testdox get_customer_tokens should respect the woocommerce_get_customer_payment_tokens_limit filter.
	 */
	public function test_get_customer_tokens_limit_filter_is_respected(): void {
		add_filter(
			'woocommerce_get_customer_payment_tokens_limit',
			function () {
				return 2;
			}
		);
		$this->create_tokens_for_user( 3 );

		$this->assertCount(
			2,
			WC_Payment_Tokens::get_customer_tokens( $this->user_id ),
			'The filter should still cap the number of returned tokens'
		);
	}

	/**
	 * @testdox Data store get_tokens should return all tokens when no limit argument is passed.
	 */
	public function test_data_store_get_tokens_returns_all_tokens_without_limit(): void {
		update_option( 'posts_per_page', 1 );
		$this->create_tokens_for_user( 3 );

		$data_store = WC_Data_Store::load( 'payment-token' );

		$this->assertCount(
			3,
			$data_store->get_tokens( array( 'user_id' => $this->user_id ) ),
			'Without an explicit limit, the data store should return all matching tokens (GDPR eraser and user-deletion cleanup rely on this)'
		);
	}

	/**
	 * @testdox Data store get_tokens should return all tokens when page is passed without a limit.
	 */
	public function test_data_store_get_tokens_ignores_page_without_limit(): void {
		$this->create_tokens_for_user( 3 );

		$data_store = WC_Data_Store::load( 'payment-token' );

		$this->assertCount(
			3,
			$data_store->get_tokens(
				array(
					'user_id' => $this->user_id,
					'page'    => 2,
				)
			),
			'A page argument without an explicit limit should not paginate the results'
		);
	}

	/**
	 * @testdox wc_delete_user_data should delete all payment tokens, not just the customer-facing limited subset.
	 */
	public function test_wc_delete_user_data_deletes_all_tokens(): void {
		add_filter(
			'woocommerce_get_customer_payment_tokens_limit',
			function () {
				return 1;
			}
		);
		$this->create_tokens_for_user( 3 );

		wc_delete_user_data( $this->user_id );

		global $wpdb;
		$remaining = (int) $wpdb->get_var(
			$wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}woocommerce_payment_tokens WHERE user_id = %d", $this->user_id )
		);
		$this->assertSame( 0, $remaining, 'Deleting a user must remove every saved payment token' );
	}

	/**
	 * @testdox Data store get_tokens should respect an explicit limit and page.
	 */
	public function test_data_store_get_tokens_respects_explicit_limit_and_page(): void {
		$this->create_tokens_for_user( 3 );

		$data_store = WC_Data_Store::load( 'payment-token' );

		$this->assertCount(
			2,
			$data_store->get_tokens(
				array(
					'user_id' => $this->user_id,
					'limit'   => 2,
				)
			),
			'An explicit limit should cap the results'
		);
		$this->assertCount(
			1,
			$data_store->get_tokens(
				array(
					'user_id' => $this->user_id,
					'limit'   => 2,
					'page'    => 2,
				)
			),
			'Pagination should return the remaining tokens on the second page'
		);
	}
}
