<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Blocks;

use Automattic\WooCommerce\Blocks\Domain\Package;
use Automattic\WooCommerce\Blocks\Package as BlocksPackage;
use Automattic\WooCommerce\Internal\Blocks\BlockLibraryRegistry;
use WC_Unit_Test_Case;
use WP_Block;
use WP_Block_Metadata_Registry;
use WP_Block_Type_Registry;

/**
 * Tests for the BlockLibraryRegistry class.
 */
class BlockLibraryRegistryTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var BlockLibraryRegistry
	 */
	private BlockLibraryRegistry $sut;

	/**
	 * Blocks package.
	 *
	 * @var Package
	 */
	private Package $package;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->package = BlocksPackage::container()->get( Package::class );
		$this->sut     = new BlockLibraryRegistry( $this->package );
		wp_deregister_script( 'wc-block-library' );
		wp_deregister_script( 'wp-icons' );
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		wp_dequeue_script( 'wc-block-library' );
		wp_deregister_script( 'wc-block-library' );
		wp_deregister_script( 'wp-icons' );

		parent::tearDown();
	}

	/**
	 * @testdox Should load generated block-library asset registration.
	 */
	public function test_loads_generated_block_library_asset_registration(): void {
		$this->sut->load_asset_registration();
		do_action_ref_array( 'wp_default_scripts', array( wp_scripts() ) );

		$script = wp_scripts()->query( 'wc-block-library', 'registered' );
		$asset  = require $this->package->get_path( 'assets/client/blocks/scripts/block-library/index.min.asset.php' );

		$this->assertNotFalse( $script, 'Block library script should be registered.' );
		$this->assertSame( $asset['dependencies'], $script->deps, 'Block library script dependencies should match generated asset data.' );
		$this->assertSame( $asset['version'], $script->ver, 'Block library script version should match generated asset data.' );
		$this->assertStringContainsString(
			'assets/client/blocks/scripts/block-library/index.min.js',
			$script->src,
			'Block library script should use the generated script URL.'
		);
		$this->assertFalse( wp_script_is( 'wp-icons', 'registered' ), 'WordPress Icons fallback should not be registered.' );
	}

	/**
	 * @testdox Should initialize self-registering block-library PHP files.
	 */
	public function test_initializes_self_registering_block_library_php_files(): void {
		$this->assertGreaterThan( 0, did_action( 'init' ), 'The test environment should already be past init.' );

		$this->sut->init();

		$this->assertTrue(
			WP_Block_Metadata_Registry::has_metadata( $this->package->get_path( 'assets/client/blocks/category-title' ) ),
			'Category title metadata should be available from the generated metadata collection.'
		);

		$block_type = WP_Block_Type_Registry::get_instance()->get_registered( 'woocommerce/category-title' );

		$this->assertNotFalse( $block_type, 'Category title should be registered by its generated PHP file.' );
		$this->assertContains( 'wc-block-library', $block_type->editor_script_handles, 'Category title should use the registered block-library script handle.' );
		$this->assert_self_registering_block_is_registered( 'product-gallery' );
		$this->assert_self_registering_block_is_registered( 'product-gallery-large-image' );
		$this->assert_self_registering_block_is_registered( 'product-gallery-large-image-next-previous' );
		$this->assert_self_registering_block_is_registered( 'product-gallery-thumbnails' );

		$product_gallery_large_image = WP_Block_Type_Registry::get_instance()->get_registered( 'woocommerce/product-gallery-large-image' );
		if ( false === $product_gallery_large_image ) {
			$this->fail( 'Product gallery large image should be registered before checking skip_inner_blocks.' );
		}
		$this->assertTrue( $product_gallery_large_image->skip_inner_blocks, 'Product gallery large image should skip default inner block rendering.' );

		$term_id      = self::factory()->term->create(
			array(
				'name'     => 'Hoodies',
				'taxonomy' => 'product_cat',
			)
		);
		$parsed_block = parse_blocks( '<!-- wp:woocommerce/category-title {"level":3,"textAlign":"center"} /-->' )[0];
		$block        = new WP_Block(
			$parsed_block,
			array(
				'termId'       => $term_id,
				'termTaxonomy' => 'product_cat',
			)
		);

		$html = $block->render();

		$this->assertStringContainsString( '<h3', $html, 'Category title should render with the configured heading level.' );
		$this->assertStringContainsString( 'has-text-align-center', $html, 'Category title should render with text alignment class.' );
		$this->assertStringContainsString( 'Hoodies', $html, 'Category title should render the current term name.' );
	}

	/**
	 * Assert that a generated block-library block was self-registered.
	 *
	 * @param string $block_name Block name without namespace.
	 */
	private function assert_self_registering_block_is_registered( string $block_name ): void {
		$this->assertTrue(
			WP_Block_Metadata_Registry::has_metadata( $this->package->get_path( 'assets/client/blocks/' . $block_name ) ),
			ucfirst( $block_name ) . ' metadata should be available from the generated metadata collection.'
		);

		$block_type = WP_Block_Type_Registry::get_instance()->get_registered( 'woocommerce/' . $block_name );

		$this->assertNotFalse( $block_type, ucfirst( $block_name ) . ' should be registered by its generated PHP file.' );
		$this->assertContains( 'wc-block-library', $block_type->editor_script_handles, ucfirst( $block_name ) . ' should use the registered block-library script handle.' );
	}
}
