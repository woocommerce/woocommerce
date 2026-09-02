<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\ShopperLists;

/**
 * Shared markup helpers for blocks that render a shopper-list item card
 * (Saved for Later, Wishlist, …). Static helpers, not an abstract base —
 * the two blocks' lifecycles diverge enough (auto-injected vs merchant-
 * placed, different actions, different empty-state gating) that inheritance
 * is not a clean fit. Consumers stitch the fragments together with their
 * own quantity / action button / heading bits.
 *
 * Any change here is co-reviewed with every consuming block — drift in the
 * shared row shape will break first paint for whoever didn't get the memo.
 */
final class ShopperListRenderer {

	/**
	 * Shared CSS root class for the row. Each section helper outputs
	 * BEM-style modifiers off this base (`__image-slot`, `__remove`, …).
	 */
	public const ROW_CLASS = 'wc-block-shopper-list-item';

	/**
	 * Wrap `$inner` in the block's outer `<section><ul>…</ul></section>`
	 * grid scaffold. `$wrapper_attrs` are merged with the block's wrapper
	 * attributes via `get_block_wrapper_attributes()`.
	 *
	 * Trust contract: callers are responsible for ensuring `$inner` and
	 * `$before_list` contain only safe, escaped HTML — typically composed
	 * from the section helpers below, never from raw schema/request input.
	 *
	 * @param array<string, mixed> $wrapper_attrs Attributes for the outer `<section>`.
	 * @param string               $list_class    Class attribute for the inner `<ul>`.
	 * @param string               $inner         Markup placed inside the `<ul>` (template + SSR rows + empty state).
	 * @param string               $before_list   Markup placed between `<section>` and `<ul>` (header, notices region).
	 * @return string
	 */
	public static function render_grid_wrapper( array $wrapper_attrs, string $list_class, string $inner, string $before_list = '' ): string {
		return sprintf(
			'<section %1$s>%2$s<ul class="%3$s">%4$s</ul></section>',
			get_block_wrapper_attributes( $wrapper_attrs ),
			$before_list,
			esc_attr( $list_class ),
			$inner
		);
	}

	/**
	 * Wrap `$row_inner_markup` in a `<template data-wp-each>` element that
	 * iAPI uses to render new rows. `$row_inner_markup` is the inner HTML
	 * for the `<li>` — everything between `<li>` and `</li>`.
	 *
	 * Trust contract: caller is responsible for ensuring `$row_inner_markup`
	 * contains only safe, escaped HTML.
	 *
	 * @param string $row_inner_markup Inner markup for the `<li>`.
	 * @return string
	 */
	public static function render_each_template( string $row_inner_markup ): string {
		return sprintf(
			'<template data-wp-each--list-item="state.currentItems" data-wp-each-key="context.listItem.key"><li class="%1$s">%2$s</li></template>',
			esc_attr( self::ROW_CLASS ),
			$row_inner_markup
		);
	}

	/**
	 * Wrap `$row_inner_markup` in an SSR `<li data-wp-each-child>` element
	 * seeded with the per-row iAPI context derived from `$item`. iAPI's
	 * hydration treats this as a no-op diff against the `<template>` if
	 * the inner markup matches.
	 *
	 * Trust contract: caller is responsible for ensuring `$row_inner_markup`
	 * contains only safe, escaped HTML.
	 *
	 * @param array<string, mixed> $item             Schema-shape item.
	 * @param string               $row_inner_markup Inner markup for the `<li>`.
	 * @return string
	 */
	public static function render_each_child( array $item, string $row_inner_markup ): string {
		$context = array( 'listItem' => $item );
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_interactivity_data_wp_context() returns a safely-encoded attribute pair; $row_inner_markup is composed of escaped fragments from the section helpers below.
		return sprintf(
			'<li class="%1$s" data-wp-each-child %2$s>%3$s</li>',
			esc_attr( self::ROW_CLASS ),
			wp_interactivity_data_wp_context( $context ),
			$row_inner_markup
		);
	}

	/**
	 * Render the image + title + price triplet for the template-mode row
	 * (no static attrs; bindings only). Identical between consumer blocks.
	 *
	 * @return string
	 */
	public static function render_template_common_row(): string {
		ob_start();
		?>
		<div class="wc-block-components-product-image wc-block-components-product-image--aspect-ratio-auto">
			<a data-wp-bind--href="context.listItem.permalink">
				<span class="<?php echo esc_attr( self::ROW_CLASS ); ?>__image-slot" data-wp-context='{"htmlField":"image_html"}' data-wp-watch="callbacks.updateInnerHtml"></span>
			</a>
			<button
				type="button"
				class="<?php echo esc_attr( self::ROW_CLASS ); ?>__remove"
				data-wp-on--click="actions.onClickRemove"
				data-wp-bind--aria-label="state.currentItemRemoveLabel"
				data-wp-bind--disabled="state.isCurrentItemPending"
			>
				<?php echo self::get_remove_icon_svg(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG markup. ?>
			</button>
			<span class="<?php echo esc_attr( self::ROW_CLASS ); ?>__variation" data-wp-bind--hidden="!state.currentItemVariationLabel" data-wp-text="state.currentItemVariationLabel"></span>
		</div>
		<h2 class="wp-block-post-title has-text-align-center has-medium-font-size">
			<a data-wp-bind--href="context.listItem.permalink" data-wp-text="state.currentItemDisplayName"></a>
		</h2>
		<div class="price wc-block-components-product-price has-text-align-center has-small-font-size" data-wp-bind--hidden="state.isPriceHidden" data-wp-context='{"htmlField":"price_html"}' data-wp-watch="callbacks.updateInnerHtml"></div>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * Render the image + title + price triplet for the SSR-mode row, with
	 * values populated from `$item` and `$remove_aria_label_template`. The
	 * binding directives match the template-mode markup so iAPI's hydration
	 * is a no-op diff after first paint.
	 *
	 * @param array<string, mixed> $item                        Schema-shape item.
	 * @param string               $remove_aria_label_template  Sprintf template for the remove button's aria-label. `%s` is replaced with the product name.
	 * @return string
	 */
	public static function render_ssr_common_row( array $item, string $remove_aria_label_template ): string {
		$is_live         = ! empty( $item['is_live'] );
		$name            = (string) ( $item['name'] ?? '' );
		$permalink       = (string) ( $item['permalink'] ?? '' );
		$alt             = html_entity_decode( $name, ENT_QUOTES, 'UTF-8' );
		$image_html      = (string) ( $item['image_html'] ?? '' );
		$price_html      = (string) ( $item['price_html'] ?? '' );
		$variation_label = self::get_variation_label( $item );
		$remove_aria     = sprintf( $remove_aria_label_template, $alt );
		$is_price_hidden = '' === $price_html;
		// Tombstone rows (`is_live=false` or empty permalink) render `<a>`
		// without an href — keeps the element shape stable for iAPI
		// reconciliation against the live-row template, and the CSS in the
		// shared partial drops link affordances when the anchor has no href.
		$href_attr = $is_live && '' !== $permalink ? 'href="' . esc_url( $permalink ) . '"' : '';

		ob_start();
		?>
		<div class="wc-block-components-product-image wc-block-components-product-image--aspect-ratio-auto">
			<a <?php echo $href_attr; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- pre-escaped above with esc_url(). ?> data-wp-bind--href="context.listItem.permalink">
				<span
					class="<?php echo esc_attr( self::ROW_CLASS ); ?>__image-slot"
					data-wp-context='{"htmlField":"image_html"}'
					data-wp-watch="callbacks.updateInnerHtml"
				>
					<?php echo wp_kses_post( $image_html ); ?>
				</span>
			</a>
			<button
				type="button"
				class="<?php echo esc_attr( self::ROW_CLASS ); ?>__remove"
				aria-label="<?php echo esc_attr( $remove_aria ); ?>"
				data-wp-on--click="actions.onClickRemove"
				data-wp-bind--aria-label="state.currentItemRemoveLabel"
				data-wp-bind--disabled="state.isCurrentItemPending"
			>
				<?php echo self::get_remove_icon_svg(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG markup. ?>
			</button>
			<span
				class="<?php echo esc_attr( self::ROW_CLASS ); ?>__variation"
				data-wp-bind--hidden="!state.currentItemVariationLabel"
				data-wp-text="state.currentItemVariationLabel"
				<?php
				if ( '' === $variation_label ) {
					echo 'hidden';
				}
				?>
			><?php echo esc_html( $variation_label ); ?></span>
		</div>
		<h2 class="wp-block-post-title has-text-align-center has-medium-font-size">
			<a <?php echo $href_attr; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- pre-escaped above with esc_url(). ?> data-wp-bind--href="context.listItem.permalink" data-wp-text="state.currentItemDisplayName"><?php echo esc_html( $alt ); ?></a>
		</h2>
		<div
			class="price wc-block-components-product-price has-text-align-center has-small-font-size"
			data-wp-bind--hidden="state.isPriceHidden"
			data-wp-context='{"htmlField":"price_html"}'
			data-wp-watch="callbacks.updateInnerHtml"
			<?php
			if ( $is_price_hidden ) {
				echo 'hidden';
			}
			?>
		>
			<?php echo wp_kses_post( $price_html ); ?>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * Empty-state `<li>` that the block toggles on once `state.isEmpty`
	 * flips. `$start_hidden = true` makes SSR ship with `hidden` so the
	 * message doesn't flash for shoppers whose list is being populated
	 * client-side. `$start_hidden = false` is for blocks (e.g. Wishlist)
	 * where the message should show on first paint when the list is empty.
	 *
	 * @param string $message      Visible empty-state message.
	 * @param string $css_class    Class attribute for the `<li>`.
	 * @param bool   $start_hidden Whether the `<li>` should be `hidden` on first paint.
	 * @return string
	 */
	public static function render_empty_state( string $message, string $css_class, bool $start_hidden = true ): string {
		return sprintf(
			'<li class="%1$s" data-wp-bind--hidden="!state.isEmpty"%2$s>%3$s</li>',
			esc_attr( $css_class ),
			$start_hidden ? ' hidden' : '',
			esc_html( $message )
		);
	}

	/**
	 * Render the iAPI store-notices region used by the row-level error
	 * banners. Mirrors `AddToCartWithOptions::render_interactivity_notices_region()`
	 * — keep in sync if the shape changes.
	 *
	 * @param string $wrapper_class Class attribute for the outer `<div>`.
	 * @return string
	 */
	public static function render_interactivity_notices_region( string $wrapper_class ): string {
		ob_start();
		?>
		<div class="<?php echo esc_attr( $wrapper_class ); ?> wc-block-components-notices" data-wp-interactive="woocommerce/store-notices" data-wp-bind--hidden="!context.notices.length" hidden>
			<template data-wp-each--notice="context.notices" data-wp-each-key="context.notice.id">
				<div
					class="wc-block-components-notice-banner"
					data-wp-class--is-error="state.isError"
					data-wp-class--is-success="state.isSuccess"
					data-wp-class--is-info="state.isInfo"
					data-wp-class--is-dismissible="context.notice.dismissible"
					data-wp-bind--role="state.role"
					data-wp-watch="callbacks.injectIcon"
				>
					<div class="wc-block-components-notice-banner__content">
						<span data-wp-init="callbacks.renderNoticeContent" aria-live="assertive" aria-atomic="true"></span>
					</div>
					<button
						type="button"
						data-wp-bind--hidden="!context.notice.dismissible"
						class="wc-block-components-button wp-element-button wc-block-components-notice-banner__dismiss contained"
						aria-label="<?php esc_attr_e( 'Dismiss this notice', 'woocommerce' ); ?>"
						data-wp-on--click="actions.removeNotice"
					>
						<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
							<path d="M13 11.8l6.1-6.3-1-1-6.1 6.2-6.1-6.2-1 1 6.1 6.3-6.5 6.7 1 1 6.5-6.6 6.5 6.6 1-1z" />
						</svg>
					</button>
				</div>
			</template>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * Markup for the trash icon used in the remove-item button. Mirrors the
	 * `trash` icon from `@wordpress/icons` that the cart line item uses for
	 * `wc-block-cart-item__remove-link`, inlined here so SSR first paint
	 * matches what JS would render after hydration. `currentColor` lets the
	 * surrounding badge wrapper drive the fill.
	 *
	 * @return string
	 */
	public static function get_remove_icon_svg(): string {
		return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" aria-hidden="true" focusable="false"><path fill="currentColor" fill-rule="evenodd" clip-rule="evenodd" d="M12 5.5A2.25 2.25 0 0 0 9.878 7h4.244A2.251 2.251 0 0 0 12 5.5ZM12 4a3.751 3.751 0 0 0-3.675 3H5v1.5h1.27l.818 8.997a2.75 2.75 0 0 0 2.739 2.501h4.347a2.75 2.75 0 0 0 2.738-2.5L17.73 8.5H19V7h-3.325A3.751 3.751 0 0 0 12 4Zm4.224 4.5H7.776l.806 8.861a1.25 1.25 0 0 0 1.245 1.137h4.347a1.25 1.25 0 0 0 1.245-1.137l.805-8.861Z"/></svg>';
	}

	/**
	 * Markup for the empty-star icon. Mirrors `starEmpty` from
	 * `@wordpress/icons`, inlined here so SSR first paint matches what JS
	 * renders after hydration. `currentColor` lets the surrounding button
	 * drive the fill.
	 *
	 * @return string
	 */
	public static function get_star_empty_svg(): string {
		return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" aria-hidden="true" focusable="false"><path fill="currentColor" fill-rule="evenodd" clip-rule="evenodd" d="M9.706 8.646a.25.25 0 01-.188.137l-4.626.672a.25.25 0 00-.139.427l3.348 3.262a.25.25 0 01.072.222l-.79 4.607a.25.25 0 00.362.264l4.138-2.176a.25.25 0 01.233 0l4.137 2.175a.25.25 0 00.363-.263l-.79-4.607a.25.25 0 01.072-.222l3.347-3.262a.25.25 0 00-.139-.427l-4.626-.672a.25.25 0 01-.188-.137l-2.069-4.192a.25.25 0 00-.448 0L9.706 8.646zM12 7.39l-.948 1.921a1.75 1.75 0 01-1.317.957l-2.12.308 1.534 1.495c.412.402.6.982.503 1.55l-.362 2.11 1.896-.997a1.75 1.75 0 011.629 0l1.895.997-.362-2.11a1.75 1.75 0 01.504-1.55l1.533-1.495-2.12-.308a1.75 1.75 0 01-1.317-.957L12 7.39z"/></svg>';
	}

	/**
	 * Markup for the filled-star icon. Mirrors `starFilled` from
	 * `@wordpress/icons`, inlined here so SSR first paint matches what JS
	 * renders after hydration. `currentColor` lets the surrounding button
	 * drive the fill.
	 *
	 * @return string
	 */
	public static function get_star_filled_svg(): string {
		return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" aria-hidden="true" focusable="false"><path fill="currentColor" d="M11.776 4.454a.25.25 0 01.448 0l2.069 4.192a.25.25 0 00.188.137l4.626.672a.25.25 0 01.139.426l-3.348 3.263a.25.25 0 00-.072.222l.79 4.607a.25.25 0 01-.362.263l-4.138-2.175a.25.25 0 00-.232 0l-4.138 2.175a.25.25 0 01-.363-.263l.79-4.607a.25.25 0 00-.071-.222L4.754 9.881a.25.25 0 01.139-.426l4.626-.672a.25.25 0 00.188-.137l2.069-4.192z"/></svg>';
	}

	/**
	 * Build a comma-separated variation label like "Color: Blue, Size: M".
	 *
	 * @param array<string, mixed> $item Schema-shape item.
	 * @return string
	 */
	public static function get_variation_label( array $item ): string {
		$variation = $item['variation'] ?? array();
		if ( ! is_array( $variation ) || empty( $variation ) ) {
			return '';
		}
		$parts = array();
		foreach ( $variation as $entry ) {
			if ( ! is_array( $entry ) ) {
				continue;
			}
			$attribute = isset( $entry['attribute'] ) ? html_entity_decode( (string) $entry['attribute'], ENT_QUOTES, 'UTF-8' ) : '';
			$value     = isset( $entry['value'] ) ? html_entity_decode( (string) $entry['value'], ENT_QUOTES, 'UTF-8' ) : '';
			if ( '' === $attribute && '' === $value ) {
				continue;
			}
			$parts[] = $attribute . ': ' . $value;
		}
		return implode( ', ', $parts );
	}
}
