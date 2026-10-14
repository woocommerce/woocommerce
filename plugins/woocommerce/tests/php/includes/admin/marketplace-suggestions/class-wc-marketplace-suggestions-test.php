<?php
declare( strict_types = 1 );

/**
 * Tests for WC_Marketplace_Suggestions feed filtering and the products list empty state setting.
 */
class WC_Marketplace_Suggestions_Test extends WC_Unit_Test_Case {

	/**
	 * Request query vars before the test, restored in tearDown().
	 *
	 * @var array
	 */
	private $original_get;

	/**
	 * Set up an admin user, one active extension and a fresh feed.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->original_get = $_GET; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Snapshot for tearDown(), not form handling.
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		update_option( 'active_plugins', array( 'woocommerce/woocommerce.php', 'woocommerce-subscriptions/woocommerce-subscriptions.php' ) );
		$this->set_feed(
			array(
				$this->item( 'bookings', 'woocommerce-bookings' ),
				$this->item( 'subscriptions', 'woocommerce-subscriptions' ),
				$this->item( 'dismissed', 'product-bundles' ),
				array_merge( $this->item( 'orders', 'shipstation' ), array( 'context' => 'orders-list-empty-body' ) ),
				array_merge( $this->item( 'memberships', 'woocommerce-memberships' ), array( 'context' => array( 'products-list-empty-body', 'product-edit-meta-tab-body' ) ) ),
				array_merge( $this->item( 'deposits', 'woocommerce-deposits' ), array( 'show-if-active' => array( 'woocommerce-bookings' ) ) ),
				array_merge( $this->item( 'gifting', 'gifting-for-subscriptions' ), array( 'show-if-active' => array( 'woocommerce-subscriptions' ) ) ),
			)
		);
		update_user_meta( get_current_user_id(), 'wc_marketplace_suggestions_dismissed_suggestions', array( 'dismissed' ) );
	}

	/**
	 * Restore the request query vars.
	 */
	public function tearDown(): void {
		$_GET = $this->original_get;
		parent::tearDown();
	}

	/**
	 * @testdox Should return items for the context, skipping dismissed ones, active plugins and unmet show-if-active rules.
	 */
	public function test_filters_suggestions_for_context(): void {
		$slugs = array_column( $this->get_products_list_suggestions(), 'slug' );

		$this->assertSame( array( 'bookings', 'memberships', 'gifting' ), $slugs );
	}

	/**
	 * @testdox Should cap results at a recommendations-count between 1 and 50, and at 5 otherwise.
	 *
	 * @testWith [1, 1]
	 *           [51, 5]
	 *
	 * @param int $count    Feed recommendations-count.
	 * @param int $expected Expected number of suggestions.
	 */
	public function test_caps_at_recommendations_count( int $count, int $expected ): void {
		$items = array();
		for ( $i = 0; $i < 7; $i++ ) {
			$items[] = $this->item( "item-$i", "plugin-$i" );
		}
		$this->set_feed( $items, $count );

		$this->assertCount( $expected, $this->get_products_list_suggestions() );
	}

	/**
	 * @testdox Should add the setting on the add products page reached from the products list.
	 */
	public function test_adds_setting_from_products_list(): void {
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
			$this->get_products_list_suggestions()[0]
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
	 * Get the productsListEmptySuggestions setting as added on the add products page reached from the products list.
	 *
	 * @return array[]
	 */
	private function get_products_list_suggestions(): array {
		$_GET = array(
			'page' => 'wc-admin',
			'path' => '/add-product',
			'from' => 'products-list',
		);

		return WC_Marketplace_Suggestions::add_products_list_empty_suggestions_setting( array() )['productsListEmptySuggestions'];
	}

	/**
	 * Store a feed in the suggestions option, marked fresh so no update gets queued.
	 *
	 * @param array $items Feed items.
	 * @param int   $count Feed recommendations-count.
	 */
	private function set_feed( array $items, int $count = 5 ): void {
		update_option(
			'woocommerce_marketplace_suggestions',
			array(
				'suggestions' => array_merge( array( array( 'recommendations-count' => $count ) ), $items ),
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
