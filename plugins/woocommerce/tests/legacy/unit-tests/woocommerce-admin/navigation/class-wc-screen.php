<?php
/**
 * Screen tests
 *
 * @package WooCommerce\Admin\Tests\Navigation
 */

use Automattic\WooCommerce\Admin\Features\Navigation\Screen;

/*
 * This is required to allow manually setting of the screen during testing.
 * phpcs:disable WordPress.WP.GlobalVariablesOverride.Prohibited
 */

/**
 * Class WC_Admin_Tests_Navigation_Screen
 */
class WC_Admin_Tests_Navigation_Screen extends WC_Unit_Test_Case {

	/**
	 * @var Screen
	 */
	private $instance;

	/**
	 * setUp
	 */
	public function setUp(): void {
		parent::setUp();
		$this->instance = new Screen();

		// Store globals for reset.
		global $pagenow, $current_screen;
		$this->_current_screen = $current_screen;
		$this->_pagenow        = $pagenow;
		/* phpcs:disable WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash */
		$this->_post      = isset( $_GET['post'] ) ? $_GET['post'] : null;
		$this->_post_type = isset( $_GET['post_type'] ) ? $_GET['post_type'] : null;
		$this->_taxonomy  = isset( $_GET['taxonomy'] ) ? $_GET['taxonomy'] : null;
		/* phpcs:enable WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash */

		// Register a post type.
		register_post_type( 'my-post-type' );
	}

	/**
	 * Reset globals.
	 */
	public function reset_globals() {
		global $pagenow, $current_screen;

		$current_screen    = $this->_current_screen;
		$pagenow           = $this->_pagenow;
		$_GET['post']      = $this->_post;
		$_GET['post_type'] = $this->_post_type;
		$_GET['taxonomy']  = $this->_taxonomy;
	}

	/**
	 * Test that screen IDs are correctly registered.
	 */
	public function test_add_screen() {
		$this->instance->add_screen( 'my-screen' );
		// Test adding a duplicate screen ID.
		$this->instance->add_screen( 'my-screen' );
		$this->instance->add_screen( 'plugins.php' );

		$screen_ids = $this->instance->get_screen_ids();

		$this->assertCount( 2, $screen_ids );
		$this->assertContains( 'admin_page_my-screen', $screen_ids );
		$this->assertContains( 'admin_page_plugins', $screen_ids );
	}

	/**
	 * Test that taxonomies are correctly registered.
	 */
	public function test_register_taxonomy() {
		$this->instance->register_taxonomy( 'my-taxonomy' );
		// Test adding a duplicate taxonomy.
		$this->instance->register_taxonomy( 'my-taxonomy' );

		$taxonomies = $this->instance->get_taxonomies();

		$this->assertCount( 1, $taxonomies );
		$this->assertContains( 'my-taxonomy', $taxonomies );
	}

	/**
	 * Test that post types are correctly registered.
	 */
	public function test_register_post_type() {
		$this->instance->register_post_type( 'my-post-type' );
		// Test adding a duplicate post type.
		$this->instance->register_post_type( 'my-post-type' );

		$post_types = $this->instance->get_post_types();

		$this->assertCount( 1, $post_types );
		$this->assertContains( 'my-post-type', $post_types );
	}

	/**
	 * Test that screens can be detected.
	 */
	public function test_is_woocommerce_page_screens() {
		$this->instance->add_screen( 'my-screen' );

		global $pagenow, $current_screen;

		$this->assertFalse( $this->instance->is_woocommerce_page() );
		$current_screen = (object) array( 'id' => 'admin_page_my-screen' );
		$this->assertTrue( $this->instance->is_woocommerce_page() );
		$current_screen = (object) array( 'id' => 'admin_page_different-screen' );
		$this->assertFalse( $this->instance->is_woocommerce_page() );

		$this->reset_globals();
	}

	/**
	 * Test that taxonomies can be detected.
	 */
	public function test_is_woocommerce_page_taxonomies() {
		global $pagenow, $current_screen;

		$this->instance->register_taxonomy( 'my-taxonomy' );

		$pagenow          = 'edit-tags.php';
		$_GET['taxonomy'] = 'my-taxonomy';
		$this->assertTrue( $this->instance->is_woocommerce_page() );
		$pagenow = 'term.php';
		$this->assertTrue( $this->instance->is_woocommerce_page() );
		$_GET['taxonomy'] = 'different-taxonomy';
		$this->assertFalse( $this->instance->is_woocommerce_page() );
		$pagenow          = 'edit.php';
		$_GET['taxonomy'] = 'my-taxonomy';
		$this->assertFalse( $this->instance->is_woocommerce_page() );

		// Core taxonomies.
		$pagenow          = 'edit-tags.php';
		$_GET['taxonomy'] = 'product_cat';
		$this->assertTrue( $this->instance->is_woocommerce_page() );
		$pagenow          = 'edit-tags.php';
		$_GET['taxonomy'] = 'product_tag';
		$this->assertTrue( $this->instance->is_woocommerce_page() );

		$this->reset_globals();
	}

	/**
	 * Test that post types can be detected.
	 */
	public function test_is_woocommerce_page_post_types() {
		global $pagenow, $current_screen;

		$this->instance->register_post_type( 'my-post-type' );

		$pagenow           = 'edit.php';
		$_GET['taxonomy']  = null;
		$_GET['post_type'] = 'my-post-type';
		$this->assertTrue( $this->instance->is_woocommerce_page() );
		$pagenow = 'post.php';
		$this->assertTrue( $this->instance->is_woocommerce_page() );
		$pagenow = 'post-new.php';
		$this->assertTrue( $this->instance->is_woocommerce_page() );
		$_GET['post_type'] = 'different-post-type';
		$this->assertFalse( $this->instance->is_woocommerce_page() );
		$pagenow           = 'edit-tags.php';
		$_GET['post_type'] = 'my-post-type';
		$this->assertFalse( $this->instance->is_woocommerce_page() );

		$my_post_id = wp_insert_post(
			array(
				'post_title' => 'Test post type',
				'post_type'  => 'my-post-type',
			)
		);

		$different_post_id = wp_insert_post(
			array(
				'post_title' => 'Different post type',
				'post_type'  => 'post',
			)
		);

		$_GET['post'] = $my_post_id;
		$pagenow      = 'edit.php';
		$this->assertTrue( $this->instance->is_woocommerce_page() );

		$_GET['post'] = $different_post_id;
		$pagenow      = 'edit.php';
		$this->assertFalse( $this->instance->is_woocommerce_page() );

		$this->reset_globals();
	}
}

/* phpcs:enable WordPress.WP.GlobalVariablesOverride.Prohibited */
