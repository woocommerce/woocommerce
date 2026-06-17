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
	}

	/**
	 * @testdox Should initialize self-registering block-library PHP files.
	 */
	public function test_initializes_self_registering_block_library_php_files(): void {
		$this->sut->init();

		$this->assertTrue(
			WP_Block_Metadata_Registry::has_metadata( $this->package->get_path( 'assets/client/blocks/category-title' ) ),
			'Category title metadata should be available from the generated metadata collection.'
		);

		$block_type = WP_Block_Type_Registry::get_instance()->get_registered( 'woocommerce/category-title' );

		$this->assertNotFalse( $block_type, 'Category title should be registered by its generated PHP file.' );

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
}
