<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\BlockTypes;

use Automattic\WooCommerce\Blocks\Assets\Api;
use Automattic\WooCommerce\Blocks\Assets\AssetDataRegistry;
use Automattic\WooCommerce\Blocks\BlockTypes\AbstractBlock;
use Automattic\WooCommerce\Blocks\Integrations\IntegrationRegistry;
use Automattic\WooCommerce\Blocks\Package;
use WC_Unit_Test_Case;
use WP_Block_Type_Registry;

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

	/**
	 * Create a testable subclass that exposes register_block_type_from_metadata for testing.
	 *
	 * @return AbstractBlock
	 */
	private function create_registrable_block(): AbstractBlock {
		return new class( Package::container()->get( Api::class ), Package::container()->get( AssetDataRegistry::class ), new IntegrationRegistry() ) extends AbstractBlock {
			protected $block_name = 'mini-cart'; // phpcs:ignore Squiz.Commenting

			protected function register_block_type() {} // phpcs:ignore Squiz.Commenting.FunctionComment.Missing

			public function proxy_register_block_type_from_metadata( string $manifest_file, array $block_settings = array() ) { // phpcs:ignore Squiz.Commenting.FunctionComment.Missing
				return $this->register_block_type_from_metadata( $manifest_file, $block_settings );
			}
		};
	}

	/**
	 * @testdox Fast path should register the block, fire both metadata filters, and merge settings.
	 */
	public function test_fast_path_registers_block_fires_filters_and_merges_settings(): void {
		// The test bootstrap copies individual block.json files but removes blocks-json.php (the manifest).
		// Re-register it so the fast path can resolve metadata from WP_Block_Metadata_Registry.
		$meta_file = WC_ABSPATH . 'assets/client/blocks/blocks-json.php';
		if ( file_exists( $meta_file ) ) {
			wp_register_block_metadata_collection( WC_ABSPATH . 'assets/client/blocks/', $meta_file );
		}

		$registry = WP_Block_Type_Registry::get_instance();
		$original = $registry->is_registered( 'woocommerce/mini-cart' ) ? $registry->get_registered( 'woocommerce/mini-cart' ) : null;
		if ( $original ) {
			$registry->unregister( 'woocommerce/mini-cart' );
		}

		// Inject a custom description via block_type_metadata, and a custom attribute via block_type_metadata_settings.
		$metadata_filter = static fn( $metadata ) => 'woocommerce/mini-cart' === $metadata['name']
			? array_merge( $metadata, array( 'description' => 'injected-by-metadata-filter' ) ) : $metadata;
		$settings_filter = static fn( $settings ) => 'woocommerce/mini-cart' === $settings['name']
			? array_merge( $settings, array( 'attributes' => array_merge( $settings['attributes'], array( 'test_attr' => array( 'type' => 'string' ) ) ) ) ) : $settings;

		add_filter( 'block_type_metadata', $metadata_filter );
		add_filter( 'block_type_metadata_settings', $settings_filter );
		try {
			$result = $this->create_registrable_block()->proxy_register_block_type_from_metadata(
				Package::container()->get( Api::class )->generate_block_metadata_path( 'mini-cart' ),
				array( 'render_callback' => '__return_empty_string' )
			);
		} finally {
			remove_filter( 'block_type_metadata', $metadata_filter );
			remove_filter( 'block_type_metadata_settings', $settings_filter );
			if ( $registry->is_registered( 'woocommerce/mini-cart' ) ) {
				$registry->unregister( 'woocommerce/mini-cart' );
			}
			if ( $original ) {
				$registry->register( $original );
			}
		}

		$this->assertNotFalse( $result );
		$this->assertSame( 'woocommerce/mini-cart', $result->name );
		$this->assertSame( 'injected-by-metadata-filter', $result->description, 'block_type_metadata filter should inject into the registered block.' );
		$this->assertSame( array( 'type' => 'string' ), $result->attributes['test_attr'], 'block_type_metadata_settings filter should inject into the registered block.' );
		$this->assertSame( '__return_empty_string', $result->render_callback, 'Caller-provided render_callback should be merged.' );
	}

	/**
	 * @testdox Fast path should fall back to the upstream function when metadata is not in the registry.
	 */
	public function test_fast_path_falls_back_for_missing_metadata(): void {
		$result = $this->create_registrable_block()->proxy_register_block_type_from_metadata(
			'/nonexistent/path/block.json',
			array( 'render_callback' => '__return_empty_string' )
		);
		$this->assertFalse( $result );
	}
}
