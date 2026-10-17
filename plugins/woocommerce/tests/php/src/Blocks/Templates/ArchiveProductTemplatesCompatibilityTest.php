<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Blocks\Templates;

use Automattic\WooCommerce\Blocks\Templates\ArchiveProductTemplatesCompatibility;
use Automattic\WooCommerce\Blocks\Templates\ProductCatalogTemplate;
use Automattic\WooCommerce\Blocks\Templates\ProductCategoryTemplate;
use Automattic\WooCommerce\Blocks\Templates\ProductSearchResultsTemplate;
use Automattic\WooCommerce\Blocks\Templates\ProductTagTemplate;
use Automattic\WooCommerce\Blocks\Templates\SingleProductTemplateCompatibility;
use WC_Unit_Test_Case;

/**
 * Tests for the archive Product Collection hook compatibility layer.
 */
class ArchiveProductTemplatesCompatibilityTest extends WC_Unit_Test_Case {

	/**
	 * Query globals captured before each test.
	 *
	 * @var array<string, array{exists: bool, value: mixed}>
	 */
	private $query_snapshots = array();

	/**
	 * Shop page option captured before each test.
	 *
	 * @var mixed
	 */
	private $original_shop_page_id;

	/**
	 * Shop page created for the archive request.
	 *
	 * @var int
	 */
	private $shop_page_id = 0;

	/**
	 * Product post type `has_archive` value captured before each test.
	 *
	 * @var string|bool
	 */
	private $original_product_has_archive;

	/**
	 * @inheritdoc
	 */
	public function setUp(): void {
		parent::setUp();
		$this->original_shop_page_id        = get_option( 'woocommerce_shop_page_id' );
		$this->original_product_has_archive = get_post_type_object( 'product' )->has_archive;

		foreach ( array( 'wp_query', 'wp_the_query' ) as $global_name ) {
			$this->query_snapshots[ $global_name ] = array(
				'exists' => array_key_exists( $global_name, $GLOBALS ),
				'value'  => $GLOBALS[ $global_name ] ?? null,
			);
		}
	}

	/**
	 * @inheritdoc
	 */
	public function tearDown(): void {
		foreach ( $this->query_snapshots as $global_name => $snapshot ) {
			if ( $snapshot['exists'] ) {
				$GLOBALS[ $global_name ] = $snapshot['value']; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Restore the exact pre-test query global.
			} else {
				unset( $GLOBALS[ $global_name ] ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Restore prior absence of the query global.
			}
		}

		get_post_type_object( 'product' )->has_archive = $this->original_product_has_archive;

		if ( false === $this->original_shop_page_id ) {
			delete_option( 'woocommerce_shop_page_id' );
		} else {
			update_option( 'woocommerce_shop_page_id', $this->original_shop_page_id );
		}
		if ( $this->shop_page_id ) {
			wp_delete_post( $this->shop_page_id, true );
			$this->shop_page_id = 0;
		}

		parent::tearDown();
	}

	/**
	 * @testdox Injects $hook_name $position the inherited $block_name block
	 * @dataProvider supported_hook_provider
	 *
	 * @param string $hook_name Hook to exercise.
	 * @param string $block_name Target block name.
	 * @param string $position Expected marker position relative to the block.
	 */
	public function test_injects_supported_hook_at_declared_position( string $hook_name, string $block_name, string $position ): void {
		$sut    = $this->initialize_archive_compatibility();
		$target = $this->get_marked_target_block( $sut, $block_name );

		add_action(
			$hook_name,
			static function () {
				echo esc_html( '__HOOK_MARKER__' );
			}
		);

		$rendered = $sut->inject_hooks( '__BLOCK_SENTINEL__', $target );

		$this->assertSame( 1, substr_count( $rendered, '__HOOK_MARKER__' ), 'The selected public hook should render exactly once.' );
		$this->assertSame( 1, substr_count( $rendered, '__BLOCK_SENTINEL__' ), 'The inherited block sentinel should render exactly once.' );

		$marker_position = strpos( $rendered, '__HOOK_MARKER__' );
		$block_position  = strpos( $rendered, '__BLOCK_SENTINEL__' );
		$this->assertIsInt( $marker_position );
		$this->assertIsInt( $block_position );

		if ( 'before' === $position ) {
			$this->assertLessThan( $block_position, $marker_position, "{$hook_name} should render before {$block_name}." );
		} else {
			$this->assertGreaterThan( $block_position, $marker_position, "{$hook_name} should render after {$block_name}." );
		}
	}

	/**
	 * Supported public hook and block positions.
	 *
	 * @return array<string, array{string, string, string}>
	 */
	public function supported_hook_provider(): array {
		return array(
			'before main content'         => array( 'woocommerce_before_main_content', 'woocommerce/product-collection', 'before' ),
			'after main content'          => array( 'woocommerce_after_main_content', 'woocommerce/product-collection', 'after' ),
			'before shop loop'            => array( 'woocommerce_before_shop_loop', 'woocommerce/product-template', 'before' ),
			'after shop loop'             => array( 'woocommerce_after_shop_loop', 'woocommerce/product-template', 'after' ),
			'before shop loop item title' => array( 'woocommerce_before_shop_loop_item_title', 'core/post-title', 'before' ),
			'shop loop item title'        => array( 'woocommerce_shop_loop_item_title', 'core/post-title', 'after' ),
			'after shop loop item title'  => array( 'woocommerce_after_shop_loop_item_title', 'core/post-title', 'after' ),
			'before shop loop item'       => array( 'woocommerce_before_shop_loop_item', 'core/null', 'before' ),
			'after shop loop item'        => array( 'woocommerce_after_shop_loop_item', 'core/null', 'after' ),
		);
	}

	/**
	 * @testdox Marks an inherited Product Collection and all descendants
	 */
	public function test_marks_inherited_product_collection_tree(): void {
		$sut  = $this->initialize_archive_compatibility();
		$tree = array(
			'blockName'   => 'woocommerce/product-collection',
			'attrs'       => array( 'query' => array( 'inherit' => true ) ),
			'innerBlocks' => array(
				array(
					'blockName'   => 'woocommerce/product-template',
					'attrs'       => array(),
					'innerBlocks' => array(
						array(
							'blockName'   => 'core/null',
							'attrs'       => array(),
							'innerBlocks' => array(
								array(
									'blockName'   => 'core/post-title',
									'attrs'       => array(),
									'innerBlocks' => array(),
								),
							),
						),
					),
				),
			),
		);

		$marked = $sut->update_render_block_data( $tree, $tree, null );

		$this->assertSame( 1, $marked['attrs']['isInherited'] );
		$this->assertSame( 1, $marked['innerBlocks'][0]['attrs']['isInherited'] );
		$this->assertSame( 1, $marked['innerBlocks'][0]['innerBlocks'][0]['attrs']['isInherited'] );
		$this->assertSame( 1, $marked['innerBlocks'][0]['innerBlocks'][0]['innerBlocks'][0]['attrs']['isInherited'] );
	}

	/**
	 * @testdox Skips blocks outside the supported inherited archive contexts
	 */
	public function test_skips_unsupported_context(): void {
		$sut = $this->initialize_archive_compatibility();
		add_action(
			'woocommerce_before_main_content',
			static function () {
				echo esc_html( '__HOOK_MARKER__' );
			}
		);
		add_action(
			'woocommerce_before_shop_loop',
			static function () {
				echo esc_html( '__HOOK_MARKER__' );
			}
		);

		$this->go_to( home_url( '/' ) );
		$GLOBALS['wp_the_query'] = $GLOBALS['wp_query']; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Keep the conditional query globals aligned for the non-archive control.

		$non_archive = array(
			'blockName'   => 'woocommerce/product-collection',
			'attrs'       => array( 'query' => array( 'inherit' => true ) ),
			'innerBlocks' => array(),
		);
		$this->assertSame( $non_archive, $sut->update_render_block_data( $non_archive, $non_archive, null ) );
		$this->assertSame( '__BLOCK_SENTINEL__', $sut->inject_hooks( '__BLOCK_SENTINEL__', $non_archive ) );

		$this->establish_shop_query();
		$supported_not_inherited = array(
			'blockName' => 'woocommerce/product-collection',
			'attrs'     => array(),
		);
		$this->assertSame( '__BLOCK_SENTINEL__', $sut->inject_hooks( '__BLOCK_SENTINEL__', $supported_not_inherited ) );

		$unsupported_inherited = array(
			'blockName' => 'core/paragraph',
			'attrs'     => array( 'isInherited' => 1 ),
		);
		$this->assertSame( '__BLOCK_SENTINEL__', $sut->inject_hooks( '__BLOCK_SENTINEL__', $unsupported_inherited ) );

		$empty_product_template = array(
			'blockName' => 'woocommerce/product-template',
			'attrs'     => array( 'isInherited' => 1 ),
		);
		$this->assertSame( '', $sut->inject_hooks( '', $empty_product_template ) );
	}

	/**
	 * @testdox Runs an injected loop hook once when several archive templates match the request.
	 * @dataProvider overlapping_archive_templates_provider
	 *
	 * @param string   $taxonomy         Product taxonomy filtered in the request.
	 * @param array    $query_args       Query arguments added to the request besides the taxonomy term.
	 * @param string[] $template_classes Templates whose conditions all match the request.
	 */
	public function test_runs_loop_hook_once_when_archive_templates_overlap( string $taxonomy, array $query_args, array $template_classes ): void {
		// The test theme doesn't declare WooCommerce support, so products are registered without an archive.
		get_post_type_object( 'product' )->has_archive = 'shop';

		$term = self::factory()->term->create_and_get(
			array(
				'taxonomy' => $taxonomy,
				'slug'     => 'compatibility-overlap',
			)
		);
		$this->go_to( add_query_arg( array_merge( $query_args, array( $taxonomy => $term->slug ) ), home_url( '/' ) ) );
		$GLOBALS['wp_the_query'] = $GLOBALS['wp_query']; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Product archive conditionals read the main query global.
		$this->assertTrue( is_post_type_archive( 'product' ) && is_tax( $taxonomy ), 'The request should match both the product archive and the taxonomy archive.' );

		foreach ( $template_classes as $template_class ) {
			( new $template_class() )->render_block_template();
		}
		// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment -- WordPress owns this hook; the test applies the real callbacks registered by the templates.
		apply_filters( 'template_include', 'index.php' );

		$hook_calls = 0;
		add_action(
			'woocommerce_before_shop_loop',
			static function () use ( &$hook_calls ) {
				++$hook_calls;
				echo esc_html( '__HOOK_MARKER__' );
			}
		);

		$tree = array(
			'blockName'   => 'woocommerce/product-collection',
			'attrs'       => array( 'query' => array( 'inherit' => true ) ),
			'innerBlocks' => array(
				array(
					'blockName'   => 'woocommerce/product-template',
					'attrs'       => array(),
					'innerBlocks' => array(),
				),
			),
		);
		// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment -- WordPress owns this hook; the test applies the real callbacks registered by the templates.
		$marked = apply_filters( 'render_block_data', $tree, $tree, null );
		// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment -- WordPress owns this hook; the test applies the real callbacks registered by the templates.
		$rendered = apply_filters( 'render_block', '__BLOCK_SENTINEL__', $marked['innerBlocks'][0], new \WP_Block( $marked['innerBlocks'][0] ) );

		$this->assertSame( 1, $hook_calls, 'woocommerce_before_shop_loop should run once however many templates match the request.' );
		$this->assertSame( 1, substr_count( $rendered, '__HOOK_MARKER__' ), 'The hook output should be injected once.' );
		$this->assertSame( 1, substr_count( $rendered, '__BLOCK_SENTINEL__' ), 'The block content should be rendered once.' );
		$this->assertLessThan( strpos( $rendered, '__BLOCK_SENTINEL__' ), strpos( $rendered, '__HOOK_MARKER__' ), 'woocommerce_before_shop_loop should render before the Product Template, as declared in the hook data.' );
	}

	/**
	 * Requests where more than one archive template matches.
	 *
	 * @return array<string, array{string, array<string, string>, string[]}>
	 */
	public function overlapping_archive_templates_provider(): array {
		return array(
			'Shop filtered by product tag'          => array( 'product_tag', array( 'post_type' => 'product' ), array( ProductCatalogTemplate::class, ProductTagTemplate::class ) ),
			'Shop filtered by product category'     => array( 'product_cat', array( 'post_type' => 'product' ), array( ProductCatalogTemplate::class, ProductCategoryTemplate::class ) ),
			'Product search filtered by a category' => array(
				'product_cat',
				array(
					's'         => 'compatibility',
					'post_type' => 'product',
				),
				array( ProductSearchResultsTemplate::class, ProductCategoryTemplate::class ),
			),
		);
	}

	/**
	 * @testdox Registers the hooks once per compatibility class, and again after the hooks are reset.
	 */
	public function test_registers_template_include_once_per_compatibility_class(): void {
		$initial_count = $this->count_template_include_callbacks();

		( new ArchiveProductTemplatesCompatibility() )->init();
		( new ArchiveProductTemplatesCompatibility() )->init();
		$this->assertSame( $initial_count + 1, $this->count_template_include_callbacks(), 'A second archive layer in the same request should not register again.' );

		( new SingleProductTemplateCompatibility() )->init();
		$this->assertSame( $initial_count + 2, $this->count_template_include_callbacks(), 'The single product layer should keep its own registration.' );

		remove_all_filters( 'template_include' );
		( new ArchiveProductTemplatesCompatibility() )->init();
		$this->assertSame( 1, $this->count_template_include_callbacks(), 'After the hooks are reset, the archive layer should register again.' );
	}

	/**
	 * Counts the callbacks registered on `template_include` at the default priority.
	 */
	private function count_template_include_callbacks(): int {
		global $wp_filter;

		return isset( $wp_filter['template_include'] ) ? count( $wp_filter['template_include']->callbacks[10] ?? array() ) : 0;
	}

	/**
	 * Initializes the real compatibility layer in a Shop request.
	 */
	private function initialize_archive_compatibility(): ArchiveProductTemplatesCompatibility {
		$this->establish_shop_query();
		$sut = new ArchiveProductTemplatesCompatibility();
		$sut->init();
		// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment -- WordPress owns this hook; the test applies the real callback registered by init().
		apply_filters( 'template_include', 'index.php' );

		return $sut;
	}

	/**
	 * Establishes and verifies the real Shop main query.
	 */
	private function establish_shop_query(): void {
		if ( ! $this->shop_page_id ) {
			$this->shop_page_id = self::factory()->post->create(
				array(
					'post_type'   => 'page',
					'post_status' => 'publish',
					'post_title'  => 'Compatibility Layer Shop',
					'post_name'   => 'compatibility-layer-shop',
				)
			);
			update_option( 'woocommerce_shop_page_id', $this->shop_page_id );
		}

		$this->go_to( get_permalink( $this->shop_page_id ) );
		$GLOBALS['wp_the_query'] = $GLOBALS['wp_query']; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Product archive conditionals read the main query global.
		$this->assertTrue( is_shop(), 'The compatibility contract must run under a real Shop main query.' );
	}

	/**
	 * Marks an inherited tree and returns the requested target block.
	 *
	 * @param ArchiveProductTemplatesCompatibility $sut        Compatibility layer.
	 * @param string                               $block_name Target block name.
	 * @return array<string, mixed>
	 */
	private function get_marked_target_block( ArchiveProductTemplatesCompatibility $sut, string $block_name ): array {
		$target = array(
			'blockName'   => $block_name,
			'attrs'       => array(),
			'innerBlocks' => array(),
		);

		if ( 'woocommerce/product-collection' === $block_name ) {
			$tree                   = $target;
			$tree['attrs']['query'] = array( 'inherit' => true );
		} else {
			$tree = array(
				'blockName'   => 'woocommerce/product-collection',
				'attrs'       => array( 'query' => array( 'inherit' => true ) ),
				'innerBlocks' => array( $target ),
			);
		}

		$marked = $sut->update_render_block_data( $tree, $tree, null );

		return 'woocommerce/product-collection' === $block_name ? $marked : $marked['innerBlocks'][0];
	}
}
