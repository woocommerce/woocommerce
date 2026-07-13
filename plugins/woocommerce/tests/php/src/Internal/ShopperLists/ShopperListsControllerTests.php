<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\ShopperLists;

use Automattic\WooCommerce\Internal\ShopperLists\ShopperListsController;
use WC_Unit_Test_Case;

/**
 * Unit tests for ShopperListsController.
 */
class ShopperListsControllerTests extends WC_Unit_Test_Case {

	private const FLUSH_QUEUE_OPTION = 'woocommerce_queue_flush_rewrite_rules';

	/**
	 * Map of shopper-list slug => feature option key.
	 */
	private const LIST_OPTIONS = array(
		'saved-for-later' => 'woocommerce_cart_save_for_later_enabled',
		'wishlist'        => 'woocommerce_product_wishlist_enabled',
	);

	/**
	 * System under test.
	 *
	 * @var ShopperListsController|null
	 */
	private $sut;

	/**
	 * Set up.
	 */
	public function setUp(): void {
		parent::setUp();
		// Direct construction (not via container) keeps each test isolated:
		// the controller has no constructor deps, and hooks attached by one
		// test don't leak into the shared container instance.
		$this->sut = new ShopperListsController();
		// Start every test with all features off; individual tests opt in.
		foreach ( array_keys( self::LIST_OPTIONS ) as $slug ) {
			$this->disable_list( $slug );
		}
	}

	/**
	 * Tear down.
	 */
	public function tearDown(): void {
		if ( null !== $this->sut ) {
			$endpoint = $this->sut->get_wishlist_endpoint();
			remove_filter( 'woocommerce_get_query_vars', array( $this->sut, 'add_wishlist_query_var' ) );
			remove_filter( 'woocommerce_account_menu_items', array( $this->sut, 'add_wishlist_menu_item' ) );
			remove_filter( 'woocommerce_endpoint_' . $endpoint . '_title', array( $this->sut, 'wishlist_endpoint_title' ) );
			remove_action( 'woocommerce_account_' . $endpoint . '_endpoint', array( $this->sut, 'render_wishlist_endpoint' ) );
		}

		foreach ( array_keys( self::LIST_OPTIONS ) as $slug ) {
			$this->disable_list( $slug );
		}
		delete_option( self::FLUSH_QUEUE_OPTION );

		$this->sut = null;
		parent::tearDown();
	}

	/**
	 * Enable the feature backing the given shopper-list slug.
	 *
	 * @param string $slug List slug.
	 */
	private function enable_list( string $slug ): void {
		update_option( self::LIST_OPTIONS[ $slug ], 'yes' );
	}

	/**
	 * Disable the feature backing the given shopper-list slug.
	 *
	 * @param string $slug List slug.
	 */
	private function disable_list( string $slug ): void {
		update_option( self::LIST_OPTIONS[ $slug ], 'no' );
	}

	/**
	 * Convenience for the (sfl, wishlist) combinations used by the providers.
	 *
	 * @param bool $sfl      Whether to enable cart_save_for_later.
	 * @param bool $wishlist Whether to enable product_wishlist.
	 */
	private function set_features( bool $sfl, bool $wishlist ): void {
		$sfl ? $this->enable_list( 'saved-for-later' ) : $this->disable_list( 'saved-for-later' );
		$wishlist ? $this->enable_list( 'wishlist' ) : $this->disable_list( 'wishlist' );
	}

	/**
	 * @testdox is_enabled reflects the underlying feature state, with null asking about any list type.
	 * @dataProvider provide_is_enabled_cases
	 *
	 * @param bool        $sfl      Whether SFL is enabled in this case.
	 * @param bool        $wishlist Whether wishlist is enabled in this case.
	 * @param string|null $slug     Slug argument to pass to is_enabled().
	 * @param bool        $expected Expected return value.
	 */
	public function test_is_enabled( bool $sfl, bool $wishlist, ?string $slug, bool $expected ): void {
		$this->set_features( $sfl, $wishlist );
		$this->assertSame( $expected, $this->sut->is_enabled( $slug ) );
	}

	/**
	 * Data provider for {@see test_is_enabled()}.
	 *
	 * @return array<string, array{0:bool, 1:bool, 2:?string, 3:bool}>
	 */
	public function provide_is_enabled_cases(): array {
		return array(
			'no slug: both off'            => array( false, false, null, false ),
			'no slug: sfl on'              => array( true, false, null, true ),
			'no slug: wishlist on'         => array( false, true, null, true ),
			'no slug: both on'             => array( true, true, null, true ),
			'saved-for-later: on'          => array( true, false, 'saved-for-later', true ),
			'saved-for-later: off'         => array( false, false, 'saved-for-later', false ),
			'wishlist: on'                 => array( false, true, 'wishlist', true ),
			'wishlist: off'                => array( false, false, 'wishlist', false ),
			'unknown slug never qualifies' => array( true, true, 'unknown-list', false ),
		);
	}

	/**
	 * @testdox get_enabled_slugs returns the slugs of currently enabled list types in declaration order.
	 * @dataProvider provide_enabled_slug_cases
	 *
	 * @param bool               $sfl      Whether SFL is enabled in this case.
	 * @param bool               $wishlist Whether wishlist is enabled in this case.
	 * @param array<int, string> $expected Expected slugs.
	 */
	public function test_get_enabled_slugs( bool $sfl, bool $wishlist, array $expected ): void {
		$this->set_features( $sfl, $wishlist );
		$this->assertSame( $expected, $this->sut->get_enabled_slugs() );
	}

	/**
	 * Data provider for {@see test_get_enabled_slugs()}.
	 *
	 * @return array<string, array{0:bool, 1:bool, 2:array<int, string>}>
	 */
	public function provide_enabled_slug_cases(): array {
		return array(
			'none'          => array( false, false, array() ),
			'sfl only'      => array( true, false, array( 'saved-for-later' ) ),
			'wishlist only' => array( false, true, array( 'wishlist' ) ),
			'both'          => array( true, true, array( 'saved-for-later', 'wishlist' ) ),
		);
	}

	/**
	 * @testdox maybe_flush_rewrite_rules queues a flush only for the product_wishlist feature.
	 * @dataProvider provide_maybe_flush_cases
	 *
	 * @param string $feature_id     The feature id passed to the callback.
	 * @param bool   $expect_queued  Whether the queue option should end up set to 'yes'.
	 */
	public function test_maybe_flush_rewrite_rules( string $feature_id, bool $expect_queued ): void {
		delete_option( self::FLUSH_QUEUE_OPTION );
		$this->sut->maybe_flush_rewrite_rules( $feature_id );
		$this->assertSame(
			$expect_queued ? 'yes' : false,
			get_option( self::FLUSH_QUEUE_OPTION, false )
		);
	}

	/**
	 * Data provider for {@see test_maybe_flush_rewrite_rules()}.
	 *
	 * @return array<string, array{0:string, 1:bool}>
	 */
	public function provide_maybe_flush_cases(): array {
		return array(
			'wishlist toggles flush'    => array( 'product_wishlist', true ),
			'sfl change ignored'        => array( 'cart_save_for_later', false ),
			'unrelated feature ignored' => array( 'agentic_checkout', false ),
		);
	}

	/**
	 * @testdox add_wishlist_query_var injects the wishlist entry and returns an array for non-array input.
	 * @dataProvider provide_query_var_cases
	 *
	 * @param mixed                $input    Filter input.
	 * @param array<string, mixed> $expected Expected return value.
	 */
	public function test_add_wishlist_query_var( $input, array $expected ): void {
		$this->assertSame( $expected, $this->sut->add_wishlist_query_var( $input ) );
	}

	/**
	 * Data provider for {@see test_add_wishlist_query_var()}.
	 *
	 * @return array<string, array{0:mixed, 1:array<string, mixed>}>
	 */
	public function provide_query_var_cases(): array {
		return array(
			'empty'           => array( array(), array( 'wishlist' => 'wishlist' ) ),
			'with other vars' => array(
				array( 'orders' => 'orders' ),
				array(
					'orders'   => 'orders',
					'wishlist' => 'wishlist',
				),
			),
			'non-array input' => array( 'not-an-array', array() ),
		);
	}

	/**
	 * @testdox add_wishlist_menu_item inserts the wishlist link before customer-logout when present, or at the end otherwise.
	 * @dataProvider provide_menu_item_cases
	 *
	 * @param mixed              $input         Filter input.
	 * @param array<int, string> $expected_keys Expected ordered keys.
	 */
	public function test_add_wishlist_menu_item( $input, array $expected_keys ): void {
		$result = $this->sut->add_wishlist_menu_item( $input );
		$this->assertSame( $expected_keys, array_keys( $result ) );
	}

	/**
	 * Data provider for {@see test_add_wishlist_menu_item()}.
	 *
	 * @return array<string, array{0:mixed, 1:array<int, string>}>
	 */
	public function provide_menu_item_cases(): array {
		return array(
			'logout present'  => array(
				array(
					'dashboard'       => 'Dashboard',
					'orders'          => 'Orders',
					'customer-logout' => 'Log out',
				),
				array( 'dashboard', 'orders', 'wishlist', 'customer-logout' ),
			),
			'logout absent'   => array(
				array(
					'dashboard' => 'Dashboard',
					'orders'    => 'Orders',
				),
				array( 'dashboard', 'orders', 'wishlist' ),
			),
			'empty'           => array( array(), array( 'wishlist' ) ),
			'non-array input' => array( 'not-an-array', array() ),
		);
	}

	/**
	 * @testdox maybe_register_wishlist_endpoint attaches the endpoint hooks only when wishlist is enabled.
	 * @dataProvider provide_register_endpoint_cases
	 *
	 * @param bool $wishlist_on    Whether wishlist is enabled in this case.
	 * @param bool $hooks_expected Whether all four endpoint hooks should be present.
	 */
	public function test_maybe_register_wishlist_endpoint( bool $wishlist_on, bool $hooks_expected ): void {
		$this->set_features( false, $wishlist_on );
		$this->sut->maybe_register_wishlist_endpoint();

		$endpoint = $this->sut->get_wishlist_endpoint();
		$hooks    = array(
			array( 'woocommerce_get_query_vars', 'add_wishlist_query_var' ),
			array( 'woocommerce_account_menu_items', 'add_wishlist_menu_item' ),
			array( 'woocommerce_endpoint_' . $endpoint . '_title', 'wishlist_endpoint_title' ),
			array( 'woocommerce_account_' . $endpoint . '_endpoint', 'render_wishlist_endpoint' ),
		);
		foreach ( $hooks as $hook ) {
			$this->assertSame(
				$hooks_expected,
				(bool) has_filter( $hook[0], array( $this->sut, $hook[1] ) ),
				"Hook {$hook[0]}::{$hook[1]} state mismatch."
			);
		}
	}

	/**
	 * Data provider for {@see test_maybe_register_wishlist_endpoint()}.
	 *
	 * @return array<string, array{0:bool, 1:bool}>
	 */
	public function provide_register_endpoint_cases(): array {
		return array(
			'wishlist on attaches hooks'   => array( true, true ),
			'wishlist off leaves no hooks' => array( false, false ),
		);
	}
}
