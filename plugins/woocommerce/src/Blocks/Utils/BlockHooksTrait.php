<?php
namespace Automattic\WooCommerce\Blocks\Utils;

/**
 * BlockHooksTrait
 *
 * Shared functionality for using the Block Hooks API with WooCommerce Blocks.
 */
trait BlockHooksTrait {
	/**
	 * Callback for `hooked_block_types` to auto-inject the mini-cart block into headers after navigation.
	 *
	 * @param array                             $hooked_blocks An array of block slugs hooked into a given context.
	 * @param string                            $position      Position of the block insertion point.
	 * @param string                            $anchor_block  The block acting as the anchor for the inserted block.
	 * @param array|\WP_Post|\WP_Block_Template $context       Where the block is embedded.
	 * @since 8.5.0
	 * @return array An array of block slugs hooked into a given context.
	 */
	public function register_hooked_block( $hooked_blocks, $position, $anchor_block, $context ) {

		/**
		 * If the block has no hook placements, return early.
		 */
		if ( ! isset( $this->hooked_block_placements ) || empty( $this->hooked_block_placements ) ) {
			return $hooked_blocks;
		}

		// Cache for active theme.
		static $active_theme_name = null;
		if ( is_null( $active_theme_name ) ) {
			$active_theme_name = wp_get_theme()->get( 'Name' );
		}

		if ( $context ) {
			foreach ( $this->hooked_block_placements as $placement ) {

				if ( $placement['position'] === $position && $placement['anchor'] === $anchor_block ) {
					// If an area has been specified for this placement.
					if (
						isset( $placement['area'] ) &&
						! $this->has_block_in_content( $context )
						&& $this->is_target_area( $context, $placement['area'] )
					) {
						$hooked_blocks[] = $this->namespace . '/' . $this->block_name;
					}

					// If no area has been specified for this placement just insert the block.
					// This is likely to be the case when we're inserting into the navigation block
					// where we don't have a specific area to target.
					if ( ! isset( $placement['area'] ) ) {
						$hooked_blocks[] = $this->namespace . '/' . $this->block_name;
					}

					// If a callback has been specified for this placement, call it. This allows for custom block-specific logic to be run.
					$callback = isset( $placement['callback'] ) && is_callable( array( $this, $placement['callback'] ) ) ? array( $this, $placement['callback'] ) : null;
					if ( null !== $callback ) {
						$modified_hooked_blocks = $callback( $hooked_blocks, $position, $anchor_block, $context );
						if ( is_array( $modified_hooked_blocks ) ) {
							$hooked_blocks = $modified_hooked_blocks;
						}
					}
				}
			}
		}

		return $hooked_blocks;
	}

	/**
	 * Checks if the provided context contains a the block already.
	 *
	 * @param array|\WP_Block_Template $context Where the block is embedded.
	 * @return boolean
	 */
	protected function has_block_in_content( $context ) {
		$content = $this->get_context_content( $context );
		return strpos( $content, 'wp:' . $this->namespace . '/' . $this->block_name ) !== false;
	}

	/**
	 * Given a provided context, returns the content of the context.
	 *
	 * @param array|\WP_Post|\WP_Block_Template $context Where the block is embedded.
	 * @since 8.5.0
	 * @return string
	 */
	protected function get_context_content( $context ) {
		$content = is_array( $context ) && isset( $context['content'] ) ? $context['content'] : '';
		$content = '' === $content && $context instanceof \WP_Block_Template ? $context->content : $content;
		$content = '' === $content && $context instanceof \WP_Post ? $context->post_content : $content;
		return $content;
	}

	/**
	 * Given a provided context, returns whether the context refers to header content.
	 *
	 * @param array|\WP_Post|\WP_Block_Template $context Where the block is embedded.
	 * @param string                            $area The area to check against before inserting.
	 * @since 8.5.0
	 * @return boolean
	 */
	protected function is_template_part_or_pattern( $context, $area ) {
		$is_pattern       = is_array( $context ) &&
		(
			( isset( $context['blockTypes'] ) && in_array( 'core/template-part/' . $area, $context['blockTypes'], true ) ) ||
			( isset( $context['categories'] ) && in_array( $area, $context['categories'], true ) )
		);
		$is_template_part = $context instanceof \WP_Block_Template && $area === $context->area;
		return ( $is_pattern || $is_template_part );
	}

	/**
	 * Given a provided context, returns whether the context refers to the target area and isn't marked as excluded.
	 *
	 * @param array|\WP_Post|\WP_Block_Template $context the context to check.
	 * @param string                            $area The area to check against before inserting.
	 * @since 8.5.0
	 * @return boolean
	 */
	protected function is_target_area( $context, $area ) {
		if ( $this->is_template_part_or_pattern( $context, $area ) && ! $this->pattern_is_excluded( $context ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Returns whether the pattern is excluded or not
	 *
	 * @since 8.5.0
	 *
	 * @param array|\WP_Block_Template $context Where the block is embedded.
	 * @return boolean
	 */
	protected function pattern_is_excluded( $context ) {
		/**
		 * A list of pattern slugs to exclude from auto-insert (useful when there are patterns that have a very specific location for the block)
		 * Note: The patterns that are currently excluded are the ones that don't work well with the mini-cart block or customer-account block.
		 *
		 * @since 8.5.0
		 */
		$pattern_exclude_list = apply_filters(
			'woocommerce_hooked_blocks_pattern_exclude_list',
			array_unique( array_merge( isset( $this->hooked_block_excluded_patterns ) ? $this->hooked_block_excluded_patterns : array(), array( 'twentytwentytwo/header-centered-logo', 'twentytwentytwo/header-stacked' ) ) )
		);

		$pattern_slug = is_array( $context ) && isset( $context['slug'] ) ? $context['slug'] : '';
		if ( ! $pattern_slug ) {
			/**
			 * Woo patterns have a slug property in $context, but core/theme patterns dont.
			 * In that case, we fallback to the name property, as they're the same.
			 */
			$pattern_slug = is_array( $context ) && isset( $context['name'] ) ? $context['name'] : '';
		}
		return in_array( $pattern_slug, $pattern_exclude_list, true );
	}
}
