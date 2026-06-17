<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\BlockTypes\ProductCollection;

use Automattic\WooCommerce\Blocks\Assets\Api;
use Automattic\WooCommerce\Blocks\BlockTypes\ProductCollection\Controller;
use Automattic\WooCommerce\Blocks\Integrations\IntegrationRegistry;
use Automattic\WooCommerce\Blocks\Package;
use Automattic\WooCommerce\Tests\Blocks\Mocks\AssetDataRegistryMock;
use WC_Unit_Test_Case;

/**
 * Tests for the Product Collection block controller.
 */
class ControllerTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var Controller
	 */
	private $sut;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();

		$asset_api            = Package::container()->get( Api::class );
		$asset_data_registry  = new AssetDataRegistryMock( $asset_api );
		$integration_registry = new IntegrationRegistry();

		$this->sut = new class( $asset_api, $asset_data_registry, $integration_registry ) extends Controller {
			/**
			 * Skip normal hook registration for unit tests.
			 */
			protected function initialize() {
				$this->renderer = new class() {
					/**
					 * Accept parsed block data from the controller under test.
					 *
					 * @param array $parsed_block The parsed block.
					 */
					public function set_parsed_block( array $parsed_block ): void {}
				};
			}
		};

		wp_interactivity_config( 'woocommerce/product-filters', array( 'forcePageReload' => false ) );
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		wp_interactivity_config( 'woocommerce/product-filters', array( 'forcePageReload' => false ) );

		parent::tearDown();
	}

	/**
	 * @testdox Should configure product filters full page reload for inherited product collections.
	 */
	public function test_configures_product_filters_full_page_reload_for_inherited_product_collections(): void {
		$parsed_block                              = Utils::get_base_parsed_block();
		$parsed_block['attrs']['forcePageReload']  = true;
		$parsed_block['attrs']['query']['inherit'] = true;

		$this->sut->add_support_for_filter_blocks( null, $parsed_block );

		$config = wp_interactivity_config( 'woocommerce/product-filters' );

		$this->assertTrue(
			$config['forcePageReload'] ?? false,
			'Product Filters should be configured to reload when the inherited Product Collection forces page reload.'
		);
	}
}
