<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Blocks\Templates;

use Automattic\WooCommerce\Blocks\Templates\ArchiveProductTemplatesCompatibility;
use WC_Unit_Test_Case;
use WP_Hook;

/**
 * Tests for the archive Product Collection hook compatibility layer.
 */
class ArchiveProductTemplatesCompatibilityTest extends WC_Unit_Test_Case {

	/**
	 * Hooks whose state the compatibility layer can change.
	 *
	 * @var string[]
	 */
	private const COMPATIBILITY_HOOKS = array(
		'woocommerce_before_main_content',
		'woocommerce_after_main_content',
		'woocommerce_before_shop_loop_item_title',
		'woocommerce_shop_loop_item_title',
		'woocommerce_after_shop_loop_item_title',
		'woocommerce_before_shop_loop_item',
		'woocommerce_after_shop_loop_item',
		'woocommerce_before_shop_loop',
		'woocommerce_after_shop_loop',
		'woocommerce_no_products_found',
		'woocommerce_archive_description',
		'template_include',
		'render_block_data',
		'render_block',
		'woocommerce_disable_compatibility_layer',
	);

	/**
	 * Hook stacks captured before each test.
	 *
	 * @var array<string, WP_Hook|null>
	 */
	private $hook_snapshots = array();

	/**
	 * Action counts captured before each test.
	 *
	 * @var array<string, int|null>
	 */
	private $action_snapshots = array();

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
	 * @inheritdoc
	 */
	public function setUp(): void {
		parent::setUp();
		$this->original_shop_page_id = get_option( 'woocommerce_shop_page_id' );

		foreach ( self::COMPATIBILITY_HOOKS as $hook_name ) {
			$this->hook_snapshots[ $hook_name ] = isset( $GLOBALS['wp_filter'][ $hook_name ] ) && $GLOBALS['wp_filter'][ $hook_name ] instanceof WP_Hook
				? clone $GLOBALS['wp_filter'][ $hook_name ]
				: null;
		}

		foreach ( array_slice( self::COMPATIBILITY_HOOKS, 0, 11 ) as $hook_name ) {
			$this->action_snapshots[ $hook_name ] = array_key_exists( $hook_name, $GLOBALS['wp_actions'] ) ? $GLOBALS['wp_actions'][ $hook_name ] : null;
		}

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
		foreach ( $this->hook_snapshots as $hook_name => $snapshot ) {
			if ( $snapshot instanceof WP_Hook ) {
				$GLOBALS['wp_filter'][ $hook_name ] = clone $snapshot; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Restore the exact pre-test hook stack.
			} else {
				unset( $GLOBALS['wp_filter'][ $hook_name ] ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Remove callbacks installed by the compatibility layer or test.
			}
		}

		foreach ( $this->action_snapshots as $hook_name => $snapshot ) {
			if ( null === $snapshot ) {
				unset( $GLOBALS['wp_actions'][ $hook_name ] ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Restore prior absence of the action counter.
			} else {
				$GLOBALS['wp_actions'][ $hook_name ] = $snapshot; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Restore the exact pre-test action count.
			}
		}

		foreach ( $this->query_snapshots as $global_name => $snapshot ) {
			if ( $snapshot['exists'] ) {
				$GLOBALS[ $global_name ] = $snapshot['value']; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Restore the exact pre-test query global.
			} else {
				unset( $GLOBALS[ $global_name ] ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Restore prior absence of the query global.
			}
		}

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
					'post_title'  => 'Slice 054 Shop',
					'post_name'   => 'slice-054-shop',
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
