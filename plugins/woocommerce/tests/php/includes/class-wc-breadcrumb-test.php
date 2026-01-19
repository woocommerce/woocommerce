<?php
declare( strict_types = 1 );

/**
 * Tests for WC_Breadcrumb.
 *
 * @package WooCommerce\Tests\Includes
 */

/**
 * WC_Breadcrumb_Test class.
 */
class WC_Breadcrumb_Test extends \WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var WC_Breadcrumb
	 */
	private $sut;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut = new WC_Breadcrumb();
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		parent::tearDown();
		$this->sut->reset();
		global $wp_query, $post;
		$wp_query = null; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$post     = null; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
	}

	/**
	 * @testdox My Account endpoint pages should show correct breadcrumb hierarchy without duplicates.
	 */
	public function test_my_account_endpoint_breadcrumb_shows_correct_hierarchy() {
		$my_account_page_id = wp_insert_post(
			array(
				'post_type'   => 'page',
				'post_status' => 'publish',
				'post_title'  => 'My Account',
				'post_name'   => 'my-account',
			)
		);
		update_option( 'woocommerce_myaccount_page_id', $my_account_page_id );

		global $post, $wp, $wp_query;
		$post = get_post( $my_account_page_id ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

		$wp             = new stdClass(); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$wp->query_vars = array( 'orders' => '' );

		$wp_query                 = new WP_Query(); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$wp_query->is_page        = true;
		$wp_query->queried_object = $post;

		$this->register_legacy_proxy_function_mocks(
			array(
				'is_wc_endpoint_url' => function () {
					return true;
				},
				'is_page'            => function () {
					return true;
				},
				'is_front_page'      => function () {
					return false;
				},
			)
		);

		$mock_query = $this->getMockBuilder( 'WC_Query' )
			->onlyMethods( array( 'get_current_endpoint', 'get_endpoint_title' ) )
			->getMock();
		$mock_query->expects( $this->any() )
			->method( 'get_current_endpoint' )
			->willReturn( 'orders' );
		$mock_query->expects( $this->any() )
			->method( 'get_endpoint_title' )
			->with( 'orders', '' )
			->willReturn( 'Orders' );

		WC()->query = $mock_query;

		$this->sut->add_crumb( 'Home', home_url() );
		$this->sut->generate();

		$breadcrumbs = $this->sut->get_breadcrumb();

		$this->assertCount( 3, $breadcrumbs );
		$this->assertEquals( 'Home', $breadcrumbs[0][0] );
		$this->assertEquals( 'My Account', $breadcrumbs[1][0] );
		$this->assertEquals( 'Orders', $breadcrumbs[2][0] );
		$this->assertNotEquals( $breadcrumbs[1][0], $breadcrumbs[2][0] );

		wp_delete_post( $my_account_page_id, true );
		delete_option( 'woocommerce_myaccount_page_id' );
	}

	/**
	 * @testdox My Account edit-address endpoint should show correct breadcrumb hierarchy.
	 */
	public function test_my_account_edit_address_endpoint_breadcrumb_shows_correct_hierarchy() {
		$my_account_page_id = wp_insert_post(
			array(
				'post_type'   => 'page',
				'post_status' => 'publish',
				'post_title'  => 'My Account',
				'post_name'   => 'my-account',
			)
		);
		update_option( 'woocommerce_myaccount_page_id', $my_account_page_id );

		global $post, $wp, $wp_query;
		$post = get_post( $my_account_page_id ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

		$wp             = new stdClass(); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$wp->query_vars = array( 'edit-address' => 'billing' );

		$wp_query                 = new WP_Query(); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$wp_query->is_page        = true;
		$wp_query->queried_object = $post;

		$this->register_legacy_proxy_function_mocks(
			array(
				'is_wc_endpoint_url' => function () {
					return true;
				},
				'is_page'            => function () {
					return true;
				},
				'is_front_page'      => function () {
					return false;
				},
			)
		);

		$mock_query = $this->getMockBuilder( 'WC_Query' )
			->onlyMethods( array( 'get_current_endpoint', 'get_endpoint_title' ) )
			->getMock();
		$mock_query->expects( $this->any() )
			->method( 'get_current_endpoint' )
			->willReturn( 'edit-address' );
		$mock_query->expects( $this->any() )
			->method( 'get_endpoint_title' )
			->with( 'edit-address', '' )
			->willReturn( 'Addresses' );

		WC()->query = $mock_query;

		$this->sut->add_crumb( 'Home', home_url() );
		$this->sut->generate();

		$breadcrumbs = $this->sut->get_breadcrumb();

		$this->assertCount( 3, $breadcrumbs );
		$this->assertEquals( 'Home', $breadcrumbs[0][0] );
		$this->assertEquals( 'My Account', $breadcrumbs[1][0] );
		$this->assertEquals( 'Addresses', $breadcrumbs[2][0] );
		$this->assertNotEquals( $breadcrumbs[1][0], $breadcrumbs[2][0] );

		wp_delete_post( $my_account_page_id, true );
		delete_option( 'woocommerce_myaccount_page_id' );
	}

	/**
	 * @testdox Regular pages without endpoints should work correctly.
	 */
	public function test_regular_page_breadcrumb_works_correctly() {
		$page_id = wp_insert_post(
			array(
				'post_type'   => 'page',
				'post_status' => 'publish',
				'post_title'  => 'About Us',
				'post_name'   => 'about-us',
			)
		);

		global $post, $wp_query;
		$post = get_post( $page_id ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

		$wp_query                 = new WP_Query(); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$wp_query->is_page        = true;
		$wp_query->queried_object = $post;

		$this->register_legacy_proxy_function_mocks(
			array(
				'is_wc_endpoint_url' => function () {
					return false;
				},
				'is_page'            => function () {
					return true;
				},
				'is_front_page'      => function () {
					return false;
				},
			)
		);

		$this->sut->add_crumb( 'Home', home_url() );
		$this->sut->generate();

		$breadcrumbs = $this->sut->get_breadcrumb();

		$this->assertCount( 2, $breadcrumbs );
		$this->assertEquals( 'Home', $breadcrumbs[0][0] );
		$this->assertEquals( 'About Us', $breadcrumbs[1][0] );

		wp_delete_post( $page_id, true );
	}
}
