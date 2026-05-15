<?php
namespace Automattic\WooCommerce\Blocks\Utils;

/**
 * BlockHooksTrait
 *
 * Shared functionality for using the Block Hooks API with WooCommerce Blocks.
 */
trait BlockHooksTrait {
	/**
	 * Expose this block's hook placements on its registered `WP_Block_Type` so the
	 * block editor can render a toggle for it in the anchor block's inspector
	 * (e.g. the Navigation block's hooked block toggles).
	 *
	 * Without this, blocks that are hooked exclusively via the `hooked_block_types`
	 * PHP filter (as opposed to the `blockHooks` field in `block.json`) are invisible
	 * to the editor's hooked block UI until the user has saved the template at least
	 * once, because the metadata that drives the toggle is only populated after that.
	 *
	 * Setting the `block_hooks` property on the `WP_Block_Type` instance makes it
	 * available via the block types REST endpoint and therefore to the editor.
	 * Actual auto-insertion is still gated by `register_hooked_block` below, so
	 * existing version-based opt-out behaviour is preserved.
	 *
	 * @return void
	 */
	public function register_block_hooks_metadata() {
		if ( empty( $this->hooked_block_placements ) ) {
			return;
		}

		$block_name = $this->namespace . '/' . $this->block_name;
		$block_type = \WP_Block_Type_Registry::get_instance()->get_registered( $block_name );

		if ( ! $block_type instanceof \WP_Block_Type ) {
			return;
		}

		$block_hooks = is_array( $block_type->block_hooks ) ? $block_type->block_hooks : array();

		foreach ( $this->hooked_block_placements as $placement ) {
			if ( ! isset( $placement['anchor'], $placement['position'] ) ) {
				continue;
			}

			// Only `before`, `after`, `first_child` and `last_child` are valid positions
			// for the `block_hooks` property on `WP_Block_Type`.
			if ( ! in_array( $placement['position'], array( 'before', 'after', 'first_child', 'last_child' ), true ) ) {
				continue;
			}

			$block_hooks[ $placement['anchor'] ] = $placement['position'];
		}

		$block_type->block_hooks = $block_hooks;
	}

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
		// If the block has no hook placements, return early.
		if ( ! isset( $this->hooked_block_placements ) || empty( $this->hooked_block_placements ) ) {
			return $hooked_blocks;
		}

		$block_name = $this->namespace . '/' . $this->block_name;

		// Cache the block hooks version.
		static $block_hooks_version = null;
		if ( defined( 'WP_RUN_CORE_TESTS' ) || is_null( $block_hooks_version ) ) {
			$block_hooks_version = get_option( 'woocommerce_hooked_blocks_version' );
		}

		// If block hooks are disabled or the version is not set, return early.
		// Also remove the block from the hooked list if it was added via the `block_hooks`
		// metadata on the registered block type (which is exposed for editor toggles).
		if ( 'no' === $block_hooks_version || false === $block_hooks_version ) {
			return $this->remove_self_from_hooked_blocks( $hooked_blocks );
		}

		// Valid placements are those that have no version specified,
		// or have a version that is less than or equal to version specified in the woocommerce_hooked_blocks_version option.
		$valid_placements = array_filter(
			$this->hooked_block_placements,
			function ( $placement ) use ( $block_hooks_version ) {
				$placement_version = isset( $placement['version'] ) ? $placement['version'] : null;
				return is_null( $placement_version ) || ! is_null( $placement_version ) && version_compare( $block_hooks_version, $placement_version, '>=' );
			}
		);

		// If no placement applies to this anchor/position pair, the block must not be
		// auto-inserted here even if `block_hooks` metadata on the registered block type
		// listed it. Strip any stale entry that core may have added.
		$placement_matches_anchor = false;
		foreach ( $valid_placements as $placement ) {
			if ( isset( $placement['position'], $placement['anchor'] )
				&& $placement['position'] === $position
				&& $placement['anchor'] === $anchor_block
			) {
				$placement_matches_anchor = true;
				break;
			}
		}
		if ( ! $placement_matches_anchor ) {
			return $this->remove_self_from_hooked_blocks( $hooked_blocks );
		}

		$should_hook = false;
		if ( $context && ! empty( $valid_placements ) ) {
			foreach ( $valid_placements as $placement ) {

				if ( $placement['position'] === $position && $placement['anchor'] === $anchor_block ) {
					// If an area has been specified for this placement.
					if (
						isset( $placement['area'] ) &&
						! $this->has_block_in_content( $context )
						&& $this->is_target_area( $context, $placement['area'] )
					) {
						$should_hook = true;
						if ( ! in_array( $block_name, $hooked_blocks, true ) ) {
							$hooked_blocks[] = $block_name;
						}
					}

					// If no area has been specified for this placement just insert the block.
					// This is likely to be the case when we're inserting into the navigation block
					// where we don't have a specific area to target.
					if ( ! isset( $placement['area'] ) ) {
						$should_hook = true;
						if ( ! in_array( $block_name, $hooked_blocks, true ) ) {
							$hooked_blocks[] = $block_name;
						}
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

		if ( ! $should_hook ) {
			$hooked_blocks = $this->remove_self_from_hooked_blocks( $hooked_blocks );
		}

		return $hooked_blocks;
	}

	/**
	 * Remove this block from a list of hooked block names.
	 *
	 * Used to clear entries that core may have added because we declare the placement
	 * via `block_hooks` metadata on the registered block type (so editor toggles work),
	 * even when our placement rules say the block should not be auto-inserted.
	 *
	 * @param array $hooked_blocks An array of block slugs hooked into a given context.
	 * @return array
	 */
	protected function remove_self_from_hooked_blocks( $hooked_blocks ) {
		$block_name = $this->namespace . '/' . $this->block_name;
		$key        = array_search( $block_name, $hooked_blocks, true );
		if ( false !== $key ) {
			unset( $hooked_blocks[ $key ] );
			$hooked_blocks = array_values( $hooked_blocks );
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
