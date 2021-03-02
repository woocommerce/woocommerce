<?php
/**
 * Favorites tests
 *
 * @package WooCommerce\Admin\Tests\Navigation
 */

use Automattic\WooCommerce\Admin\Features\Navigation\Favorites;


/**
 * Class WC_Tests_Navigation_Favorites
 */
class WC_Tests_Navigation_Favorites extends WC_Unit_Test_Case {

	/**
	 * @var Favorites
	 */
	private $instance;

	/**
	 * setUp
	 */
	public function setUp() {
		parent::setUp();
		$this->instance = new Favorites();
		$this->user     = $this->factory->user->create(
			array(
				'role' => 'administrator',
			)
		);
	}

	/**
	 * Test that errors are given when no user is provided or logged in.
	 */
	public function test_favorites_without_user() {
		$result = $this->instance->add_item( 'menu-item' );
		$this->assertInstanceOf( 'WP_Error', $result );

		$result = $this->instance->remove_item( 'menu-item' );
		$this->assertInstanceOf( 'WP_Error', $result );

		$result = $this->instance->get_all();
		$this->assertInstanceOf( 'WP_Error', $result );
	}

	/**
	 * Test that favorites can be added.
	 */
	public function test_add_favorites() {
		wp_set_current_user( $this->user );

		$result = $this->instance->add_item( 'menu-item' );
		$this->assertTrue( $result );
		$result = $this->instance->add_item( 'menu-item2' );
		$this->assertTrue( $result );

		$favorites = $this->instance->get_all();
		$this->assertContains( 'menu-item', $favorites );
		$this->assertContains( 'menu-item2', $favorites );
	}

	/**
	 * Test that favorites can be removed.
	 */
	public function test_remove_favorites() {
		wp_set_current_user( $this->user );

		$result = $this->instance->add_item( 'item-to-remove' );
		$this->assertTrue( $result );

		$favorites = $this->instance->get_all();
		$this->assertContains( 'item-to-remove', $favorites );

		$result    = $this->instance->remove_item( 'item-to-remove' );
		$favorites = $this->instance->get_all();
		$this->assertNotContains( 'item-to-remove', $favorites );
	}

	/**
	 * Test that existing favorites can not be added again.
	 */
	public function test_add_previously_added_favorite() {
		wp_set_current_user( $this->user );

		$result = $this->instance->add_item( 'duplicate-item' );
		$this->assertTrue( $result );

		$result = $this->instance->add_item( 'duplicate-item' );
		$this->assertInstanceOf( 'WP_Error', $result );
	}

	/**
	 * Test removing a favorite that does not exist.
	 */
	public function test_remove_invalid_favorite() {
		wp_set_current_user( $this->user );

		$result = $this->instance->remove_item( 'does-not-exist' );
		$this->assertInstanceOf( 'WP_Error', $result );
	}
}
