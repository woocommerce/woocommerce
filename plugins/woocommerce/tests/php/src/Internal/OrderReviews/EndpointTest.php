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
		// Feature flag gates the OrderReviews stack.
		update_option( 'woocommerce_feature_customer_review_request_enabled', 'yes' );
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
		wp_reset_postdata();
		wp_set_current_user( 0 );
		delete_option( 'woocommerce_feature_customer_review_request_enabled' );
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
	 * Build a minimal `WP_Block` stand-in carrying the given `postId` context.
	 * Avoids constructing a real `WP_Block`, which would require fully-parsed
	 * block + registry plumbing.
	 *
	 * @param int $post_id Value to expose at `$instance->context['postId']`.
	 * @return \WP_Block
	 */
	private function make_block_instance( int $post_id ): \WP_Block {
		$instance          = $this->getMockBuilder( \WP_Block::class )
			->disableOriginalConstructor()
			->getMock();
		$instance->context = array( 'postId' => $post_id );
		return $instance;
	}

	/**
	 * @testdox A successful gate_request() registers the title-suppression filters so they fire on the rest of the request.
	 */
	public function test_gate_request_registers_title_filters_after_authorisation(): void {
		$page_id = (int) wc_get_page_id( Endpoint::PAGE_KEY );

		$order = OrderHelper::create_order();
		$order->set_status( OrderStatus::COMPLETED );
		$order->save();

		// Stage the globals gate_request() reads: `is_page( review_order_page_id )`
		// for the early return, `$wp->query_vars[ QUERY_VAR ]` for the order id,
		// and `$_GET['key']` for the order key.
		global $wp, $wp_query, $wp_the_query;
		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- test fixture: singular page query so is_page() returns true.
		$wp_query = new WP_Query( array( 'page_id' => $page_id ) );
		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- test fixture: matching main query.
		$wp_the_query = $wp_query;
		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- test fixture: stage a fresh WP instance carrying our query var.
		$wp                                    = new \WP();
		$wp->query_vars[ Endpoint::QUERY_VAR ] = (string) $order->get_id();
		$_GET                                  = array( 'key' => $order->get_order_key() );

		// Both filters must be absent before gate runs.
		$this->assertFalse(
			has_filter( 'the_title', array( $this->endpoint, 'maybe_hide_page_title' ) )
		);
		$this->assertFalse(
			has_filter( 'render_block_core/post-title', array( $this->endpoint, 'maybe_hide_post_title_block' ) )
		);

		$this->endpoint->gate_request();

		$this->assertNotFalse(
			has_filter( 'the_title', array( $this->endpoint, 'maybe_hide_page_title' ) )
		);
		$this->assertNotFalse(
			has_filter( 'render_block_core/post-title', array( $this->endpoint, 'maybe_hide_post_title_block' ) )
		);

		remove_filter( 'the_title', array( $this->endpoint, 'maybe_hide_page_title' ), 10 );
		remove_filter( 'render_block_core/post-title', array( $this->endpoint, 'maybe_hide_post_title_block' ), 10 );
	}

	/**
	 * @testdox maybe_hide_page_title() empties the title for the Review Order page when iterating the main loop.
	 */
	public function test_maybe_hide_page_title_empties_review_order_page_title_in_main_loop(): void {
		$page_id = (int) wc_get_page_id( Endpoint::PAGE_KEY );

		// Stage a main query so in_the_loop() + is_main_query() both pass.
		global $wp_query, $wp_the_query;
		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- test fixture: drive a fake main query.
		$wp_query = new WP_Query( array( 'page_id' => $page_id ) );
		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- test fixture: drive a fake main query.
		$wp_the_query = $wp_query;
		$wp_query->the_post();

		$this->assertSame(
			'',
			$this->endpoint->maybe_hide_page_title( 'Review your order', $page_id )
		);
	}

	/**
	 * @testdox maybe_hide_page_title() leaves titles for other posts alone (e.g. a nav link on the same render).
	 */
	public function test_maybe_hide_page_title_leaves_other_post_titles(): void {
		$other_id = (int) wp_insert_post(
			array(
				'post_type'   => 'page',
				'post_status' => 'publish',
				'post_title'  => 'Sample Page',
			)
		);

		global $wp_query, $wp_the_query;
		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- test fixture: drive a fake main query on a different page.
		$wp_query = new WP_Query( array( 'page_id' => $other_id ) );
		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- test fixture: drive a fake main query on a different page.
		$wp_the_query = $wp_query;
		$wp_query->the_post();

		$this->assertSame(
			'Sample Page',
			$this->endpoint->maybe_hide_page_title( 'Sample Page', $other_id )
		);
	}

	/**
	 * @testdox maybe_hide_page_title() leaves the title alone when the request is outside any loop (e.g. wp_get_document_title()).
	 */
	public function test_maybe_hide_page_title_leaves_title_when_not_in_loop(): void {
		$page_id = (int) wc_get_page_id( Endpoint::PAGE_KEY );

		// Main query exists and `is_main_query()` is true, but `the_post()`
		// has not been called so `in_the_loop()` returns false. This is the
		// state `wp_get_document_title()` reads the post title in.
		global $wp_query, $wp_the_query;
		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- test fixture: main query without the_post().
		$wp_query = new WP_Query( array( 'page_id' => $page_id ) );
		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- test fixture: keep is_main_query() true.
		$wp_the_query = $wp_query;

		$this->assertFalse( in_the_loop() );
		$this->assertTrue( is_main_query() );

		$this->assertSame(
			'Review your order',
			$this->endpoint->maybe_hide_page_title( 'Review your order', $page_id )
		);
	}

	/**
	 * @testdox maybe_hide_page_title() leaves the title alone when the loop belongs to a secondary query (is_main_query() is false).
	 */
	public function test_maybe_hide_page_title_leaves_title_when_not_main_query(): void {
		$page_id = (int) wc_get_page_id( Endpoint::PAGE_KEY );

		// `$wp_the_query` is some other query (an empty one is enough), so
		// is_main_query() returns false even though we are inside `$wp_query`'s loop.
		global $wp_query, $wp_the_query;
		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- test fixture: separate main query.
		$wp_the_query = new WP_Query();
		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- test fixture: secondary query iterating the page.
		$wp_query = new WP_Query( array( 'page_id' => $page_id ) );
		$wp_query->the_post();

		$this->assertTrue( in_the_loop() );
		$this->assertFalse( is_main_query() );

		$this->assertSame(
			'Review your order',
			$this->endpoint->maybe_hide_page_title( 'Review your order', $page_id )
		);
	}

	/**
	 * @testdox maybe_hide_post_title_block() empties `core/post-title` markup when bound to the Review Order page.
	 */
	public function test_maybe_hide_post_title_block_empties_when_bound_to_review_order_page(): void {
		$page_id = (int) wc_get_page_id( Endpoint::PAGE_KEY );

		$this->assertSame(
			'',
			$this->endpoint->maybe_hide_post_title_block(
				'<h1 class="wp-block-post-title">Review your order</h1>',
				array( 'blockName' => 'core/post-title' ),
				$this->make_block_instance( $page_id )
			)
		);
	}

	/**
	 * @testdox maybe_hide_post_title_block() leaves the title alone when the block is bound to a different post (e.g. inside a Query Loop).
	 */
	public function test_maybe_hide_post_title_block_leaves_other_post_context(): void {
		$other_post_id = (int) wp_insert_post(
			array(
				'post_type'   => 'page',
				'post_status' => 'publish',
				'post_title'  => 'Another page',
			)
		);

		$markup = '<h1 class="wp-block-post-title">Another page</h1>';
		$this->assertSame(
			$markup,
			$this->endpoint->maybe_hide_post_title_block(
				$markup,
				array( 'blockName' => 'core/post-title' ),
				$this->make_block_instance( $other_post_id )
			)
		);
	}

	/**
	 * @testdox maybe_hide_post_title_block() leaves the title alone when the third arg is not a WP_Block (defensive guard).
	 */
	public function test_maybe_hide_post_title_block_leaves_title_when_instance_missing(): void {
		$markup = '<h1 class="wp-block-post-title">Review your order</h1>';
		$this->assertSame(
			$markup,
			$this->endpoint->maybe_hide_post_title_block(
				$markup,
				array( 'blockName' => 'core/post-title' ),
				null
			)
		);
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
	 * @testdox The disabled-products info notice renders when at least one order item is STATUS_SKIP and the form is still active.
	 */
	public function test_disabled_products_notice_renders_above_form(): void {
		$order      = OrderHelper::create_order();
		$reviewable = WC_Helper_Product::create_simple_product();
		$disabled   = WC_Helper_Product::create_simple_product();
		wp_update_post(
			array(
				'ID'             => $disabled->get_id(),
				'comment_status' => 'closed',
			)
		);
		$order->set_status( OrderStatus::COMPLETED );
		foreach ( $order->get_items() as $item ) {
			$order->remove_item( $item->get_id() );
		}
		$order->add_product( $reviewable, 1 );
		$order->add_product( $disabled, 1 );
		$order->save();

		$_GET = array( 'key' => $order->get_order_key() );

		$html = $this->render( $order->get_id() );

		$this->assertStringContainsString( 'woocommerce-info woocommerce-review-order__notice', $html );
		$this->assertStringContainsString( 'see all your products?', $html );
		$this->assertStringContainsString( 'woocommerce-review-order__form', $html );
	}

	/**
	 * @testdox The empty-state thank-you template renders the meta line, heading, and body when no actionable rows remain.
	 */
	public function test_empty_state_template_renders_meta_and_thank_you(): void {
		$order   = OrderHelper::create_order();
		$product = WC_Helper_Product::create_simple_product();
		$order->set_billing_email( 'thanks@example.test' );
		$order->set_status( OrderStatus::COMPLETED );
		foreach ( $order->get_items() as $item ) {
			$order->remove_item( $item->get_id() );
		}
		$order->add_product( $product, 1 );
		$order->save();

		$comment_id = (int) wp_insert_comment(
			array(
				'comment_post_ID'      => $product->get_id(),
				'comment_author'       => 'Thanks',
				'comment_author_email' => 'thanks@example.test',
				'comment_content'      => 'Loved it.',
				'comment_type'         => 'review',
				'comment_approved'     => 1,
			)
		);
		add_comment_meta( $comment_id, ItemEligibility::ORDER_META_KEY, (int) $order->get_id(), true );

		$_GET = array( 'key' => $order->get_order_key() );

		$html = $this->render( $order->get_id() );

		$this->assertStringContainsString( 'woocommerce-review-order--empty', $html );
		$this->assertStringContainsString( 'woocommerce-breadcrumb woocommerce-review-order__meta', $html );
		$this->assertStringContainsString( 'Order #' . $order->get_order_number(), $html );
		$this->assertStringContainsString( 'Thank you for your reviews', $html );
		$this->assertStringContainsString( 'Your feedback helps', $html );
	}

	/**
	 * @testdox A pre-filled row exposes the existing rating and text via data-initial-* attributes so the JS dirty gate can detect edits.
	 */
	public function test_row_exposes_data_initial_attributes_for_prefilled_review(): void {
		$order      = OrderHelper::create_order();
		$reviewed   = WC_Helper_Product::create_simple_product();
		$unreviewed = WC_Helper_Product::create_simple_product();
		$order->set_billing_email( 'prefill@example.test' );
		$order->set_status( OrderStatus::COMPLETED );
		foreach ( $order->get_items() as $item ) {
			$order->remove_item( $item->get_id() );
		}
		$order->add_product( $reviewed, 1 );
		$order->add_product( $unreviewed, 1 );
		$order->save();

		$comment_id = (int) wp_insert_comment(
			array(
				'comment_post_ID'      => $reviewed->get_id(),
				'comment_author'       => 'Prefill',
				'comment_author_email' => 'prefill@example.test',
				'comment_content'      => 'Solid four.',
				'comment_type'         => 'review',
				'comment_approved'     => 1,
			)
		);
		add_comment_meta( $comment_id, 'rating', 4, true );
		add_comment_meta( $comment_id, ItemEligibility::ORDER_META_KEY, (int) $order->get_id(), true );

		$_GET = array( 'key' => $order->get_order_key() );

		$html = $this->render( $order->get_id() );

		$this->assertStringContainsString( 'data-initial-rating="4"', $html );
		$this->assertStringContainsString( 'data-initial-text="Solid four."', $html );
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

	/**
	 * @testdox maybe_create_host_page() re-aligns the option with the slug-routed page when duplicates exist.
	 *
	 * Prior activation/disable cycles can leave multiple pages with slug
	 * `review-order`. WP's permalink routing resolves `/review-order/` to the
	 * lowest-id match, so the option must agree with that or `gate_request()`
	 * silently skips its work (assets never enqueue).
	 */
	public function test_maybe_create_host_page_adopts_slug_canonical_when_option_dangles(): void {
		global $wpdb;

		// Wipe whatever the shared setUp seeded so this test controls state.
		$this->reset_review_order_pages();

		$first_id  = (int) wp_insert_post(
			array(
				'post_type'    => 'page',
				'post_status'  => 'publish',
				'post_title'   => 'Review your order',
				'post_name'    => 'review-order',
				'post_content' => '<!-- wp:shortcode -->[woocommerce_review_order]<!-- /wp:shortcode -->',
			)
		);
		$second_id = (int) wp_insert_post(
			array(
				'post_type'    => 'page',
				'post_status'  => 'publish',
				'post_title'   => 'Review your order',
				'post_name'    => 'review-order-alt',
				'post_content' => '<!-- wp:shortcode -->[woocommerce_review_order]<!-- /wp:shortcode -->',
			)
		);
		// Force the slug clash that WP's uniqueness check would normally avoid.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange
		$wpdb->update( $wpdb->posts, array( 'post_name' => 'review-order' ), array( 'ID' => $first_id ) );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange
		$wpdb->update( $wpdb->posts, array( 'post_name' => 'review-order' ), array( 'ID' => $second_id ) );
		clean_post_cache( $first_id );
		clean_post_cache( $second_id );

		// Option absent so the fast-path short-circuit fails and reconciliation runs.
		delete_option( 'woocommerce_review_order_page_id' );
		delete_option( 'woocommerce_review_order_flush_rewrite_pending' );

		$this->endpoint->maybe_create_host_page();

		$this->assertSame( $first_id, (int) wc_get_page_id( Endpoint::PAGE_KEY ), 'option should adopt the slug-routed (lowest-id) page' );
		$this->assertSame( 'yes', get_option( 'woocommerce_review_order_flush_rewrite_pending' ), 'rewrite flush should be queued when the option moves' );
	}

	/**
	 * @testdox maybe_create_host_page() republishes a draft host page and queues a rewrite flush.
	 */
	public function test_maybe_create_host_page_republishes_draft_host_page(): void {
		$this->reset_review_order_pages();

		$page_id = (int) wp_insert_post(
			array(
				'post_type'    => 'page',
				'post_status'  => 'draft',
				'post_title'   => 'Review your order',
				'post_name'    => 'review-order',
				'post_content' => '<!-- wp:shortcode -->[woocommerce_review_order]<!-- /wp:shortcode -->',
			)
		);
		update_option( 'woocommerce_review_order_page_id', $page_id );
		delete_option( 'woocommerce_review_order_flush_rewrite_pending' );

		$this->endpoint->maybe_create_host_page();

		$fresh = get_post( $page_id );
		$this->assertSame( 'publish', $fresh->post_status, 'draft host page should be republished' );
		$this->assertSame( 'yes', get_option( 'woocommerce_review_order_flush_rewrite_pending' ) );
	}

	/**
	 * @testdox The `woocommerce_create_pages` filter injects the Review Order entry so any caller of `WC_Install::create_pages()` (e.g. Status → Tools repair) seeds the page.
	 */
	public function test_inject_review_order_page_filter_adds_entry_for_third_party_callers(): void {
		$pages = $this->endpoint->inject_review_order_page( array() );

		$this->assertArrayHasKey( Endpoint::PAGE_KEY, $pages );
		$this->assertSame( 'review-order', $pages[ Endpoint::PAGE_KEY ]['name'] );
		$this->assertStringContainsString( '[woocommerce_review_order]', $pages[ Endpoint::PAGE_KEY ]['content'] );

		// Defensive: a non-array value passes through untouched (matches the
		// guard inside the method so other filters in the chain stay intact).
		$this->assertNull( $this->endpoint->inject_review_order_page( null ) );
	}

	/**
	 * Remove every page that could match the Review Order lookup, plus the
	 * stored option, so a test can stage a clean slate before exercising
	 * `maybe_create_host_page()`.
	 */
	private function reset_review_order_pages(): void {
		$candidates = get_posts(
			array(
				'name'             => 'review-order',
				'post_type'        => 'page',
				'post_status'      => 'any',
				'numberposts'      => -1,
				'suppress_filters' => false,
			)
		);
		foreach ( $candidates as $page ) {
			wp_delete_post( (int) $page->ID, true );
		}
		delete_option( 'woocommerce_review_order_page_id' );
	}
}
