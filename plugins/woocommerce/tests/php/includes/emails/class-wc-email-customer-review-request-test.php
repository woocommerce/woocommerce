<?php
declare( strict_types = 1 );

/**
 * WC_Email_Customer_Review_Request test.
 *
 * @covers WC_Email_Customer_Review_Request
 */
class WC_Email_Customer_Review_Request_Test extends \WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var WC_Email_Customer_Review_Request
	 */
	private $sut;

	/**
	 * Load up the email classes since they aren't loaded by default.
	 *
	 * `WC_Emails::init()` only registers the review-request email class
	 * when the `customer_review_request` feature flag is on, so the suite
	 * has to enable the option (and re-init the mailer to pick up the
	 * flag change) before exercising the mailer-level registration. Doing
	 * it here makes every test self-contained rather than relying on the
	 * incidental order of other OrderReviews suites that also flip the flag.
	 */
	public function setUp(): void {
		parent::setUp();

		update_option( 'woocommerce_feature_customer_review_request_enabled', 'yes' );

		$bootstrap = \WC_Unit_Tests_Bootstrap::instance();
		require_once $bootstrap->plugin_dir . '/includes/emails/class-wc-email.php';
		require_once $bootstrap->plugin_dir . '/includes/emails/class-wc-email-customer-review-request.php';

		$this->ensure_review_order_page();

		WC()->mailer()->init();

		$this->sut = new WC_Email_Customer_Review_Request();
	}

	/**
	 * Reset the feature flag between tests so the suite doesn't leak the
	 * enabled state into unrelated test classes that assume the default.
	 */
	public function tearDown(): void {
		delete_option( 'woocommerce_feature_customer_review_request_enabled' );

		parent::tearDown();
	}

	/**
	 * Make sure the WC-managed Review Order page exists for tests that build a
	 * URL through the endpoint. The bootstrap install seeds the page, but the
	 * stored option can outlive the post across test runs, so re-create it
	 * defensively if `woocommerce_review_order_page_id` doesn't resolve.
	 */
	private function ensure_review_order_page(): void {
		$page_id = (int) get_option( 'woocommerce_review_order_page_id' );
		if ( $page_id > 0 && get_post( $page_id ) instanceof \WP_Post ) {
			return;
		}

		$new_page_id = wp_insert_post(
			array(
				'post_title'   => 'Review your order',
				'post_name'    => 'review-order',
				'post_status'  => 'publish',
				'post_type'    => 'page',
				'post_content' => '<!-- wp:shortcode -->[woocommerce_review_order]<!-- /wp:shortcode -->',
			)
		);
		if ( ! is_wp_error( $new_page_id ) && $new_page_id > 0 ) {
			update_option( 'woocommerce_review_order_page_id', $new_page_id );
		}
	}

	/**
	 * @testdox Email is disabled by default so it has no effect on sites that don't opt in.
	 */
	public function test_disabled_by_default(): void {
		// Option is intentionally unset, mirroring a fresh install.
		$this->assertFalse( $this->sut->is_enabled() );
	}

	/**
	 * @testdox Default delay is seven days and feeds the delay_seconds helper.
	 */
	public function test_default_delay_seconds(): void {
		$this->assertSame( 7 * DAY_IN_SECONDS, $this->sut->get_delay_seconds() );
	}

	/**
	 * @testdox Admin-entered delay_days is clamped to 1..60 before conversion.
	 */
	public function test_delay_days_is_clamped(): void {
		$this->sut->update_option( 'delay_days', '0' );
		$this->assertSame( 1 * DAY_IN_SECONDS, $this->sut->get_delay_seconds() );

		$this->sut->update_option( 'delay_days', '200' );
		$this->assertSame( 60 * DAY_IN_SECONDS, $this->sut->get_delay_seconds() );

		$this->sut->update_option( 'delay_days', '14' );
		$this->assertSame( 14 * DAY_IN_SECONDS, $this->sut->get_delay_seconds() );
	}

	/**
	 * @testdox A negative stored delay_days clamps to MIN_DELAY_DAYS rather than flipping positive.
	 */
	public function test_delay_days_clamps_negative_to_minimum(): void {
		$this->sut->update_option( 'delay_days', '-5' );
		$this->assertSame( 1 * DAY_IN_SECONDS, $this->sut->get_delay_seconds() );
	}

	/**
	 * @testdox The woocommerce_review_request_delay_seconds filter wins over the admin setting.
	 */
	public function test_delay_seconds_filter_overrides_setting(): void {
		$this->sut->update_option( 'delay_days', '7' );

		$override = static function () {
			return 90;
		};
		add_filter( 'woocommerce_review_request_delay_seconds', $override );

		$this->assertSame( 90, $this->sut->get_delay_seconds() );

		remove_filter( 'woocommerce_review_request_delay_seconds', $override );
	}

	/**
	 * @testdox Review Order URL references the review-order endpoint and carries the order key.
	 */
	public function test_review_order_url_shape(): void {
		$order = \Automattic\WooCommerce\RestApi\UnitTests\Helpers\OrderHelper::create_order();
		$this->sut->trigger( $order->get_id() );

		$url = $this->sut->get_review_order_url();

		// wc_get_endpoint_url renders as "review-order/{id}/" on pretty permalinks
		// and "review-order={id}" on plain permalinks — accept either.
		$this->assertMatchesRegularExpression(
			'#review-order[/=]' . $order->get_id() . '#',
			$url
		);
		$this->assertStringContainsString( 'key=' . $order->get_order_key(), $url );
	}

	/**
	 * @testdox get_review_order_url returns empty string when no order is bound.
	 */
	public function test_review_order_url_empty_without_order(): void {
		$this->assertSame( '', $this->sut->get_review_order_url() );
	}

	/**
	 * @testdox woocommerce_review_order_url filter can replace the generated URL.
	 */
	public function test_review_order_url_filterable(): void {
		$order = \Automattic\WooCommerce\RestApi\UnitTests\Helpers\OrderHelper::create_order();
		$this->sut->trigger( $order->get_id() );

		$override = static function () {
			return 'https://example.test/custom';
		};
		add_filter( 'woocommerce_review_order_url', $override );

		$this->assertSame( 'https://example.test/custom', $this->sut->get_review_order_url() );

		remove_filter( 'woocommerce_review_order_url', $override );
	}

	/**
	 * @testdox Settings form exposes the delay_days field alongside the standard WC_Email fields.
	 */
	public function test_form_fields_expose_delay(): void {
		$this->sut->init_form_fields();

		$this->assertArrayHasKey( 'enabled', $this->sut->form_fields );
		$this->assertArrayHasKey( 'delay_days', $this->sut->form_fields );
		$this->assertSame( 'number', $this->sut->form_fields['delay_days']['type'] );
		$this->assertSame( '1', $this->sut->form_fields['delay_days']['custom_attributes']['min'] );
		$this->assertSame( '60', $this->sut->form_fields['delay_days']['custom_attributes']['max'] );
	}

	/**
	 * @testdox Class registers as a known WC_Email so the WC Settings > Emails page renders it.
	 */
	public function test_is_registered_with_wc_emails(): void {
		$emails = WC()->mailer()->get_emails();

		$this->assertArrayHasKey( 'WC_Email_Customer_Review_Request', $emails );
	}

	/**
	 * @testdox Calling trigger() with an invalid order id after a valid call does not dispatch to the previous recipient.
	 */
	public function test_trigger_clears_state_on_invalid_order(): void {
		$this->sut->update_option( 'enabled', 'yes' );
		$this->sut->enabled = 'yes';

		$order = \Automattic\WooCommerce\RestApi\UnitTests\Helpers\OrderHelper::create_order();

		// First call populates recipient + placeholders from a valid order.
		$this->sut->trigger( $order->get_id() );

		// Second call with an invalid id should not fall through with the previous state.
		$mailer = tests_retrieve_phpmailer_instance();
		$before = count( $mailer->mock_sent );
		$this->sut->trigger( 0 );
		$after = count( $mailer->mock_sent );

		$this->assertSame( $before, $after, 'trigger() must not send to the previous order\'s recipient when called with an invalid id.' );
		$this->assertSame( '', $this->sut->recipient );
		$this->assertFalse( $this->sut->object );
	}

	/**
	 * @testdox trigger() is a no-op when the email is disabled (default state).
	 */
	public function test_trigger_is_noop_when_disabled(): void {
		$order = \Automattic\WooCommerce\RestApi\UnitTests\Helpers\OrderHelper::create_order();

		$mailer = tests_retrieve_phpmailer_instance();
		$before = count( $mailer->mock_sent );
		$this->sut->trigger( $order->get_id() );
		$after = count( $mailer->mock_sent );

		$this->assertSame( $before, $after, 'Disabled review-request email must not dispatch any mail.' );
	}

	/**
	 * @testdox trigger() refuses to send when the order is no longer in an eligible status.
	 *
	 * Defence-in-depth against the scheduler missing a transition out of
	 * `completed` (WOOPLUG-6672): even if the action fires, the email must
	 * not go out for an order that is no longer eligible.
	 */
	public function test_trigger_skips_when_order_not_in_eligible_status(): void {
		$this->sut->update_option( 'enabled', 'yes' );
		$this->sut->enabled = 'yes';

		$order = \Automattic\WooCommerce\RestApi\UnitTests\Helpers\OrderHelper::create_order();
		$order->set_status( 'completed' );
		$order->save();
		$order->set_status( 'processing' );
		$order->save();

		$mailer = tests_retrieve_phpmailer_instance();
		$before = count( $mailer->mock_sent );
		$this->sut->trigger( $order->get_id() );
		$after = count( $mailer->mock_sent );

		$this->assertSame( $before, $after, 'Review-request email must not dispatch for non-eligible status.' );
	}

	/**
	 * @testdox trigger() skips orders whose items all have reviews disabled.
	 *
	 * Eligibility can change between scheduling and sending — e.g. the admin
	 * disables product reviews site-wide during the delay window — so the
	 * email gates on `ItemEligibility::has_actionable_items()` at send time.
	 */
	public function test_trigger_skips_when_no_actionable_items(): void {
		$this->sut->update_option( 'enabled', 'yes' );
		$this->sut->enabled = 'yes';

		$product = \WC_Helper_Product::create_simple_product();
		$product->set_reviews_allowed( false );
		$product->save();

		$order = \Automattic\WooCommerce\RestApi\UnitTests\Helpers\OrderHelper::create_order( 1, $product );
		$order->set_status( 'completed' );
		$order->save();

		$mailer = tests_retrieve_phpmailer_instance();
		$before = count( $mailer->mock_sent );
		$this->sut->trigger( $order->get_id() );
		$after = count( $mailer->mock_sent );

		$this->assertSame( $before, $after, 'Review-request email must not dispatch when nothing on the order is reviewable.' );
	}

	/**
	 * @testdox The woocommerce_review_order_eligible_statuses filter widens the eligible set for trigger().
	 */
	public function test_trigger_eligible_statuses_filter_can_widen(): void {
		$this->sut->update_option( 'enabled', 'yes' );
		$this->sut->enabled = 'yes';

		$order = \Automattic\WooCommerce\RestApi\UnitTests\Helpers\OrderHelper::create_order();
		$order->set_status( 'processing' );
		$order->save();

		$widen_statuses = static function () {
			return array( 'completed', 'processing' );
		};
		add_filter( 'woocommerce_review_order_eligible_statuses', $widen_statuses );

		$mailer = tests_retrieve_phpmailer_instance();
		$before = count( $mailer->mock_sent );
		try {
			$this->sut->trigger( $order->get_id() );
			$after = count( $mailer->mock_sent );
		} finally {
			remove_filter( 'woocommerce_review_order_eligible_statuses', $widen_statuses );
		}

		$this->assertSame( $before + 1, $after, 'Filter must allow non-default statuses to receive the email.' );
	}
}
