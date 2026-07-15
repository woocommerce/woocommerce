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
		remove_all_filters( 'woocommerce_get_payment_tokens_unscoped_limit' );
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
	 * @testdox Data store get_tokens should treat page zero as the first page.
	 */
	public function test_data_store_get_tokens_treats_page_zero_as_first_page(): void {
		$this->create_tokens_for_user( 3 );

		$data_store = WC_Data_Store::load( 'payment-token' );

		$this->assertCount(
			2,
			$data_store->get_tokens(
				array(
					'user_id' => $this->user_id,
					'limit'   => 2,
					'page'    => 0,
				)
			),
			'Page 0 must return the first page of results, not skip past it'
		);
	}

	/**
	 * @testdox get_customer_tokens should default the limit filter to DEFAULT_CUSTOMER_TOKENS_LIMIT.
	 */
	public function test_get_customer_tokens_defaults_to_the_documented_limit(): void {
		$received = null;
		add_filter(
			'woocommerce_get_customer_payment_tokens_limit',
			function ( $limit ) use ( &$received ) {
				$received = $limit;
				return $limit;
			}
		);

		WC_Payment_Tokens::get_customer_tokens( $this->user_id );

		// Pins the default without creating 100 tokens: a silent change to the constant fails here.
		$this->assertSame(
			WC_Payment_Tokens::DEFAULT_CUSTOMER_TOKENS_LIMIT,
			$received,
			'The customer token limit filter should receive DEFAULT_CUSTOMER_TOKENS_LIMIT as its default'
		);
		$this->assertSame( 100, WC_Payment_Tokens::DEFAULT_CUSTOMER_TOKENS_LIMIT );
	}

	/**
	 * @testdox Data store get_tokens should cap an unscoped query with the fallback ceiling.
	 */
	public function test_data_store_get_tokens_caps_unscoped_queries(): void {
		$received = null;
		add_filter(
			'woocommerce_get_payment_tokens_unscoped_limit',
			function ( $limit ) use ( &$received ) {
				$received = $limit;
				return 2;
			}
		);
		$this->create_tokens_for_user( 3 );

		$data_store = WC_Data_Store::load( 'payment-token' );

		// An unscoped query matches on the unindexed gateway_id/type columns, so it must not read the
		// whole table.
		$this->assertCount(
			2,
			$data_store->get_tokens( array() ),
			'An unscoped query should be capped by the fallback ceiling'
		);

		// Pins the ceiling's default without creating 500 tokens.
		$this->assertSame(
			WC_Payment_Token_Data_Store::DEFAULT_UNSCOPED_TOKENS_LIMIT,
			$received,
			'The unscoped limit filter should receive DEFAULT_UNSCOPED_TOKENS_LIMIT as its default'
		);
		$this->assertSame( 500, WC_Payment_Token_Data_Store::DEFAULT_UNSCOPED_TOKENS_LIMIT );
	}

	/**
	 * @testdox Data store get_tokens should not apply the unscoped ceiling to scoped queries.
	 */
	public function test_data_store_get_tokens_ceiling_does_not_apply_to_scoped_queries(): void {
		add_filter(
			'woocommerce_get_payment_tokens_unscoped_limit',
			function () {
				return 1;
			}
		);
		$this->create_tokens_for_user( 3 );

		$data_store = WC_Data_Store::load( 'payment-token' );
		$token_ids  = wp_list_pluck( $data_store->get_tokens( array( 'user_id' => $this->user_id ) ), 'token_id' );

		// The eraser and user-deletion cleanup scope by user_id and must stay unlimited.
		$this->assertCount(
			3,
			$data_store->get_tokens( array( 'user_id' => $this->user_id ) ),
			'A user_id-scoped query must not be capped by the unscoped ceiling'
		);
		$this->assertCount(
			3,
			$data_store->get_tokens( array( 'token_id' => $token_ids ) ),
			'A token_id-scoped query must not be capped by the unscoped ceiling'
		);
	}

	/**
	 * @testdox An explicit limit should override the unscoped ceiling.
	 */
	public function test_data_store_get_tokens_explicit_limit_overrides_unscoped_ceiling(): void {
		add_filter(
			'woocommerce_get_payment_tokens_unscoped_limit',
			function () {
				return 1;
			}
		);
		$this->create_tokens_for_user( 3 );

		$data_store = WC_Data_Store::load( 'payment-token' );

		$this->assertCount(
			3,
			$data_store->get_tokens( array( 'limit' => 10 ) ),
			'An explicit limit should take precedence over the unscoped fallback ceiling'
		);
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
