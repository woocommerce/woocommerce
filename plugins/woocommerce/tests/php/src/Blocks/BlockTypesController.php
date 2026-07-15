<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks;

use Automattic\WooCommerce\Blocks\Assets\Api;
use Automattic\WooCommerce\Blocks\BlockTypesController as TestedBlockTypesController;
use Automattic\WooCommerce\Tests\Blocks\Mocks\AssetDataRegistryMock;
use Automattic\WooCommerce\Blocks\Package;

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
	 * Block types registered during a test.
	 *
	 * @var string[]
	 */
	private $registered_test_block_types = array();

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
			new AssetDataRegistryMock( Package::container()->get( API::class ) )
		);
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		foreach ( $this->registered_test_block_types as $block_type ) {
			if ( \WP_Block_Type_Registry::get_instance()->is_registered( $block_type ) ) {
				unregister_block_type( $block_type );
			}
		}

		$this->registered_test_block_types = array();

		remove_filter( 'allowed_block_types_all', array( $this->block_types_controller, 'filter_allowed_block_types' ), 10 );

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
	 * @testdox Should hide post-editor WooCommerce blocks from unrestricted post editors.
	 */
	public function test_edit_post_context_hides_post_editor_block_types_from_unrestricted_list(): void {
		foreach (
			array(
				'core/test-paragraph',
				'third-party/test-block',
				'woocommerce/product-search',
				'woocommerce/breadcrumbs',
				'woocommerce/product-reviews',
				'woocommerce/order-confirmation-status',
			) as $block_type
		) {
			$this->register_test_block_type( $block_type );
		}

		$result = $this->block_types_controller->filter_allowed_block_types(
			true,
			$this->get_block_editor_context( 'core/edit-post' )
		);

		$this->assertContains( 'core/test-paragraph', $result, 'Non-WooCommerce blocks should remain available.' );
		$this->assertContains( 'third-party/test-block', $result, 'Third-party blocks should remain available.' );
		$this->assertContains( 'woocommerce/product-search', $result, 'WooCommerce blocks outside the denylist should remain available.' );
		$this->assertNotContains( 'woocommerce/breadcrumbs', $result, 'Store Breadcrumbs should be hidden in post editors.' );
		$this->assertNotContains( 'woocommerce/product-reviews', $result, 'Product Reviews should be hidden in post editors.' );
		$this->assertNotContains( 'woocommerce/order-confirmation-status', $result, 'Order Confirmation blocks should be hidden in post editors.' );
	}

	/**
	 * @testdox Should keep only widget-area WooCommerce blocks in widget editors.
	 *
	 * @dataProvider widget_editor_context_provider
	 *
	 * @param string $editor_context_name Editor context name.
	 */
	public function test_widget_context_hides_woocommerce_blocks_not_allowed_in_widget_areas( string $editor_context_name ): void {
		foreach (
			array(
				'core/test-paragraph',
				'third-party/test-block',
				'woocommerce/product-search',
				'woocommerce/product-filters',
				'woocommerce/checkout',
				'woocommerce/order-confirmation-status',
			) as $block_type
		) {
			$this->register_test_block_type( $block_type );
		}

		$result = $this->block_types_controller->filter_allowed_block_types(
			true,
			$this->get_block_editor_context( $editor_context_name )
		);

		$this->assertContains( 'core/test-paragraph', $result, 'Non-WooCommerce blocks should remain available.' );
		$this->assertContains( 'third-party/test-block', $result, 'Third-party blocks should remain available.' );
		$this->assertContains( 'woocommerce/product-search', $result, 'Widget-area WooCommerce blocks should remain available.' );
		$this->assertContains( 'woocommerce/product-filters', $result, 'Widget-area WooCommerce blocks should remain available.' );
		$this->assertNotContains( 'woocommerce/checkout', $result, 'WooCommerce blocks outside the widget allowlist should be hidden.' );
		$this->assertNotContains( 'woocommerce/order-confirmation-status', $result, 'WooCommerce blocks outside the widget allowlist should be hidden.' );
	}

	/**
	 * @testdox Should leave Site Editor block availability unchanged.
	 */
	public function test_site_editor_context_leaves_allowed_block_types_unchanged(): void {
		$allowed_block_types = array(
			'core/test-paragraph',
			'woocommerce/breadcrumbs',
			'woocommerce/checkout',
		);

		$result = $this->block_types_controller->filter_allowed_block_types(
			$allowed_block_types,
			$this->get_block_editor_context( 'core/edit-site' )
		);

		$this->assertSame( $allowed_block_types, $result, 'Site Editor block availability should not be changed.' );
	}

	/**
	 * @testdox Should preserve an existing post editor allowlist while removing denied WooCommerce blocks.
	 */
	public function test_edit_post_context_preserves_existing_allowlist(): void {
		$allowed_block_types = array(
			'core/test-paragraph',
			'woocommerce/product-search',
			'woocommerce/catalog-sorting',
			'third-party/test-block',
		);

		$result = $this->block_types_controller->filter_allowed_block_types(
			$allowed_block_types,
			$this->get_block_editor_context( 'core/edit-post' )
		);

		$this->assertSame(
			array(
				'core/test-paragraph',
				'woocommerce/product-search',
				'third-party/test-block',
			),
			$result,
			'Existing allowlists should keep their original restrictions.'
		);
	}

	/**
	 * @testdox Should preserve an existing widget editor allowlist while removing disallowed WooCommerce blocks.
	 */
	public function test_widget_context_preserves_existing_allowlist(): void {
		$allowed_block_types = array(
			'core/test-paragraph',
			'woocommerce/product-search',
			'woocommerce/checkout',
			'third-party/test-block',
		);

		$result = $this->block_types_controller->filter_allowed_block_types(
			$allowed_block_types,
			$this->get_block_editor_context( 'core/edit-widgets' )
		);

		$this->assertSame(
			array(
				'core/test-paragraph',
				'woocommerce/product-search',
				'third-party/test-block',
			),
			$result,
			'Existing allowlists should keep their original restrictions.'
		);
	}

	/**
	 * @testdox Should preserve false block availability for restricted editors.
	 *
	 * @dataProvider restricted_editor_context_provider
	 *
	 * @param string $editor_context_name Editor context name.
	 */
	public function test_restricted_context_preserves_false_allowed_block_types( string $editor_context_name ): void {
		$result = $this->block_types_controller->filter_allowed_block_types(
			false,
			$this->get_block_editor_context( $editor_context_name )
		);

		$this->assertFalse( $result, 'Existing disabled block availability should be preserved.' );
	}

	/**
	 * Data provider for widget editor contexts.
	 *
	 * @return array<string, array<string>>
	 */
	public function widget_editor_context_provider(): array {
		return array(
			'widgets editor'    => array( 'core/edit-widgets' ),
			'customizer editor' => array( 'core/customize-widgets' ),
		);
	}

	/**
	 * Data provider for restricted editor contexts.
	 *
	 * @return array<string, array<string>>
	 */
	public function restricted_editor_context_provider(): array {
		return array(
			'post editor'       => array( 'core/edit-post' ),
			'widgets editor'    => array( 'core/edit-widgets' ),
			'customizer editor' => array( 'core/customize-widgets' ),
		);
	}

	/**
	 * Register a block type and track it for cleanup.
	 *
	 * @param string $block_type Block type slug.
	 */
	private function register_test_block_type( string $block_type ): void {
		if ( \WP_Block_Type_Registry::get_instance()->is_registered( $block_type ) ) {
			return;
		}

		register_block_type( $block_type );

		$this->registered_test_block_types[] = $block_type;
	}

	/**
	 * Get a block editor context for tests.
	 *
	 * @param string $editor_context_name Editor context name.
	 * @return \WP_Block_Editor_Context Block editor context.
	 */
	private function get_block_editor_context( string $editor_context_name ): \WP_Block_Editor_Context {
		return new \WP_Block_Editor_Context(
			array(
				'name' => $editor_context_name,
			)
		);
	}
}
