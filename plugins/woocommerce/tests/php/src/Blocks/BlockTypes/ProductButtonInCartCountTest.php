<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\BlockTypes;

use Automattic\WooCommerce\Blocks\BlockTypes\ProductButton;
use Automattic\WooCommerce\Blocks\Domain\Services\Hydration;
use Automattic\WooCommerce\Blocks\Package;
use Automattic\WooCommerce\Blocks\Utils\BlocksSharedState;
use Automattic\WooCommerce\Tests\Blocks\Mocks\ProductButtonMock;

/**
 * Tests for the ProductButton server-side in-cart count seed.
 *
 * Covers three layers: a deterministic mirror-rule layer that controls the
 * hydrated snapshot exactly via a fake Hydration, a real-cart integration
 * layer that exercises the actual Store API cart route, and an
 * invocation-profile layer that counts filter calls to prove the seed adds
 * no extra cart-key or canonical-line evaluations of its own.
 */
class ProductButtonInCartCountTest extends \WC_Unit_Test_Case {

	/**
	 * The name of the filter that resolves a cart line's canonical status.
	 *
	 * @var string
	 */
	private const CANONICAL_LINE_FILTER = 'woocommerce_store_api_cart_item_is_canonical_line';

	/**
	 * The System Under Test.
	 *
	 * @var ProductButtonMock
	 */
	private $sut;

	/**
	 * The original product-button block registration.
	 *
	 * @var \WP_Block_Type|null
	 */
	private $original_block_type;

	/**
	 * Captured original Hydration registry entry for restoration in tearDown.
	 *
	 * @var mixed
	 */
	private $original_hydration_registry_entry = null;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();

		$registry                  = \WP_Block_Type_Registry::get_instance();
		$this->original_block_type = null;
		if ( $registry->is_registered( 'woocommerce/product-button' ) ) {
			$this->original_block_type = $registry->get_registered( 'woocommerce/product-button' );
			$registry->unregister( 'woocommerce/product-button' );
		}

		$this->sut = new ProductButtonMock();

		wc_empty_cart();
		$this->reset_blocks_shared_cart_state();
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		$this->restore_hydration_container_entry();
		$this->reset_blocks_shared_cart_state();
		remove_all_filters( self::CANONICAL_LINE_FILTER );
		remove_all_filters( 'woocommerce_cart_id' );

		wc_empty_cart();

		$registry = \WP_Block_Type_Registry::get_instance();
		if ( $registry->is_registered( 'woocommerce/product-button' ) ) {
			$registry->unregister( 'woocommerce/product-button' );
		}
		if ( $this->original_block_type ) {
			$registry->register( $this->original_block_type );
		}

		parent::tearDown();
	}

	// -------------------------------------------------------------------------
	// Shared fixtures
	// -------------------------------------------------------------------------

	/**
	 * Build a minimal Store-API-shaped cart item entry.
	 *
	 * @param int       $id        The product ID.
	 * @param int|float $quantity  The line quantity.
	 * @param array     $overrides Additional or overriding keys, e.g. 'is_canonical_line', 'type'.
	 * @return array
	 */
	private function item( int $id, $quantity, array $overrides = array() ): array {
		return array_merge(
			array(
				'id'       => $id,
				'quantity' => $quantity,
			),
			$overrides
		);
	}

	/**
	 * Wrap a list of items into the response shape load_cart_state() publishes from.
	 *
	 * @param array $items The cart items.
	 * @return array
	 */
	private function cart_response( array $items ): array {
		return array( 'body' => array( 'items' => $items ) );
	}

	/**
	 * Add a specific variation to the real cart.
	 *
	 * @param int $parent_id    The parent (variable) product ID.
	 * @param int $variation_id The variation ID.
	 * @param int $quantity     The line quantity.
	 */
	private function add_variation_to_cart( int $parent_id, int $variation_id, int $quantity = 1 ): void {
		WC()->cart->add_to_cart( $parent_id, $quantity, $variation_id, wc_get_product_variation_attributes( $variation_id ) );
	}

	/**
	 * Reset BlocksSharedState's memoized cart snapshot so no state bleeds across tests.
	 */
	private function reset_blocks_shared_cart_state(): void {
		$reflection = new \ReflectionClass( BlocksSharedState::class );
		$cart_state = $reflection->getProperty( 'blocks_shared_cart_state' );
		$cart_state->setAccessible( true );
		$cart_state->setValue( null, null );

		// The seed reads the published interactivity state, and
		// wp_interactivity_state() merges recursively rather than replacing, so
		// a previous test's cart would blend into the next one's if left behind.
		$interactivity     = wp_interactivity();
		$interactivity_ref = new \ReflectionClass( $interactivity );
		$state_data        = $interactivity_ref->getProperty( 'state_data' );
		$state_data->setAccessible( true );
		$data = $state_data->getValue( $interactivity );
		unset( $data['woocommerce'] );
		$state_data->setValue( $interactivity, $data );
	}

	/**
	 * Create an anonymous Hydration stand-in that counts how many times
	 * get_rest_api_response_data was called and returns a canned response.
	 *
	 * @param array $response The response to return from get_rest_api_response_data.
	 * @return object A fake Hydration with public `$call_count`.
	 */
	private function create_counting_hydration( array $response ): object {
		return new class( $response ) {
			/**
			 * The canned response.
			 *
			 * @var array
			 */
			private array $response;

			/**
			 * How many times get_rest_api_response_data was called.
			 *
			 * @var int
			 */
			public int $call_count = 0;

			/**
			 * Constructor.
			 *
			 * @param array $response The canned response.
			 */
			public function __construct( array $response ) {
				$this->response = $response;
			}

			/**
			 * Mimic Hydration::get_rest_api_response_data.
			 *
			 * @param string $path The REST path (ignored).
			 * @return array The canned response.
			 */
			public function get_rest_api_response_data( string $path ): array {
				unset( $path );
				// Avoid parameter not used PHPCS errors.
				++$this->call_count;
				return $this->response;
			}
		};
	}

	/**
	 * Swap the Hydration entry in the Blocks DI container with a fake. Also
	 * captures the original entry so tearDown() can restore it.
	 *
	 * @param object $fake The fake Hydration instance.
	 */
	private function inject_hydration( object $fake ): void {
		$container            = Package::container();
		$container_reflection = new \ReflectionClass( $container );
		$registry_property    = $container_reflection->getProperty( 'registry' );
		$registry_property->setAccessible( true );
		$registry = $registry_property->getValue( $container );

		if ( null === $this->original_hydration_registry_entry ) {
			$this->original_hydration_registry_entry = $registry[ Hydration::class ] ?? false;
		}

		$shared_type_class            = 'Automattic\\WooCommerce\\Blocks\\Registry\\SharedType';
		$registry[ Hydration::class ] = new $shared_type_class(
			function () use ( $fake ) {
				return $fake;
			}
		);

		$registry_property->setValue( $container, $registry );
	}

	/**
	 * Restore the original Hydration entry in the container registry, if we
	 * swapped it during a test.
	 */
	private function restore_hydration_container_entry(): void {
		if ( null === $this->original_hydration_registry_entry ) {
			return;
		}

		$container            = Package::container();
		$container_reflection = new \ReflectionClass( $container );
		$registry_property    = $container_reflection->getProperty( 'registry' );
		$registry_property->setAccessible( true );
		$registry = $registry_property->getValue( $container );

		if ( false === $this->original_hydration_registry_entry ) {
			unset( $registry[ Hydration::class ] );
		} else {
			$registry[ Hydration::class ] = $this->original_hydration_registry_entry;
		}

		$registry_property->setValue( $container, $registry );
		$this->original_hydration_registry_entry = null;
	}

	// -------------------------------------------------------------------------
	// Deterministic mirror-rule layer
	// -------------------------------------------------------------------------

	/**
	 * @testdox Should not count a line whose is_canonical_line is strictly false.
	 */
	public function test_skips_line_with_is_canonical_line_strictly_false(): void {
		$fake = $this->create_counting_hydration( $this->cart_response( array( $this->item( 10, 2, array( 'is_canonical_line' => false ) ) ) ) );
		$this->inject_hydration( $fake );

		$result = $this->sut->call_get_cart_item_quantity_by_product_id( 10 );

		$this->assertSame( 0, $result, 'A line with is_canonical_line strictly false must not be counted.' );
	}

	/**
	 * @testdox Should count a line with no is_canonical_line key at all.
	 */
	public function test_counts_line_with_missing_is_canonical_line_key(): void {
		$fake = $this->create_counting_hydration( $this->cart_response( array( $this->item( 10, 2 ) ) ) );
		$this->inject_hydration( $fake );

		$result = $this->sut->call_get_cart_item_quantity_by_product_id( 10 );

		$this->assertSame( 2, $result, 'A missing is_canonical_line field must degrade to counted, matching the client.' );
	}

	/**
	 * @testdox Should never count a variation-typed line, whatever its is_canonical_line value.
	 * @dataProvider provider_variation_typed_line_overrides
	 *
	 * @param array $overrides Overrides merged into the item, in addition to `type => variation`.
	 */
	public function test_skips_variation_typed_line_regardless_of_is_canonical_line( array $overrides ): void {
		$overrides['type'] = 'variation';
		$fake              = $this->create_counting_hydration( $this->cart_response( array( $this->item( 10, 2, $overrides ) ) ) );
		$this->inject_hydration( $fake );

		$result = $this->sut->call_get_cart_item_quantity_by_product_id( 10 );

		$this->assertSame( 0, $result, 'A variation-typed line must never be matched by product ID alone.' );
	}

	/**
	 * Data provider of is_canonical_line overrides for variation-typed lines.
	 *
	 * @return array
	 */
	public function provider_variation_typed_line_overrides(): array {
		return array(
			'is_canonical_line true'    => array( array( 'is_canonical_line' => true ) ),
			'is_canonical_line missing' => array( array() ),
		);
	}

	/**
	 * @testdox Should skip a literal empty-array entry without error, notice or fatal.
	 */
	public function test_skips_literal_empty_array_entry_without_error(): void {
		$fake = $this->create_counting_hydration( $this->cart_response( array( array(), $this->item( 10, 2 ) ) ) );
		$this->inject_hydration( $fake );

		$result = $this->sut->call_get_cart_item_quantity_by_product_id( 10 );

		$this->assertSame( 2, $result, 'A literal empty-array entry must be skipped, and the following line must still be counted.' );
	}

	/**
	 * @testdox Should return the first of two canonical lines for one product, in cart order, and never their sum.
	 */
	public function test_first_canonical_line_wins_over_a_later_one_for_the_same_product(): void {
		$fake = $this->create_counting_hydration(
			$this->cart_response(
				array(
					$this->item( 10, 2 ),
					$this->item( 10, 3 ),
				)
			)
		);
		$this->inject_hydration( $fake );

		$result = $this->sut->call_get_cart_item_quantity_by_product_id( 10 );

		$this->assertSame( 2, $result, 'The first canonical line in cart order must win.' );
		$this->assertNotSame( 5, $result, 'The quantity must never be the sum of both lines.' );
	}

	/**
	 * @testdox Should return a fractional quantity unchanged as a float.
	 */
	public function test_returns_fractional_quantity_unchanged_as_float(): void {
		$fake = $this->create_counting_hydration( $this->cart_response( array( $this->item( 10, 1.5 ) ) ) );
		$this->inject_hydration( $fake );

		$result = $this->sut->call_get_cart_item_quantity_by_product_id( 10 );

		$this->assertSame( 1.5, $result, 'A fractional quantity must pass through unchanged.' );
		$this->assertIsFloat( $result, 'Nothing must cast the fractional quantity to int.' );
	}

	/**
	 * @testdox Should return zero and raise no error for an empty snapshot.
	 */
	public function test_returns_zero_for_empty_snapshot(): void {
		$fake = $this->create_counting_hydration( $this->cart_response( array() ) );
		$this->inject_hydration( $fake );

		$result = $this->sut->call_get_cart_item_quantity_by_product_id( 10 );

		$this->assertSame( 0, $result, 'An empty snapshot must yield zero.' );
	}

	/**
	 * @testdox Should return zero and raise no error when the items key is absent.
	 */
	public function test_returns_zero_when_items_key_is_absent(): void {
		$fake = $this->create_counting_hydration( array( 'body' => array() ) );
		$this->inject_hydration( $fake );

		$result = $this->sut->call_get_cart_item_quantity_by_product_id( 10 );

		$this->assertSame( 0, $result, 'An absent items key must yield zero.' );
	}

	/**
	 * @testdox Should return zero and raise no error when the cart is unavailable.
	 */
	public function test_returns_zero_when_cart_is_unavailable(): void {
		$original_cart = WC()->cart;
		WC()->cart     = null; // phpcs:ignore

		$result = $this->sut->call_get_cart_item_quantity_by_product_id( 10 );

		WC()->cart = $original_cart; // phpcs:ignore

		$this->assertSame( 0, $result, 'An unavailable cart must yield zero.' );
	}

	/**
	 * @testdox Should hydrate the cart at most once when seeding several different products.
	 */
	public function test_hydrates_cart_at_most_once_when_seeding_several_products(): void {
		$fake = $this->create_counting_hydration(
			$this->cart_response(
				array(
					$this->item( 10, 1 ),
					$this->item( 20, 2 ),
					$this->item( 30, 3 ),
				)
			)
		);
		$this->inject_hydration( $fake );

		$this->sut->call_get_cart_item_quantity_by_product_id( 10 );
		$this->sut->call_get_cart_item_quantity_by_product_id( 20 );
		$this->sut->call_get_cart_item_quantity_by_product_id( 30 );

		$this->assertSame( 1, $fake->call_count, 'Seeding several products against one snapshot must hydrate the cart at most once.' );
	}

	/**
	 * @testdox Should build the index eagerly in one pass, so the memo already holds entries for products not yet asked about.
	 */
	public function test_index_is_built_eagerly_for_all_products_in_one_pass(): void {
		$fake = $this->create_counting_hydration(
			$this->cart_response(
				array(
					$this->item( 10, 2 ),
					$this->item( 20, 5 ),
					$this->item( 30, 7 ),
				)
			)
		);
		$this->inject_hydration( $fake );

		$this->sut->call_get_cart_item_quantity_by_product_id( 10 );

		$index = $this->get_cart_item_quantity_index();

		$this->assertSame( 5, $index[20] ?? null, 'The memo must already hold the entry for product 20, asked about later.' );
		$this->assertSame( 7, $index[30] ?? null, 'The memo must already hold the entry for product 30, asked about later.' );
	}

	/**
	 * @testdox Should serve a later call from the memo rather than rescanning the snapshot.
	 */
	public function test_later_call_is_served_from_the_memo_not_a_rescan(): void {
		$fake = $this->create_counting_hydration( $this->cart_response( array( $this->item( 10, 2 ) ) ) );
		$this->inject_hydration( $fake );

		$first = $this->sut->call_get_cart_item_quantity_by_product_id( 10 );
		$this->assertSame( 2, $first );

		$index     = $this->get_cart_item_quantity_index();
		$index[10] = 99;
		$this->set_cart_item_quantity_index( $index );

		$second = $this->sut->call_get_cart_item_quantity_by_product_id( 10 );

		$this->assertSame( 99, $second, 'A later call must be served from the memoized index rather than rescanning the snapshot.' );
		$this->assertSame( 1, $fake->call_count, 'Hydration must still have run exactly once.' );
	}

	/**
	 * Read the private, per-instance cart item quantity index memo via reflection.
	 *
	 * @return array
	 */
	private function get_cart_item_quantity_index(): array {
		$reflection = new \ReflectionClass( ProductButton::class );
		$property   = $reflection->getProperty( 'cart_item_quantity_index' );
		$property->setAccessible( true );

		return $property->getValue( $this->sut ) ?? array();
	}

	/**
	 * Overwrite the private, per-instance cart item quantity index memo via reflection.
	 *
	 * @param array $index The index to set.
	 */
	private function set_cart_item_quantity_index( array $index ): void {
		$reflection = new \ReflectionClass( ProductButton::class );
		$property   = $reflection->getProperty( 'cart_item_quantity_index' );
		$property->setAccessible( true );
		$property->setValue( $this->sut, $index );
	}

	// -------------------------------------------------------------------------
	// Real-cart integration layer
	// -------------------------------------------------------------------------

	/**
	 * @testdox Filter-applied, real cart: a callback marking a meta-differentiated line canonical makes the seed return that line's quantity.
	 */
	public function test_filter_marking_a_meta_differentiated_line_canonical_returns_its_quantity(): void {
		$product = \WC_Helper_Product::create_simple_product();
		$key     = WC()->cart->add_to_cart( $product->get_id(), 3, 0, array(), array( '_bundle' => 'bundle-parent-1' ) );

		add_filter(
			self::CANONICAL_LINE_FILTER,
			function ( $core_computed_default, $cart_item ) use ( $key ) {
				unset( $core_computed_default );
				return $cart_item['key'] === $key;
			},
			10,
			2
		);

		$result = $this->sut->call_get_cart_item_quantity_by_product_id( $product->get_id() );

		$this->assertSame( 3, $result, 'A callback marking the product\'s only line canonical must make the seed return its quantity.' );
	}

	/**
	 * @testdox No callback, real cart: a plain line returns its quantity.
	 */
	public function test_no_callback_plain_line_returns_its_quantity(): void {
		$product = \WC_Helper_Product::create_simple_product();
		WC()->cart->add_to_cart( $product->get_id(), 2 );

		$result = $this->sut->call_get_cart_item_quantity_by_product_id( $product->get_id() );

		$this->assertSame( 2, $result );
	}

	/**
	 * @testdox No callback, real cart: a product present only as a meta-differentiated line returns zero.
	 */
	public function test_no_callback_meta_differentiated_only_returns_zero(): void {
		$product = \WC_Helper_Product::create_simple_product();
		WC()->cart->add_to_cart( $product->get_id(), 4, 0, array(), array( '_bundle' => 'bundle-parent-1' ) );

		$result = $this->sut->call_get_cart_item_quantity_by_product_id( $product->get_id() );

		$this->assertSame( 0, $result );
	}

	/**
	 * @testdox No callback, real cart: a plain line alongside a meta-differentiated line returns the plain line's quantity.
	 */
	public function test_no_callback_plain_line_alongside_meta_differentiated_line_returns_plain_quantity(): void {
		$product = \WC_Helper_Product::create_simple_product();
		WC()->cart->add_to_cart( $product->get_id(), 2 );
		WC()->cart->add_to_cart( $product->get_id(), 3, 0, array(), array( '_bundle' => 'bundle-parent-1' ) );

		$result = $this->sut->call_get_cart_item_quantity_by_product_id( $product->get_id() );

		$this->assertSame( 2, $result );
	}

	/**
	 * @testdox No callback, real cart: an unavailable cart returns zero even with a real product configured.
	 */
	public function test_no_callback_unavailable_cart_returns_zero_with_a_real_product(): void {
		$product = \WC_Helper_Product::create_simple_product();

		$original_cart = WC()->cart;
		WC()->cart     = null; // phpcs:ignore

		$result = $this->sut->call_get_cart_item_quantity_by_product_id( $product->get_id() );

		WC()->cart = $original_cart; // phpcs:ignore

		$this->assertSame( 0, $result );
	}

	/**
	 * @testdox Should return zero for a grouped product's parent ID even with a callback that marks every line canonical.
	 */
	public function test_grouped_product_parent_id_returns_zero_even_with_callback_marking_every_line_canonical(): void {
		$grouped   = \WC_Helper_Product::create_grouped_product();
		$child_ids = $grouped->get_children();

		WC()->cart->add_to_cart( $child_ids[0], 2 );

		add_filter( self::CANONICAL_LINE_FILTER, '__return_true' );

		$result = $this->sut->call_get_cart_item_quantity_by_product_id( $grouped->get_id() );

		$this->assertSame( 0, $result, 'A grouped product has no cart line of its own, so its parent ID must never be counted.' );
	}

	/**
	 * @testdox No callback: two variations of a variable product in the cart return zero for the parent product ID.
	 */
	public function test_no_callback_two_variations_in_cart_parent_id_returns_zero(): void {
		$variable = \WC_Helper_Product::create_variation_product();
		$children = $variable->get_children();

		$this->add_variation_to_cart( $variable->get_id(), $children[0] );
		$this->add_variation_to_cart( $variable->get_id(), $children[1] );

		$result = $this->sut->call_get_cart_item_quantity_by_product_id( $variable->get_id() );

		$this->assertSame( 0, $result );
	}

	/**
	 * @testdox With a callback marking every line canonical, the same two-variations-in-cart state still returns zero for the parent product ID.
	 */
	public function test_with_callback_marking_every_line_canonical_two_variations_parent_id_still_returns_zero(): void {
		$variable = \WC_Helper_Product::create_variation_product();
		$children = $variable->get_children();

		$this->add_variation_to_cart( $variable->get_id(), $children[0] );
		$this->add_variation_to_cart( $variable->get_id(), $children[1] );

		add_filter( self::CANONICAL_LINE_FILTER, '__return_true' );

		$result = $this->sut->call_get_cart_item_quantity_by_product_id( $variable->get_id() );

		$this->assertSame( 0, $result, 'A variation-typed line must never be matched by the parent product ID, even when marked canonical.' );
	}

	// -------------------------------------------------------------------------
	// Invocation-profile layer
	// -------------------------------------------------------------------------

	/**
	 * @testdox Seeding must add zero woocommerce_cart_id invocations beyond the one-time hydration cost.
	 */
	public function test_seeding_adds_no_extra_cart_id_invocations(): void {
		$product = \WC_Helper_Product::create_simple_product();
		WC()->cart->add_to_cart( $product->get_id(), 2 );

		$filter_calls = 0;
		$counter      = function ( $id ) use ( &$filter_calls ) {
			++$filter_calls;
			return $id;
		};
		add_filter( 'woocommerce_cart_id', $counter );

		// The first call may hydrate the cart, which itself computes
		// is_canonical_line per line via generate_cart_id(); that one-time
		// cost is the "cart setup" this criterion allows for.
		$this->sut->call_get_cart_item_quantity_by_product_id( $product->get_id() );
		$after_first_call = $filter_calls;

		$this->sut->call_get_cart_item_quantity_by_product_id( $product->get_id() );
		$this->sut->call_get_cart_item_quantity_by_product_id( $product->get_id() + 1 );

		remove_filter( 'woocommerce_cart_id', $counter );

		$this->assertSame( $after_first_call, $filter_calls, 'Seeding must add zero woocommerce_cart_id invocations beyond the one-time hydration cost.' );
	}

	/**
	 * @testdox The canonical-line filter must fire once per cart line regardless of how many times the seed is called.
	 */
	public function test_canonical_line_filter_fires_once_per_line_regardless_of_seed_call_count(): void {
		$product_a = \WC_Helper_Product::create_simple_product();
		$product_b = \WC_Helper_Product::create_simple_product();
		WC()->cart->add_to_cart( $product_a->get_id(), 1 );
		WC()->cart->add_to_cart( $product_b->get_id(), 1 );

		$filter_calls = 0;
		$counter      = function ( $is_canonical, $cart_item ) use ( &$filter_calls ) {
			unset( $cart_item );
			++$filter_calls;
			return $is_canonical;
		};
		add_filter( self::CANONICAL_LINE_FILTER, $counter, 10, 2 );

		$this->sut->call_get_cart_item_quantity_by_product_id( $product_a->get_id() );
		$this->sut->call_get_cart_item_quantity_by_product_id( $product_a->get_id() );
		$this->sut->call_get_cart_item_quantity_by_product_id( $product_b->get_id() );

		remove_filter( self::CANONICAL_LINE_FILTER, $counter, 10 );

		$this->assertSame( 2, $filter_calls, 'The canonical-line filter must fire exactly once per cart line, regardless of how many times the seed is called.' );
	}

	// -------------------------------------------------------------------------
	// Structural cleanup
	// -------------------------------------------------------------------------

	/**
	 * @testdox The dead $cart static property is gone from ProductButton, along with its PHPStan baseline entry.
	 */
	public function test_dead_cart_static_property_and_its_baseline_entry_are_removed(): void {
		$reflection = new \ReflectionClass( ProductButton::class );

		$this->assertFalse( $reflection->hasProperty( 'cart' ), 'ProductButton must not declare a $cart property.' );

		$baseline = file_get_contents( WC_ABSPATH . 'phpstan-baseline.neon' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Local repository file, read only to assert on its contents in this test.

		$this->assertIsString( $baseline, 'The PHPStan baseline file must be readable.' );
		$this->assertStringNotContainsString(
			'\$cart is never read, only written',
			$baseline,
			'The PHPStan baseline must not carry an entry for the removed $cart property.'
		);
	}

	/**
	 * @testdox The seed's and the mock wrapper's docblocks avoid forbidden wording and document int|float without a native return type.
	 */
	public function test_docblocks_avoid_forbidden_wording_and_document_int_or_float(): void {
		$seed_method = ( new \ReflectionClass( ProductButton::class ) )->getMethod( 'get_cart_item_quantity_by_product_id' );
		$mock_method = ( new \ReflectionClass( ProductButtonMock::class ) )->getMethod( 'call_get_cart_item_quantity_by_product_id' );

		foreach ( array(
			'seed'         => $seed_method,
			'mock wrapper' => $mock_method,
		) as $label => $method ) {
			$doc_comment = $method->getDocComment();

			$this->assertIsString( $doc_comment, "The {$label} method must have a docblock." );
			$this->assertStringNotContainsString( 'standalone', $doc_comment, "The {$label} docblock must not use \"standalone\" wording." );
			$this->assertStringNotContainsString( 'sum', $doc_comment, "The {$label} docblock must not claim the count sums cart lines." );
			$this->assertStringNotContainsString( 'independent', $doc_comment, "The {$label} docblock must not claim the value is derived independently of the shared canonical-line value." );
			$this->assertStringContainsString( 'int|float', $doc_comment, "The {$label} docblock must document int|float." );
			$this->assertFalse( $method->hasReturnType(), "The {$label} method must not declare a native return type." );
		}
	}
}
