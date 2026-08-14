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
	}

	/**
	 * @testdox Should load Product Filters styles only when rendered in a classic theme.
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_classic_theme_loads_woocommerce_block_styles_only_when_rendered(): void {
		$block_name = 'woocommerce/product-filters';
		$block_type = \WP_Block_Type_Registry::get_instance()->get_registered( $block_name );

		$this->assertFalse( is_admin(), 'The test must run in a frontend context.' );
		$this->assertFalse( wp_is_block_theme(), 'The test must run with a classic theme.' );
		$this->assertInstanceOf( \WP_Block_Type::class, $block_type );
		if ( ! $block_type instanceof \WP_Block_Type ) {
			return;
		}

		$metadata_style_handles = $block_type->style_handles;
		$this->assertNotEmpty( $metadata_style_handles, 'Product Filters must have metadata-derived style handles before re-registration.' );

		add_filter( 'should_load_separate_core_block_assets', '__return_false', PHP_INT_MAX );
		add_filter( 'should_load_block_assets_on_demand', '__return_false', PHP_INT_MAX );
		$this->assertFalse( wp_should_load_separate_core_block_assets(), 'The test must disable separate Core block assets.' );
		$this->assertFalse( wp_should_load_block_assets_on_demand(), 'The test must disable block-asset loading on demand.' );

		foreach ( $metadata_style_handles as $style_handle ) {
			wp_dequeue_style( $style_handle );
		}

		$block_registry = \WP_Block_Type_Registry::get_instance();
		foreach ( array_keys( $block_registry->get_all_registered() ) as $registered_block_name ) {
			if ( str_starts_with( $registered_block_name, 'woocommerce/' ) ) {
				$block_registry->unregister( $registered_block_name );
			}
		}
		$this->block_types_controller->register_blocks();

		$block_type = \WP_Block_Type_Registry::get_instance()->get_registered( $block_name );
		$this->assertInstanceOf( \WP_Block_Type::class, $block_type );
		if ( ! $block_type instanceof \WP_Block_Type ) {
			return;
		}
		$this->assertNotEmpty( $block_type->render_callback, 'Product Filters must use its registered render callback.' );
		$this->assertSame( array(), $block_type->style_handles, 'Classic-theme registration must clear Product Filters style handles.' );

		foreach ( $metadata_style_handles as $style_handle ) {
			$this->assertTrue( wp_style_is( $style_handle, 'registered' ), 'Product Filters metadata styles must remain registered.' );
			$this->assertFalse( wp_style_is( $style_handle, 'enqueued' ), 'Product Filters styles must not be queued before rendering.' );
		}

		wp_enqueue_registered_block_scripts_and_styles();
		remove_action( 'wp_print_styles', 'print_emoji_styles' );

		$output_level = ob_get_level();
		ob_start();
		try {
			wp_print_styles();
			$styles_without_woocommerce_blocks = (string) ob_get_clean();
		} finally {
			while ( ob_get_level() > $output_level ) {
				ob_end_clean();
			}
		}

		foreach ( $metadata_style_handles as $style_handle ) {
			$this->assertStringNotContainsString( $style_handle, $styles_without_woocommerce_blocks, 'Product Filters styles must not print before rendering.' );
		}
		$this->assertDoesNotMatchRegularExpression(
			'#<link[^>]*href=[\'\"][^\'\"]*assets/client/blocks/(?![^\'\"]*wc-blocks\\.css)[^\'\"]*[\'\"][^>]*>#',
			$styles_without_woocommerce_blocks,
			'Classic pages without WooCommerce blocks must not print per-block styles.'
		);
		$this->assertDoesNotMatchRegularExpression(
			'#<style[^>]*id=[\'\"]woocommerce-[^\'\"]+-style-inline-css[\'\"][^>]*>#',
			$styles_without_woocommerce_blocks,
			'Classic pages without WooCommerce blocks must not print inline block styles.'
		);

		$rendered_block = do_blocks( '<!-- wp:woocommerce/product-filters /-->' );
		$this->assertStringContainsString( 'wc-block-product-filters', $rendered_block );
		$this->assertSame(
			$metadata_style_handles,
			array_values( array_intersect( $metadata_style_handles, wp_styles()->queue ) ),
			'Rendering Product Filters must queue every metadata-derived style handle.'
		);

		$output_level = ob_get_level();
		ob_start();
		try {
			wp_print_styles();
			$styles_with_woocommerce_block = (string) ob_get_clean();
		} finally {
			while ( ob_get_level() > $output_level ) {
				ob_end_clean();
			}
		}

		$this->assertMatchesRegularExpression(
			'#(?:href=[\'\"][^\'\"]*product-filters[^\'\"]*[\'\"]|id=[\'\"]woocommerce-product-filters-style-inline-css[\'\"])#',
			$styles_with_woocommerce_block,
			'Rendering Product Filters must print its file or inline style identifier.'
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
