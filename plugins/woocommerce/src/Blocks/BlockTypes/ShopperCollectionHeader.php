<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Blocks\BlockTypes;

/**
 * Shopper Collection Header block.
 *
 * Inner block of `woocommerce/shopper-collection`. Renders a container
 * around an editable `core/heading` (the merchant's heading text) and
 * appends a live item-count badge. Reads from the parent's iAPI store
 * via DOM-inherited `data-wp-interactive` — no namespace of its own —
 * so the badge updates without a re-render when items are added or
 * removed.
 *
 * SSR initial badge text comes from block context populated by the
 * parent's manual inner-block rendering.
 */
final class ShopperCollectionHeader extends AbstractInnerBlock {
	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'shopper-collection-header';

	/**
	 * Render the block.
	 *
	 * @param array     $attributes Block attributes.
	 * @param string    $content    Block content (rendered inner blocks — the heading).
	 * @param \WP_Block $block      Block instance.
	 * @return string Rendered block type output.
	 */
	protected function render( $attributes, $content, $block ) {
		// Sanitize the same way the parent does — the slug flows into a
		// DOM `id` and an `aria-labelledby` reference, so anything looser
		// than `[a-z0-9_-]+` is unacceptable.
		$list_slug = sanitize_title( (string) ( $block->context['woocommerce/shopperListSlug'] ?? '' ) );
		if ( '' === $list_slug ) {
			$list_slug = 'saved-for-later';
		}

		$heading_id = 'wc-block-shopper-collection-heading-' . $list_slug;

		// Block-level supports (margin/padding/blockGap only — see
		// block.json) flow through `get_block_wrapper_attributes`. The
		// heading's own typography/color supports apply to `core/heading`
		// itself; the count span inherits its styling from the wrapper.
		// `data-wp-bind--hidden` reads from the parent's iAPI namespace
		// (inherited via the parent's `data-wp-interactive` on the
		// section wrapper) so the entire header disappears when the list
		// is empty.
		$wrapper_attributes = get_block_wrapper_attributes(
			array(
				'class'                => 'wc-block-shopper-collection-header',
				'id'                   => $heading_id,
				'data-wp-bind--hidden' => '!state.hasItems',
			)
		);

		$initial_suffix = $this->build_initial_suffix( $block );
		$hidden_attr    = '' === $initial_suffix ? ' hidden' : '';

		return sprintf(
			'<div %1$s%2$s>%3$s<span class="wc-block-shopper-collection-header__count" data-wp-text="state.itemCountSuffix">%4$s</span></div>',
			$wrapper_attributes,
			$hidden_attr,
			$content,
			esc_html( $initial_suffix )
		);
	}

	/**
	 * Pull the count and templates off block context and format the
	 * initial "(N items)" suffix. Returns an empty string when the list
	 * is empty so the header can render hidden on first paint (matches
	 * the JS-side `hasItems` toggle).
	 *
	 * @param \WP_Block $block Block instance.
	 * @return string
	 */
	private function build_initial_suffix( \WP_Block $block ): string {
		$count = (int) ( $block->context['woocommerce/shopperItemCount'] ?? 0 );
		if ( $count <= 0 ) {
			return '';
		}

		$singular = (string) ( $block->context['woocommerce/shopperHeaderCountSingular'] ?? '' );
		$plural   = (string) ( $block->context['woocommerce/shopperHeaderCountPlural'] ?? '' );

		$template = 1 === $count ? $singular : $plural;
		if ( '' === $template ) {
			return '';
		}

		return sprintf( $template, $count );
	}
}
