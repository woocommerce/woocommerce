<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\DataStores\Reviews;

use Automattic\WooCommerce\Internal\DataStores\Reviews\ReviewVerificationDataStore;
use Automattic\WooCommerce\Internal\DataStores\Reviews\ReviewVerificationService;
use Automattic\WooCommerce\RestApi\UnitTests\Helpers\ProductHelper;

/**
 * Tests for ReviewVerificationService and ReviewVerificationDataStore.
 */
class ReviewVerificationServiceTest extends \WC_Unit_Test_Case {

	/**
	 * The scheduling service under test.
	 *
	 * @var ReviewVerificationService
	 */
	private ReviewVerificationService $service;

	/**
	 * The resolving data store under test.
	 *
	 * @var ReviewVerificationDataStore
	 */
	private ReviewVerificationDataStore $data_store;

	/**
	 * Resolve the service and data store from the container.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->service    = wc_get_container()->get( ReviewVerificationService::class );
		$this->data_store = wc_get_container()->get( ReviewVerificationDataStore::class );
	}

	/**
	 * Clear any backfill actions scheduled by fixtures or prior tests.
	 */
	public function tearDown(): void {
		if ( function_exists( 'as_unschedule_all_actions' ) ) {
			as_unschedule_all_actions( ReviewVerificationService::BACKFILL_ACTION );
		}
		parent::tearDown();
	}

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
	 * Fetch the approved review comments for a product.
	 *
	 * @param int $product_id Product post id.
	 * @return \WP_Comment[]
	 */
	private function get_product_reviews( int $product_id ): array {
		return get_comments(
			array(
				'post_id' => $product_id,
				'type'    => 'review',
				'status'  => 'approve',
			)
		);
	}

	/**
	 * Count the pending backfill actions scheduled for a product.
	 *
	 * @param int $product_id Product post id.
	 * @return int
	 */
	private function count_scheduled_backfills( int $product_id ): int {
		$ids = as_get_scheduled_actions(
			array(
				'hook'     => ReviewVerificationService::BACKFILL_ACTION,
				'args'     => array( 'product_id' => $product_id ),
				'status'   => \ActionScheduler_Store::STATUS_PENDING,
				'per_page' => -1,
			),
			'ids'
		);
		return count( $ids );
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
	 * The data store must resolve every reviewer in a single query and persist the verified meta,
	 * so the per-review badge path then issues no further queries.
	 */
	public function test_backfill_resolves_all_in_single_query(): void {
		list( $product, $expected ) = $this->build_verified_owner_fixture();

		wp_cache_flush();
		$backfill_queries = $this->count_bought_product_queries(
			function () use ( $product ) {
				$this->data_store->backfill( $product->get_id(), 500 );
			}
		);

		// One query for the whole product, regardless of reviewer count.
		$this->assertSame( 1, $backfill_queries, 'Backfill should run exactly one bought-product query.' );

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
		$this->assertSame( 0, $badge_queries, 'Verified-owner badge should not query after the backfill.' );
	}

	/**
	 * The backfilled result must match the per-review (unbatched) path.
	 */
	public function test_backfill_matches_per_review_path(): void {
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

		// Backfilled path on a fresh fixture.
		list( $product_b, $expected_b ) = $this->build_verified_owner_fixture();
		$this->data_store->backfill( $product_b->get_id(), 500 );

		foreach ( $expected_b as $comment_id => $is_verified ) {
			$this->assertSame( $is_verified, wc_review_is_from_verified_owner( $comment_id ), "Backfilled path wrong for comment $comment_id." );
		}
	}

	/**
	 * When disabled via filter, the backfill is a no-op and leaves resolution to the per-review path.
	 */
	public function test_backfill_can_be_disabled_via_filter(): void {
		list( $product, $expected ) = $this->build_verified_owner_fixture();

		add_filter( 'woocommerce_prime_review_verification_meta', '__return_false' );
		$this->data_store->backfill( $product->get_id(), 500 );
		remove_filter( 'woocommerce_prime_review_verification_meta', '__return_false' );

		// No meta should have been written by the disabled backfill.
		foreach ( array_keys( $expected ) as $comment_id ) {
			$this->assertSame( '', get_comment_meta( $comment_id, 'verified', true ), "Disabled backfill should not write meta for $comment_id." );
		}
	}

	/**
	 * A product page with unresolved reviews must schedule a backfill without resolving on read,
	 * and repeated scheduling must be deduplicated to a single pending action.
	 */
	public function test_schedule_for_page_enqueues_single_backfill_without_resolving(): void {
		list( $product, $expected ) = $this->build_verified_owner_fixture();
		$product_id                 = $product->get_id();
		$comments                   = $this->get_product_reviews( $product_id );

		// Clear the action the fixture's inserts already scheduled, to assert scheduling cleanly.
		as_unschedule_all_actions( ReviewVerificationService::BACKFILL_ACTION );

		$read_queries = $this->count_bought_product_queries(
			function () use ( $comments, $product_id ) {
				$this->service->schedule_for_page( $comments, $product_id );
				$this->service->schedule_for_page( $comments, $product_id );
			}
		);

		$this->assertSame( 0, $read_queries, 'Scheduling on read must not run the bought-product query.' );
		$this->assertSame( 1, $this->count_scheduled_backfills( $product_id ), 'Repeated scheduling must dedupe to one pending backfill.' );

		// Nothing resolved yet: meta stays empty until the scheduled task runs.
		foreach ( array_keys( $expected ) as $comment_id ) {
			$this->assertSame( '', get_comment_meta( $comment_id, 'verified', true ), "Scheduling must not resolve comment $comment_id on read." );
		}
	}

	/**
	 * A page whose reviews are all already resolved must not schedule a backfill.
	 */
	public function test_schedule_for_page_skips_when_all_resolved(): void {
		list( $product ) = $this->build_verified_owner_fixture();
		$product_id      = $product->get_id();

		$this->data_store->backfill( $product_id, 500 );
		as_unschedule_all_actions( ReviewVerificationService::BACKFILL_ACTION );

		$this->service->schedule_for_page( $this->get_product_reviews( $product_id ), $product_id );

		$this->assertSame( 0, $this->count_scheduled_backfills( $product_id ), 'A fully resolved page must not schedule a backfill.' );
	}

	/**
	 * A review whose email differs in case from the order billing email must still resolve verified,
	 * matching the case-insensitive DB collation the per-review path relies on.
	 */
	public function test_backfill_matches_verified_owner_across_email_case(): void {
		$product    = ProductHelper::create_simple_product();
		$product_id = $product->get_id();

		$this->create_paid_order_for( $product, 0, 'buyer@example.test' );
		$comment_id = $this->insert_unverified_review( $product_id, 'Buyer@Example.TEST', 0 );

		$this->data_store->backfill( $product_id, 500 );

		$this->assertSame( '1', get_comment_meta( $comment_id, 'verified', true ), 'A case-different guest email must still resolve as a verified owner.' );
		$this->assertTrue( wc_review_is_from_verified_owner( $comment_id ), 'The per-review path must agree.' );
	}

	/**
	 * A hooked woocommerce_pre_customer_bought_product filter owns the result, so the batch must
	 * defer to the per-review path rather than persist its own answer.
	 */
	public function test_backfill_defers_when_pre_check_filter_is_hooked(): void {
		list( $product, $expected ) = $this->build_verified_owner_fixture();

		add_filter( 'woocommerce_pre_customer_bought_product', '__return_true' );
		$this->data_store->backfill( $product->get_id(), 500 );
		remove_filter( 'woocommerce_pre_customer_bought_product', '__return_true' );

		foreach ( array_keys( $expected ) as $comment_id ) {
			$this->assertSame( '', get_comment_meta( $comment_id, 'verified', true ), "Backfill must not persist over a pre-check filter for $comment_id." );
		}
	}

	/**
	 * A non-review comment must not schedule a backfill.
	 */
	public function test_schedule_for_new_review_ignores_non_reviews(): void {
		$product_id = ProductHelper::create_simple_product()->get_id();
		as_unschedule_all_actions( ReviewVerificationService::BACKFILL_ACTION );

		$comment_id = (int) wp_insert_comment(
			array(
				'comment_post_ID'  => $product_id,
				'comment_content'  => 'Not a review.',
				'comment_type'     => 'comment',
				'comment_approved' => 1,
			)
		);
		as_unschedule_all_actions( ReviewVerificationService::BACKFILL_ACTION );

		$this->service->schedule_for_new_review( $comment_id, get_comment( $comment_id ) );

		$this->assertSame( 0, $this->count_scheduled_backfills( $product_id ), 'A non-review comment must not schedule a backfill.' );
	}

	/**
	 * A backfill run that fills its batch must reschedule a follow-up for the remaining reviews.
	 */
	public function test_backfill_reschedules_when_batch_is_full(): void {
		list( $product ) = $this->build_verified_owner_fixture();
		$product_id      = $product->get_id();

		// Force a batch smaller than the 4-review fixture so the first run is "full".
		add_filter( 'woocommerce_review_verification_backfill_batch_size', fn() => 2 );
		as_unschedule_all_actions( ReviewVerificationService::BACKFILL_ACTION );
		$this->service->run_backfill( $product_id );
		remove_all_filters( 'woocommerce_review_verification_backfill_batch_size' );

		$this->assertSame( 1, $this->count_scheduled_backfills( $product_id ), 'A full batch must reschedule a follow-up for the remainder.' );
	}

	/**
	 * The follow-up must be scheduled even while an action for the same product is in progress — the
	 * real state when a backfill reschedules itself. A pending-and-running dedup would drop it.
	 */
	public function test_backfill_reschedules_while_an_action_is_running(): void {
		if ( ! class_exists( '\ActionScheduler_Action' ) || ! class_exists( '\ActionScheduler_SimpleSchedule' ) ) {
			$this->markTestSkipped( 'Action Scheduler classes unavailable.' );
		}

		list( $product ) = $this->build_verified_owner_fixture();
		$product_id      = $product->get_id();

		add_filter( 'woocommerce_review_verification_backfill_batch_size', fn() => 2 );
		as_unschedule_all_actions( ReviewVerificationService::BACKFILL_ACTION );

		// Persist an action for this product and mark it in-progress, mirroring the scheduler runner.
		$store      = \ActionScheduler::store();
		$action     = new \ActionScheduler_Action(
			ReviewVerificationService::BACKFILL_ACTION,
			array( 'product_id' => $product_id ),
			new \ActionScheduler_SimpleSchedule( as_get_datetime_object() ),
			'woocommerce-review-verification'
		);
		$running_id = $store->save_action( $action );
		$store->log_execution( $running_id );

		$this->service->run_backfill( $product_id );
		remove_all_filters( 'woocommerce_review_verification_backfill_batch_size' );

		$this->assertSame( 1, $this->count_scheduled_backfills( $product_id ), 'A follow-up must schedule even while a backfill action for the product is running.' );
	}

	/**
	 * A follow-up must not stack on top of one already pending for the same product.
	 */
	public function test_backfill_reschedule_dedupes_pending(): void {
		list( $product ) = $this->build_verified_owner_fixture();
		$product_id      = $product->get_id();

		add_filter( 'woocommerce_review_verification_backfill_batch_size', fn() => 2 );
		as_unschedule_all_actions( ReviewVerificationService::BACKFILL_ACTION );
		$this->service->run_backfill( $product_id );
		$this->service->run_backfill( $product_id );
		remove_all_filters( 'woocommerce_review_verification_backfill_batch_size' );

		$this->assertSame( 1, $this->count_scheduled_backfills( $product_id ), 'Repeated full-batch runs must not stack pending follow-ups.' );
	}

	/**
	 * Inserting a new product review must schedule a backfill for its product.
	 */
	public function test_schedule_for_new_review_enqueues_backfill(): void {
		$product    = ProductHelper::create_simple_product();
		$product_id = $product->get_id();
		as_unschedule_all_actions( ReviewVerificationService::BACKFILL_ACTION );

		$comment_id = $this->insert_unverified_review( $product_id, 'someone@example.test', 0 );
		$comment    = get_comment( $comment_id );

		$this->service->schedule_for_new_review( $comment_id, $comment );

		$this->assertSame( 1, $this->count_scheduled_backfills( $product_id ), 'A new product review must schedule a backfill.' );
	}
}
