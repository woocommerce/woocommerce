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
		remove_all_filters( 'woocommerce_get_payment_tokens_page_size' );
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
	 * @testdox Data store get_tokens should paginate when page is passed without a limit.
	 */
	public function test_data_store_get_tokens_paginates_page_without_limit(): void {
		$this->create_tokens_for_user( 3 );

		$data_store = WC_Data_Store::load( 'payment-token' );

		// A page argument signals pagination intent: with 3 tokens and the default page size, the
		// second page must be empty so consumers looping until a short/empty page terminate.
		$this->assertCount(
			3,
			$data_store->get_tokens(
				array(
					'user_id' => $this->user_id,
					'page'    => 1,
				)
			),
			'The first page should contain all 3 tokens'
		);
		$this->assertCount(
			0,
			$data_store->get_tokens(
				array(
					'user_id' => $this->user_id,
					'page'    => 2,
				)
			),
			'A page argument without an explicit limit must still paginate the results'
		);
	}

	/**
	 * @testdox Data store get_tokens should size a page-without-limit query with the default page size.
	 */
	public function test_data_store_get_tokens_page_without_limit_uses_the_default_page_size(): void {
		$data_store = WC_Data_Store::load( 'payment-token' );

		// Pins the default page size against the emitted SQL, so it holds without creating 100 tokens.
		$query = $this->capture_token_query(
			function () use ( $data_store ) {
				$data_store->get_tokens(
					array(
						'user_id' => $this->user_id,
						'page'    => 2,
					)
				);
			}
		);

		$this->assertStringContainsString(
			'LIMIT 100, 100',
			$query,
			'A page without an explicit limit should be sized by DEFAULT_PAGE_SIZE and offset by it'
		);
	}

	/**
	 * @testdox Data store get_tokens should respect the woocommerce_get_payment_tokens_page_size filter.
	 */
	public function test_data_store_get_tokens_page_size_filter_is_respected(): void {
		$received = null;
		add_filter(
			'woocommerce_get_payment_tokens_page_size',
			function ( $page_size ) use ( &$received ) {
				$received = $page_size;
				return 2;
			}
		);
		$this->create_tokens_for_user( 3 );

		$data_store = WC_Data_Store::load( 'payment-token' );

		$this->assertCount(
			2,
			$data_store->get_tokens(
				array(
					'user_id' => $this->user_id,
					'page'    => 1,
				)
			),
			'The first page should be sized by the filtered page size'
		);
		$this->assertCount(
			1,
			$data_store->get_tokens(
				array(
					'user_id' => $this->user_id,
					'page'    => 2,
				)
			),
			'The second page should return the remaining tokens'
		);
		$this->assertSame(
			WC_Payment_Token_Data_Store::DEFAULT_PAGE_SIZE,
			$received,
			'The page size filter should receive DEFAULT_PAGE_SIZE as its default'
		);
	}

	/**
	 * Filter return values that must not be used as a row count.
	 *
	 * Each either fails to be a number of at least one, or is one that `absint()` turns into 0,
	 * which would empty the result set.
	 *
	 * @return array<string, array{0: mixed}>
	 */
	public function unusable_row_count_provider(): array {
		return array(
			'null'               => array( null ),
			'false'              => array( false ),
			'zero'               => array( 0 ),
			'fraction below 1'   => array( 0.4 ),
			'numeric string 0'   => array( '0.5' ),
			'negative one'       => array( -1 ),
			'negative'           => array( -5 ),
			'non-numeric'        => array( 'abc' ),
			'infinity'           => array( INF ),
			'negative infinity'  => array( -INF ),
			'not a number'       => array( NAN ),
			'overflowing float'  => array( 1e309 ),
			'overflowing string' => array( '1e309' ),
		);
	}

	/**
	 * @testdox Data store get_tokens should ignore a page size filter value that is not usable as a row count.
	 *
	 * @dataProvider unusable_row_count_provider
	 *
	 * @param mixed $filtered_value Value returned by the page size filter.
	 */
	public function test_data_store_get_tokens_ignores_a_page_size_below_one( $filtered_value ): void {
		add_filter(
			'woocommerce_get_payment_tokens_page_size',
			function () use ( $filtered_value ) {
				return $filtered_value;
			}
		);
		$this->create_tokens_for_user( 3 );

		$data_store = WC_Data_Store::load( 'payment-token' );

		// Asserting on the emitted SQL rather than a row count: absint() turns -5 into a page size
		// of 5, which returns the same 3 rows as the default would and hides the bug.
		$query = $this->capture_token_query(
			function () use ( $data_store ) {
				$data_store->get_tokens(
					array(
						'user_id' => $this->user_id,
						'page'    => 1,
					)
				);
			}
		);

		$this->assertStringContainsString(
			'LIMIT 0, ' . WC_Payment_Token_Data_Store::DEFAULT_PAGE_SIZE,
			$query,
			'A page size that is not a positive number should fall back to the default'
		);
	}

	/**
	 * @testdox Data store get_tokens should ignore an unscoped ceiling filter value that is not usable as a row count.
	 *
	 * @dataProvider unusable_row_count_provider
	 *
	 * @param mixed $filtered_value Value returned by the unscoped limit filter.
	 */
	public function test_data_store_get_tokens_ignores_an_unscoped_ceiling_below_one( $filtered_value ): void {
		add_filter(
			'woocommerce_get_payment_tokens_unscoped_limit',
			function () use ( $filtered_value ) {
				return $filtered_value;
			}
		);
		$this->create_tokens_for_user( 3 );

		$data_store = WC_Data_Store::load( 'payment-token' );

		// Asserting on the emitted SQL rather than a row count: absint() turns -5 into a ceiling of
		// 5, which returns the same 3 rows as the default would and hides the bug.
		$query = $this->capture_token_query(
			function () use ( $data_store ) {
				$data_store->get_tokens( array() );
			}
		);

		$this->assertStringContainsString(
			'LIMIT 0, ' . WC_Payment_Token_Data_Store::DEFAULT_UNSCOPED_TOKENS_LIMIT,
			$query,
			'A ceiling that is not a positive number should fall back to the default'
		);
	}

	/**
	 * @testdox Data store get_tokens should size an unscoped query passing page by the page size, not the unscoped ceiling.
	 */
	public function test_data_store_get_tokens_unscoped_query_with_page_uses_the_page_size(): void {
		add_filter(
			'woocommerce_get_payment_tokens_page_size',
			function () {
				return 2;
			}
		);
		add_filter(
			'woocommerce_get_payment_tokens_unscoped_limit',
			function () {
				return 1;
			}
		);
		$this->create_tokens_for_user( 3 );

		$data_store = WC_Data_Store::load( 'payment-token' );

		// The page tier is evaluated before the unscoped ceiling, so the page size wins here.
		$this->assertCount(
			2,
			$data_store->get_tokens( array( 'page' => 1 ) ),
			'An unscoped query passing page should be sized by the page size, not the unscoped ceiling'
		);
	}

	/**
	 * Capture the SELECT issued against the payment tokens table by the given callback.
	 *
	 * Asserting on the emitted SQL is the only way to tell a bounded query from an unbounded one
	 * without creating more tokens than the limit under test.
	 *
	 * @param callable $callback Code that triggers the query.
	 * @return string The captured query, or an empty string if none was issued.
	 */
	private function capture_token_query( callable $callback ): string {
		global $wpdb;

		$captured = '';
		$listener = function ( $query ) use ( &$captured, $wpdb ) {
			if ( false !== strpos( $query, "SELECT * FROM {$wpdb->prefix}woocommerce_payment_tokens" ) ) {
				$captured = $query;
			}
			return $query;
		};

		add_filter( 'query', $listener );
		try {
			$callback();
		} finally {
			remove_filter( 'query', $listener );
		}

		return $captured;
	}

	/**
	 * @testdox get_customer_tokens should fall back to the default limit when the filter returns null.
	 */
	public function test_get_customer_tokens_falls_back_when_the_limit_filter_returns_null(): void {
		// A callback with a conditional return yields null on its else path; treating that as
		// "no limit" would leave the saved methods query unbounded.
		add_filter( 'woocommerce_get_customer_payment_tokens_limit', '__return_null' );
		$this->create_tokens_for_user( 3 );

		$query = $this->capture_token_query(
			function () {
				WC_Payment_Tokens::get_customer_tokens( $this->user_id );
			}
		);

		$this->assertStringContainsString(
			'LIMIT 0, ' . WC_Payment_Tokens::DEFAULT_CUSTOMER_TOKENS_LIMIT,
			$query,
			'A null limit filter should fall back to the documented default rather than dropping the LIMIT clause'
		);
	}

	/**
	 * @testdox get_customer_tokens should keep hiding saved methods when the limit filter returns a falsy non-null value.
	 *
	 * @testWith ["__return_false"]
	 *           ["__return_empty_string"]
	 *
	 * @param string $callback Filter callback returning a falsy, non-null value.
	 */
	public function test_get_customer_tokens_returns_no_tokens_for_a_falsy_limit_filter( string $callback ): void {
		// These have always meant "no tokens" via absint(): only null may fall back to the default,
		// or an extension hiding saved methods would suddenly expose them.
		add_filter( 'woocommerce_get_customer_payment_tokens_limit', $callback );
		$this->create_tokens_for_user( 3 );

		$this->assertCount(
			0,
			WC_Payment_Tokens::get_customer_tokens( $this->user_id ),
			'A falsy non-null limit filter must keep returning no tokens'
		);
	}

	/**
	 * @testdox get_customer_tokens should honor numeric-string and float limit filter values.
	 *
	 * @testWith ["2", 2]
	 *           [2.7, 2]
	 *
	 * @param mixed $filtered_value Value returned by the limit filter.
	 * @param int   $expected       Expected number of tokens.
	 */
	public function test_get_customer_tokens_casts_numeric_limit_filter_values( $filtered_value, int $expected ): void {
		add_filter(
			'woocommerce_get_customer_payment_tokens_limit',
			function () use ( $filtered_value ) {
				return $filtered_value;
			}
		);
		$this->create_tokens_for_user( 3 );

		$this->assertCount(
			$expected,
			WC_Payment_Tokens::get_customer_tokens( $this->user_id ),
			'Numeric limit filter values should be cast with absint() as they always have been'
		);
	}

	/**
	 * @testdox get_customer_tokens should return no tokens when the limit filter returns zero.
	 */
	public function test_get_customer_tokens_returns_no_tokens_when_limit_filtered_to_zero(): void {
		add_filter( 'woocommerce_get_customer_payment_tokens_limit', '__return_zero' );
		$this->create_tokens_for_user( 3 );

		// Extensions return 0 from this filter to hide saved methods on My Account and checkout.
		$this->assertCount(
			0,
			WC_Payment_Tokens::get_customer_tokens( $this->user_id ),
			'A limit of 0 must return zero tokens, not all of them'
		);
	}

	/**
	 * @testdox Data store get_tokens should return no rows for an explicit limit of zero.
	 */
	public function test_data_store_get_tokens_explicit_zero_limit_returns_no_rows(): void {
		$this->create_tokens_for_user( 3 );

		$data_store = WC_Data_Store::load( 'payment-token' );

		$this->assertCount(
			0,
			$data_store->get_tokens(
				array(
					'user_id' => $this->user_id,
					'limit'   => 0,
				)
			),
			'An explicit limit of 0 on a scoped query must return no rows'
		);
		$this->assertCount(
			0,
			$data_store->get_tokens( array( 'limit' => 0 ) ),
			'An explicit limit of 0 on an unscoped query must return no rows, not fall back to the ceiling'
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
