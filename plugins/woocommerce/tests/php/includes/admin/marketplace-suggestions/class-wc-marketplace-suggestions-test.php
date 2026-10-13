<?php
declare( strict_types = 1 );

/**
 * Tests for WC_Marketplace_Suggestions feed filtering and the products list empty state setting.
 */
class WC_Marketplace_Suggestions_Test extends WC_Unit_Test_Case {

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		update_option( 'active_plugins', array( 'woocommerce/woocommerce.php', 'woocommerce-subscriptions/woocommerce-subscriptions.php' ) );
	}

	/**
	 * Store a mock feed in the suggestions option, marked fresh so no update gets queued.
	 *
	 * @param array $items Feed items, after the recommendations-count element.
	 * @param mixed $count Value of recommendations-count.
	 */
	private function set_feed( array $items, $count = 5 ): void {
		update_option(
			'woocommerce_marketplace_suggestions',
			array(
				'suggestions' => array_merge( array( array( 'recommendations-count' => $count ) ), $items ),
				'updated'     => time(),
			)
		);
	}

	/**
	 * Get the slugs of the suggestions for a context.
	 *
	 * @param string $context Display context.
	 * @return string[]
	 */
	private function get_slugs( string $context ): array {
		return array_column( WC_Marketplace_Suggestions::get_suggestions_for_context( $context ), 'slug' );
	}

	/**
	 * @testdox Should match string and array contexts in feed order and skip malformed items.
	 */
	public function test_matches_context_in_feed_order(): void {
		$this->set_feed(
			array(
				array(
					'slug'    => 'a',
					'context' => 'products-list-empty-body',
				),
				'not-an-item',
				array(
					'slug'    => 'b',
					'context' => 'orders-list-empty-body',
				),
				array(
					'slug'    => 'c',
					'context' => array( 'orders-list-empty-body', 'products-list-empty-body' ),
				),
				array( 'context' => 'products-list-empty-body' ),
			)
		);

		$this->assertSame( array( 'a', 'c' ), $this->get_slugs( 'products-list-empty-body' ) );
	}

	/**
	 * @testdox Should skip suggestions the current user dismissed.
	 */
	public function test_skips_dismissed_suggestions(): void {
		$this->set_feed(
			array(
				array(
					'slug'    => 'a',
					'context' => 'products-list-empty-body',
				),
				array(
					'slug'    => 'b',
					'context' => 'products-list-empty-body',
				),
			)
		);
		update_user_meta( get_current_user_id(), 'wc_marketplace_suggestions_dismissed_suggestions', array( 'a' ) );

		$this->assertSame( array( 'b' ), $this->get_slugs( 'products-list-empty-body' ) );
	}

	/**
	 * @testdox Should skip active products and suggestions whose show-if-active plugins are not active.
	 */
	public function test_applies_active_plugin_rules(): void {
		$this->set_feed(
			array(
				array(
					'slug'    => 'installed',
					'context' => 'products-list-empty-body',
					'product' => 'woocommerce-subscriptions',
				),
				array(
					'slug'           => 'prerequisite-active',
					'context'        => 'products-list-empty-body',
					'product'        => 'woocommerce-all-products-for-subscriptions',
					'show-if-active' => array( 'woocommerce-subscriptions' ),
				),
				array(
					'slug'           => 'prerequisite-missing',
					'context'        => 'products-list-empty-body',
					'product'        => 'woocommerce-bookings-availability',
					'show-if-active' => array( 'woocommerce-bookings' ),
				),
				array(
					'slug'    => 'not-installed',
					'context' => 'products-list-empty-body',
					'product' => 'woocommerce-bookings',
				),
			)
		);

		$this->assertSame( array( 'prerequisite-active', 'not-installed' ), $this->get_slugs( 'products-list-empty-body' ) );
	}

	/**
	 * @testdox Should cap results at a valid recommendations-count and fall back to 5 otherwise.
	 *
	 * @testWith [3, 3]
	 *           [0, 5]
	 *           [51, 5]
	 *           ["many", 5]
	 *
	 * @param mixed $count    Feed recommendations-count.
	 * @param int   $expected Expected number of suggestions.
	 */
	public function test_caps_at_recommendations_count( $count, int $expected ): void {
		$items = array();
		for ( $i = 0; $i < 7; $i++ ) {
			$items[] = array(
				'slug'    => "item-$i",
				'context' => 'products-list-empty-body',
			);
		}
		$this->set_feed( $items, $count );

		$this->assertCount( $expected, $this->get_slugs( 'products-list-empty-body' ) );
	}

	/**
	 * @testdox Should add the products list empty suggestions setting on the add products page reached from the products list.
	 */
	public function test_adds_shared_setting_from_products_list(): void {
		$this->set_feed(
			array(
				array(
					'slug'        => 'products-empty-bookings',
					'context'     => 'products-list-empty-body',
					'product'     => 'woocommerce-bookings',
					'title'       => 'Bookings',
					'copy'        => 'Let customers book appointments.',
					'icon'        => 'https://woocommerce.com/icon.png',
					'url'         => 'https://woocommerce.com/products/woocommerce-bookings/',
					'button-text' => 'Learn More',
					'promoted'    => 'category-bookings',
				),
				array(
					'slug'    => 'bad-url',
					'context' => 'products-list-empty-body',
					'url'     => 'javascript:alert(1)',
				),
			)
		);
		$_GET = array(
			'page' => 'wc-admin',
			'path' => '/add-product',
			'from' => 'products-list',
		);

		$settings = WC_Marketplace_Suggestions::add_products_list_empty_suggestions_setting( array( 'other' => 1 ) );

		$this->assertSame(
			array(
				'other'                        => 1,
				'productsListEmptySuggestions' => array(
					array(
						'slug'     => 'products-empty-bookings',
						'product'  => 'woocommerce-bookings',
						'title'    => 'Bookings',
						'copy'     => 'Let customers book appointments.',
						'icon'     => 'https://woocommerce.com/icon.png',
						'url'      => 'https://woocommerce.com/products/woocommerce-bookings/',
						'promoted' => 'category-bookings',
					),
				),
			),
			$settings
		);
	}

	/**
	 * @testdox Should leave shared settings unchanged on other pages.
	 *
	 * @testWith ["wc-admin", "/add-product", ""]
	 *           ["wc-admin", "/", "products-list"]
	 *           ["wc-settings", "/add-product", "products-list"]
	 *
	 * @param string $page Page query arg.
	 * @param string $path Path query arg.
	 * @param string $from From query arg.
	 */
	public function test_leaves_shared_settings_on_other_pages( string $page, string $path, string $from ): void {
		$this->set_feed(
			array(
				array(
					'slug'    => 'a',
					'context' => 'products-list-empty-body',
					'url'     => 'https://woocommerce.com/products/a/',
				),
			)
		);
		$_GET = array_filter( compact( 'page', 'path', 'from' ) );

		$this->assertSame( array( 'other' => 1 ), WC_Marketplace_Suggestions::add_products_list_empty_suggestions_setting( array( 'other' => 1 ) ) );
	}
}
