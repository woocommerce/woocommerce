<?php
/**
 * Tests for WooCommerce page functions, specifically nav-related helpers.
 *
 * @package WooCommerce\Tests\Util
 */

/**
 * Class WC_Tests_Page_Functions
 */
class WC_Tests_Page_Functions extends WC_Unit_Test_Case {

	/**
	 * The original "shop" page id, restored in tearDown.
	 *
	 * @var int
	 */
	private $original_shop_page_id;

	/**
	 * Set up: ensure a shop page is configured.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->original_shop_page_id = (int) get_option( 'woocommerce_shop_page_id', 0 );

		if ( $this->original_shop_page_id <= 0 ) {
			$page_id = wp_insert_post(
				array(
					'post_title'  => 'Shop',
					'post_status' => 'publish',
					'post_type'   => 'page',
					'post_name'   => 'shop',
				)
			);

			update_option( 'woocommerce_shop_page_id', $page_id );
		}
	}

	/**
	 * Tear down.
	 */
	public function tearDown(): void {
		update_option( 'woocommerce_shop_page_id', $this->original_shop_page_id );
		parent::tearDown();
	}

	/**
	 * Build a minimal core/navigation-link parsed-block array.
	 *
	 * @param int $id Linked post id.
	 * @return array
	 */
	private function make_nav_link_block( $id ) {
		return array(
			'blockName'    => 'core/navigation-link',
			'attrs'        => array(
				'id'    => $id,
				'label' => 'Shop',
				'url'   => 'http://example.com/shop/',
				'kind'  => 'post-type',
				'type'  => 'page',
			),
			'innerBlocks'  => array(),
			'innerHTML'    => '',
			'innerContent' => array(),
		);
	}

	/**
	 * @testdox Adds current-menu-item class to navigation-link block targeting the shop page when viewing the shop.
	 */
	public function test_navigation_link_block_gets_current_menu_item_on_shop() {
		$shop_id = (int) wc_get_page_id( 'shop' );
		$this->assertGreaterThan( 0, $shop_id, 'Shop page must exist for this test.' );

		// Put WP into a state where is_shop() returns true. The simplest route in unit tests
		// is to "visit" the configured shop page, which makes is_page( shop_id ) true.
		$this->go_to( get_permalink( $shop_id ) );

		$this->assertTrue( is_shop(), 'Pre-condition: is_shop() should be true after navigating to the shop page.' );

		$rendered = '<li class="wp-block-navigation-item"><a href="http://example.com/shop/">Shop</a></li>';
		$block    = $this->make_nav_link_block( $shop_id );

		$result = wc_nav_menu_link_block_current_shop( $rendered, $block );

		$this->assertStringContainsString( 'current-menu-item', $result, 'Shop link should receive current-menu-item class on shop view.' );
		$this->assertStringContainsString( 'current_page_item', $result, 'Shop link should also receive current_page_item class for parity with classic menus.' );
		$this->assertStringContainsString( 'aria-current="page"', $result, 'Shop link anchor should advertise aria-current="page" for assistive tech.' );
	}

	/**
	 * @testdox Adds current_page_parent class on single product view for the shop nav link.
	 */
	public function test_navigation_link_block_gets_current_parent_on_product() {
		$shop_id = (int) wc_get_page_id( 'shop' );

		$product = new WC_Product_Simple();
		$product->set_name( 'Test product' );
		$product->save();

		$this->go_to( get_permalink( $product->get_id() ) );

		if ( ! is_singular( 'product' ) ) {
			$this->markTestSkipped( 'Could not enter single product context in this test environment.' );
		}

		$rendered = '<li class="wp-block-navigation-item"><a href="http://example.com/shop/">Shop</a></li>';
		$block    = $this->make_nav_link_block( $shop_id );

		$result = wc_nav_menu_link_block_current_shop( $rendered, $block );

		$this->assertStringContainsString( 'current_page_parent', $result, 'Shop link should receive current_page_parent on single product views.' );
		$this->assertStringContainsString( 'current-menu-ancestor', $result, 'Shop link should also receive current-menu-ancestor on single product views.' );
		$this->assertStringNotContainsString( 'aria-current=', $result, 'aria-current must not be set on ancestor links.' );

		$product->delete( true );
	}

	/**
	 * @testdox Leaves non-navigation-link blocks untouched.
	 */
	public function test_other_blocks_are_untouched() {
		$shop_id = (int) wc_get_page_id( 'shop' );

		$rendered = '<p>some content</p>';
		$block    = array(
			'blockName' => 'core/paragraph',
			'attrs'     => array( 'id' => $shop_id ),
		);

		$this->assertSame( $rendered, wc_nav_menu_link_block_current_shop( $rendered, $block ) );
	}

	/**
	 * @testdox Leaves navigation-link blocks pointing at non-shop pages untouched.
	 */
	public function test_non_shop_navigation_link_is_untouched() {
		$other_page_id = wp_insert_post(
			array(
				'post_title'  => 'About',
				'post_status' => 'publish',
				'post_type'   => 'page',
			)
		);

		$rendered = '<li class="wp-block-navigation-item"><a href="http://example.com/about/">About</a></li>';
		$block    = $this->make_nav_link_block( (int) $other_page_id );

		$this->assertSame( $rendered, wc_nav_menu_link_block_current_shop( $rendered, $block ) );
	}

	/**
	 * @testdox Leaves shop navigation-link untouched when not viewing shop or a product.
	 */
	public function test_shop_navigation_link_untouched_when_not_on_shop_or_product() {
		$shop_id = (int) wc_get_page_id( 'shop' );

		// Navigate to the homepage so neither is_shop() nor is_singular( 'product' ) is true.
		$this->go_to( home_url( '/' ) );

		if ( is_shop() || is_singular( 'product' ) ) {
			$this->markTestSkipped( 'Unable to leave shop/product context in this test environment.' );
		}

		$rendered = '<li class="wp-block-navigation-item"><a href="http://example.com/shop/">Shop</a></li>';
		$block    = $this->make_nav_link_block( $shop_id );

		$this->assertSame( $rendered, wc_nav_menu_link_block_current_shop( $rendered, $block ) );
	}

	/**
	 * @testdox Returns empty content unchanged.
	 */
	public function test_empty_block_content_is_passthrough() {
		$shop_id = (int) wc_get_page_id( 'shop' );

		$this->assertSame( '', wc_nav_menu_link_block_current_shop( '', $this->make_nav_link_block( $shop_id ) ) );
	}
}
