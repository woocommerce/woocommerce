<?php
/**
 * WC_Admin_Menus unit tests.
 *
 * @package WooCommerce
 */

declare( strict_types = 1 );

/**
 * WC_Admin_Menus_Test
 */
class WC_Admin_Menus_Test extends WC_Unit_Test_Case {

	/**
	 * Holds the original $wp_meta_boxes global state.
	 *
	 * @var array|null
	 */
	private $wp_meta_boxes_backup = null;

	/**
	 * Holds the original product_brand taxonomy object when a test unregisters it.
	 *
	 * @var WP_Taxonomy|false|null
	 */
	private $brand_taxonomy_backup = null;

	/**
	 * Set up test data.
	 */
	public function setUp(): void {
		parent::setUp();
		global $wp_meta_boxes;
		$this->wp_meta_boxes_backup = isset( $wp_meta_boxes ) ? $wp_meta_boxes : array();
	}

	/**
	 * Tear down test data.
	 */
	public function tearDown(): void {
		if ( null !== $this->brand_taxonomy_backup && $this->brand_taxonomy_backup instanceof WP_Taxonomy ) {
			register_taxonomy(
				$this->brand_taxonomy_backup->name,
				$this->brand_taxonomy_backup->object_type,
				(array) $this->brand_taxonomy_backup
			);
		}
		$GLOBALS['wp_meta_boxes'] = $this->wp_meta_boxes_backup;  // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		parent::tearDown();
	}

	/**
	 * Build a fake nav-menus meta box registry for testing.
	 *
	 * @return array
	 */
	private function get_nav_meta_boxes() {
		return array(
			'nav-menus' => array(
				'side' => array(
					'default' => array(
						'add-post-type-page' => array(
							'id'    => 'add-post-type-page',
							'title' => 'Pages',
						),
						'add-post-type-post' => array(
							'id'    => 'add-post-type-post',
							'title' => 'Posts',
						),
						'add-custom-links'   => array(
							'id'    => 'add-custom-links',
							'title' => 'Custom Links',
						),
						'add-category'       => array(
							'id'    => 'add-category',
							'title' => 'Categories',
						),
						'add-product_cat'    => array(
							'id'    => 'add-product_cat',
							'title' => 'Product Categories',
						),
						'add-product_tag'    => array(
							'id'    => 'add-product_tag',
							'title' => 'Product Tags',
						),
						'add-product_brand'  => array(
							'id'    => 'add-product_brand',
							'title' => 'Brands',
						),
						'add-post-type-news' => array(
							'id'    => 'add-post-type-news',
							'title' => 'News',
						),
					),
				),
			),
		);
	}

	/**
	 * First-time users should see the WC taxonomy boxes on the nav-menus screen
	 * while third-party boxes remain hidden by default.
	 */
	public function test_filter_returns_wc_taxonomy_boxes_visible_for_first_time_user() {
		$GLOBALS['wp_meta_boxes'] = $this->get_nav_meta_boxes();  // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$user_id                  = $this->factory->user->create();

		$menus = new WC_Admin_Menus();
		$menus->register_default_nav_menu_meta_boxes_filter();

		$hidden = get_user_option( 'metaboxhidden_nav-menus', $user_id );

		$this->assertIsArray( $hidden );
		$this->assertContains( 'add-post-type-news', $hidden );
		$this->assertNotContains( 'add-post-type-page', $hidden );
		$this->assertNotContains( 'add-post-type-post', $hidden );
		$this->assertNotContains( 'add-custom-links', $hidden );
		$this->assertNotContains( 'add-category', $hidden );
		$this->assertNotContains( 'add-product_cat', $hidden );
		$this->assertNotContains( 'add-product_tag', $hidden );
		$this->assertNotContains( 'add-product_brand', $hidden );
	}

	/**
	 * Existing users who already saved a preference should keep it unchanged.
	 */
	public function test_filter_preserves_existing_user_preference() {
		$user_id = $this->factory->user->create();
		update_user_option( $user_id, 'metaboxhidden_nav-menus', array( 'add-product_cat' ) );

		$menus = new WC_Admin_Menus();
		$menus->register_default_nav_menu_meta_boxes_filter();

		$hidden = get_user_option( 'metaboxhidden_nav-menus', $user_id );

		$this->assertSame( array( 'add-product_cat' ), $hidden );
	}

	/**
	 * When the user ID is invalid, get_user_option short-circuits before the
	 * filter is reached, so the default value stays unchanged.
	 */
	public function test_filter_does_not_reach_callback_when_user_not_logged_in() {
		wp_set_current_user( 0 );

		$menus = new WC_Admin_Menus();
		$menus->register_default_nav_menu_meta_boxes_filter();

		$hidden = get_user_option( 'metaboxhidden_nav-menus' );

		// get_user_option returns false for an invalid user ID, so the default filter should not be reached.
		$this->assertFalse( $hidden );
	}

	/**
	 * The callback should return the default value unchanged when no user object is passed.
	 */
	public function test_filter_returns_default_value_when_no_user_object() {
		$menus  = new WC_Admin_Menus();
		$hidden = $menus->filter_default_nav_menu_hidden_meta_boxes( false, 'metaboxhidden_nav-menus', null );

		$this->assertFalse( $hidden );
	}

	/**
	 * When the Brands taxonomy is not registered, the brand box should not be treated as visible by default.
	 */
	public function test_filter_keeps_brand_box_hidden_when_brand_taxonomy_unregistered() {
		$this->brand_taxonomy_backup = get_taxonomy( 'product_brand' );
		unregister_taxonomy( 'product_brand' );

		$GLOBALS['wp_meta_boxes'] = $this->get_nav_meta_boxes();  // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$user_id                  = $this->factory->user->create();

		$menus = new WC_Admin_Menus();
		$menus->register_default_nav_menu_meta_boxes_filter();

		$hidden = get_user_option( 'metaboxhidden_nav-menus', $user_id );

		$this->assertIsArray( $hidden );
		$this->assertContains( 'add-product_brand', $hidden );
		$this->assertNotContains( 'add-product_cat', $hidden );
		$this->assertNotContains( 'add-product_tag', $hidden );
	}
}
