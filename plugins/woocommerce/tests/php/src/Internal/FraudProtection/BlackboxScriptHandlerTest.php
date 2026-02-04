<?php
/**
 * BlackboxScriptHandlerTest class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\FraudProtection;

use Automattic\WooCommerce\Internal\FraudProtection\BlackboxScriptHandler;
use Automattic\WooCommerce\RestApi\UnitTests\LoggerSpyTrait;
use WC_Unit_Test_Case;

/**
 * Tests for BlackboxScriptHandler.
 *
 * @covers \Automattic\WooCommerce\Internal\FraudProtection\BlackboxScriptHandler
 */
class BlackboxScriptHandlerTest extends WC_Unit_Test_Case {

	use LoggerSpyTrait;

	/**
	 * The System Under Test.
	 *
	 * @var BlackboxScriptHandler
	 */
	private $sut;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->sut = new BlackboxScriptHandler();
		$this->sut->register();
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		parent::tearDown();
		remove_all_filters( 'woocommerce_fraud_protection_enqueue_blackbox_scripts' );
		remove_all_filters( 'woocommerce_is_checkout' );
		remove_all_filters( 'pre_option_jetpack_options' );
		remove_all_filters( 'pre_option_woocommerce_myaccount_page_id' );
		wp_dequeue_script( 'wc-fraud-protection-blackbox' );
		wp_dequeue_script( 'wc-fraud-protection-blackbox-init' );
		wp_deregister_script( 'wc-fraud-protection-blackbox' );
		wp_deregister_script( 'wc-fraud-protection-blackbox-init' );

		// Clean up global query vars and post.
		global $wp, $post;
		unset( $wp->query_vars['order-pay'] );
		unset( $wp->query_vars['add-payment-method'] );
		$post = null; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Test teardown cleanup.
	}

	/**
	 * @testdox Should enqueue Blackbox scripts on checkout page.
	 */
	public function test_enqueues_scripts_on_checkout(): void {
		$this->mock_jetpack_blog_id( 12345 );
		$this->mock_wc_page( 'checkout' );

		do_action( 'wp_enqueue_scripts' ); // phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment

		$this->assertTrue( wp_script_is( 'wc-fraud-protection-blackbox', 'enqueued' ), 'Blackbox SDK should be enqueued on checkout' );
		$this->assertTrue( wp_script_is( 'wc-fraud-protection-blackbox-init', 'enqueued' ), 'Blackbox init script should be enqueued on checkout' );
	}

	/**
	 * @testdox Should enqueue Blackbox scripts on pay-for-order page.
	 */
	public function test_enqueues_scripts_on_pay_for_order(): void {
		$this->mock_jetpack_blog_id( 12345 );
		$this->mock_wc_page( 'order-pay' );

		do_action( 'wp_enqueue_scripts' ); // phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment

		$this->assertTrue( wp_script_is( 'wc-fraud-protection-blackbox', 'enqueued' ), 'Blackbox SDK should be enqueued on pay-for-order' );
		$this->assertTrue( wp_script_is( 'wc-fraud-protection-blackbox-init', 'enqueued' ), 'Blackbox init script should be enqueued on pay-for-order' );
	}

	/**
	 * @testdox Should enqueue Blackbox scripts on add-payment-method page.
	 */
	public function test_enqueues_scripts_on_add_payment_method(): void {
		$this->mock_jetpack_blog_id( 12345 );
		$this->mock_wc_page( 'add-payment-method' );

		do_action( 'wp_enqueue_scripts' ); // phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment

		$this->assertTrue( wp_script_is( 'wc-fraud-protection-blackbox', 'enqueued' ), 'Blackbox SDK should be enqueued on add-payment-method' );
		$this->assertTrue( wp_script_is( 'wc-fraud-protection-blackbox-init', 'enqueued' ), 'Blackbox init script should be enqueued on add-payment-method' );
	}

	/**
	 * @testdox Should enqueue Blackbox scripts on a custom page with the checkout block.
	 */
	public function test_enqueues_scripts_on_custom_checkout_block_page(): void {
		$this->mock_jetpack_blog_id( 12345 );
		$this->mock_wc_page( 'custom-blocks-checkout' );

		do_action( 'wp_enqueue_scripts' ); // phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment

		$this->assertTrue( wp_script_is( 'wc-fraud-protection-blackbox', 'enqueued' ), 'Blackbox SDK should be enqueued on custom checkout block page' );
		$this->assertTrue( wp_script_is( 'wc-fraud-protection-blackbox-init', 'enqueued' ), 'Blackbox init script should be enqueued on custom checkout block page' );
	}

	/**
	 * @testdox Should not enqueue Blackbox scripts on non-payment pages.
	 */
	public function test_does_not_enqueue_scripts_on_other_pages(): void {
		$this->markTestSkipped( 'Flaky in full suite due to is_checkout returning true (despite the resets).' );

		$this->mock_jetpack_blog_id( 12345 );

		do_action( 'wp_enqueue_scripts' ); // phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment

		$this->assertFalse( wp_script_is( 'wc-fraud-protection-blackbox', 'enqueued' ), 'Blackbox SDK should not be enqueued on non-payment pages' );
		$this->assertFalse( wp_script_is( 'wc-fraud-protection-blackbox-init', 'enqueued' ), 'Blackbox init script should not be enqueued on non-payment pages' );
	}

	/**
	 * @testdox Should not enqueue scripts and log error when Jetpack blog ID is unavailable.
	 */
	public function test_does_not_enqueue_scripts_without_blog_id(): void {
		$this->mock_wc_page( 'checkout' );

		do_action( 'wp_enqueue_scripts' ); // phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment

		$this->assertFalse( wp_script_is( 'wc-fraud-protection-blackbox', 'enqueued' ), 'Blackbox SDK should not be enqueued without blog ID' );
		$this->assertLogged( 'error', 'Jetpack blog ID not available' );
	}

	/**
	 * @testdox Should pass correct config data via wp_localize_script.
	 */
	public function test_passes_correct_config_data(): void {
		$this->mock_jetpack_blog_id( 42 );
		$this->mock_wc_page( 'checkout' );

		do_action( 'wp_enqueue_scripts' ); // phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment

		$data = wp_scripts()->get_data( 'wc-fraud-protection-blackbox-init', 'data' );
		$this->assertStringContainsString( '"woocommerce"', $data, 'Should contain API key' );
		$this->assertStringContainsString( '"42"', $data, 'Should contain blog ID' );
	}

	/**
	 * @testdox Should allow extensions to enable scripts on additional pages via filter.
	 */
	public function test_filter_enables_scripts_on_custom_pages(): void {
		$this->mock_jetpack_blog_id( 12345 );
		add_filter( 'woocommerce_fraud_protection_enqueue_blackbox_scripts', '__return_true' );

		do_action( 'wp_enqueue_scripts' ); // phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment

		$this->assertTrue( wp_script_is( 'wc-fraud-protection-blackbox', 'enqueued' ), 'Blackbox SDK should be enqueued when filter returns true' );
	}

	/**
	 * @testdox Should allow extensions to disable scripts on checkout via filter.
	 */
	public function test_filter_disables_scripts_on_checkout(): void {
		$this->mock_jetpack_blog_id( 12345 );
		$this->mock_wc_page( 'checkout' );
		add_filter( 'woocommerce_fraud_protection_enqueue_blackbox_scripts', '__return_false' );

		do_action( 'wp_enqueue_scripts' ); // phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment

		$this->assertFalse( wp_script_is( 'wc-fraud-protection-blackbox', 'enqueued' ), 'Blackbox SDK should not be enqueued when filter returns false' );
	}

	/**
	 * Mock the Jetpack blog ID via the pre_option_jetpack_options filter.
	 *
	 * @param int $blog_id The blog ID to return.
	 */
	private function mock_jetpack_blog_id( int $blog_id ): void {
		add_filter(
			'pre_option_jetpack_options',
			function () use ( $blog_id ) {
				return array( 'id' => $blog_id );
			}
		);
	}

	/**
	 * Mock a WooCommerce page URL.
	 *
	 * @param string $page The page to mock (e.g., 'checkout', 'custom-blocks-checkout', 'order-pay', 'add-payment-method').
	 */
	private function mock_wc_page( string $page ): void {
		global $wp, $post, $wp_query;

		switch ( $page ) {
			case 'checkout':
				add_filter( 'woocommerce_is_checkout', '__return_true' );
				break;
			case 'custom-blocks-checkout':
				$post = $this->factory()->post->create_and_get( // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Test needs to simulate a page with checkout block.
					array( 'post_content' => '<!-- wp:woocommerce/checkout --><div class="wp-block-woocommerce-checkout"></div><!-- /wp:woocommerce/checkout -->' )
				);
				break;
			case 'order-pay':
				$wp->query_vars['order-pay'] = true;
				add_filter( 'woocommerce_is_checkout', '__return_true' );
				break;
			case 'add-payment-method':
				$page_id = $this->factory()->post->create(
					array(
						'post_type'  => 'page',
						'post_title' => 'My account',
					)
				);
				add_filter(
					'pre_option_woocommerce_myaccount_page_id',
					function () use ( $page_id ) {
						return $page_id;
					}
				);
				$this->go_to( '?page_id=' . $page_id );
				$wp->query_vars['add-payment-method'] = true;
				break;
		}
	}
}
