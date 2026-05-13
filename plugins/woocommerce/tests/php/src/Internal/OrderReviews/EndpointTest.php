<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\OrderReviews;

use Automattic\WooCommerce\Enums\OrderStatus;
use Automattic\WooCommerce\Internal\OrderReviews\Endpoint;
use Automattic\WooCommerce\Internal\OrderReviews\ItemEligibility;
use Automattic\WooCommerce\Internal\OrderReviews\SubmissionHandler;
use Automattic\WooCommerce\RestApi\UnitTests\Helpers\OrderHelper;
use WC_Helper_Product;
use WC_Unit_Test_Case;
use WP_Query;

/**
 * Tests for the standalone Review Order endpoint and `wc_get_review_order_url()` helper.
 */
class EndpointTest extends WC_Unit_Test_Case {

	/**
	 * System under test.
	 *
	 * @var Endpoint
	 */
	private Endpoint $endpoint;

	/**
	 * Set up a fresh endpoint instance and a clean query.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->endpoint = new Endpoint();

		// `Endpoint::get_url()` derives the URL from the WC-managed Review
		// Order page; tests need that page to exist to exercise the helper.
		// Test transactions roll the post back between runs but the option
		// persists, so the existing id may point at a deleted post — recreate
		// when that's the case.
		$existing = (int) wc_get_page_id( Endpoint::PAGE_KEY );
		if ( $existing <= 0 || ! get_post( $existing ) instanceof \WP_Post ) {
			$page_id = (int) wp_insert_post(
				array(
					'post_type'    => 'page',
					'post_status'  => 'publish',
					'post_title'   => 'Review your order',
					'post_name'    => 'review-order',
					'post_content' => '[woocommerce_review_order]',
				)
			);
			update_option( 'woocommerce_review_order_page_id', $page_id );
		}
	}

	/**
	 * Reset $_GET, the global query, and any logged-in user between tests.
	 */
	public function tearDown(): void {
		$_GET = array();
		global $wp_query;
		if ( $wp_query instanceof WP_Query ) {
			$wp_query->is_404 = false;
		}
		wp_set_current_user( 0 );
		parent::tearDown();
	}

	/**
	 * Run the gating + render with output captured.
	 *
	 * @param int $order_id Order id to dispatch.
	 * @return string Rendered HTML.
	 */
	private function render( int $order_id ): string {
		ob_start();
		$this->endpoint->render( $order_id );
		return (string) ob_get_clean();
	}

	/**
	 * @testdox The query var is registered with WP.
	 */
	public function test_query_var_filter_adds_review_order(): void {
		$vars = $this->endpoint->add_query_var( array( 'foo' ) );
		$this->assertContains( Endpoint::QUERY_VAR, $vars );
	}

	/**
	 * @testdox wc_get_review_order_url returns a tokenized URL pointing at the new endpoint.
	 */
	public function test_helper_returns_tokenized_url(): void {
		$order = OrderHelper::create_order();
		$url   = wc_get_review_order_url( $order );

		// Path style on pretty permalinks, query-arg style on plain — accept either.
		$this->assertMatchesRegularExpression(
			'#review-order[/=]' . $order->get_id() . '#',
			$url
		);
		$this->assertStringContainsString( 'key=' . $order->get_order_key(), $url );
	}

	/**
	 * @testdox wc_get_review_order_url returns empty string for non-order input.
	 */
	public function test_helper_empty_for_non_order(): void {
		$this->assertSame( '', wc_get_review_order_url( null ) );
		$this->assertSame( '', wc_get_review_order_url( 0 ) );
		$this->assertSame( '', wc_get_review_order_url( new \stdClass() ) );
	}

	/**
	 * @testdox The woocommerce_review_order_url filter can replace the helper output.
	 */
	public function test_helper_filterable(): void {
		$order    = OrderHelper::create_order();
		$override = static function () {
			return 'https://example.test/custom';
		};
		add_filter( 'woocommerce_review_order_url', $override );

		$this->assertSame( 'https://example.test/custom', wc_get_review_order_url( $order ) );

		remove_filter( 'woocommerce_review_order_url', $override );
	}

	/**
	 * @testdox 404s when the order id does not resolve.
	 */
	public function test_404_when_order_missing(): void {
		$this->render( 999999 );

		global $wp_query;
		$this->assertTrue( $wp_query->is_404 );
	}

	/**
	 * @testdox 404s when no key query arg is supplied.
	 */
	public function test_404_when_key_missing(): void {
		$order = OrderHelper::create_order();
		$order->set_status( OrderStatus::COMPLETED );
		$order->save();
		$_GET = array();

		$this->render( $order->get_id() );

		global $wp_query;
		$this->assertTrue( $wp_query->is_404 );
	}

	/**
	 * @testdox 404s when the supplied key does not match the order key.
	 */
	public function test_404_when_key_mismatched(): void {
		$order = OrderHelper::create_order();
		$order->set_status( OrderStatus::COMPLETED );
		$order->save();
		$_GET = array( 'key' => 'wc_order_definitelywrong' );

		$this->render( $order->get_id() );

		global $wp_query;
		$this->assertTrue( $wp_query->is_404 );
	}

	/**
	 * @testdox 404s when the order status is not in the eligible set.
	 */
	public function test_404_when_status_ineligible(): void {
		$order = OrderHelper::create_order();
		$order->set_status( OrderStatus::PROCESSING );
		$order->save();
		$_GET = array( 'key' => $order->get_order_key() );

		$this->render( $order->get_id() );

		global $wp_query;
		$this->assertTrue( $wp_query->is_404 );
	}

	/**
	 * @testdox 404s when a logged-in user does not own the order.
	 */
	public function test_404_when_logged_in_customer_mismatch(): void {
		$customer_id = self::factory()->user->create();
		$other_id    = self::factory()->user->create();

		$order = OrderHelper::create_order( $customer_id );
		$order->set_status( OrderStatus::COMPLETED );
		$order->save();

		wp_set_current_user( $other_id );
		$_GET = array( 'key' => $order->get_order_key() );

		$this->render( $order->get_id() );

		global $wp_query;
		$this->assertTrue( $wp_query->is_404 );
	}

	/**
	 * @testdox Renders the template for a valid completed-order link.
	 */
	public function test_renders_template_on_success(): void {
		$order = OrderHelper::create_order();
		$order->set_status( OrderStatus::COMPLETED );
		$order->save();
		$_GET = array( 'key' => $order->get_order_key() );

		$html = $this->render( $order->get_id() );

		global $wp_query;
		$this->assertFalse( $wp_query->is_404 );
		$this->assertStringContainsString( 'woocommerce-review-order', $html );
		$this->assertStringContainsString( 'Review your order', $html );
		$this->assertStringContainsString( 'Order #' . $order->get_order_number(), $html );
	}

	/**
	 * @testdox The woocommerce_review_order_eligible_statuses filter widens the eligible set.
	 */
	public function test_eligible_statuses_filter_widens_set(): void {
		$order = OrderHelper::create_order();
		$order->set_status( OrderStatus::PROCESSING );
		$order->save();
		$_GET = array( 'key' => $order->get_order_key() );

		$widen = static function () {
			return array( OrderStatus::COMPLETED, OrderStatus::PROCESSING );
		};
		add_filter( 'woocommerce_review_order_eligible_statuses', $widen );

		$html = $this->render( $order->get_id() );

		remove_filter( 'woocommerce_review_order_eligible_statuses', $widen );

		global $wp_query;
		$this->assertFalse( $wp_query->is_404 );
		$this->assertStringContainsString( 'woocommerce-review-order', $html );
	}

	/**
	 * @testdox Loading the page when no actionable rows remain stamps the completed-at meta.
	 */
	public function test_no_actionable_rows_stamps_completed_meta(): void {
		$order   = OrderHelper::create_order();
		$product = WC_Helper_Product::create_simple_product();
		$order->set_billing_email( 'reviewed@example.test' );
		$order->set_status( OrderStatus::COMPLETED );
		// Wipe the helper's default item, attach our reviewable product.
		foreach ( $order->get_items() as $item ) {
			$order->remove_item( $item->get_id() );
		}
		$order->add_product( $product, 1 );
		$order->save();

		// Pre-create a matching review tied to this order so decide() surfaces
		// the existing comment and the page treats every row as already reviewed.
		$comment_id = (int) wp_insert_comment(
			array(
				'comment_post_ID'      => $product->get_id(),
				'comment_author'       => 'Already',
				'comment_author_email' => 'reviewed@example.test',
				'comment_content'      => 'Was good.',
				'comment_type'         => 'review',
				'comment_approved'     => 1,
			)
		);
		add_comment_meta( $comment_id, ItemEligibility::ORDER_META_KEY, (int) $order->get_id(), true );

		$_GET = array( 'key' => $order->get_order_key() );

		$this->render( $order->get_id() );

		$fresh = wc_get_order( $order->get_id() );
		$this->assertNotEmpty( $fresh->get_meta( SubmissionHandler::COMPLETED_META_KEY ) );
	}

	/**
	 * @testdox Loading the page with at least one actionable row leaves the completed-at meta unset.
	 */
	public function test_actionable_row_does_not_stamp_completed_meta(): void {
		$order   = OrderHelper::create_order();
		$product = WC_Helper_Product::create_simple_product();
		$order->set_billing_email( 'fresh@example.test' );
		$order->set_status( OrderStatus::COMPLETED );
		foreach ( $order->get_items() as $item ) {
			$order->remove_item( $item->get_id() );
		}
		$order->add_product( $product, 1 );
		$order->save();

		$_GET = array( 'key' => $order->get_order_key() );

		$this->render( $order->get_id() );

		$fresh = wc_get_order( $order->get_id() );
		$this->assertEmpty( $fresh->get_meta( SubmissionHandler::COMPLETED_META_KEY ) );
	}

	/**
	 * @testdox The completed-at meta is never overwritten on subsequent loads.
	 */
	public function test_completed_meta_is_not_overwritten(): void {
		$order = OrderHelper::create_order();
		$order->set_status( OrderStatus::COMPLETED );
		// Empty the order so the no-actionable-rows path falls through and
		// would re-stamp `time()` if the early-return guard were removed;
		// keeping items here lets the loop's unreviewed-item bail-out hide
		// the guard's effect and the test would pass without it.
		foreach ( $order->get_items() as $item ) {
			$order->remove_item( $item->get_id() );
		}
		$preset = (string) ( time() - 3600 );
		$order->update_meta_data( SubmissionHandler::COMPLETED_META_KEY, $preset );
		$order->save();

		$_GET = array( 'key' => $order->get_order_key() );

		$this->render( $order->get_id() );

		$fresh = wc_get_order( $order->get_id() );
		$this->assertSame( $preset, (string) $fresh->get_meta( SubmissionHandler::COMPLETED_META_KEY ) );
	}
}
