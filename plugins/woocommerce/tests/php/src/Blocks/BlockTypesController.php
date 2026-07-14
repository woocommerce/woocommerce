<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks;

use Automattic\WooCommerce\Blocks\Assets\Api;
use Automattic\WooCommerce\Blocks\BlockTypesController as TestedBlockTypesController;
use Automattic\WooCommerce\Blocks\Package;
use Automattic\WooCommerce\Internal\Features\BlockEditorAssetConsolidation;
use Automattic\WooCommerce\Tests\Blocks\Mocks\AssetDataRegistryMock;

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
		delete_option( BlockEditorAssetConsolidation::OPTION_NAME );
		$this->block_types_controller = new TestedBlockTypesController(
			Package::container()->get( Api::class ),
			new AssetDataRegistryMock( Package::container()->get( API::class ) )
		);
	}

	/**
	 * Clean up feature and screen state.
	 */
	public function tearDown(): void {
		delete_option( BlockEditorAssetConsolidation::OPTION_NAME );
		set_current_screen( 'front' );
		parent::tearDown();
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
	 * @testdox Should preserve block editor styles when asset consolidation is disabled.
	 */
	public function test_preserves_editor_styles_when_consolidation_is_disabled(): void {
		set_current_screen( 'post.php' );
		$args = array(
			'style_handles'        => array( 'core-style' ),
			'editor_style_handles' => array( 'core-editor-style' ),
		);

		$this->assertSame(
			$args,
			$this->block_types_controller->use_single_block_editor_style( $args, 'woocommerce/mini-cart' )
		);
	}

	/**
	 * @testdox Should replace styles only for WooCommerce-owned blocks when consolidation is enabled.
	 */
	public function test_replaces_only_woocommerce_owned_editor_styles_when_consolidation_is_enabled(): void {
		update_option( BlockEditorAssetConsolidation::OPTION_NAME, 'yes' );
		set_current_screen( 'post.php' );
		$args = array(
			'style_handles'        => array( 'extension-style' ),
			'style'                => array( 'extension-style' ),
			'editor_style_handles' => array( 'extension-editor-style' ),
			'editor_style'         => array( 'extension-editor-style' ),
		);

		$core_result = $this->block_types_controller->use_single_block_editor_style( $args, 'woocommerce/mini-cart' );

		$this->assertSame( array(), $core_result['style_handles'] );
		$this->assertSame( array(), $core_result['style'] );
		$this->assertSame( array( 'wc-block-library-style' ), $core_result['editor_style_handles'] );
		$this->assertSame( array( 'wc-block-library-style' ), $core_result['editor_style'] );
		$this->assertSame(
			$args,
			$this->block_types_controller->use_single_block_editor_style( $args, 'woocommerce/extension-block' )
		);
	}
}
