<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks;

use Automattic\WooCommerce\Blocks\Assets\Api;
use Automattic\WooCommerce\Blocks\BlockTypesController as TestedBlockTypesController;
use Automattic\WooCommerce\Tests\Blocks\Mocks\AssetDataRegistryMock;
use Automattic\WooCommerce\Blocks\Package;
use WP_Block;
use WP_Block_Type;
use WP_Block_Type_Registry;

/**
 * Unit tests for the PatternRegistry class.
 */
class BlockTypesController extends \WP_UnitTestCase {

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
	protected function setUp(): void {
		parent::setUp();
		$this->block_types_controller = new TestedBlockTypesController(
			Package::container()->get( Api::class ),
			new AssetDataRegistryMock( Package::container()->get( Api::class ) )
		);
	}

	/**
	 * Tear down test fixtures.
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		$this->unregister_block_type( 'woocommerce/category-title' );

		parent::tearDown();
	}

	/**
	 * @testdox Should register category title from block-library metadata.
	 *
	 * @return void
	 */
	public function test_registers_category_title_from_block_library_metadata(): void {
		$this->unregister_block_type( 'woocommerce/category-title' );

		$this->register_block_library_block_type( 'category-title' );

		$block_type = WP_Block_Type_Registry::get_instance()->get_registered( 'woocommerce/category-title' );
		$this->assertInstanceOf( WP_Block_Type::class, $block_type, 'Category title should be registered.' );
		$this->assertIsCallable( $block_type->render_callback, 'Category title should use metadata render callback.' );
	}

	/**
	 * @testdox Should render category title from the block-library render file.
	 *
	 * @return void
	 */
	public function test_renders_category_title_from_block_library_render_file(): void {
		$this->unregister_block_type( 'woocommerce/category-title' );
		$this->register_block_library_block_type( 'category-title' );

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
	 * Register 3 blocks, one will be allowed by full name, one by namespace,and one because it has a parent with a
	 * woocommerce namespace.
	 *
	 * @return void
	 */
	public function test_block_should_have_data_attributes() {

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
	 * Register a block-library block type.
	 *
	 * @param string $block_name Block metadata directory name.
	 * @return void
	 */
	private function register_block_library_block_type( string $block_name ): void {
		$method = new \ReflectionMethod( $this->block_types_controller, 'register_block_library_block_type' );
		$method->setAccessible( true );
		$method->invoke( $this->block_types_controller, $block_name );
	}

	/**
	 * Unregister a block type if it is registered.
	 *
	 * @param string $block_name Block name.
	 * @return void
	 */
	private function unregister_block_type( string $block_name ): void {
		$registry = WP_Block_Type_Registry::get_instance();

		if ( $registry->is_registered( $block_name ) ) {
			$registry->unregister( $block_name );
		}
	}
}
