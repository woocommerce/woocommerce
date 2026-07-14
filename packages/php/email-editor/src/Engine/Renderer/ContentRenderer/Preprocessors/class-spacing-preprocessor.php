<?php
/**
 * This file is part of the WooCommerce Email Editor package
 *
 * @package Automattic\WooCommerce\EmailEditor
 */

declare(strict_types = 1);
namespace Automattic\WooCommerce\EmailEditor\Engine\Renderer\ContentRenderer\Preprocessors;

use Automattic\WooCommerce\EmailEditor\Engine\Renderer\ContentRenderer\Preset_Variable_Resolver;
use Automattic\WooCommerce\EmailEditor\Engine\Renderer\ContentRenderer\Rendering_Context;

/**
 * This preprocessor is responsible for setting default spacing values for blocks.
 * In the early development phase, we are setting only margin-top for blocks that are not first or last in the columns block.
 */
class Spacing_Preprocessor implements Context_Aware_Preprocessor {
	/**
	 * Cached post-content block names to avoid repeated apply_filters calls.
	 *
	 * @var string[]|null
	 */
	private ?array $post_content_block_names = null;

	/**
	 * Preprocesses the parsed blocks.
	 *
	 * @param array $parsed_blocks Parsed blocks.
	 * @param array $layout Layout.
	 * @param array $styles Styles.
	 * @return array
	 */
	public function preprocess( array $parsed_blocks, array $layout, array $styles ): array {
		return $this->preprocess_with_context( $parsed_blocks, $layout, $styles );
	}

	/**
	 * Preprocesses the parsed blocks with rendering context.
	 *
	 * @param array                  $parsed_blocks Parsed blocks.
	 * @param array                  $layout Layout.
	 * @param array                  $styles Styles.
	 * @param Rendering_Context|null $rendering_context Rendering context.
	 * @return array
	 */
	public function preprocess_with_context( array $parsed_blocks, array $layout, array $styles, ?Rendering_Context $rendering_context = null ): array {
		$root_padding      = $this->get_root_padding( $styles );
		$container_padding = $styles['__container_padding'] ?? array();
		$variables_map     = $styles['__variables_map'] ?? array();
		$gap_padding_side  = $rendering_context && $rendering_context->is_rtl() ? 'padding-right' : 'padding-left';
		$parsed_blocks     = $this->add_block_gaps(
			$parsed_blocks,
			$styles['spacing']['blockGap'] ?? '',
			null,
			$root_padding,
			false,
			$container_padding,
			$variables_map,
			$gap_padding_side
		);
		return $parsed_blocks;
	}

	/**
	 * Extract and validate horizontal padding from a block's style attributes.
	 *
	 * Preset variable references (e.g. "var:preset|spacing|20") are resolved
	 * to their pixel values using the variables map when provided.
	 *
	 * @param array $block The block to extract padding from.
	 * @param array $variables_map Map of CSS variable names to resolved values.
	 * @return array Padding with 'left' and 'right' keys, or empty array if invalid/absent.
	 */
	private function get_block_horizontal_padding( array $block, array $variables_map = array() ): array {
		$padding   = $block['attrs']['style']['spacing']['padding'] ?? array();
		$has_left  = isset( $padding['left'] );
		$has_right = isset( $padding['right'] );

		if ( ! $has_left && ! $has_right ) {
			return array();
		}

		$left  = $has_left ? $padding['left'] : '0px';
		$right = $has_right ? $padding['right'] : '0px';

		if ( ! is_string( $left ) || ! is_string( $right ) || preg_match( '/[<>"\']/', $left . $right ) ) {
			return array();
		}

		// Resolve preset variable references (e.g. "var:preset|spacing|20")
		// to their pixel values so downstream consumers get usable CSS values.
		$left  = Preset_Variable_Resolver::resolve( $left, $variables_map );
		$right = Preset_Variable_Resolver::resolve( $right, $variables_map );

		if ( $this->is_zero_value( $left ) && $this->is_zero_value( $right ) ) {
			return array();
		}

		return array(
			'left'  => $left,
			'right' => $right,
		);
	}

	/**
	 * Container block names that delegate root padding to their children
	 * instead of receiving it themselves.
	 */
	private const CONTAINER_BLOCKS = array( 'core/group', 'core/post-content' );

	/**
	 * Adds spacing to blocks: margin-top for vertical gaps, horizontal padding for
	 * column gaps, and root padding for children of root-level containers.
	 *
	 * Root padding is distributed from the outer email wrapper to individual block
	 * wrappers. Plain root-level containers (groups without post-content) delegate
	 * padding to their children instead of taking it themselves, so alignfull
	 * children can skip root padding and span the full email width.
	 *
	 * A container that wraps post-content and has its own horizontal padding is a
	 * self-contained box: it takes the root padding as an inset itself, and its own
	 * padding is suppressed on the box and distributed to descendants as container
	 * padding (via a suppress-horizontal-padding flag). This lets full-width children
	 * break out of the box, and keeps the two paddings nesting (e.g. 30px outer +
	 * 24px own) instead of stacking on every block.
	 *
	 * @param array      $parsed_blocks Parsed blocks.
	 * @param string     $gap Gap.
	 * @param array|null $parent_block Parent block.
	 * @param array      $root_padding Root horizontal padding with 'left' and 'right' keys.
	 * @param bool       $apply_root_padding Whether this block should receive root padding (delegated by parent container).
	 * @param array      $container_padding Container horizontal padding with 'left' and 'right' keys.
	 * @param array      $variables_map Map of CSS variable names to resolved values for preset resolution.
	 * @param string     $gap_padding_side Physical padding side for generated column gaps.
	 * @return array
	 */
	private function add_block_gaps( array $parsed_blocks, string $gap = '', $parent_block = null, array $root_padding = array(), bool $apply_root_padding = false, array $container_padding = array(), array $variables_map = array(), string $gap_padding_side = 'padding-left' ): array {
		foreach ( $parsed_blocks as $key => $block ) {
			$block_name        = $block['blockName'] ?? '';
			$parent_block_name = $parent_block['blockName'] ?? '';
			// Ensure that email_attrs are set.
			$block['email_attrs'] = $block['email_attrs'] ?? array();

			/**
			 * Do not add a gap to:
			 * - first child
			 * - parent block is a buttons block (where buttons are side by side).
			 */
			if ( 0 !== $key && $gap && 'core/buttons' !== $parent_block_name ) {
				$block['email_attrs']['margin-top'] = $gap;
			}

			// Handle horizontal gap for columns: apply physical padding to column children (except the first).
			// Only an explicitly defined column blockGap is applied. We intentionally do not
			// derive it from the global (vertical) block spacing.
			if ( 'core/columns' === $parent_block_name && 0 !== $key && null !== $parent_block ) {
				$columns_gap = $this->get_columns_block_gap( $parent_block );
				if ( $columns_gap ) {
					$block['email_attrs'][ $gap_padding_side ] = $columns_gap;
				}
			}

			// Distribute horizontal padding.
			//
			// A container that wraps post-content AND has its own horizontal padding
			// is a self-contained box: it takes the root padding as an inset itself,
			// while its own padding is suppressed on the box and distributed to
			// descendants as container padding (so full-width children can still
			// break out of it). Because the box is inset, post-content ends up
			// narrower than contentSize — the signal Content_Renderer uses to drop
			// root padding for the user blocks in the second pass. Without the inset,
			// the two paddings would stack on every block (e.g. 30px + 24px = 54px).
			$is_root_level            = null === $parent_block;
			$is_container             = in_array( $block_name, self::CONTAINER_BLOCKS, true );
			$alignment                = $block['attrs']['align'] ?? null;
			$has_zero_padding         = $this->has_zero_horizontal_padding( $block );
			$has_own_padding          = $this->has_explicit_horizontal_padding( $block );
			$post_content_block_names = $this->get_post_content_block_names();
			$is_post_content          = in_array( $block_name, $post_content_block_names, true );
			$wraps_post_content       = $is_container && $this->contains_post_content( $block );
			$is_box                   = $wraps_post_content && $has_own_padding && ! $has_zero_padding;

			// A delegator passes padding down to its children instead of taking it,
			// so each child is inset on its own and full-width children can break out.
			$delegates = ! $is_box && (
				( $is_root_level && $is_container && ! $has_own_padding )
				|| ( $apply_root_padding && $is_post_content )
				|| $wraps_post_content
			);

			// Everything else applies the padding to itself, except full-width and
			// explicitly zero-padded blocks.
			$is_recipient = ! $delegates && ! $is_post_content && ! $has_zero_padding && 'full' !== $alignment;

			if ( $is_recipient && ( $apply_root_padding || $is_root_level ) && ! empty( $root_padding ) ) {
				$block['email_attrs']['root-padding-left']  = $root_padding['left'];
				$block['email_attrs']['root-padding-right'] = $root_padding['right'];
			}

			$applied_container = $is_recipient && ! empty( $container_padding );
			if ( $applied_container ) {
				$block['email_attrs']['container-padding-left']  = $container_padding['left'];
				$block['email_attrs']['container-padding-right'] = $container_padding['right'];
			}

			// Pass padding on to the children. Container padding keeps flowing down
			// until a block applies it, then stops — so a nested block (e.g. an image
			// inside a column) doesn't get it a second time.
			$children_container_pad = $applied_container ? array() : $container_padding;
			if ( $is_box ) {
				$block_padding = $this->get_block_horizontal_padding( $block, $variables_map );
				if ( ! empty( $block_padding ) ) {
					$children_container_pad                              = $block_padding;
					$block['email_attrs']['suppress-horizontal-padding'] = true;
				}
			}

			$block['innerBlocks']  = $this->add_block_gaps( $block['innerBlocks'] ?? array(), $gap, $block, $root_padding, $delegates, $children_container_pad, $variables_map, $gap_padding_side );
			$parsed_blocks[ $key ] = $block;
		}

		return $parsed_blocks;
	}

	/**
	 * Returns the list of block names treated as "post content" for padding delegation.
	 *
	 * Filterable so that integrations can register custom post-content-like blocks
	 * without modifying this file.
	 *
	 * @return string[]
	 */
	private function get_post_content_block_names(): array {
		if ( null === $this->post_content_block_names ) {
			$this->post_content_block_names = (array) apply_filters(
				'woocommerce_email_editor_post_content_block_names',
				array( 'core/post-content' )
			);
		}
		return $this->post_content_block_names;
	}

	/**
	 * Checks whether a block contains a core/post-content descendant.
	 *
	 * Searches recursively through container blocks (groups) so that
	 * deeply nested template structures like group → group → post-content
	 * are handled correctly.
	 *
	 * @param array $block The block to check.
	 * @return bool True if the block has a post-content descendant.
	 */
	private function contains_post_content( array $block ): bool {
		$post_content_block_names = $this->get_post_content_block_names();
		foreach ( $block['innerBlocks'] ?? array() as $inner_block ) {
			$name = $inner_block['blockName'] ?? '';
			if ( in_array( $name, $post_content_block_names, true ) ) {
				return true;
			}
			if ( in_array( $name, self::CONTAINER_BLOCKS, true ) && $this->contains_post_content( $inner_block ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Checks whether a block explicitly sets zero horizontal padding.
	 *
	 * Explicit zero padding (0, 0px, 0em, etc.) signals that the block
	 * intentionally wants edge-to-edge layout. Root padding should not
	 * be added on top.
	 *
	 * Non-zero padding (e.g. 20px) is internal content spacing and does
	 * not affect root padding — both can coexist independently.
	 *
	 * @param array $block The block to check.
	 * @return bool True if the block explicitly sets zero horizontal padding.
	 */
	private function has_zero_horizontal_padding( array $block ): bool {
		$padding = $block['attrs']['style']['spacing']['padding'] ?? array();
		$left    = $padding['left'] ?? null;
		$right   = $padding['right'] ?? null;

		return $this->is_zero_value( $left ) || $this->is_zero_value( $right );
	}

	/**
	 * Checks whether a block explicitly defines any horizontal padding.
	 *
	 * Containers with explicit padding (any value) manage their own
	 * layout and should stop delegating root padding to their children.
	 *
	 * @param array $block The block to check.
	 * @return bool True if the block defines horizontal padding.
	 */
	private function has_explicit_horizontal_padding( array $block ): bool {
		$padding = $block['attrs']['style']['spacing']['padding'] ?? array();
		return isset( $padding['left'] ) || isset( $padding['right'] );
	}

	/**
	 * Checks whether a CSS value is explicitly zero.
	 *
	 * Matches '0', '0px', '0em', '0rem', '0%', etc.
	 *
	 * @param mixed $value The CSS value to check.
	 * @return bool True if the value is explicitly zero.
	 */
	private function is_zero_value( $value ): bool {
		if ( ! is_string( $value ) && ! is_numeric( $value ) ) {
			return false;
		}

		return (bool) preg_match( '/^0(%|[a-z]*)?$/i', trim( (string) $value ) );
	}

	/**
	 * Extracts and sanitizes root horizontal padding from theme styles.
	 *
	 * @param array $styles Theme styles.
	 * @return array Root padding with 'left' and 'right' keys, or empty array if invalid.
	 */
	private function get_root_padding( array $styles ): array {
		$padding   = $styles['spacing']['padding'] ?? array();
		$has_left  = isset( $padding['left'] );
		$has_right = isset( $padding['right'] );

		// If neither horizontal padding key is defined, skip root padding entirely.
		if ( ! $has_left && ! $has_right ) {
			return array();
		}

		$left  = $has_left ? $padding['left'] : '0px';
		$right = $has_right ? $padding['right'] : '0px';

		// Validate against potentially malicious values.
		if ( ! is_string( $left ) || ! is_string( $right ) || preg_match( '/[<>"\']/', $left . $right ) ) {
			return array();
		}

		return array(
			'left'  => $left,
			'right' => $right,
		);
	}

	/**
	 * Extracts the horizontal blockGap from a columns block.
	 *
	 * Only an explicitly defined horizontal gap (blockGap.left) is honored; we do
	 * not fall back to the global block spacing, which is vertical-only in the
	 * editor and would otherwise add a gap that widens the rendered email.
	 *
	 * @param array $columns_block The columns block.
	 * @return string|null The horizontal gap value (e.g., "30px" or "var:preset|spacing|30") or null if not explicitly set.
	 */
	private function get_columns_block_gap( array $columns_block ): ?string {
		$block_gap = $columns_block['attrs']['style']['spacing']['blockGap'] ?? null;

		// Columns block uses object format: { "top": "...", "left": "..." }.
		// Only apply a horizontal gap when blockGap.left is explicitly set.
		if ( is_array( $block_gap ) && isset( $block_gap['left'] ) && is_string( $block_gap['left'] ) ) {
			$gap_value = $block_gap['left'];

			// Validate against potentially malicious values.
			if ( preg_match( '/[<>"\']/', $gap_value ) ) {
				return null;
			}

			// Return the value as-is. WP's styles engine will handle transformation of preset variables.
			return $gap_value;
		}

		return null;
	}
}
