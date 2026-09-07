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
	 * Block registered through the real registration path, so register_block_type_args fires on it.
	 */
	private const PROBE_BLOCK = 'woocommerce/classic-theme-fallback-probe';

	/**
	 * Style handle the probe block declares, registered without a source.
	 */
	private const PROBE_STYLE = 'wc-classic-theme-fallback-probe';

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
	 * Removes the probe block and style; the base class does not reset the block registry or the style queue.
	 */
	public function tearDown(): void {
		try {
			if ( \WP_Block_Type_Registry::get_instance()->is_registered( self::PROBE_BLOCK ) ) {
				unregister_block_type( self::PROBE_BLOCK );
			}
			wp_dequeue_style( self::PROBE_STYLE );
			wp_deregister_style( self::PROBE_STYLE );
		} finally {
			parent::tearDown();
		}
	}

	/**
	 * @testdox Should defer a block's style until the block renders on a classic theme.
	 */
	public function test_classic_theme_defers_block_style_until_render(): void {
		switch_theme( 'storefront' );
		$this->assertFalse( wp_is_block_theme(), 'The test must run with a classic theme.' );

		// WordPress 6.9+ opts classic themes into on-demand block assets at priority 0 (see
		// wp_load_classic_theme_block_styles_on_demand()), so the fallback is only live on sites that opt out.
		add_filter( 'should_load_separate_core_block_assets', '__return_false', PHP_INT_MAX );
		add_filter( 'should_load_block_assets_on_demand', '__return_false', PHP_INT_MAX );
		$this->assertFalse( wp_should_load_block_assets_on_demand(), 'The test must simulate a site that opted out of on-demand block assets.' );

		$block_type = $this->register_probe_block();

		$this->assertSame( array(), $block_type->style_handles, 'Registration must strip the style so Core does not queue it on every page.' );
		$this->assertFalse( wp_style_is( self::PROBE_STYLE, 'enqueued' ), 'The style must not be queued before the block renders.' );

		$this->assertStringContainsString( 'class="probe"', do_blocks( '<!-- wp:' . self::PROBE_BLOCK . ' /-->' ) );
		$this->assertTrue( wp_style_is( self::PROBE_STYLE, 'enqueued' ), 'Rendering the block must queue its style.' );
	}

	/**
	 * @testdox Should stand down when WordPress already loads block assets on demand.
	 * @testWith ["storefront"]
	 *           ["twentytwentytwo"]
	 *
	 * @param string $theme Theme to activate before the decision is made.
	 */
	public function test_stands_down_when_core_loads_block_assets_on_demand( string $theme ): void {
		switch_theme( $theme );
		$this->assertTrue( wp_should_load_block_assets_on_demand(), 'WordPress must already be loading block assets on demand.' );
		$args = array( 'style_handles' => array( self::PROBE_STYLE ) );

		$result = $this->block_types_controller->enqueue_block_style_for_classic_themes( $args, self::PROBE_BLOCK );

		$this->assertSame( $args, $result, 'Core queues the style on render already, so the args must pass through untouched.' );
		$this->assertFalse(
			has_filter( 'register_block_type_args', array( $this->block_types_controller, 'enqueue_block_style_for_classic_themes' ) ),
			'The fallback must unhook itself once it decides it is not needed.'
		);
	}

	/**
	 * Registers the probe block and its style through the real registration path.
	 *
	 * @return \WP_Block_Type The registered block type.
	 */
	private function register_probe_block(): \WP_Block_Type {
		wp_register_style( self::PROBE_STYLE, false, array(), '1' );
		$block_type = register_block_type(
			self::PROBE_BLOCK,
			array(
				'style_handles'   => array( self::PROBE_STYLE ),
				'render_callback' => static fn() => '<div class="probe"></div>',
			)
		);
		$this->assertInstanceOf( \WP_Block_Type::class, $block_type );

		return $block_type;
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
