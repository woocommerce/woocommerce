<?php

namespace Automattic\WooCommerce\Tests\ComingSoon;

use Automattic\WooCommerce\Internal\ComingSoon\ComingSoonRequestHandler;
use Automattic\WooCommerce\Admin\Features\Features;

/**
 * Tests for the coming soon cache invalidator class.
 */
class ComingSoonRequestHandlerTest extends \WC_Unit_Test_Case {

	/**
	 * System under test.
	 *
	 * @var ComingSoonRequestHandler;
	 */
	private $sut;

	/**
	 * Setup.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut = wc_get_container()->get( ComingSoonRequestHandler::class );
	}

	/**
	 * @testdox Test request parser displays a coming soon page to public visitor.
	 */
	public function test_coming_soon_mode_shown_to_visitor() {
		update_option( 'woocommerce_coming_soon', 'yes' );
		update_option( 'woocommerce_coming_soon_page_id', 99 );
		$wp          = new \WP();
		$wp->request = '/';
		do_action_ref_array( 'parse_request', array( &$wp ) );

		$this->assertSame( $wp->query_vars['page_id'], 99 );
	}

	/**
	 * @testdox Test request parser displays a live page to public visitor.
	 */
	public function test_live_mode_shown_to_visitor() {
		update_option( 'woocommerce_coming_soon', 'no' );
		update_option( 'woocommerce_coming_soon_page_id', 99 );
		$wp          = new \WP();
		$wp->request = '/';
		do_action_ref_array( 'parse_request', array( &$wp ) );

		$this->assertSame( $wp->query_vars['page_id'], null );
	}

	/**
	 * @testdox Test request parser excludes admins.
	 */
	public function test_shop_manager_exclusion() {
		update_option( 'woocommerce_coming_soon', 'yes' );
		update_option( 'woocommerce_coming_soon_page_id', 99 );
		$user_id = $this->factory->user->create(
			array(
				'role' => 'shop_manager',
			)
		);
		wp_set_current_user( $user_id );

		$wp          = new \WP();
		$wp->request = '/';
		do_action_ref_array( 'parse_request', array( &$wp ) );

		$this->assertSame( $wp->query_vars['page_id'], null );
	}
}
