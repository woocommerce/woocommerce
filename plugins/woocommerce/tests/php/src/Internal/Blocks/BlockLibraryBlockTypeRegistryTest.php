<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Blocks;

use Automattic\WooCommerce\Blocks\Domain\Package;
use Automattic\WooCommerce\Blocks\Package as BlocksPackage;
use Automattic\WooCommerce\Internal\Blocks\BlockLibraryBlockTypeRegistry;
use WC_Unit_Test_Case;
use WP_Block;
use WP_Block_Type;
use WP_Block_Type_Registry;

/**
 * Tests for the BlockLibraryBlockTypeRegistry class.
 */
class BlockLibraryBlockTypeRegistryTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var BlockLibraryBlockTypeRegistry
	 */
	private BlockLibraryBlockTypeRegistry $sut;

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
		$this->sut     = new BlockLibraryBlockTypeRegistry( $this->package );
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		$this->unregister_block_type( 'woocommerce/category-title' );
		wp_dequeue_script( 'wc-block-library' );

		parent::tearDown();
	}

	/**
	 * @testdox Should remove migrated block types from the legacy block type list.
	 */
	public function test_removes_migrated_block_types_from_legacy_block_type_list(): void {
		$this->assertSame(
			array( 'Cart', 'Checkout' ),
			$this->sut->remove_migrated_block_types( array( 'Cart', 'CategoryTitle', 'Checkout' ) )
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

		$this->sut->register_block_type_from_metadata( 'category-title' );

		$block_type = WP_Block_Type_Registry::get_instance()->get_registered( 'woocommerce/category-title' );
		$this->assertInstanceOf( WP_Block_Type::class, $block_type, 'Category title should be registered.' );
		$this->assertContains( 'wc-block-library', $block_type->editor_script_handles, 'Category title should use the registered block-library script handle.' );
	}

	/**
	 * @testdox Should register block types immediately when init has already fired.
	 */
	public function test_registers_block_types_immediately_when_init_has_already_fired(): void {
		$this->unregister_block_type( 'woocommerce/category-title' );
		$this->assertGreaterThan( 0, did_action( 'init' ), 'The test environment should already be past init.' );

		$this->sut->init();

		$this->assertTrue(
			WP_Block_Type_Registry::get_instance()->is_registered( 'woocommerce/category-title' ),
			'Category title should be registered even when the registry is initialized after init.'
		);
	}

	/**
	 * @testdox Should enqueue editor scripts declared by migrated block metadata.
	 */
	public function test_enqueues_editor_scripts_declared_by_migrated_block_metadata(): void {
		wp_deregister_script( 'wc-block-library' );
		wp_register_script( 'wc-block-library', 'https://example.com/wc-block-library.js', array(), '1.0.0', true );

		$this->sut->enqueue_block_editor_assets();

		$this->assertTrue( wp_script_is( 'wc-block-library', 'enqueued' ), 'Block library editor script should be enqueued.' );
	}

	/**
	 * @testdox Should render category title from the block-library render file.
	 */
	public function test_renders_category_title_from_block_library_render_file(): void {
		$this->unregister_block_type( 'woocommerce/category-title' );
		$this->sut->register_block_type_from_metadata( 'category-title' );

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
