<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Blocks;

use Automattic\WooCommerce\Blocks\Domain\Package;
use Automattic\WooCommerce\Blocks\Package as BlocksPackage;
use Automattic\WooCommerce\Internal\Blocks\BlockLibraryScriptRegistry;
use WC_Unit_Test_Case;

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
		wp_deregister_script( 'wp-icons' );
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		wp_deregister_script( 'wc-block-library' );
		wp_deregister_script( 'wp-icons' );

		parent::tearDown();
	}

	/**
	 * @testdox Should register the block-library script from the generated registry.
	 */
	public function test_registers_block_library_script_from_generated_registry(): void {
		$this->sut->init();
		do_action_ref_array( 'wp_default_scripts', array( wp_scripts() ) );

		$this->assertTrue( wp_script_is( 'wc-block-library', 'registered' ), 'Block library script should be registered.' );
	}

	/**
	 * @testdox Should load dependencies and version from the generated asset file.
	 */
	public function test_loads_dependencies_and_version_from_generated_asset_file(): void {
		$this->sut->init();
		do_action_ref_array( 'wp_default_scripts', array( wp_scripts() ) );

		$script = wp_scripts()->query( 'wc-block-library', 'registered' );
		$asset  = require $this->package->get_path( 'assets/client/blocks/scripts/block-library/index.min.asset.php' );

		$this->assertSame( $asset['dependencies'], $script->deps, 'Block library script dependencies should match generated asset data.' );
		$this->assertSame( $asset['version'], $script->ver, 'Block library script version should match generated asset data.' );
	}

	/**
	 * @testdox Should not register a WordPress Icons fallback.
	 */
	public function test_does_not_register_wordpress_icons_fallback(): void {
		$this->sut->init();
		do_action_ref_array( 'wp_default_scripts', array( wp_scripts() ) );

		$this->assertFalse( wp_script_is( 'wp-icons', 'registered' ), 'WordPress Icons fallback should not be registered.' );
	}

	/**
	 * @testdox Should use the generated block-library script URL.
	 */
	public function test_uses_generated_block_library_script_url(): void {
		$this->sut->init();
		do_action_ref_array( 'wp_default_scripts', array( wp_scripts() ) );

		$script = wp_scripts()->query( 'wc-block-library', 'registered' );

		$this->assertStringContainsString(
			'assets/client/blocks/scripts/block-library/index.min.js',
			$script->src,
			'Block library script should use the generated script URL.'
		);
	}
}
