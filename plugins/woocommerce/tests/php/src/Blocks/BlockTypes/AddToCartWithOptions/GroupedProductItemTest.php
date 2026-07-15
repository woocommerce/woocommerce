<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\BlockTypes\AddToCartWithOptions;

use Automattic\WooCommerce\Blocks\BlockTypes\AddToCartWithOptions\GroupedProductItem;
use Automattic\WooCommerce\Tests\Blocks\Mocks\AddToCartWithOptionsGroupedProductItemMock;
use Automattic\WooCommerce\Tests\Blocks\Mocks\AddToCartWithOptionsGroupedProductItemSelectorMock;
use ReflectionClass;
use WC_Product_Grouped;
use WC_Product_Simple;
use WC_Unit_Test_Case;

/**
 * Tests for the Grouped Product Item block type.
 */
class GroupedProductItemTest extends WC_Unit_Test_Case {

	/**
	 * Tracks whether blocks have been registered.
	 *
	 * @var bool
	 */
	protected static $are_blocks_registered = false;

	/**
	 * The Grouped Product Item block instance under test.
	 *
	 * Block types can only be registered once per process, so this single
	 * instance is created once (in setUp()) and reused by every test that
	 * needs to call its methods directly, rather than constructing a new
	 * instance (which would re-register the block and fail).
	 *
	 * @var AddToCartWithOptionsGroupedProductItemMock
	 */
	protected static $grouped_product_item;

	/**
	 * Register the blocks under test.
	 */
	public function setUp(): void {
		parent::setUp();

		if ( ! self::$are_blocks_registered ) {
			$registry = \WP_Block_Type_Registry::get_instance();

			// Another test class (e.g. AddToCartWithOptions.php) may have
			// already registered these block types in the same PHPUnit
			// process. This class still needs its own instance for the
			// direct method calls below, and re-registering over an existing
			// block type would otherwise trigger an "already registered"
			// doing_it_wrong notice (and be a no-op), so unregister first.
			foreach (
				array(
					'woocommerce/add-to-cart-with-options-grouped-product-item',
					'woocommerce/add-to-cart-with-options-grouped-product-item-selector',
				) as $block_name
			) {
				if ( $registry->is_registered( $block_name ) ) {
					$registry->unregister( $block_name );
				}
			}

			// The blocks are not registered on `init` when running under a
			// classic theme, so register them explicitly for these tests.
			self::$grouped_product_item = new AddToCartWithOptionsGroupedProductItemMock();
			new AddToCartWithOptionsGroupedProductItemSelectorMock();

			self::$are_blocks_registered = true;
		}
	}

	/**
	 * Gets markup for a Grouped Product Item row containing its item selector.
	 *
	 * @return string Block markup.
	 */
	private function get_row_markup(): string {
		return '<!-- wp:woocommerce/add-to-cart-with-options-grouped-product-item -->
<!-- wp:woocommerce/add-to-cart-with-options-grouped-product-item-selector /-->
<!-- /wp:woocommerce/add-to-cart-with-options-grouped-product-item -->';
	}

	/**
	 * Registers a `render_block_context` filter at priority 1 that pins
	 * `postId`/`postType` to a given product, mirroring the priority-1
	 * filter that `ProductTemplate::render()` registers for each Product
	 * Collection loop item.
	 *
	 * @param int $product_id Product ID to force onto the context.
	 * @return callable The registered filter callback, so it can be removed later.
	 */
	private function force_outer_product_context( int $product_id ): callable {
		$filter = static function ( $context ) use ( $product_id ) {
			$context['postId']   = $product_id;
			$context['postType'] = 'product';
			return $context;
		};

		add_filter( 'render_block_context', $filter, 1 );

		return $filter;
	}

	/**
	 * @testdox Each grouped child row's inner blocks resolve the row's own child product, even when an outer render_block_context filter (like a Product Collection loop) has already pinned the context to a different product.
	 */
	public function test_row_context_survives_an_outer_render_block_context_override(): void {
		global $product;
		$previous_product = $product;

		$first_child = new WC_Product_Simple();
		$first_child->set_regular_price( 10 );
		$first_child->set_name( 'First grouped child' );
		$first_child_id = $first_child->save();

		$second_child = new WC_Product_Simple();
		$second_child->set_regular_price( 15 );
		$second_child->set_name( 'Second grouped child' );
		$second_child_id = $second_child->save();

		$outer_product = new WC_Product_Simple();
		$outer_product->set_regular_price( 20 );
		$outer_product->set_name( 'Outer loop product' );
		$outer_product_id = $outer_product->save();

		$grouped = new WC_Product_Grouped();
		$grouped->set_children( array( $first_child_id, $second_child_id ) );
		$grouped->save();

		$product = $grouped;

		$filter = $this->force_outer_product_context( $outer_product_id );

		try {
			$markup = do_blocks( $this->get_row_markup() );
		} finally {
			remove_filter( 'render_block_context', $filter, 1 );
			$product = $previous_product;
		}

		$this->assertStringContainsString( 'name="quantity[' . $first_child_id . ']"', $markup, 'The first row resolves its own child product, rendering that child\'s quantity input.' );
		$this->assertStringContainsString( 'name="quantity[' . $second_child_id . ']"', $markup, 'The second row resolves its own child product, rendering that child\'s quantity input.' );
		$this->assertStringNotContainsString( 'name="quantity[' . $outer_product_id . ']"', $markup, 'Neither row falls back to the product pinned onto context by the outer filter.' );
	}

	/**
	 * @testdox A grouped child row's inner blocks resolve the row's own child product when no outer render_block_context filter is present.
	 */
	public function test_row_context_resolves_child_product_without_an_outer_filter(): void {
		global $product;
		$previous_product = $product;

		$child = new WC_Product_Simple();
		$child->set_regular_price( 10 );
		$child->set_name( 'Grouped child' );
		$child_id = $child->save();

		$grouped = new WC_Product_Grouped();
		$grouped->set_children( array( $child_id ) );
		$grouped->save();

		$product = $grouped;

		$markup = do_blocks( $this->get_row_markup() );

		$product = $previous_product;

		$this->assertStringContainsString( 'name="quantity[' . $child_id . ']"', $markup, 'The row resolves its own child product when rendered without any outer context override.' );
	}

	/**
	 * @testdox The context callback re-asserts the row's stashed product id and type, overriding whatever an earlier-priority filter already set on the context.
	 */
	public function test_context_callback_reasserts_stashed_row_product_id(): void {
		$sut = self::$grouped_product_item;

		// Reflect via the declaring class: ReflectionClass::getProperty()
		// cannot see a private property through a subclass's reflection.
		$reflection = new ReflectionClass( GroupedProductItem::class );
		$property   = $reflection->getProperty( 'current_row_product_id' );
		$property->setAccessible( true );
		$property->setValue( $sut, 123 );

		try {
			$context = $sut->set_is_descendant_of_grouped_product_selector_context(
				array(
					'postId'   => 999,
					'postType' => 'page',
				),
				array( 'blockName' => 'core/paragraph' )
			);
		} finally {
			// Restore the shared instance to its "no row in progress" state
			// so it doesn't leak into other tests.
			$property->setValue( $sut, null );
		}

		$this->assertSame( 123, $context['postId'], 'The row\'s own product id overrides whatever an earlier-priority filter set.' );
		$this->assertSame( 'product', $context['postType'], 'The postType is re-asserted to "product" alongside the row\'s own id.' );
	}

	/**
	 * @testdox The context callback leaves postId/postType untouched when no row is currently being rendered.
	 */
	public function test_context_callback_leaves_context_untouched_outside_a_row(): void {
		$sut = self::$grouped_product_item;

		$context = $sut->set_is_descendant_of_grouped_product_selector_context(
			array(
				'postId'   => 999,
				'postType' => 'page',
			),
			array( 'blockName' => 'core/paragraph' )
		);

		$this->assertSame( 999, $context['postId'], 'postId is left untouched when no row is being rendered.' );
		$this->assertSame( 'page', $context['postType'], 'postType is left untouched when no row is being rendered.' );
	}

	/**
	 * @testdox The context callback still flags Product Price and Product Stock Indicator blocks as descendants of the grouped product selector.
	 */
	public function test_context_callback_still_flags_price_and_stock_indicator_descendants(): void {
		$sut = self::$grouped_product_item;

		$price_context = $sut->set_is_descendant_of_grouped_product_selector_context(
			array(),
			array( 'blockName' => 'woocommerce/product-price' )
		);
		$stock_context = $sut->set_is_descendant_of_grouped_product_selector_context(
			array(),
			array( 'blockName' => 'woocommerce/product-stock-indicator' )
		);

		$this->assertArrayHasKey( 'isDescendantOfGroupedProductSelector', $price_context );
		$this->assertTrue( $price_context['isDescendantOfGroupedProductSelector'] );
		$this->assertArrayHasKey( 'isDescendantOfGroupedProductSelector', $stock_context );
		$this->assertTrue( $stock_context['isDescendantOfGroupedProductSelector'] );
	}
}
