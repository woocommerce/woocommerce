<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Blocks;

use Automattic\WooCommerce\Blocks\Domain\Package;
use Automattic\WooCommerce\Blocks\Package as BlocksPackage;
use Automattic\WooCommerce\Internal\Blocks\BlockLibraryScriptRegistry;
use WC_Unit_Test_Case;
use WP_Block_Type;
use WP_Block_Type_Registry;

/**
 * Tests for the BlockLibraryScriptRegistry class.
 */
class BlockLibraryScriptRegistryTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var BlockLibraryScriptRegistry
	 */
	private BlockLibraryScriptRegistry $sut;

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
		$this->sut     = new BlockLibraryScriptRegistry( $this->package );
		wp_deregister_script( 'wc-block-library' );
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		$this->unregister_block_type( 'woocommerce/category-title' );
		wp_deregister_script( 'wc-block-library' );

		parent::tearDown();
	}

	/**
	 * @testdox Should register the block-library script from the generated registry.
	 */
	public function test_registers_block_library_script_from_generated_registry(): void {
		$this->sut->register_scripts();

		$this->assertTrue( wp_script_is( 'wc-block-library', 'registered' ), 'Block library script should be registered.' );
	}

	/**
	 * @testdox Should load dependencies and version from the generated asset file.
	 */
	public function test_loads_dependencies_and_version_from_generated_asset_file(): void {
		$this->sut->register_scripts();

		$script = wp_scripts()->query( 'wc-block-library', 'registered' );
		$asset  = require $this->package->get_path( 'assets/client/blocks/scripts/block-library/index.min.asset.php' );

		$this->assertSame( $asset['dependencies'], $script->deps, 'Block library script dependencies should match generated asset data.' );
		$this->assertSame( $asset['version'], $script->ver, 'Block library script version should match generated asset data.' );
	}

	/**
	 * @testdox Should use the generated block-library script URL.
	 */
	public function test_uses_generated_block_library_script_url(): void {
		$this->sut->register_scripts();

		$script = wp_scripts()->query( 'wc-block-library', 'registered' );

		$this->assertStringContainsString(
			'assets/client/blocks/scripts/block-library/index.min.js',
			$script->src,
			'Block library script should use the generated script URL.'
		);
	}

	/**
	 * @testdox Should get metadata paths from the complete block-library metadata package.
	 */
	public function test_gets_metadata_path_from_complete_block_library_metadata_package(): void {
		$metadata_path = $this->sut->get_block_metadata_path( 'category-title' );

		$this->assertStringEndsWith(
			'assets/client/blocks/category-title',
			$metadata_path,
			'Block library metadata path should point to a directory with block.json and the referenced render file.'
		);
	}

	/**
	 * @testdox Should register block types from block-library metadata.
	 */
	public function test_registers_block_type_from_metadata(): void {
		$this->unregister_block_type( 'woocommerce/category-title' );
		$this->sut->register_scripts();

		$this->sut->register_block_type_from_metadata( 'category-title' );

		$block_type = WP_Block_Type_Registry::get_instance()->get_registered( 'woocommerce/category-title' );
		$this->assertInstanceOf( WP_Block_Type::class, $block_type, 'Category title should be registered.' );
		$this->assertContains( 'wc-block-library', $block_type->editor_script_handles, 'Category title should use the registered block-library script handle.' );
	}

	/**
	 * Unregister a block type if it is registered.
	 *
	 * @param string $block_name Block name.
	 */
	private function unregister_block_type( string $block_name ): void {
		$registry = WP_Block_Type_Registry::get_instance();

		if ( $registry->is_registered( $block_name ) ) {
			$registry->unregister( $block_name );
		}
	}
}
