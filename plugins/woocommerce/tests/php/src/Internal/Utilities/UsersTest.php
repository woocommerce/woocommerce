<?php

namespace Automattic\WooCommerce\Tests\Internal\Utilities;

use ActionScheduler_Logger;
use ActionScheduler_Store;
use Automattic\WooCommerce\Internal\Utilities\Users;
use WC_Helper_Customer;
use WC_Install;
use WC_Unit_Test_Case;

class UsersTest extends WC_Unit_Test_Case {
	/**
	 * Within a multisite network, the same order IDs can be re-used across multiple blogs. This test describes our
	 * management of this problem, in relation to our caching of the last order placed by each user.
	 *
	 * To test this, we contrive a situation where two separate users place orders on different blogs within the same
	 * network, and where those orders have the same ID. Then, ensure we don't expose information about one customer's
	 * order to the other customer.
	 */
	public function test_get_last_customer_order() {
		$this->skipWithoutMultisite();

		$customer = WC_Helper_Customer::create_customer( 'test1', 'pass1', 'test1@example.com' )->get_id();
		$subsite  = $this->make_network_site( '/site-1/' );

		// Create some network-wide user meta data, and repeat using the equivalent network-aware functionality from
		// the Users utility.
		update_user_meta( $customer, 'test_data', 'foo' );
		Users::update_site_user_meta( $customer, 'test_data', 'bar' );

		// Switch to our subsite, and ensure meta data is appropriately isolated.
		switch_to_blog( $subsite );

		$this->assertEquals(
			'foo',
			get_user_meta( $customer, 'test_data', true ),
			'It remains possible to fetch the network-wide user meta using standard WP functions.'
		);

		$this->assertNotEquals(
			'bar',
			Users::get_site_user_meta( $customer, 'test_data', true ),
			'Site-specific user meta set at network level is unavailable in the context of a subsite.'
		);

		Users::update_site_user_meta( $customer, 'test_data', 'baz' );

		$this->assertEquals(
			'baz',
			Users::get_site_user_meta( $customer, 'test_data', true ),
			'Once site-specific user meta is set, it will successfully be fetched.'
		);

		$this->assertEquals(
			'foo',
			get_user_meta( $customer, 'test_data', true ),
			'Network-wide user meta remains isolated from site-specific user meta (unless a prefix collision occurs.'
		);

		$this->assertFalse(
			Users::delete_site_user_meta( $customer, 'test_data', 'bad' ),
			'Deletion of site-specific user meta follows the same rules as delete_user_meta when a previous value is provided.'
		);

		$this->assertTrue(
			Users::delete_site_user_meta( $customer, 'test_data', 'baz' ),
			'Deletion of site-specific user meta follows the same rules as delete_user_meta when a previous value is provided.'
		);

		wp_delete_site( $subsite );
		restore_current_blog();
	}

	/**
	 * @testDox Test that we can retrieve a user in the current site.
	 */
	public function test_get_user_in_current_site() {
		$this->skipWithoutMultisite();
		$this->register_legacy_proxy_function_mocks(
			array(
				'user_can' => function ( $user, $capability ) use ( &$manage_network_users ) {
					return 'manage_network_users' === $capability ? $manage_network_users : user_can( $user, $capability );
				},
			)
		);

		// We start with a customer and we make the active user a regular (non super-)admin within the same site.
		$customer             = WC_Helper_Customer::create_customer( 'users_test1', 'pass1', 'users_test1@example.com' );
		$active_user          = self::factory()->user->create( array( 'role' => 'administrator' ) );
		$manage_network_users = false;

		wp_set_current_user( $active_user );
		$this->assertEquals(
			$customer->get_id(),
			Users::get_user_in_current_site( $customer->get_id() )->ID,
			'The active user can see customers who belong to the same site.'
		);

		// We create and switch to a new site within the network. Initially, neither test user is added to this site.
		$subsite = $this->make_network_site( '/independently-operated-site-within-network/' );
		switch_to_blog( $subsite );

		$this->assertWPError(
			Users::get_user_in_current_site( $customer->get_id() ),
			'The active user cannot see the customer, as neither are part of the same site.'
		);

		add_user_to_blog( $subsite, $active_user, 'administrator' );
		$this->assertWPError(
			Users::get_user_in_current_site( $customer->get_id() ),
			'The active user cannot see a customer who is not part of the same site.'
		);

		$manage_network_users = true;
		$this->assertEquals(
			$customer->get_id(),
			Users::get_user_in_current_site( $customer->get_id() )->ID,
			'An active user with manage_network_users capabilities can see customers even if they do not belong to the current site.'
		);

		$manage_network_users = false;
		update_site_option( 'woocommerce_network_wide_customers', 'yes' );
		$this->assertEquals(
			$customer->get_id(),
			Users::get_user_in_current_site( $customer->get_id() )->ID,
			'An active user can see customers who do not belong to the current site if the legacy woocommerce_network_wide_customers mode is enabled.'
		);

		wp_delete_site( $subsite );
		restore_current_blog();
	}

	/**
	 * Creates a new subsite on the network.
	 *
	 * This work is expensive, so should probably be moved to the bootstrap phase (if we detect the tests are running in
	 * a multisite environment) and we can re-use the schema instead of recreating them. Currently, though, this is one
	 * of the only areas where we require this.
	 *
	 * @param string $path Subsite blog path.
	 *
	 * @return int
	 */
	private function make_network_site( string $path ): int {
		$blog_id = $this->factory->blog->create(
			array(
				'path' => $path,
			)
		);

		switch_to_blog( $blog_id );
		WC_Install::install();
		ActionScheduler_Store::instance()->init();
		ActionScheduler_Logger::instance()->init();
		restore_current_blog();

		return $blog_id;
	}
}
