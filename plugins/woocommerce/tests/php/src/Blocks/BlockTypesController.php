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

		// These patterns are referenced from the default Cart page content created at install
		// (see WC_Install::get_cart_block_content()). They must be registered by the controller
		// itself, which runs unconditionally, rather than by the Cart block type, which is only
		// registered when the Cart block is enabled; otherwise the installed page renders nothing
		// for the references.
		$slugs = array( 'woocommerce/cart-empty-message', 'woocommerce/cart-new-in-store-message' );

		foreach ( $slugs as $slug ) {
			if ( $registry->is_registered( $slug ) ) {
				unregister_block_pattern( $slug );
			}
		}

		try {
			$this->block_types_controller->register_block_patterns();

			foreach ( $slugs as $slug ) {
				$this->assertTrue(
					$registry->is_registered( $slug ),
					"BlockTypesController::register_block_patterns() should register {$slug}; the installed Cart page depends on it."
				);
			}
		} finally {
			// Restore the global registry state for later tests even if an assertion above failed.
			$this->block_types_controller->register_block_patterns();
		}
	}
}
