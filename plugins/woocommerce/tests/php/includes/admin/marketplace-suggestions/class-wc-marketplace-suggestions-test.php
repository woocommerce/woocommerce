<?php
declare( strict_types = 1 );

/**
 * Tests for WC_Marketplace_Suggestions feed filtering and the products list empty state setting.
 */
class WC_Marketplace_Suggestions_Test extends WC_Unit_Test_Case {

	/**
	 * Set up an admin user, one active extension and a fresh feed.
	 */
	public function setUp(): void {
		parent::setUp();
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		update_option( 'active_plugins', array( 'woocommerce/woocommerce.php', 'woocommerce-subscriptions/woocommerce-subscriptions.php' ) );
		$this->set_feed(
			array(
				$this->item( 'bookings', 'woocommerce-bookings' ),
				$this->item( 'subscriptions', 'woocommerce-subscriptions' ),
				$this->item( 'dismissed', 'product-bundles' ),
				array_merge( $this->item( 'orders', 'shipstation' ), array( 'context' => 'orders-list-empty-body' ) ),
				array_merge( $this->item( 'memberships', 'woocommerce-memberships' ), array( 'context' => array( 'products-list-empty-body', 'product-edit-meta-tab-body' ) ) ),
			)
		);
		update_user_meta( get_current_user_id(), 'wc_marketplace_suggestions_dismissed_suggestions', array( 'dismissed' ) );
	}

	/**
	 * Clean up the request globals.
	 */
	public function tearDown(): void {
		$_GET = array();
		parent::tearDown();
	}

	/**
	 * @testdox Should return items for the context, skipping dismissed ones and active plugins.
	 */
	public function test_filters_suggestions_for_context(): void {
		$slugs = array_column( WC_Marketplace_Suggestions::get_suggestions_for_context( 'products-list-empty-body' ), 'slug' );

		$this->assertSame( array( 'bookings', 'memberships' ), $slugs );
	}

	/**
	 * @testdox Should cap results at the feed's recommendations-count.
	 */
	public function test_caps_at_recommendations_count(): void {
		$data = get_option( 'woocommerce_marketplace_suggestions' );
		$data['suggestions'][0]['recommendations-count'] = 1;
		update_option( 'woocommerce_marketplace_suggestions', $data );

		$this->assertCount( 1, WC_Marketplace_Suggestions::get_suggestions_for_context( 'products-list-empty-body' ) );
	}

	/**
	 * @testdox Should add the setting on the add products page reached from the products list.
	 */
	public function test_adds_setting_from_products_list(): void {
		$_GET = array(
			'page' => 'wc-admin',
			'path' => '/add-product',
			'from' => 'products-list',
		);

		$settings = WC_Marketplace_Suggestions::add_products_list_empty_suggestions_setting( array() );

		$this->assertSame(
			array(
				'slug'     => 'bookings',
				'product'  => 'woocommerce-bookings',
				'title'    => 'bookings',
				'copy'     => 'Copy.',
				'icon'     => 'https://woocommerce.com/icon.png',
				'url'      => 'https://woocommerce.com/products/woocommerce-bookings/',
				'promoted' => '',
			),
			$settings['productsListEmptySuggestions'][0]
		);
	}

	/**
	 * @testdox Should leave settings unchanged when the page wasn't reached from the products list.
	 */
	public function test_leaves_settings_on_other_pages(): void {
		$_GET = array(
			'page' => 'wc-admin',
			'path' => '/add-product',
		);

		$this->assertSame( array(), WC_Marketplace_Suggestions::add_products_list_empty_suggestions_setting( array() ) );
	}

	/**
	 * Store a feed in the suggestions option, marked fresh so no update gets queued.
	 *
	 * @param array $items Feed items.
	 */
	private function set_feed( array $items ): void {
		update_option(
			'woocommerce_marketplace_suggestions',
			array(
				'suggestions' => array_merge( array( array( 'recommendations-count' => 5 ) ), $items ),
				'updated'     => time(),
			)
		);
	}

	/**
	 * Build a products-list-empty-body feed item.
	 *
	 * @param string $slug    Suggestion slug.
	 * @param string $product Plugin slug.
	 * @return array
	 */
	private function item( string $slug, string $product ): array {
		return array(
			'slug'    => $slug,
			'context' => 'products-list-empty-body',
			'product' => $product,
			'title'   => $slug,
			'copy'    => 'Copy.',
			'icon'    => 'https://woocommerce.com/icon.png',
			'url'     => 'https://woocommerce.com/products/' . $product . '/',
		);
	}
}
