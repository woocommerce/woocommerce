<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\DataStores\Reviews;

use Automattic\WooCommerce\Internal\DataStores\Reviews\ReviewVerificationQuery;
use Automattic\WooCommerce\RestApi\UnitTests\Helpers\ProductHelper;

/**
 * Tests for ReviewVerificationQuery.
 */
class ReviewVerificationQueryTest extends \WC_Unit_Test_Case {

	/**
	 * Create a paid order containing a product for a given identity.
	 *
	 * @param \WC_Product $product  Product to add.
	 * @param int         $customer Customer user id (0 for guest).
	 * @param string      $email    Billing email.
	 * @return \WC_Order
	 */
	private function create_paid_order_for( $product, int $customer, string $email ) {
		$order = wc_create_order();
		$order->add_product( $product, 1 );
		if ( $customer ) {
			$order->set_customer_id( $customer );
		}
		$order->set_billing_email( $email );
		$order->calculate_totals();
		$order->set_status( 'completed' );
		$order->save();
		return $order;
	}

	/**
	 * Insert an approved product review WITHOUT the verified meta (mirrors imported reviews).
	 *
	 * @param int    $product_id Product post id.
	 * @param string $email      Author email.
	 * @param int    $user_id    Author user id (0 for guest).
	 * @return int Comment id.
	 */
	private function insert_unverified_review( int $product_id, string $email, int $user_id = 0 ) {
		return (int) wp_insert_comment(
			array(
				'comment_post_ID'      => $product_id,
				'comment_author'       => 'Reviewer',
				'comment_author_email' => $email,
				'comment_content'      => 'A review.',
				'comment_type'         => 'review',
				'comment_approved'     => 1,
				'user_id'              => $user_id,
			)
		);
	}

	/**
	 * Build the verified-owner badge fixture: one product with registered-buyer,
	 * guest-buyer and non-buyer reviews. Returns [ product, comment_id => expected_verified ].
	 *
	 * @return array{0: \WC_Product, 1: array<int,bool>}
	 */
	private function build_verified_owner_fixture(): array {
		static $seq = 0;
		$tag        = ++$seq;

		$product    = ProductHelper::create_simple_product();
		$product_id = $product->get_id();

		$expected = array();

		// Registered buyer (verified via customer_id).
		$buyer_id = self::factory()->user->create(
			array(
				'role'       => 'customer',
				'user_email' => "buyer-$tag@example.test",
			)
		);
		$this->create_paid_order_for( $product, $buyer_id, "buyer-$tag@example.test" );
		$expected[ $this->insert_unverified_review( $product_id, '', $buyer_id ) ] = true;

		// Guest buyer (verified via billing email).
		$this->create_paid_order_for( $product, 0, "guest-$tag@example.test" );
		$expected[ $this->insert_unverified_review( $product_id, "guest-$tag@example.test", 0 ) ] = true;

		// Registered non-buyer.
		$nonbuyer_id = self::factory()->user->create(
			array(
				'role'       => 'customer',
				'user_email' => "nobuy-$tag@example.test",
			)
		);
		$expected[ $this->insert_unverified_review( $product_id, '', $nonbuyer_id ) ] = false;

		// Guest non-buyer.
		$expected[ $this->insert_unverified_review( $product_id, "nobuy-guest-$tag@example.test", 0 ) ] = false;

		return array( $product, $expected );
	}

	/**
	 * Count the heavy "bought product" order-join queries while running a callback.
	 *
	 * @param callable $callback Code to execute.
	 * @return int Number of heavy queries observed.
	 */
	private function count_bought_product_queries( callable $callback ): int {
		$count = 0;
		$spy   = function ( $query ) use ( &$count ) {
			if ( false !== stripos( $query, 'woocommerce_order_itemmeta' ) && false !== stripos( $query, '_variation_id' ) ) {
				++$count;
			}
			return $query;
		};
		add_filter( 'query', $spy );
		$callback();
		remove_filter( 'query', $spy );
		return $count;
	}

	/**
	 * The batched prime must resolve every reviewer in a single query and persist
	 * the verified meta, so the per-review badge path issues no further queries.
	 */
	public function test_prime_resolves_all_in_single_query(): void {
		list( $product, $expected ) = $this->build_verified_owner_fixture();
		$comments                   = get_comments(
			array(
				'post_id' => $product->get_id(),
				'type'    => 'review',
				'status'  => 'approve',
			)
		);

		wp_cache_flush();
		$prime_queries = $this->count_bought_product_queries(
			function () use ( $comments, $product ) {
				( new ReviewVerificationQuery() )->prime( $comments, $product->get_id() );
			}
		);

		// One query for the whole page of reviews, regardless of reviewer count.
		$this->assertSame( 1, $prime_queries, 'Batched prime should run exactly one bought-product query.' );

		// Verified meta is persisted with the correct value for every review.
		foreach ( $expected as $comment_id => $is_verified ) {
			$this->assertSame( $is_verified ? '1' : '0', get_comment_meta( $comment_id, 'verified', true ), "Wrong verified meta for comment $comment_id." );
		}

		// The per-review badge path now hits the persisted meta: zero further heavy queries.
		$badge_queries = $this->count_bought_product_queries(
			function () use ( $expected ) {
				foreach ( array_keys( $expected ) as $comment_id ) {
					wc_review_is_from_verified_owner( $comment_id );
				}
			}
		);
		$this->assertSame( 0, $badge_queries, 'Verified-owner badge should not query after the batched prime.' );
	}

	/**
	 * The batched result must be byte-identical to the per-review (unbatched) path.
	 */
	public function test_prime_matches_per_review_path(): void {
		// Unbatched baseline via the filter gate.
		list( , $expected_a ) = $this->build_verified_owner_fixture();
		add_filter( 'woocommerce_prime_review_verification_meta', '__return_false' );
		$unbatched = array();
		foreach ( array_keys( $expected_a ) as $comment_id ) {
			$unbatched[ $comment_id ] = wc_review_is_from_verified_owner( $comment_id );
		}
		remove_filter( 'woocommerce_prime_review_verification_meta', '__return_false' );

		foreach ( $expected_a as $comment_id => $is_verified ) {
			$this->assertSame( $is_verified, $unbatched[ $comment_id ], "Unbatched path wrong for comment $comment_id." );
		}

		// Batched path on a fresh fixture.
		list( $product_b, $expected_b ) = $this->build_verified_owner_fixture();
		$comments                       = get_comments(
			array(
				'post_id' => $product_b->get_id(),
				'type'    => 'review',
				'status'  => 'approve',
			)
		);
		( new ReviewVerificationQuery() )->prime( $comments, $product_b->get_id() );

		foreach ( $expected_b as $comment_id => $is_verified ) {
			$this->assertSame( $is_verified, wc_review_is_from_verified_owner( $comment_id ), "Batched path wrong for comment $comment_id." );
		}
	}

	/**
	 * When disabled via filter, the prime is a no-op and leaves resolution to the per-review path.
	 */
	public function test_prime_can_be_disabled_via_filter(): void {
		list( $product, $expected ) = $this->build_verified_owner_fixture();
		$comments                   = get_comments(
			array(
				'post_id' => $product->get_id(),
				'type'    => 'review',
				'status'  => 'approve',
			)
		);

		add_filter( 'woocommerce_prime_review_verification_meta', '__return_false' );
		( new ReviewVerificationQuery() )->prime( $comments, $product->get_id() );
		remove_filter( 'woocommerce_prime_review_verification_meta', '__return_false' );

		// No meta should have been written by the disabled prime.
		foreach ( array_keys( $expected ) as $comment_id ) {
			$this->assertSame( '', get_comment_meta( $comment_id, 'verified', true ), "Disabled prime should not write meta for $comment_id." );
		}
	}
}
