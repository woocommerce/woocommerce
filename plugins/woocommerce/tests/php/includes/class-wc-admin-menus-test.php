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
	 * Holds the original current user ID when a test changes it.
	 *
	 * @var int|null
	 */
	private $current_user_backup = null;

	/**
	 * Set up test data.
	 */
	public function setUp(): void {
		parent::setUp();
		global $wp_meta_boxes;
		$this->wp_meta_boxes_backup = isset( $wp_meta_boxes ) ? $wp_meta_boxes : array();
		$this->current_user_backup  = get_current_user_id();
	}

	/**
	 * Tear down test data.
	 */
	public function tearDown(): void {
		if ( $this->brand_taxonomy_backup instanceof WP_Taxonomy ) {
			// Restore the original registration directly; register_taxonomy() takes a 'capabilities'
			// array arg but WP_Taxonomy only exposes a 'cap' object, so casting the object back
			// would silently drop the real capabilities.
			$GLOBALS['wp_taxonomies']['product_brand'] = $this->brand_taxonomy_backup; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

			// unregister_taxonomy() also called remove_rewrite_rules() and remove_hooks(),
			// which mutate $wp_rewrite and $wp_filter. Undo both to fully restore state.
			$this->brand_taxonomy_backup->add_rewrite_rules();
			$this->brand_taxonomy_backup->add_hooks();
		}
		$GLOBALS['wp_meta_boxes'] = $this->wp_meta_boxes_backup;  // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		wp_set_current_user( $this->current_user_backup );
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
		$this->assertNotContains( 'woocommerce_endpoints_nav_link', $hidden );
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

	/**
	 * A box registered after admin_head-nav-menus.php fires (the "WooCommerce
	 * endpoints" box) must stay visible. The option is read twice on the same
	 * page load: once before admin_head and again inside do_accordion_sections()
	 * after admin_head-nav-menus.php. Memoization keeps the hidden list stable
	 * across both reads so the late-registered box is not swept into it.
	 */
	public function test_filter_keeps_endpoints_box_visible_after_late_registration() {
		$GLOBALS['wp_meta_boxes'] = $this->get_nav_meta_boxes();  // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$user_id                  = $this->factory->user->create();

		$menus = new WC_Admin_Menus();
		$menus->register_default_nav_menu_meta_boxes_filter();

		// First read happens before admin_head-nav-menus.php, so the endpoints box is not yet registered.
		$hidden_before = get_user_option( 'metaboxhidden_nav-menus', $user_id );

		// Simulate admin_head-nav-menus.php registering the WooCommerce endpoints box.
		$GLOBALS['wp_meta_boxes']['nav-menus']['side']['low']['woocommerce_endpoints_nav_link'] = array( // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
			'id'    => 'woocommerce_endpoints_nav_link',
			'title' => 'WooCommerce endpoints',
		);

		// Second read happens inside do_accordion_sections(), after the box was registered.
		$hidden_after = get_user_option( 'metaboxhidden_nav-menus', $user_id );

		$this->assertIsArray( $hidden_before );
		$this->assertIsArray( $hidden_after );
		$this->assertSame( $hidden_before, $hidden_after );
		$this->assertNotContains( 'woocommerce_endpoints_nav_link', $hidden_after );
	}
}
