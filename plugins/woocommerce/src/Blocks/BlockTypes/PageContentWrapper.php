<?php
namespace Automattic\WooCommerce\Blocks\BlockTypes;

/**
 * Used in templates to wrap page content. Allows content to be populated at template level.
 *
 * @internal
 */
class PageContentWrapper extends AbstractBlock {
	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'page-content-wrapper';

	/**
	 * Initialize this block type.
	 *
	 * - Intercept template loading to add post ID to this block.
	 */
	public function initialize() {
		add_filter( 'get_block_templates', [ $this, 'add_correct_post_id_to_template' ], 10, 1 );
		parent::initialize();
	}

	/**
	 * @param \WP_Block_Template[] $block_templates Array of block templates.
	 *
	 * @return \WP_Block_Template[]
	 */
	public function add_correct_post_id_to_template( $block_templates ) {

		if ( ! is_array( $block_templates ) ) {
			return $block_templates;
		}

		// Filter $block_templates to find ones where $block_template->content contains woocommerce/page-content-wrapper.
		$page_wrapper_templates = array_filter(
			$block_templates,
			function ( $block_template ) {
				if ( empty( $block_template->content ) ) {
					return false;
				}
				return false !== strpos( $block_template->content, 'woocommerce/page-content-wrapper' );
			}
		);

		foreach ( $page_wrapper_templates as $template_index => $page_wrapper_template ) {

			if ( empty( $page_wrapper_template->content ) ) {
				continue;
			}

			// Parse the template into actual blocks, this will make it easier for us to reason with than a string.
			$parsed_block_content = parse_blocks( $page_wrapper_template->content );

			// Find the woocommerce/page-content-wrapper blocks from the array of blocks in the template.
			$blocks = array_values(
				array_filter(
					$parsed_block_content,
					function ( $block ) {
						if ( empty( $block['blockName'] ) ) {
							return false;
						}
						return 'woocommerce/page-content-wrapper' === $block['blockName'];
					}
				)
			);
			// Find each match of the block in the $block_content->content.
			preg_match( '/<!--\s?wp:woocommerce\/page-content-wrapper.*?\s?-->/', $page_wrapper_template->content, $matches );

			if ( ! is_array( $matches ) ) {
				continue;
			}

			foreach ( $matches as $index => $match ) {
				// Keep the changed attributes separate so we can  avoid overwriting any that are already set.
				$new_attributes = [ 'postType' => 'page' ];

				// If there's no page attribute, skip modifying because we won't be able to tell which post ID to assign.
				if ( empty( $blocks[ $index ]['attrs']['page'] ) || ! is_array( $blocks[ $index ] ) || ! is_array( $blocks[ $index ]['attrs'] ) ) {
					continue;
				}

				if ( 'cart' === $blocks[ $index ]['attrs']['page'] ) {
					$new_attributes['postId'] = wc_get_page_id( 'cart' );
				}
				if ( 'checkout' === $blocks[ $index ]['attrs']['page'] ) {
					$new_attributes['postId'] = wc_get_page_id( 'checkout' );
				}

				// If we still don't have a post ID after the checks, skip modifying. This will happen if page was not cart or checkout.
				if ( empty( $blocks[ $index ]['attrs']['postId'] ) ) {
					continue;
				}

				// Merge the existing attributes with the ones that were added.
				$final_attributes = array_replace( $new_attributes, $blocks[ $index ]['attrs'] );
				if ( is_string( $final_attributes['postId'] ) ) {
					$final_attributes['postId'] = (int) $final_attributes['postId'];
				}
				$json_final_attributes = wp_json_encode( $final_attributes, JSON_NUMERIC_CHECK );

				$new_block                      = '<!-- wp:woocommerce/page-content-wrapper ' . $json_final_attributes . ' -->';
				$page_wrapper_template->content = str_replace( $match, $new_block, $page_wrapper_template->content );
			}
			$block_templates[ $template_index ] = $page_wrapper_template;
		}
		return $block_templates;
	}

	/**
	 * It isn't necessary to register block assets.
	 *
	 * @param string $key Data to get, or default to everything.
	 * @return array|string|null
	 */
	protected function get_block_type_script( $key = null ) {
		return null;
	}

	/**
	 * Get the frontend style handle for this block type.
	 *
	 * @return null
	 */
	protected function get_block_type_style() {
		return null;
	}
}
