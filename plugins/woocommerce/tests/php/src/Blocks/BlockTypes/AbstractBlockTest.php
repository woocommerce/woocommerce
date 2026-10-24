<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\BlockTypes;

use Automattic\WooCommerce\Blocks\Assets\Api;
use Automattic\WooCommerce\Blocks\Assets\AssetDataRegistry;
use Automattic\WooCommerce\Blocks\BlockTypes\AbstractBlock;
use Automattic\WooCommerce\Blocks\Integrations\IntegrationRegistry;
use Automattic\WooCommerce\Blocks\Package;
use WC_Unit_Test_Case;

/**
 * Tests for the AbstractBlock class.
 */
class AbstractBlockTest extends WC_Unit_Test_Case {

	/**
	 * Create a testable AbstractBlock instance with a mocked asset API.
	 *
	 * @param Api  $asset_api Mocked asset API.
	 * @param bool $with_script_handle Whether the block has a front-end script handle.
	 * @return AbstractBlock
	 */
	private function create_block( Api $asset_api, bool $with_script_handle ): AbstractBlock {
		$asset_data_registry = Package::container()->get( AssetDataRegistry::class );

		return new class( $asset_api, $asset_data_registry, new IntegrationRegistry(), 'test-block', $with_script_handle ) extends AbstractBlock {
			/**
			 * Whether the block has a front-end script handle.
			 *
			 * @var bool
			 */
			private $with_script_handle;

			/**
			 * Constructor.
			 *
			 * @param Api                 $asset_api Instance of the asset API.
			 * @param AssetDataRegistry   $asset_data_registry Instance of the asset data registry.
			 * @param IntegrationRegistry $integration_registry Instance of the integration registry.
			 * @param string              $block_name Block name.
			 * @param bool                $with_script_handle Whether the block has a front-end script handle.
			 */
			public function __construct( $asset_api, $asset_data_registry, $integration_registry, $block_name, bool $with_script_handle ) {
				$this->with_script_handle = $with_script_handle;
				parent::__construct( $asset_api, $asset_data_registry, $integration_registry, $block_name );
			}

			/**
			 * Skip block registration in tests.
			 */
			protected function register_block_type() {}

			/**
			 * Blocks without a front-end script (e.g. Coming Soon) return null.
			 *
			 * @param string $key Data to get, or default to everything.
			 * @return array|string|null
			 */
			protected function get_block_type_script( $key = null ) {
				return $this->with_script_handle ? parent::get_block_type_script( $key ) : null;
			}

			/**
			 * Public wrapper for register_chunk_translations.
			 *
			 * @param string[] $chunks Array of chunk names.
			 */
			public function call_register_chunk_translations( $chunks ) {
				$this->register_chunk_translations( $chunks );
			}
		};
	}

	/**
	 * @testdox Should not register chunk scripts when the block has no front-end script handle.
	 */
	public function test_register_chunk_translations_skips_blocks_without_script_handle(): void {
		$asset_api = $this->createMock( Api::class );
		$asset_api->expects( $this->never() )->method( 'register_script' );

		$block = $this->create_block( $asset_api, false );

		$block->call_register_chunk_translations( array( 'test-chunk' ) );
	}

	/**
	 * @testdox Should register chunk scripts when the block has a front-end script handle.
	 */
	public function test_register_chunk_translations_registers_chunks_for_blocks_with_script_handle(): void {
		$asset_api = $this->createMock( Api::class );
		$asset_api->method( 'get_block_asset_build_path' )->willReturnCallback(
			function ( $filename ) {
				return "assets/client/blocks/{$filename}.js";
			}
		);
		$asset_api->expects( $this->once() )
			->method( 'register_script' )
			->with( 'wc-blocks-test-chunk-chunk', 'assets/client/blocks/test-chunk.js', array(), true );

		$block = $this->create_block( $asset_api, true );

		$block->call_register_chunk_translations( array( 'test-chunk' ) );
	}
}
