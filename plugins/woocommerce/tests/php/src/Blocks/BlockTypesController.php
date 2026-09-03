<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks;

use Automattic\WooCommerce\Blocks\Assets\Api;
use Automattic\WooCommerce\Blocks\BlockTypesController as TestedBlockTypesController;
use Automattic\WooCommerce\Blocks\Package;
use Automattic\WooCommerce\Tests\Blocks\Mocks\AssetDataRegistryMock;
use WC_Unit_Test_Case;

/**
 * Unit tests for the BlockTypesController class.
 */
class BlockTypesController extends WC_Unit_Test_Case {

	/**
	 * Holds the BlockTypesController under test.
	 *
	 * @var TestedBlockTypesController The BlockTypesController under test.
	 */
	private $block_types_controller;

	/**
	 * WooCommerce block types registered before the test ran, keyed by name.
	 *
	 * @var \WP_Block_Type[]
	 */
	private $registered_woo_blocks = array();

	/**
	 * Style handles queued before the test ran.
	 *
	 * @var string[]
	 */
	private $styles_queue = array();

	/**
	 * Sets up a new TestedBlockTypesController so it can be tested.
	 *
	 * @return void
	 * @throws \Exception If there is no dependency for the given identifier in the container the setup will fail.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->block_types_controller = new TestedBlockTypesController(
			Package::container()->get( Api::class ),
			new AssetDataRegistryMock( Package::container()->get( API::class ) )
		);

		$registry                    = \WP_Block_Type_Registry::get_instance();
		$this->registered_woo_blocks = array_filter(
			$registry->get_all_registered(),
			fn( $name ) => str_starts_with( $name, 'woocommerce/' ),
			ARRAY_FILTER_USE_KEY
		);
		$this->styles_queue          = wp_styles()->queue;
	}

	/**
	 * Restores the block registry and style queue a test may have rebuilt.
	 */
	public function tearDown(): void {
		$registry = \WP_Block_Type_Registry::get_instance();
		foreach ( array_keys( $registry->get_all_registered() ) as $name ) {
			if ( str_starts_with( $name, 'woocommerce/' ) ) {
				$registry->unregister( $name );
			}
		}
		foreach ( $this->registered_woo_blocks as $block_type ) {
			$registry->register( $block_type );
		}
		wp_styles()->queue = $this->styles_queue;

		parent::tearDown();
	}

	/**
	 * @testdox Should queue the Product Filters style only when the block renders in a classic theme.
	 */
	public function test_classic_theme_queues_product_filters_style_only_when_rendered(): void {
		$block_name = 'woocommerce/product-filters';
		$registry   = \WP_Block_Type_Registry::get_instance();

		// The fallback under test only runs for classic themes, and the suite's active theme depends on
		// the WordPress version, so pin a classic theme rather than relying on the ambient one.
		switch_theme( 'storefront' );

		$this->assertFalse( is_admin(), 'The test must run in a frontend context.' );
		$this->assertFalse( wp_is_block_theme(), 'The test must run with a classic theme.' );

		$block_type = $registry->get_registered( $block_name );
		$this->assertInstanceOf( \WP_Block_Type::class, $block_type );
		$metadata_style_handles = $block_type->style_handles;
		$this->assertNotEmpty( $metadata_style_handles, 'Product Filters must have metadata-derived style handles before re-registration.' );

		// Put WordPress on the classic-asset path WooCommerce's fallback exists for, then rebuild the registry under it.
		add_filter( 'should_load_separate_core_block_assets', '__return_false', PHP_INT_MAX );
		add_filter( 'should_load_block_assets_on_demand', '__return_false', PHP_INT_MAX );
		$this->assertFalse( wp_should_load_separate_core_block_assets(), 'The test must disable separate Core block assets.' );
		$this->assertFalse( wp_should_load_block_assets_on_demand(), 'The test must disable block-asset loading on demand.' );

		// Earlier tests may have rendered blocks and left their styles queued; start from an empty queue so the
		// assertions below only see what this registration and render produce. tearDown restores the original queue.
		wp_styles()->queue = array();

		foreach ( array_keys( $this->registered_woo_blocks ) as $name ) {
			$registry->unregister( $name );
		}
		$this->block_types_controller->register_blocks();

		$block_type = $registry->get_registered( $block_name );
		$this->assertInstanceOf( \WP_Block_Type::class, $block_type );
		$this->assertSame( array(), $block_type->style_handles, 'Classic-theme registration must clear Product Filters style handles.' );

		foreach ( $metadata_style_handles as $style_handle ) {
			$this->assertTrue( wp_style_is( $style_handle, 'registered' ), 'Product Filters metadata styles must remain registered.' );
		}

		// This is the Core path that would otherwise queue every registered block style on a classic page.
		wp_enqueue_registered_block_scripts_and_styles();
		$this->assertSame(
			array(),
			array_values( array_intersect( $metadata_style_handles, wp_styles()->queue ) ),
			'Product Filters metadata styles must not be queued before the block renders.'
		);
		$this->assertSame(
			array(),
			preg_grep( '#^woocommerce-.+-style$#', wp_styles()->queue ),
			'Classic pages without WooCommerce blocks must not queue any block style.'
		);

		$rendered_block = do_blocks( '<!-- wp:woocommerce/product-filters /-->' );
		$this->assertStringContainsString( 'wc-block-product-filters', $rendered_block );
		$this->assertSame(
			$metadata_style_handles,
			array_values( array_intersect( $metadata_style_handles, wp_styles()->queue ) ),
			'Rendering Product Filters must queue every metadata-derived style handle.'
		);
	}

	/**
	 * @testdox Should identify blocks that should have data attributes.
	 */
	public function test_block_should_have_data_attributes(): void {

		// A block that will not be allowed data attributes.
		register_block_type(
			'unrelated-namespace/unrelated-block-name',
		);

		// A block that will be allowed explicitly by full name.
		register_block_type(
			'namespace/allowed-block-name',
		);

		// A block that will be allowed explicitly by full name.
		register_block_type(
			'allowed-namespace/block-name',
			[
				'parent' => [ 'core/paragraph' ],
			]
		);

		// A block that will be allowed because it has a parent with a woocommerce namespace.
		register_block_type(
			'child-of-woo/block-name',
			[
				'parent' => [ 'woocommerce/checkout-contact-information-block' ],
			]
		);

		$answer = $this->block_types_controller->block_should_have_data_attributes( 'unrelated-namespace/unrelated-block-name' );
		$this->assertFalse( $answer );

		add_filter(
			'__experimental_woocommerce_blocks_add_data_attributes_to_block',
			function ( $blocks ) {
				$blocks[] = 'namespace/allowed-block-name';
				return $blocks;
			}
		);
		$answer = $this->block_types_controller->block_should_have_data_attributes( 'namespace/allowed-block-name' );
		$this->assertTrue( $answer );

		add_filter(
			'__experimental_woocommerce_blocks_add_data_attributes_to_namespace',
			function ( $namespaces ) {
				$namespaces[] = 'allowed-namespace';
				return $namespaces;
			}
		);
		$answer = $this->block_types_controller->block_should_have_data_attributes( 'allowed-namespace/block-name' );
		$this->assertTrue( $answer );

		$answer = $this->block_types_controller->block_should_have_data_attributes( 'child-of-woo/block-name' );
		$this->assertTrue( $answer );
	}

	/**
	 * @testdox register_block_patterns() registers the empty cart message patterns referenced by the installed Cart page.
	 */
	public function test_register_block_patterns_registers_installed_cart_page_patterns(): void {
		$registry = \WP_Block_Patterns_Registry::get_instance();

		// The default Cart page created at install references these patterns
		// (see WC_Install::get_cart_block_content()). Registering them here rather than
		// in the Cart block type means the page can still resolve the references when
		// the Cart block itself is not registered.
		$slugs = array( 'woocommerce/cart-empty-message', 'woocommerce/cart-new-in-store-message' );

		foreach ( $slugs as $slug ) {
			if ( $registry->is_registered( $slug ) ) {
				unregister_block_pattern( $slug );
			}
		}

		$this->block_types_controller->register_block_patterns();

		foreach ( $slugs as $slug ) {
			$this->assertTrue(
				$registry->is_registered( $slug ),
				"BlockTypesController::register_block_patterns() should register {$slug}; the installed Cart page depends on it."
			);
		}
	}
}
