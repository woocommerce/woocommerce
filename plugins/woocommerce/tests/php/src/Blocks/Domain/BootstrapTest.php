<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\Domain;

use Automattic\WooCommerce\Blocks\BlockTypesController;
use WC_Unit_Test_Case;
use WP_Block_Type_Registry;

/**
 * Tests for the on-demand WooCommerce block-type registration wired up by Bootstrap.
 *
 * Product and variation descriptions are run through do_blocks on the woocommerce_short_description filter in
 * contexts where eager block registration is skipped (the products REST endpoints, the Store API schemas, the
 * variation AJAX endpoint and product webhooks). Bootstrap registers block types on demand there so those blocks
 * do not render empty.
 */
class BootstrapTest extends WC_Unit_Test_Case {

	/**
	 * A foundational WooCommerce block that register_blocks() always registers (it is not gated behind a theme
	 * or feature flag), so it is a stable subject for the on-demand registration assertions.
	 *
	 * @var string
	 */
	private const SAMPLE_BLOCK = 'woocommerce/product-price';

	/**
	 * Snapshot of the WooCommerce block types registered before each test, restored afterwards so the shared
	 * global registry is not left in a modified state for other tests.
	 *
	 * @var array<string, \WP_Block_Type>
	 */
	private array $registered_woo_blocks = array();

	/**
	 * Snapshot and unregister the WooCommerce blocks, and clear the BlockTypesController registration flag, so
	 * each test starts from a state that mirrors a fresh request whose eager block registration was skipped.
	 */
	public function setUp(): void {
		parent::setUp();

		$registry = WP_Block_Type_Registry::get_instance();
		foreach ( $registry->get_all_registered() as $name => $block_type ) {
			if ( 0 === strpos( $name, 'woocommerce/' ) ) {
				$this->registered_woo_blocks[ $name ] = $block_type;
				$registry->unregister( $name );
			}
		}

		$this->set_register_blocks_has_run_flag( false );
	}

	/**
	 * Restore the exact set of WooCommerce blocks that was registered before the test ran, and mark them as
	 * registered again on the shared BlockTypesController so later tests see a consistent state.
	 */
	public function tearDown(): void {
		$registry = WP_Block_Type_Registry::get_instance();
		foreach ( array_keys( $registry->get_all_registered() ) as $name ) {
			if ( 0 === strpos( (string) $name, 'woocommerce/' ) ) {
				$registry->unregister( $name );
			}
		}
		foreach ( $this->registered_woo_blocks as $block_type ) {
			$registry->register( $block_type );
		}
		$this->registered_woo_blocks = array();

		$this->set_register_blocks_has_run_flag( true );

		parent::tearDown();
	}

	/**
	 * Set the static registration flag on BlockTypesController.
	 *
	 * The flag records whether register_blocks() ran in the current request. The test bootstrap registers the
	 * blocks once for the whole PHPUnit process, so simulating a request whose eager registration was skipped
	 * requires clearing the flag alongside unregistering the block types. It is static (see the property
	 * docblock), so this sets it on the class, not on any one container instance.
	 *
	 * @param bool $has_run The flag value to set.
	 */
	private function set_register_blocks_has_run_flag( bool $has_run ): void {
		$property = new \ReflectionProperty( BlockTypesController::class, 'register_blocks_has_run' );
		$property->setAccessible( true );
		$property->setValue( null, $has_run );
	}

	/**
	 * @testdox Bootstrap hooks on-demand block-type registration to woocommerce_short_description before do_blocks.
	 */
	public function test_short_description_registration_is_hooked_before_do_blocks(): void {
		global $wp_filter;

		$this->assertArrayHasKey( 'woocommerce_short_description', $wp_filter, 'The filter should have registered callbacks.' );

		$do_blocks_priority = has_filter( 'woocommerce_short_description', 'do_blocks' );
		$this->assertNotFalse( $do_blocks_priority, 'do_blocks should be hooked to woocommerce_short_description.' );

		$registration_priority = false;
		foreach ( $wp_filter['woocommerce_short_description']->callbacks as $priority => $callbacks ) {
			foreach ( $callbacks as $callback ) {
				if (
					is_array( $callback['function'] )
					&& is_object( $callback['function'][0] ?? null )
					&& 'maybe_register_blocks_from_content' === ( $callback['function'][1] ?? '' )
				) {
					$registration_priority = $priority;
					break 2;
				}
			}
		}

		$this->assertNotFalse( $registration_priority, 'Bootstrap should hook on-demand block registration to woocommerce_short_description.' );
		$this->assertLessThan(
			$do_blocks_priority,
			$registration_priority,
			'On-demand block registration must run at an earlier priority than do_blocks, or description blocks render empty.'
		);
	}

	/**
	 * @testdox Filtering a description that contains a WooCommerce block registers block types that were missing.
	 */
	public function test_short_description_filter_registers_missing_block_types(): void {
		$registry = WP_Block_Type_Registry::get_instance();

		$this->assertNotEmpty( $this->registered_woo_blocks, 'The test bootstrap should have registered WooCommerce blocks to snapshot.' );
		$this->assertFalse( $registry->is_registered( self::SAMPLE_BLOCK ), 'WooCommerce blocks should start unregistered for this test.' );

		apply_filters( 'woocommerce_short_description', 'Intro <!-- wp:woocommerce/product-price /--> outro' );

		$this->assertTrue(
			$registry->is_registered( self::SAMPLE_BLOCK ),
			'Block types should be registered on demand when a description containing a block is rendered.'
		);
	}

	/**
	 * Block markup variants the block parser accepts: its grammar allows any run of whitespace after the
	 * comment opener (`<!--\s+wp:`), so the detection must tolerate the same formatting.
	 *
	 * @return array<string, array{string}>
	 */
	public function block_markup_whitespace_variants(): array {
		return array(
			'extra spaces after opener' => array( '<!--  wp:woocommerce/accordion-group /-->' ),
			'newline after opener'      => array( "<!--\nwp:woocommerce/accordion-group /-->" ),
			'tab after opener'          => array( "<!--\twp:woocommerce/accordion-group /-->" ),
		);
	}

	/**
	 * @testdox Detection tolerates the whitespace variants the block parser accepts.
	 * @dataProvider block_markup_whitespace_variants
	 *
	 * @param string $markup Block markup with non-canonical whitespace.
	 */
	public function test_short_description_filter_detects_blocks_regardless_of_whitespace( string $markup ): void {
		$registry = WP_Block_Type_Registry::get_instance();

		$this->assertFalse( $registry->is_registered( 'woocommerce/accordion-group' ), 'WooCommerce blocks should start unregistered for this test.' );

		apply_filters( 'woocommerce_short_description', $markup );

		$this->assertTrue(
			$registry->is_registered( 'woocommerce/accordion-group' ),
			'Detection should accept any whitespace the block parser accepts after the comment opener.'
		);
	}

	/**
	 * @testdox A description referencing a synced pattern registers block types defensively.
	 */
	public function test_short_description_filter_registers_block_types_for_synced_pattern_reference(): void {
		$registry = WP_Block_Type_Registry::get_instance();

		$this->assertFalse( $registry->is_registered( self::SAMPLE_BLOCK ), 'WooCommerce blocks should start unregistered for this test.' );

		// The referenced pattern's content cannot be inspected without fetching it, so a wp:block reference
		// must register the block types in case the pattern contains a WooCommerce block at any depth.
		apply_filters( 'woocommerce_short_description', '<!-- wp:block {"ref":129} /-->' );

		$this->assertTrue(
			$registry->is_registered( self::SAMPLE_BLOCK ),
			'A synced pattern reference should register block types, since the pattern may contain WooCommerce blocks.'
		);
	}

	/**
	 * @testdox Filtering a description without WooCommerce block markup does not register block types.
	 */
	public function test_short_description_filter_skips_registration_for_plain_content(): void {
		$registry = WP_Block_Type_Registry::get_instance();

		$this->assertNotEmpty( $this->registered_woo_blocks, 'The test bootstrap should have registered WooCommerce blocks to snapshot.' );
		$this->assertFalse( $registry->is_registered( self::SAMPLE_BLOCK ), 'WooCommerce blocks should start unregistered for this test.' );

		apply_filters( 'woocommerce_short_description', 'Just plain text with no blocks, or only a core <!-- wp:paragraph -->.' );

		$this->assertFalse(
			$registry->is_registered( self::SAMPLE_BLOCK ),
			'A description without WooCommerce block markup should stay on the fast path and register nothing.'
		);

		// Without whitespace after the comment opener this is not a block per the parser grammar (`<!--\s+wp:`),
		// so do_blocks would leave it untouched and registration would be wasted.
		apply_filters( 'woocommerce_short_description', '<!--wp:woocommerce/product-price /-->' );

		$this->assertFalse(
			$registry->is_registered( self::SAMPLE_BLOCK ),
			'Markup the block parser does not recognise as a block should not trigger registration.'
		);
	}

	/**
	 * @testdox Fetching a product through the wc/v3 REST API registers block types on demand for its description.
	 */
	public function test_products_rest_request_registers_block_types_on_demand(): void {
		$registry = WP_Block_Type_Registry::get_instance();

		$product = new \WC_Product_Simple();
		$product->set_short_description( '<!-- wp:woocommerce/product-price /-->' );
		$product->save();

		$this->assertFalse( $registry->is_registered( self::SAMPLE_BLOCK ), 'Blocks should start unregistered for this test.' );

		wp_set_current_user( $this->factory->user->create( array( 'role' => 'administrator' ) ) );
		$response = rest_do_request( new \WP_REST_Request( 'GET', '/wc/v3/products/' . $product->get_id() ) );

		$this->assertSame( 200, $response->get_status(), 'The products REST request should succeed.' );
		$this->assertTrue(
			$registry->is_registered( self::SAMPLE_BLOCK ),
			'Fetching a product through the REST API should register block types on demand so description blocks render.'
		);

		$product->delete( true );
	}

	/**
	 * @testdox The wc/v3 product response carries the rendered output of a description block, not empty markup.
	 */
	public function test_products_rest_response_contains_rendered_block_output(): void {
		// The accordion renders complete markup server-side with no post or cart context, so it works in a
		// REST request; blocks like mini-cart or product-price render empty there regardless of registration
		// and cannot show the difference. Same block the manual testing instructions use.
		$markup = <<<'HTML'
<!-- wp:woocommerce/accordion-group -->
<div class="wp-block-woocommerce-accordion-group"><!-- wp:woocommerce/accordion-item -->
<div class="wp-block-woocommerce-accordion-item"><!-- wp:woocommerce/accordion-header -->
<h3 class="wp-block-woocommerce-accordion-header accordion-item__heading"><button class="accordion-item__toggle"><span>Care instructions</span><span class="accordion-item__toggle-icon" style="width:1.2em;height:1.2em"></span></button></h3>
<!-- /wp:woocommerce/accordion-header -->

<!-- wp:woocommerce/accordion-panel -->
<div class="wp-block-woocommerce-accordion-panel"><div class="accordion-content__wrapper"><!-- wp:paragraph -->
<p>Machine wash cold, tumble dry low.</p>
<!-- /wp:paragraph --></div></div>
<!-- /wp:woocommerce/accordion-panel --></div>
<!-- /wp:woocommerce/accordion-item --></div>
<!-- /wp:woocommerce/accordion-group -->
HTML;

		$product = new \WC_Product_Simple();
		$product->set_short_description( $markup );
		$product->save();

		wp_set_current_user( $this->factory->user->create( array( 'role' => 'administrator' ) ) );
		$response = rest_do_request( new \WP_REST_Request( 'GET', '/wc/v3/products/' . $product->get_id() ) );

		$this->assertSame( 200, $response->get_status(), 'The products REST request should succeed.' );

		$short_description = $response->get_data()['short_description'] ?? '';
		$this->assertStringContainsString(
			'data-wp-interactive="woocommerce/accordion"',
			$short_description,
			'The short description should contain the accordion markup processed by its render callback.'
		);
		$this->assertStringContainsString(
			'Machine wash cold, tumble dry low.',
			$short_description,
			'The short description should contain the accordion panel content.'
		);
		$this->assertStringNotContainsString(
			'<!-- wp:',
			$short_description,
			'The serialized block comments should have been consumed by do_blocks.'
		);

		$product->delete( true );
	}

	/**
	 * @testdox Fetching the cart through the Store API registers block types on demand for an item's description.
	 */
	public function test_store_api_cart_request_registers_block_types_on_demand(): void {
		$registry = WP_Block_Type_Registry::get_instance();

		$product = new \WC_Product_Simple();
		$product->set_regular_price( '10' );
		$product->set_short_description( '<!-- wp:woocommerce/product-price /-->' );
		$product->save();

		$cart_item_key = wc()->cart->add_to_cart( $product->get_id() );
		$this->assertNotEmpty( $cart_item_key, 'The product should be added to the cart so the item renders in the response.' );

		$this->assertFalse( $registry->is_registered( self::SAMPLE_BLOCK ), 'Blocks should start unregistered for this test.' );

		$response = rest_do_request( new \WP_REST_Request( 'GET', '/wc/store/v1/cart' ) );

		$this->assertSame( 200, $response->get_status(), 'The Store API cart request should succeed.' );
		$this->assertTrue(
			$registry->is_registered( self::SAMPLE_BLOCK ),
			'Fetching the cart through the Store API should register block types on demand so description blocks render.'
		);

		wc()->cart->empty_cart();
		$product->delete( true );
	}

	/**
	 * @testdox Filtering a description does not re-register block types when they are already registered.
	 */
	public function test_short_description_filter_skips_registration_when_blocks_already_registered(): void {
		$registry = WP_Block_Type_Registry::get_instance();

		// Restore the blocks and the controller flag so the request looks like a normal one whose eager
		// registration already ran.
		foreach ( $this->registered_woo_blocks as $block_type ) {
			$registry->register( $block_type );
		}
		$this->set_register_blocks_has_run_flag( true );
		$this->assertTrue( $registry->is_registered( self::SAMPLE_BLOCK ), 'Blocks should be registered before the filter runs.' );

		// Firing the filter must not call register_blocks() again — doing so would re-register already-registered
		// block types and trigger a doing_it_wrong failure, which WC_Unit_Test_Case turns into a test failure.
		apply_filters( 'woocommerce_short_description', 'Intro <!-- wp:woocommerce/product-price /--> outro' );

		$this->assertTrue(
			$registry->is_registered( self::SAMPLE_BLOCK ),
			'Block types should remain registered and must not be re-registered on demand.'
		);
	}
}
