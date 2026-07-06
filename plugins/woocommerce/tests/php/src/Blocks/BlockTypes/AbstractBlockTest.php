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
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		wp_deregister_script( 'wc-blocks-test-chunk-chunk' );
		wp_deregister_script( 'wc-test-block-block-frontend' );
		parent::tearDown();
	}

	/**
	 * Create a testable AbstractBlock instance.
	 *
	 * @param bool $with_script_handle Whether the block has a front-end script handle.
	 * @return AbstractBlock
	 */
	private function create_block( bool $with_script_handle ): AbstractBlock {
		$asset_api           = Package::container()->get( Api::class );
		$asset_data_registry = Package::container()->get( AssetDataRegistry::class );

		if ( $with_script_handle ) {
			return new class( $asset_api, $asset_data_registry, new IntegrationRegistry(), 'test-block' ) extends AbstractBlock {
				/**
				 * Skip block registration in tests.
				 */
				protected function register_block_type() {}

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

		return new class( $asset_api, $asset_data_registry, new IntegrationRegistry(), 'test-block' ) extends AbstractBlock {
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
				return null;
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
		$block = $this->create_block( false );

		$block->call_register_chunk_translations( array( 'test-chunk' ) );

		$this->assertFalse(
			wp_script_is( 'wc-blocks-test-chunk-chunk', 'registered' ),
			'Chunk script should not be registered when the block has no front-end script handle'
		);
	}

	/**
	 * @testdox Should register chunk scripts and attach translations when the block has a front-end script handle.
	 */
	public function test_register_chunk_translations_registers_chunks_for_blocks_with_script_handle(): void {
		$block = $this->create_block( true );
		wp_register_script( 'wc-test-block-block-frontend', 'https://example.com/test-block-frontend.js', array(), '1.0', true );

		$block->call_register_chunk_translations( array( 'test-chunk' ) );

		$this->assertTrue(
			wp_script_is( 'wc-blocks-test-chunk-chunk', 'registered' ),
			'Chunk script should be registered when the block has a front-end script handle'
		);
		$this->assertNotEmpty(
			wp_scripts()->get_data( 'wc-test-block-block-frontend', 'before' ),
			'Translations should be attached as an inline script on the block front-end script handle'
		);
	}
}
