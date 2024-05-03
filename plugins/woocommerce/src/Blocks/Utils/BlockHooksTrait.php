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
	 * @param array                    $hooked_blocks An array of block slugs hooked into a given context.
	 * @param string                   $position      Position of the block insertion point.
	 * @param string                   $anchor_block  The block acting as the anchor for the inserted block.
	 * @param \WP_Block_Template|array $context       Where the block is embedded.
	 * @since $VID:$
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

		/**
		 * A list of pattern slugs to exclude from auto-insert (useful when
		 * there are patterns that have a very specific location for the block)
		 *
		 * @since $VID:$
		 */
		$pattern_exclude_list = apply_filters( 'woocommerce_hooked_blocks_pattern_exclude_list', array( 'twentytwentytwo/header-centered-logo', 'twentytwentytwo/header-stacked' ) );

		/**
		 * A list of theme slugs to execute this with. This is a temporary
		 * measure until improvements to the Block Hooks API allow for exposing
		 * to all block themes.
		 *
		 * @since $VID:$
		 */
		$theme_include_list = apply_filters( 'woocommerce_hooked_blocks_theme_include_list', array( 'Twenty Twenty-Four', 'Twenty Twenty-Three', 'Twenty Twenty-Two', 'Tsubaki', 'Zaino', 'Thriving Artist', 'Amulet', 'Tazza' ) );

		if ( $context && in_array( $active_theme_name, $theme_include_list, true ) ) {
			foreach ( $this->hooked_block_placements as $placement ) {
				if (
					$placement['position'] === $position &&
					$placement['anchor'] === $anchor_block &&
					(
						isset( $placement['area'] ) &&
						$this->is_template_part_or_pattern( $context, $placement['area'] )
					) &&
					! $this->pattern_is_excluded( $context, $pattern_exclude_list ) &&
					! $this->has_block_in_content( $context )
				) {
					$hooked_blocks[] = $this->namespace . '/' . $this->block_name;
				}
			}
		}

		return $hooked_blocks;
	}

	/**
	 * Checks if the provided context contains a the block already.
	 *
	 * @param array|\WP_Block_Template $context Where the block is embedded.
	 * @since $VID:$
	 * @return boolean
	 */
	protected function has_block_in_content( $context ) {
		$content = is_array( $context ) && isset( $context['content'] ) ? $context['content'] : '';
		$content = '' === $content && $context instanceof \WP_Block_Template ? $context->content : $content;
		return strpos( $content, 'wp:' . $this->namespace . '/' . $this->block_name ) !== false;
	}

	/**
	 * Given a provided context, returns whether the context refers to header content.
	 *
	 * @param array|\WP_Block_Template $context Where the block is embedded.
	 * @param string                   $area The area to check against before inserting.
	 * @since $VID:$
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
	 * Returns whether the pattern is excluded or not
	 *
	 * @param array|\WP_Block_Template $context Where the block is embedded.
	 * @param array                    $pattern_exclude_list List of pattern slugs to exclude.
	 * @since $VID:$
	 * @return boolean
	 */
	protected function pattern_is_excluded( $context, $pattern_exclude_list = array() ) {
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
