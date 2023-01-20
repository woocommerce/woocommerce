<?php
namespace Automattic\WooCommerce\Blocks\Templates;

/**
 * BlockTemplatesCompatibility class.
 *
 * To bridge the gap on compatibility with PHP hooks and blockified templates.
 *
 * @internal
 */
class BlockTemplatesCompatibility {

	/**
	 * The custom ID of the loop item block as the replacement of the core/null block.
	 */
	const LOOP_ITEM_ID = 'product-loop-item';

	/**
	 * The data of supported hooks, containing the hook name, the block name,
	 * position, and the callbacks.
	 *
	 * @var array $hook_data The hook data.
	 */
	protected $hook_data;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->set_hook_data();
		$this->init();
	}

	/**
	 * Initialization method.
	 */
	protected function init() {
		if ( ! wc_current_theme_is_fse_theme() ) {
			return;
		}

		add_filter( 'render_block_data', array( $this, 'update_render_block_data' ), 10, 3 );
		add_filter( 'render_block', array( $this, 'inject_hooks' ), 10, 2 );
	}

	/**
	 * Update the render block data to inject our custom attribute needed to
	 * determine which blocks belong to an inherited Products block.
	 *
	 * @param array         $parsed_block The block being rendered.
	 * @param array         $source_block An un-modified copy of $parsed_block, as it appeared in the source content.
	 * @param WP_Block|null $parent_block If this is a nested block, a reference to the parent block.
	 *
	 * @return array
	 */
	public function update_render_block_data( $parsed_block, $source_block, $parent_block ) {

		if ( ! $this->is_archive_template() ) {
			return $parsed_block;
		}

		/**
		 * Custom data can be injected to top level block only, as Gutenberg
		 * will use this data to render the blocks and its nested blocks.
		 */
		if ( $parent_block ) {
			return $parsed_block;
		}

		array_walk( $parsed_block['innerBlocks'], array( $this, 'inner_blocks_walker' ) );

		return $parsed_block;
	}

	/**
	 * Inject hooks to rendered content of corresponding blocks.
	 *
	 * @param mixed $block_content The rendered block content.
	 * @param mixed $block         The parsed block data.
	 * @return string
	 */
	public function inject_hooks( $block_content, $block ) {
		if ( ! $this->is_archive_template() ) {
			return $block_content;
		}
		/**
		 * If the block is not inherited, we don't need to inject hooks.
		 */
		if ( empty( $block['attrs']['isInherited'] ) ) {
			return $block_content;
		}

		$block_name = $block['blockName'];

		/**
		 * The core/post-template has two different block names:
		 * - core/post-template when the wrapper is rendered.
		 * - core/null when the loop item is rendered.
		 */
		if (
			'core/null' === $block_name &&
			isset( $block['attrs']['__woocommerceNamespace'] ) &&
			'woocommerce/product-query/product-template' === $block['attrs']['__woocommerceNamespace']
		) {
			$block_name = self::LOOP_ITEM_ID;
		}

		$supported_blocks = array_map(
			function( $hook ) {
				return $hook['block_name'];
			},
			array_values( $this->hook_data )
		);

		if ( ! in_array( $block_name, $supported_blocks, true ) ) {
			return $block_content;
		}

		/**
		 * `core/query-no-result` is a special case because it can return two
		 * different content depending on the context. We need to check if the
		 * block content is empty to determine if we need to inject hooks.
		 */
		if (
			'core/query-no-results' === $block_name &&
			empty( trim( $block_content ) )
		) {
			return $block_content;
		}

		$block_hooks = array_filter(
			$this->hook_data,
			function( $hook ) use ( $block_name ) {
				return $hook['block_name'] === $block_name;
			}
		);

		return sprintf(
			'%1$s%2$s%3$s',
			$this->get_hooks_buffer( $block_hooks, 'before' ),
			$block_content,
			$this->get_hooks_buffer( $block_hooks, 'after' )
		);
	}

	/**
	 * The hook data to inject to the rendered content of blocks. This also
	 * contains hooked functions that will be removed by remove_default_hooks.
	 *
	 * The array format:
	 * [
	 *   <hook-name> => [
	 *     block_name => <block-name>,
	 *     position => before|after,
	 *     hooked => [
	 *       <function-name> => <priority>,
	 *        ...
	 *     ],
	 *  ],
	 * ]
	 * Where:
	 * - hook-name is the name of the hook that will be replaced.
	 * - block-name is the name of the block that will replace the hook.
	 * - position is the position of the block relative to the hook.
	 * - hooked is an array of functions hooked to the hook that will be
	 *   replaced. The key is the function name and the value is the
	 *   priority.
	 */
	protected function set_hook_data() {
		$this->hook_data = array(
			'woocommerce_before_main_content'         => array(
				'block_name' => 'core/query',
				'position'   => 'before',
				'hooked'     => array(
					'woocommerce_output_content_wrapper' => 10,
					'woocommerce_breadcrumb'             => 20,
				),
			),
			'woocommerce_after_main_content'          => array(
				'block_name' => 'core/query',
				'position'   => 'after',
				'hooked'     => array(
					'woocommerce_output_content_wrapper_end' => 10,
				),
			),
			'woocommerce_before_shop_loop_item_title' => array(
				'block_name' => 'core/post-title',
				'position'   => 'before',
				'hooked'     => array(
					'woocommerce_show_product_loop_sale_flash' => 10,
					'woocommerce_template_loop_product_thumbnail' => 10,
				),
			),
			'woocommerce_shop_loop_item_title'        => array(
				'block_name' => 'core/post-title',
				'position'   => 'after',
				'hooked'     => array(
					'woocommerce_template_loop_product_title' => 10,
				),
			),
			'woocommerce_after_shop_loop_item_title'  => array(
				'block_name' => 'core/post-title',
				'position'   => 'before',
				'hooked'     => array(
					'woocommerce_template_loop_rating' => 5,
					'woocommerce_template_loop_price'  => 10,
				),
			),
			'woocommerce_before_shop_loop_item'       => array(
				'block_name' => self::LOOP_ITEM_ID,
				'position'   => 'before',
				'hooked'     => array(
					'woocommerce_template_loop_product_link_open' => 10,
				),
			),
			'woocommerce_after_shop_loop_item'        => array(
				'block_name' => self::LOOP_ITEM_ID,
				'position'   => 'after',
				'hooked'     => array(
					'woocommerce_template_loop_product_link_close' => 5,
					'woocommerce_template_loop_add_to_cart' => 10,
				),
			),
			'woocommerce_before_shop_loop'            => array(
				'block_name' => 'core/post-template',
				'position'   => 'before',
				'hooked'     => array(
					'woocommerce_output_all_notices' => 10,
					'woocommerce_result_count'       => 20,
					'woocommerce_catalog_ordering'   => 30,
				),
			),
			'woocommerce_after_shop_loop'             => array(
				'block_name' => 'core/post-template',
				'position'   => 'after',
				'hooked'     => array(
					'woocommerce_pagination' => 10,
				),
			),
			'woocommerce_no_products_found'           => array(
				'block_name' => 'core/query-no-results',
				'position'   => 'before',
				'hooked'     => array(
					'wc_no_products_found' => 10,
				),
			),
		);
	}

	/**
	 * Check if current page is a product archive template.
	 */
	protected function is_archive_template() {
		return is_shop() || is_product_taxonomy();
	}

	/**
	 * Remove the default callback added by WooCommerce. We replaced these
	 * callbacks by blocks so we have to remove them to prevent duplicated
	 * content.
	 */
	protected function remove_default_hooks() {
		foreach ( $this->hook_data as $hook => $data ) {
			if ( ! isset( $data['hooked'] ) ) {
				continue;
			}
			foreach ( $data['hooked'] as $callback => $priority ) {
				remove_action( $hook, $callback, $priority );
			}
		}

		/**
		 * When extensions implement their equivalent blocks of the template
		 * hook functions, they can use this filter to register their old hooked
		 * data here, so in the blockified template, the old hooked functions
		 * can be removed in favor of the new blocks while keeping the old
		 * hooked functions working in classic templates.
		 *
		 * Accepts an array of hooked data. The array should be in the following
		 * format:
		 * [
		 *   [
		 *     hook => <hook-name>,
		 *     function => <function-name>,
		 *     priority => <priority>,
		 *  ],
		 *  ...
		 * ]
		 * Where:
		 * - hook-name is the name of the hook that have the functions hooked to.
		 * - function-name is the hooked function name.
		 * - priority is the priority of the hooked function.
		 *
		 * @param array $data Additional hooked data. Default to empty
		 */
		$additional_hook_data = apply_filters( 'woocommerce_blocks_hook_compatibility_additional_data', array() );

		if ( empty( $additional_hook_data ) || ! is_array( $additional_hook_data ) ) {
			return;
		}

		foreach ( $additional_hook_data as $data ) {
			if ( ! isset( $data['hook'], $data['function'], $data['priority'] ) ) {
				continue;
			}
			remove_action( $data['hook'], $data['function'], $data['priority'] );
		}
	}

	/**
	 * Get the buffer content of the hooks to append/prepend to render content.
	 *
	 * @param array  $hooks    The hooks to be rendered.
	 * @param string $position The position of the hooks.
	 *
	 * @return string
	 */
	protected function get_hooks_buffer( $hooks, $position ) {
		ob_start();
		foreach ( $hooks as $hook => $data ) {
			if ( $data['position'] === $position ) {
				do_action( $hook );
			}
		}
		return ob_get_clean();
	}

	/**
	 * Loop through inner blocks recursively to find the Products blocks that
	 * inherits query from template.
	 *
	 * @param array $block Parsed block data.
	 */
	protected function inner_blocks_walker( &$block ) {
		if (
			'core/query' === $block['blockName'] &&
			isset( $block['attrs']['namespace'] ) &&
			'woocommerce/product-query' === $block['attrs']['namespace'] &&
			isset( $block['attrs']['query']['inherit'] ) &&
			$block['attrs']['query']['inherit']
		) {
			$this->inject_attribute( $block );
			$this->remove_default_hooks();
		}

		if ( ! empty( $block['innerBlocks'] ) ) {
			array_walk( $block['innerBlocks'], array( $this, 'inner_blocks_walker' ) );
		}
	}

	/**
	 * Recursively inject the custom attribute to all nested blocks.
	 *
	 * @param array $block Parsed block data.
	 */
	protected function inject_attribute( &$block ) {
		$block['attrs']['isInherited'] = 1;

		if ( ! empty( $block['innerBlocks'] ) ) {
			array_walk( $block['innerBlocks'], array( $this, 'inject_attribute' ) );
		}
	}

}
