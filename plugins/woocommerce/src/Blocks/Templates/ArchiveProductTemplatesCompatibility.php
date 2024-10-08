<?php
namespace Automattic\WooCommerce\Blocks\Templates;

/**
 * ArchiveProductTemplatesCompatibility class.
 *
 * To bridge the gap on compatibility with PHP hooks and Product Archive blockified templates.
 *
 * @internal
 */
class ArchiveProductTemplatesCompatibility extends AbstractTemplateCompatibility {

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

		$this->inner_blocks_walker( $parsed_block );

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

		if ( $this->is_null_post_template( $block ) ) {
			$block_name = self::LOOP_ITEM_ID;
		}

		$block_hooks = array_filter(
			$this->hook_data,
			function ( $hook ) use ( $block_name ) {
				return in_array( $block_name, $hook['block_names'], true );
			}
		);

		// We want to inject hooks to the core/post-template or product template block only when the products exist:
		// https://github.com/woocommerce/woocommerce-blocks/issues/9463.
		if ( $this->is_post_or_product_template( $block_name ) && ! empty( $block_content ) ) {
			$this->restore_default_hooks();
			$content = sprintf(
				'%1$s%2$s%3$s',
				$this->get_hooks_buffer( $block_hooks, 'before' ),
				$block_content,
				$this->get_hooks_buffer( $block_hooks, 'after' )
			);
			$this->remove_default_hooks();
			return $content;
		}

		$supported_blocks = array_merge(
			array(),
			...array_map(
				function ( $hook ) {
					return $hook['block_names'];
				},
				array_values( $this->hook_data )
			)
		);

		if ( ! in_array( $block_name, $supported_blocks, true ) ) {
			return $block_content;
		}

		if (
			'core/query-no-results' === $block_name
		) {

			/**
			 * `core/query-no-result` is a special case because it can return two
			 * different content depending on the context. We need to check if the
			 * block content is empty to determine if we need to inject hooks.
			 */
			if ( empty( trim( $block_content ) ) ) {
				return $block_content;
			}

			$this->restore_default_hooks();

			$content = sprintf(
				'%1$s%2$s%3$s',
				$this->get_hooks_buffer( $block_hooks, 'before' ),
				$block_content,
				$this->get_hooks_buffer( $block_hooks, 'after' )
			);

			$this->remove_default_hooks();

			return $content;
		}

		if ( empty( $block_content ) ) {
			return $block_content;
		}

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
	 *     permanently_removed_actions => [
	 *         <function-name>
	 *    ]
	 *  ],
	 * ]
	 * Where:
	 * - hook-name is the name of the hook that will be replaced.
	 * - block-name is the name of the block that will replace the hook.
	 * - position is the position of the block relative to the hook.
	 * - hooked is an array of functions hooked to the hook that will be
	 *   replaced. The key is the function name and the value is the
	 *   priority.
	 * - permanently_removed_actions is an array of functions that we do not want to re-add after they have been removed to avoid duplicate content with the Products block and its inner blocks.
	 */
	protected function set_hook_data() {
		$this->hook_data = array(
			'woocommerce_before_main_content'         => array(
				'block_names' => array( 'core/query', 'woocommerce/product-collection' ),
				'position'    => 'before',
				'hooked'      => array(
					'woocommerce_output_content_wrapper' => 10,
					'woocommerce_breadcrumb'             => 20,
				),
			),
			'woocommerce_after_main_content'          => array(
				'block_names' => array( 'core/query', 'woocommerce/product-collection' ),
				'position'    => 'after',
				'hooked'      => array(
					'woocommerce_output_content_wrapper_end' => 10,
				),
			),
			'woocommerce_before_shop_loop_item_title' => array(
				'block_names' => array( 'core/post-title' ),
				'position'    => 'before',
				'hooked'      => array(
					'woocommerce_show_product_loop_sale_flash' => 10,
					'woocommerce_template_loop_product_thumbnail' => 10,
				),
			),
			'woocommerce_shop_loop_item_title'        => array(
				'block_names' => array( 'core/post-title' ),
				'position'    => 'after',
				'hooked'      => array(
					'woocommerce_template_loop_product_title' => 10,
				),
			),
			'woocommerce_after_shop_loop_item_title'  => array(
				'block_names' => array( 'core/post-title' ),
				'position'    => 'after',
				'hooked'      => array(
					'woocommerce_template_loop_rating' => 5,
					'woocommerce_template_loop_price'  => 10,
				),
			),
			'woocommerce_before_shop_loop_item'       => array(
				'block_names' => array( self::LOOP_ITEM_ID ),
				'position'    => 'before',
				'hooked'      => array(
					'woocommerce_template_loop_product_link_open' => 10,
				),
			),
			'woocommerce_after_shop_loop_item'        => array(
				'block_names' => array( self::LOOP_ITEM_ID ),
				'position'    => 'after',
				'hooked'      => array(
					'woocommerce_template_loop_product_link_close' => 5,
					'woocommerce_template_loop_add_to_cart' => 10,
				),
			),
			'woocommerce_before_shop_loop'            => array(
				'block_names'                 => array( 'core/post-template', 'woocommerce/product-template' ),
				'position'                    => 'before',
				'hooked'                      => array(
					'woocommerce_output_all_notices' => 10,
					'woocommerce_result_count'       => 20,
					'woocommerce_catalog_ordering'   => 30,
				),
				'permanently_removed_actions' => array(
					'woocommerce_output_all_notices',
					'woocommerce_result_count',
					'woocommerce_catalog_ordering',
				),
			),
			'woocommerce_after_shop_loop'             => array(
				'block_names'                 => array( 'core/post-template', 'woocommerce/product-template' ),
				'position'                    => 'after',
				'hooked'                      => array(
					'woocommerce_pagination' => 10,
				),
				'permanently_removed_actions' => array(
					'woocommerce_pagination',
				),
			),
			'woocommerce_no_products_found'           => array(
				'block_names'                 => array( 'core/query-no-results' ),
				'position'                    => 'before',
				'hooked'                      => array(
					'wc_no_products_found' => 10,
				),
				'permanently_removed_actions' => array(
					'wc_no_products_found',
				),
			),
			'woocommerce_archive_description'         => array(
				'block_names' => array( 'core/term-description' ),
				'position'    => 'before',
				'hooked'      => array(
					'woocommerce_taxonomy_archive_description' => 10,
					'woocommerce_product_archive_description'  => 10,
				),
			),
		);
	}

	/**
	 * Check if current page is a product archive template.
	 */
	private function is_archive_template() {
		return is_shop() || is_product_taxonomy();
	}

	/**
	 * Loop through inner blocks recursively to find the Products blocks that
	 * inherits query from template.
	 *
	 * @param array $block Parsed block data.
	 */
	private function inner_blocks_walker( &$block ) {
		if (
			$this->is_products_block_with_inherit_query( $block ) || $this->is_product_collection_block_with_inherit_query( $block )
		) {
			$this->inject_attribute( $block );
			$this->remove_default_hooks();
		}

		if ( ! empty( $block['innerBlocks'] ) ) {
			array_walk( $block['innerBlocks'], array( $this, 'inner_blocks_walker' ) );
		}
	}

	/**
	 * Restore default hooks except the ones that are not supposed to be re-added.
	 */
	private function restore_default_hooks() {
		foreach ( $this->hook_data as $hook => $data ) {
			if ( ! isset( $data['hooked'] ) ) {
				continue;
			}
			foreach ( $data['hooked'] as $callback => $priority ) {
				if ( ! in_array( $callback, $data['permanently_removed_actions'] ?? array(), true ) ) {
					add_action( $hook, $callback, $priority );
				}
			}
		}
	}

	/**
	 * Check whether block is within the product-query namespace.
	 *
	 * @param array $block Parsed block data.
	 */
	private function is_block_within_namespace( $block ) {
		$attributes = $block['attrs'];

		return isset( $attributes['__woocommerceNamespace'] ) && 'woocommerce/product-query/product-template' === $attributes['__woocommerceNamespace'];
	}

	/**
	 * Check whether block has isInherited attribute assigned.
	 *
	 * @param array $block Parsed block data.
	 */
	private function is_block_inherited( $block ) {
		$attributes = $block['attrs'];

		$outcome = isset( $attributes['isInherited'] ) && 1 === $attributes['isInherited'];

		return $outcome;
	}

	/**
	 * The core/post-template has two different block names:
	 * - core/post-template when the wrapper is rendered.
	 * - core/null when the loop item is rendered.
	 *
	 * @param array $block Parsed block data.
	 */
	private function is_null_post_template( $block ) {
		$block_name = $block['blockName'];

		return 'core/null' === $block_name && ( $this->is_block_inherited( $block ) || $this->is_block_within_namespace( $block ) );
	}

	/**
	 * Check whether block is a Post template.
	 *
	 * @param string $block_name Block name.
	 */
	private function is_post_template( $block_name ) {
		return 'core/post-template' === $block_name;
	}

	/**
	 * Check whether block is a Product Template.
	 *
	 * @param string $block_name Block name.
	 */
	private function is_product_template( $block_name ) {
		return 'woocommerce/product-template' === $block_name;
	}

	/**
	 * Check if block is either a Post template or a Product Template
	 *
	 * @param string $block_name Block name.
	 */
	private function is_post_or_product_template( $block_name ) {
		return $this->is_post_template( $block_name ) || $this->is_product_template( $block_name );
	}

	/**
	 * Check if the block is a Products block that inherits query from template.
	 *
	 * @param array $block Parsed block data.
	 */
	private function is_products_block_with_inherit_query( $block ) {
		return 'core/query' === $block['blockName'] &&
		isset( $block['attrs']['namespace'] ) &&
		'woocommerce/product-query' === $block['attrs']['namespace'] &&
		isset( $block['attrs']['query']['inherit'] ) &&
		$block['attrs']['query']['inherit'];
	}

	/**
	 * Check if the block is a Product Collection block that inherits query from template.
	 *
	 * @param array $block Parsed block data.
	 */
	private function is_product_collection_block_with_inherit_query( $block ) {
		return 'woocommerce/product-collection' === $block['blockName'] &&
		isset( $block['attrs']['query']['inherit'] ) &&
		$block['attrs']['query']['inherit'];
	}


	/**
	 * Recursively inject the custom attribute to all nested blocks.
	 *
	 * @param array $block Parsed block data.
	 */
	private function inject_attribute( &$block ) {
		$block['attrs']['isInherited'] = 1;

		if ( ! empty( $block['innerBlocks'] ) ) {
			array_walk( $block['innerBlocks'], array( $this, 'inject_attribute' ) );
		}
	}
}
