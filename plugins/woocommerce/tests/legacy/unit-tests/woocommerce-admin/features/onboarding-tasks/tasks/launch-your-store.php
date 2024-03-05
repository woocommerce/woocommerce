<?php
/**
 * Test the LaunchYourStore class.
 *
 * @package WooCommerce\Admin\Tests\OnboardingTasks
 */

use Automattic\WooCommerce\Internal\Admin\Onboarding\OnboardingThemes;
use Automattic\WooCommerce\Internal\Admin\Onboarding\OnboardingProfile;
use Automattic\WooCommerce\Admin\Features\OnboardingTasks\TaskList;
use Automattic\WooCommerce\Admin\Features\OnboardingTasks\Tasks\LaunchYourStore;

/**
 * class WC_Admin_Tests_OnboardingTasks_Task_LaunchYourStore
 */
class WC_Admin_Tests_OnboardingTasks_Task_LaunchYourStore extends WC_Unit_Test_Case {
	/**
	 * Task list.
	 *
	 * @var Task|null
	 */
	protected $task = null;

	/**
	 * Setup test data. Called before every test.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->user = $this->factory->user->create(
			array(
				'role' => 'administrator',
			)
		);
		wp_set_current_user( $this->user );

		$this->task = new LaunchYourStore( new TaskList() );
	}

	/**
	 * Tear down.
	 */
	public function tearDown(): void {
		parent::tearDown();

		delete_option( 'woocommerce_coming_soon' );
		delete_option( 'woocommerce_store_pages_only' );
		delete_option( 'woocommerce_private_link' );
		delete_option( 'woocommerce_share_key' );
	}

	/**
	 * Test for fresh installation scenario.
	 */
	public function test_fresh_installation() {
		// Simulate 'woocommerce_installed' action.
		$this->set_current_action( 'woocommerce_installed' );

		update_option( 'fresh_site', '1' );

		$this->task->add_default_values();

		$this->assertEquals( 'yes', get_option( 'woocommerce_coming_soon' ) );
		$this->assertEquals( 'yes', get_option( 'woocommerce_store_pages_only' ) );
		$this->assertEquals( 'yes', get_option( 'woocommerce_private_link' ) );
		$this->assertNotEmpty( get_option( 'woocommerce_share_key' ) );
		$this->assertMatchesRegularExpression( '/^[a-zA-Z0-9]{32}$/', get_option( 'woocommerce_share_key' ) );
	}

	/**
	 * Test for WooCommerce update scenario.
	 */
	public function test_wc_update() {
		// Simulate 'woocommerce_updated' action.
		$this->set_current_action( 'woocommerce_updated' );

		update_option( 'fresh_site', '0' );

		$this->task->add_default_values();

		$this->assertEquals( 'no', get_option( 'woocommerce_coming_soon' ) );
		$this->assertEquals( 'no', get_option( 'woocommerce_store_pages_only' ) );
		$this->assertEquals( 'yes', get_option( 'woocommerce_private_link' ) );
		$this->assertNotEmpty( get_option( 'woocommerce_share_key' ) );
		$this->assertMatchesRegularExpression( '/^[a-zA-Z0-9]{32}$/', get_option( 'woocommerce_share_key' ) );
	}

	/**
	 * Test when the options are already set.
	 *
	 */
	public function test_options_already_set() {
		// Simulate 'woocommerce_updated' action.
		$this->set_current_action( 'woocommerce_updated' );

		update_option( 'fresh_site', '0' );
		update_option( 'woocommerce_coming_soon', 'yes' );
		update_option( 'woocommerce_store_pages_only', 'yes' );
		update_option( 'woocommerce_private_link', 'yes' );
		update_option( 'woocommerce_share_key', 'test' );

		$this->task->add_default_values();

		$this->assertEquals( 'yes', get_option( 'woocommerce_coming_soon' ) );
		$this->assertEquals( 'yes', get_option( 'woocommerce_store_pages_only' ) );
		$this->assertEquals( 'yes', get_option( 'woocommerce_private_link' ) );
		$this->assertEquals( 'test', get_option( 'woocommerce_share_key' ) );
	}

	/**
	 * Helper method to set the current action for testing.
	 *
	 * @param string $action The action to set.
	 */
	private function set_current_action( $action ) {
		global $wp_current_filter;
		$wp_current_filter[] = $action; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
	}
}
