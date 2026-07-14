<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\BlockTypes;

use Automattic\WooCommerce\Blocks\Assets\Api;
use Automattic\WooCommerce\Blocks\Assets\AssetDataRegistry;
use Automattic\WooCommerce\Blocks\BlockTypes\AbstractBlock;
use Automattic\WooCommerce\Blocks\Integrations\IntegrationRegistry;
use Automattic\WooCommerce\Blocks\Package;
use Automattic\WooCommerce\Internal\Features\BlockEditorUnifiedAssets;
use WC_Unit_Test_Case;

/**
 * Tests for the AbstractBlock class.
 */
class AbstractBlockTest extends WC_Unit_Test_Case {

	/**
	 * Set up the feature option.
	 */
	public function setUp(): void {
		parent::setUp();
		delete_option( BlockEditorUnifiedAssets::OPTION_NAME );
	}

	/**
	 * Clean up the feature option.
	 */
	public function tearDown(): void {
		delete_option( BlockEditorUnifiedAssets::OPTION_NAME );
		parent::tearDown();
	}

	/**
	 * Create a testable AbstractBlock instance with a mocked asset API.
	 *
	 * @param Api                      $asset_api           Mocked asset API.
	 * @param bool                     $with_script_handle  Whether the block has a front-end script handle.
	 * @param IntegrationRegistry|null $integration_registry Optional integration registry.
	 * @return AbstractBlock
	 */
	private function create_block( Api $asset_api, bool $with_script_handle, ?IntegrationRegistry $integration_registry = null ): AbstractBlock {
		$asset_data_registry = Package::container()->get( AssetDataRegistry::class );

		return new class( $asset_api, $asset_data_registry, $integration_registry ?? new IntegrationRegistry(), 'test-block', $with_script_handle ) extends AbstractBlock {
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

			/**
			 * Get the editor script data for tests.
			 *
			 * @return array Editor script data.
			 */
			public function get_editor_script_for_test(): array {
				return $this->get_block_type_editor_script();
			}

			/**
			 * Get the editor style for tests.
			 *
			 * @return string Editor style handle.
			 */
			public function get_editor_style_for_test(): string {
				return $this->get_block_type_editor_style();
			}

			/**
			 * Register block assets for tests.
			 */
			public function register_block_type_assets_for_test(): void {
				$this->register_block_type_assets();
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

	/**
	 * @testdox Should use per-block editor assets when unified assets are disabled.
	 */
	public function test_uses_legacy_editor_assets_by_default(): void {
		$asset_api = $this->createMock( Api::class );
		$asset_api->method( 'get_block_asset_build_path' )->willReturnCallback(
			function ( $filename ) {
				return "assets/client/blocks/{$filename}.js";
			}
		);

		$block = $this->create_block( $asset_api, false );

		$this->assertSame( 'wc-test-block-block', $block->get_editor_script_for_test()['handle'] );
		$this->assertSame( array( 'wc-blocks' ), $block->get_editor_script_for_test()['dependencies'] );
		$this->assertSame( 'wc-blocks-editor-style', $block->get_editor_style_for_test() );
	}

	/**
	 * @testdox Should use unified editor assets when the feature is enabled.
	 */
	public function test_uses_unified_editor_assets_when_enabled(): void {
		update_option( BlockEditorUnifiedAssets::OPTION_NAME, 'yes' );
		$asset_api = $this->createMock( Api::class );
		$asset_api->method( 'get_block_asset_build_path' )->willReturnCallback(
			function ( $filename ) {
				return "assets/client/blocks/{$filename}.js";
			}
		);

		$block = $this->create_block( $asset_api, false );

		$this->assertSame( 'wc-block-library', $block->get_editor_script_for_test()['handle'] );
		$this->assertSame( array(), $block->get_editor_script_for_test()['dependencies'] );
		$this->assertSame( 'wc-block-library-style', $block->get_editor_style_for_test() );
	}

	/**
	 * @testdox Should add integration dependencies to legacy per-block editor handles.
	 */
	public function test_adds_integration_dependencies_to_legacy_editor_handle(): void {
		wp_deregister_script( 'wc-test-block-block' );

		$asset_api = $this->createMock( Api::class );
		$asset_api->method( 'get_block_asset_build_path' )->willReturnCallback(
			function ( $filename ) {
				return "assets/client/blocks/{$filename}.js";
			}
		);
		$asset_api->method( 'get_script_data' )->willReturn( array( 'dependencies' => array() ) );
		$asset_api->expects( $this->once() )
			->method( 'register_script' )
			->with(
				'wc-test-block-block',
				'assets/client/blocks/test-block.js',
				array( 'wc-blocks', 'integration-script' ),
				false
			);

		$integration_registry = $this->createMock( IntegrationRegistry::class );
		$integration_registry->method( 'get_all_registered_editor_script_handles' )->willReturn( array( 'integration-script' ) );

		$this->create_block( $asset_api, false, $integration_registry )->register_block_type_assets_for_test();
	}

	/**
	 * @testdox Should merge unified integration dependencies without duplicates.
	 */
	public function test_merges_unified_integration_dependencies_without_duplicates(): void {
		update_option( BlockEditorUnifiedAssets::OPTION_NAME, 'yes' );
		wp_deregister_script( 'wc-block-library' );
		wp_register_script( 'wc-block-library', '', array( 'existing-script', 'integration-script' ), 'test', true );

		$asset_api = $this->createMock( Api::class );
		$asset_api->method( 'get_block_asset_build_path' )->willReturnCallback(
			function ( $filename ) {
				return "assets/client/blocks/{$filename}.js";
			}
		);
		$asset_api->expects( $this->never() )->method( 'register_script' );

		$integration_registry = $this->createMock( IntegrationRegistry::class );
		$integration_registry->method( 'get_all_registered_editor_script_handles' )->willReturn( array( 'integration-script', 'new-integration-script' ) );

		$this->create_block( $asset_api, false, $integration_registry )->register_block_type_assets_for_test();

		$this->assertSame(
			array( 'existing-script', 'integration-script', 'new-integration-script' ),
			wp_scripts()->registered['wc-block-library']->deps
		);

		wp_deregister_script( 'wc-block-library' );
	}
}
