<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\BlockTypes\AddToCartWithOptions;

use Automattic\WooCommerce\Blocks\BlockTypes\AddToCartWithOptions\QuantitySelector;
use Automattic\WooCommerce\Enums\ProductStockStatus;
use Automattic\WooCommerce\Tests\Blocks\Helpers\FixtureData;
use Automattic\WooCommerce\Tests\Blocks\Mocks\AddToCartWithOptionsQuantitySelectorMock;
use ReflectionMethod;
use WC_Product_Simple;
use WC_Unit_Test_Case;
use WP_Block_Supports;
use WP_Block_Type_Registry;
use stdClass;

/**
 * Tests for the QuantitySelector block type.
 */
class QuantitySelectorTest extends WC_Unit_Test_Case {

	/**
	 * The Quantity Selector block instance under test.
	 *
	 * Block types can only be registered once per process, so this single
	 * instance is created once (in setUp()) and reused by every test that
	 * needs to call its methods directly, rather than constructing a new
	 * instance (which would re-register the block and fail).
	 *
	 * @var AddToCartWithOptionsQuantitySelectorMock
	 */
	protected static $quantity_selector;

	/**
	 * Tracks whether the block has been registered.
	 *
	 * @var bool
	 */
	protected static $is_block_registered = false;

	/**
	 * Register the block under test.
	 */
	public function setUp(): void {
		parent::setUp();

		if ( ! self::$is_block_registered ) {
			$registry = WP_Block_Type_Registry::get_instance();

			// Another test class (e.g. AddToCartWithOptions.php) may have
			// already registered this block type in the same PHPUnit
			// process. This class still needs its own instance for the
			// direct method calls below, and re-registering over an
			// existing block type would otherwise trigger an "already
			// registered" doing_it_wrong notice (and be a no-op), so
			// unregister first.
			if ( $registry->is_registered( 'woocommerce/add-to-cart-with-options-quantity-selector' ) ) {
				$registry->unregister( 'woocommerce/add-to-cart-with-options-quantity-selector' );
			}

			self::$quantity_selector = new AddToCartWithOptionsQuantitySelectorMock();
			self::$is_block_registered = true;
		}
	}

	/**
	 * Builds a block context stub carrying `postId` and, optionally,
	 * `draftKey`, mirroring the context `QuantitySelector::render()` reads
	 * once the block declares both in its `usesContext`.
	 *
	 * @param int         $product_id Product ID to place on the context.
	 * @param string|null $draft_key  Draft key to place on the context, or null to leave it unset.
	 * @return stdClass Block stub with a `context` property.
	 */
	private function build_block_stub( int $product_id, ?string $draft_key = null ): stdClass {
		$block          = new stdClass();
		$block->context = array( 'postId' => $product_id );

		if ( null !== $draft_key ) {
			$block->context['draftKey'] = $draft_key;
		}

		return $block;
	}

	/**
	 * Calls the protected `render()` method via reflection, with
	 * `WP_Block_Supports::$block_to_render` set up front so
	 * `get_block_wrapper_attributes()` (called internally for layout/style
	 * supports) has the context it expects when invoked outside the usual
	 * block-render pipeline (mirrors AddToWishlistButtonTests::invoke_render()).
	 *
	 * @param stdClass $block Block stub.
	 * @return string Rendered markup.
	 */
	private function invoke_render( stdClass $block ): string {
		$previous_block_to_render            = WP_Block_Supports::$block_to_render;
		WP_Block_Supports::$block_to_render = array(
			'blockName' => 'woocommerce/add-to-cart-with-options-quantity-selector',
			'attrs'     => array(),
		);

		try {
			$render = new ReflectionMethod( QuantitySelector::class, 'render' );
			$render->setAccessible( true );

			return (string) $render->invoke( self::$quantity_selector, array(), '', $block );
		} finally {
			WP_Block_Supports::$block_to_render = $previous_block_to_render;
		}
	}

	/**
	 * @testdox The stepper files its draft seed under the reserved global collection key when its block context carries no draftKey.
	 */
	public function test_render_files_draft_seed_under_global_key_by_default(): void {
		$product = new WC_Product_Simple();
		$product->set_regular_price( 10 );
		$product_id = $product->save();

		$markup = $this->invoke_render( $this->build_block_stub( $product_id ) );

		$state        = wp_interactivity_state( 'woocommerce/cart' );
		$global_seeds = $state['draftSeeds']['woocommerce/global'] ?? array();

		$this->assertArrayHasKey( $product_id, $global_seeds, 'The stepper files its draft seed under the reserved global collection key by default.' );
		$this->assertSame(
			array(
				'id'       => $product_id,
				'quantity' => 1,
			),
			$global_seeds[ $product_id ]
		);

		$this->assertStringNotContainsString( 'data-wp-context---draft-seed', $markup, 'The stepper no longer emits a draft-seed context bag.' );
		$this->assertStringNotContainsString( 'data-wp-init--seed-draft', $markup, 'The stepper no longer emits the seedDraftIfAbsent init trigger.' );
	}

	/**
	 * @testdox The stepper files its draft seed under its block context's draftKey when one is present.
	 */
	public function test_render_files_draft_seed_under_block_context_draft_key(): void {
		$product = new WC_Product_Simple();
		$product->set_regular_price( 10 );
		$product_id = $product->save();
		$draft_key  = 'collection/0/' . $product_id;

		$this->invoke_render( $this->build_block_stub( $product_id, $draft_key ) );

		$state = wp_interactivity_state( 'woocommerce/cart' );

		$this->assertArrayHasKey( $draft_key, $state['draftSeeds'] ?? array(), 'The stepper files its draft seed under its block context\'s draftKey.' );
		$this->assertSame(
			array(
				'id'       => $product_id,
				'quantity' => 1,
			),
			$state['draftSeeds'][ $draft_key ][ $product_id ]
		);
	}

	/**
	 * @testdox A direct-variation stepper (a Single Product block pointing at a variation id, with no enclosing selectedAttributes context) files a draft seed carrying its variation attributes.
	 */
	public function test_render_direct_variation_draft_seed_includes_variation_attributes(): void {
		$fixtures = new FixtureData();

		$variable_product = $fixtures->get_variable_product(
			array(),
			array(
				$fixtures->get_product_attribute( 'color', array( 'red', 'green' ) ),
			)
		);

		$variation = $fixtures->get_variation_product(
			$variable_product->get_id(),
			array( 'pa_color' => 'red-slug' ),
			array(
				'regular_price' => 10,
				'stock_status'  => ProductStockStatus::IN_STOCK,
			)
		);

		$draft_key = 'single-product/' . $variation->get_id() . '/1';

		$this->invoke_render( $this->build_block_stub( $variation->get_id(), $draft_key ) );

		$state = wp_interactivity_state( 'woocommerce/cart' );

		$this->assertSame(
			array(
				'id'        => $variation->get_id(),
				'quantity'  => $variation->get_min_purchase_quantity(),
				'variation' => array(
					array(
						'attribute' => 'attribute_pa_color',
						'value'     => 'red-slug',
					),
				),
			),
			$state['draftSeeds'][ $draft_key ][ $variation->get_id() ],
			'The direct-variation stepper carries its own variation attributes, so an untouched direct-variation surface still posts its default at addItem time.'
		);
	}
}
