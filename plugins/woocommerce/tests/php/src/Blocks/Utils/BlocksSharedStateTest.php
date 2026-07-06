<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\Utils;

use Automattic\WooCommerce\Blocks\Utils\BlocksSharedState;

/**
 * Tests for the BlocksSharedState class.
 */
class BlocksSharedStateTest extends \WC_Unit_Test_Case {

	/**
	 * The consent statement required by the private API.
	 *
	 * @var string
	 */
	private string $consent = 'I acknowledge that using private APIs means my theme or plugin will inevitably break in the next version of WooCommerce';

	/**
	 * Set up each test.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->reset_shared_state();
	}

	/**
	 * Tear down each test.
	 */
	public function tearDown(): void {
		remove_all_filters( 'woocommerce_cart_contents_count' );
		$this->reset_shared_state();
		parent::tearDown();
	}

	/**
	 * Reset the static flags and interactivity config so load_store_config()
	 * and load_cart_state() can run again with a clean slate.
	 */
	private function reset_shared_state(): void {
		$reflection = new \ReflectionClass( BlocksSharedState::class );

		$prop = $reflection->getProperty( 'core_config_registered' );
		$prop->setAccessible( true );
		$prop->setValue( null, false );

		$cart_state = $reflection->getProperty( 'blocks_shared_cart_state' );
		$cart_state->setAccessible( true );
		$cart_state->setValue( null, null );

		$cart_getters = $reflection->getProperty( 'cart_getters_registered' );
		$cart_getters->setAccessible( true );
		$cart_getters->setValue( null, false );

		$interactivity     = wp_interactivity();
		$interactivity_ref = new \ReflectionClass( $interactivity );
		$config_data       = $interactivity_ref->getProperty( 'config_data' );

		$config_data->setAccessible( true );
		$data = $config_data->getValue( $interactivity );
		unset( $data['woocommerce'] );
		$config_data->setValue( $interactivity, $data );

		foreach ( array( 'state_data', 'derived_state_closures' ) as $name ) {
			if ( ! $interactivity_ref->hasProperty( $name ) ) {
				continue;
			}
			$property = $interactivity_ref->getProperty( $name );
			$property->setAccessible( true );
			$property->setValue( $interactivity, array() );
		}
	}

	/**
	 * Invoke a private static method on BlocksSharedState via reflection.
	 *
	 * @param string $method The method name.
	 * @param array  $args   The arguments.
	 * @return mixed The method return value.
	 */
	private function invoke_private( string $method, array $args ) {
		$reflection = new \ReflectionMethod( BlocksSharedState::class, $method );
		$reflection->setAccessible( true );
		return $reflection->invokeArgs( null, $args );
	}

	/**
	 * @testdox nonOptimisticProperties is empty when no filter is registered.
	 */
	public function test_no_filter_returns_empty_non_optimistic_properties(): void {
		BlocksSharedState::load_cart_state( $this->consent );

		$config = wp_interactivity_config( 'woocommerce' );

		$this->assertArrayHasKey( 'nonOptimisticProperties', $config );
		$this->assertSame( array(), $config['nonOptimisticProperties'] );
	}

	/**
	 * @testdox nonOptimisticProperties contains items_count when a third-party filter is registered.
	 */
	public function test_third_party_filter_detected(): void {
		add_filter( 'woocommerce_cart_contents_count', fn( $count ) => $count + 1 );

		BlocksSharedState::load_cart_state( $this->consent );

		$config = wp_interactivity_config( 'woocommerce' );

		$this->assertArrayHasKey( 'nonOptimisticProperties', $config );
		$this->assertContains( 'cart.items_count', $config['nonOptimisticProperties'] );
	}

	/**
	 * @testdox nonOptimisticProperties is empty when a filter is added and then removed.
	 */
	public function test_filter_added_then_removed_returns_empty(): void {
		$callback = fn( $count ) => $count + 1;

		add_filter( 'woocommerce_cart_contents_count', $callback );
		remove_filter( 'woocommerce_cart_contents_count', $callback );

		BlocksSharedState::load_cart_state( $this->consent );

		$config = wp_interactivity_config( 'woocommerce' );

		$this->assertArrayHasKey( 'nonOptimisticProperties', $config );
		$this->assertSame( array(), $config['nonOptimisticProperties'] );
	}

	/**
	 * @testdox load_cart_state seeds an empty draftItems array and the itemInContext closure.
	 */
	public function test_cart_state_seeds_drafts_and_envelope_closure(): void {
		BlocksSharedState::load_cart_state( $this->consent );

		$state = wp_interactivity_state( 'woocommerce/cart' );

		$this->assertArrayHasKey( 'draftItems', $state );
		$this->assertSame( array(), $state['draftItems'] );
		$this->assertArrayHasKey( 'itemInContext', $state );
		$this->assertInstanceOf( \Closure::class, $state['itemInContext'] );
	}

	/**
	 * @testdox itemInContext resolves conservatively (no cart, not in cart) when there is no context.
	 */
	public function test_item_in_context_is_conservative_without_context(): void {
		$this->setExpectedIncorrectUsage( 'WP_Interactivity_API::get_context' );

		BlocksSharedState::load_cart_state( $this->consent );

		$state    = wp_interactivity_state( 'woocommerce/cart' );
		$envelope = $state['itemInContext']();

		$this->assertNull( $envelope['cart'], 'No context draft → no exact cart line.' );
		$this->assertNull( $envelope['draft'] );
		$this->assertFalse( $envelope['isInCart'] );
	}

	/**
	 * @testdox get_draft_extension_props returns only namespaced props, excluding reserved keys.
	 */
	public function test_get_draft_extension_props_excludes_reserved_keys(): void {
		$draft = array(
			'id'                  => 100,
			'quantity'            => 2,
			'variation'           => array(
				array(
					'attribute' => 'attribute_pa_color',
					'value'     => 'green',
				),
			),
			'my-plugin/gift-note' => 'A',
		);

		$props = $this->invoke_private( 'get_draft_extension_props', array( $draft ) );

		$this->assertSame( array( 'my-plugin/gift-note' => 'A' ), $props );
	}

	/**
	 * @testdox line_has_unaccounted_content flags an unaccounted extension namespace.
	 */
	public function test_presence_heuristic_flags_unaccounted_extension(): void {
		$line = array(
			'key'        => 'k',
			'id'         => 100,
			'extensions' => array( 'my-plugin' => array( 'note' => 'A' ) ),
			'item_data'  => array(),
		);

		$this->assertTrue(
			$this->invoke_private( 'line_has_unaccounted_content', array( array(), $line ) ),
			'A non-empty extension the draft has no prop for is unaccounted.'
		);
	}

	/**
	 * @testdox line_has_unaccounted_content flags visible item_data when the draft accounts for no extensions.
	 */
	public function test_presence_heuristic_flags_visible_item_data(): void {
		$line = array(
			'key'        => 'k',
			'id'         => 100,
			'extensions' => array(),
			'item_data'  => array(
				array(
					'key'   => 'Gift note',
					'value' => 'A',
				),
			),
		);

		$this->assertTrue(
			$this->invoke_private( 'line_has_unaccounted_content', array( array(), $line ) )
		);
	}

	/**
	 * @testdox line_has_unaccounted_content excuses item_data when the draft matches the line's extensions.
	 */
	public function test_presence_heuristic_excuses_accounted_item_data(): void {
		$draft_props = array( 'my-plugin' => array( 'note' => 'B' ) );
		$line        = array(
			'key'        => 'k',
			'id'         => 100,
			'extensions' => array( 'my-plugin' => array( 'note' => 'B' ) ),
			'item_data'  => array(
				array(
					'key'   => 'Gift note',
					'value' => 'B',
				),
			),
		);

		$this->assertFalse(
			$this->invoke_private( 'line_has_unaccounted_content', array( $draft_props, $line ) ),
			'item_data displaying data the draft already matched must not exclude the line.'
		);
	}

	/**
	 * @testdox narrow_candidates keeps the one matching line, excludes decorated lines a plain draft cannot account for, and keeps invisible bare twins.
	 */
	public function test_narrow_candidates(): void {
		$note_a = array(
			'key'        => 'noteA',
			'id'         => 100,
			'extensions' => array( 'my-plugin' => array( 'note' => 'A' ) ),
			'item_data'  => array(
				array(
					'key'   => 'Gift note',
					'value' => 'A',
				),
			),
		);
		$note_b = array(
			'key'        => 'noteB',
			'id'         => 100,
			'extensions' => array( 'my-plugin' => array( 'note' => 'B' ) ),
			'item_data'  => array(
				array(
					'key'   => 'Gift note',
					'value' => 'B',
				),
			),
		);

		$paired = $this->invoke_private(
			'narrow_candidates',
			array(
				array( $note_a, $note_b ),
				array(
					'id'        => 100,
					'quantity'  => 1,
					'my-plugin' => array( 'note' => 'B' ),
				),
			)
		);
		$this->assertCount( 1, $paired, 'Matching draft props → exactly one survivor.' );
		$this->assertSame( 'noteB', $paired[0]['key'] );

		$excluded = $this->invoke_private(
			'narrow_candidates',
			array(
				array( $note_a, $note_b ),
				array(
					'id'       => 100,
					'quantity' => 1,
				),
			)
		);
		$this->assertCount( 0, $excluded, 'Plain draft + decorated-only lines → zero survivors → isInCart false (never first-match).' );

		$bare_twin_1 = array(
			'key'        => 'x1',
			'id'         => 100,
			'extensions' => array(),
			'item_data'  => array(),
		);
		$bare_twin_2 = array(
			'key'        => 'x2',
			'id'         => 100,
			'extensions' => array(),
			'item_data'  => array(),
		);

		$twins = $this->invoke_private(
			'narrow_candidates',
			array(
				array( $bare_twin_1, $bare_twin_2 ),
				array(
					'id'       => 100,
					'quantity' => 1,
				),
			)
		);
		$this->assertCount( 2, $twins, 'Invisible bare twins both survive → isInCart true with cart null (ambiguous presence).' );
	}
}
