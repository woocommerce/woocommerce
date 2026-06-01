<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Blocks\BlockTypes;

use Automattic\WooCommerce\Blocks\Utils\BlocksSharedState;
use Automattic\WooCommerce\Internal\ShopperLists\ShopperListRenderer;

/**
 * Wishlist block.
 *
 * Renders the shopper's wishlist, wired to the `shopper-lists` Store API
 * endpoints via the shared `woocommerce/shopper-lists` iAPI store. PHP
 * prefetches the list so the first paint is already populated; JS then
 * takes over for adds, removes, and the per-row "Add to cart" action.
 *
 * Unlike Saved for Later, this block is merchant-placed — no Block Hooks
 * API integration. It's rendered by the `/my-account/wishlist/` endpoint
 * (gated by the `product_wishlist` feature flag) and can also be placed
 * on any other page or template. "Add to cart" mirrors Saved for Later's
 * Move-to-cart flow: add the product to the cart, then remove it from the
 * wishlist on confirmed success.
 */
final class Wishlist extends AbstractBlock {
	/**
	 * The list slug this block renders. Constant — when additional list
	 * types ship as their own blocks, each one hardcodes its own slug.
	 */
	private const LIST_SLUG = 'wishlist';

	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'wishlist';

	/**
	 * Render the block.
	 *
	 * @param array     $attributes Block attributes.
	 * @param string    $content    Block content.
	 * @param \WP_Block $block      Block instance.
	 * @return string Rendered block type output.
	 */
	protected function render( $attributes, $content, $block ) {
		// Guests have no personal list — bail before enqueuing assets or
		// seeding state. The My Account endpoint isn't reachable for
		// guests, but the block can also be placed by a merchant on any
		// page, where this guard is what stops it from rendering an
		// empty shell for logged-out visitors.
		if ( ! is_user_logged_in() ) {
			return '';
		}

		// Clamp to the 2-6 range the SCSS `@for $i from 2 through 6` loop
		// and the editor `RangeControl` both support. `absint()` first
		// defends against a code-editor override (the attribute can be set
		// to any JSON value there); the `min`/`max` then keep the value
		// within the range where a `&.columns-#{$i}` rule actually exists.
		$column_count = min( 6, max( 2, absint( $attributes['columnCount'] ?? 5 ) ) );

		wp_enqueue_script_module( $this->get_full_block_name() );

		$consent = 'I acknowledge that using private APIs means my theme or plugin will inevitably break in the next version of WooCommerce';
		BlocksSharedState::load_store_config( $consent );
		BlocksSharedState::load_placeholder_image( $consent );
		// `Add to cart` calls into the shared cart store, which expects
		// `state.cart.items` and friends. Without this load the cart store
		// would have no hydrated cart and the action would throw on the
		// first click.
		BlocksSharedState::load_cart_state( $consent );

		$items = $this->prefetch_items();

		// Seed the shared shopper-lists store with the rest URL, the
		// pre-fetched items, and a starter nonce. The starter nonce is
		// what the cart store also seeds via `state.nonce` — the JS layer
		// keeps it fresh by reading the `Nonce` response header on every
		// subsequent request, so this is just the bootstrap value (and
		// avoids deadlocking mutations that await `isNonceReady` before
		// any GET has fired).
		wp_interactivity_state(
			'woocommerce/shopper-lists',
			array(
				'restUrl' => get_rest_url(),
				'nonce'   => wp_create_nonce( 'wc_store_api' ),
				'lists'   => array(
					self::LIST_SLUG => array(
						'items'     => $items,
						'isLoading' => false,
					),
				),
			)
		);

		// Only the remove-button aria-label template needs JS-side
		// interpolation; visible strings (empty state, action label) are
		// rendered server-side and toggled with directives.
		wp_interactivity_config(
			'woocommerce/wishlist',
			array(
				'removeLabelTemplate' => $this->get_remove_label_template(),
			)
		);

		// No `hasShownItems` flag: unlike Saved for Later (which auto-
		// renders on every cart visit and must avoid flashing an empty
		// message before a runtime save lands), Wishlist is reached
		// deliberately — by the My Account endpoint or because a merchant
		// placed it. Showing the empty message immediately is the right
		// signal: the visitor came to look at their wishlist, and it's
		// empty. `data-wp-context---notices` seeds the store-notices
		// namespace alongside the block's own context on the same wrapper.
		$wrapper_attributes = array(
			'class'                     => 'wc-block-wishlist',
			'data-wp-interactive'       => 'woocommerce/wishlist',
			'data-wp-context'           => (string) wp_json_encode(
				array(
					// `stdClass` so it serialises as `{}`, not `[]` —
					// iAPI's reactive proxy only fires updates on object
					// writes, not array expandos.
					'pendingKeys' => new \stdClass(),
				)
			),
			'data-wp-context---notices' => 'woocommerce/store-notices::' . (string) wp_json_encode( array( 'notices' => array() ) ),
		);

		$list_class  = sprintf( 'wc-block-wishlist__list columns-%d', $column_count );
		$ul_inner    = $this->render_template_markup() . $this->render_items_markup( $items ) . $this->render_empty_markup( $items );
		$before_list = $this->render_header_markup( $content ) . ShopperListRenderer::render_interactivity_notices_region( 'wc-block-wishlist__notices' );

		return ShopperListRenderer::render_grid_wrapper( $wrapper_attributes, $list_class, $ul_inner, $before_list );
	}

	/**
	 * Prefetch the wishlist items via `rest_do_request()`. Logged-out
	 * users short-circuit to an empty list — the route requires
	 * authentication and we don't want to fire an API call that's only
	 * going to 401.
	 *
	 * @return array<int, array<string, mixed>> Items in the schema response shape.
	 */
	private function prefetch_items(): array {
		if ( ! is_user_logged_in() ) {
			return array();
		}

		$request  = new \WP_REST_Request( 'GET', '/wc/store/v1/shopper-lists/' . self::LIST_SLUG . '/items' );
		$response = rest_do_request( $request );

		if ( $response->is_error() ) {
			$error   = $response->as_error();
			$message = $error instanceof \WP_Error ? $error->get_error_message() : 'Unknown error';
			// Logged at debug level on purpose: prefetch failures are
			// often transient (network blips, auth refresh races) and
			// the user-visible behaviour is the empty state — nothing
			// for ops to act on.
			wc_get_logger()->debug(
				sprintf( 'Wishlist prefetch failed: %s', $message ),
				array(
					'source' => 'wishlist',
					'data'   => array( 'slug' => self::LIST_SLUG ),
				)
			);
			return array();
		}

		$data = $response->get_data();
		if ( ! is_array( $data ) && ! is_object( $data ) ) {
			return array();
		}

		// The schema casts `prices` and image entries to stdClass so the
		// JSON response renders objects, not arrays. Round-trip through
		// JSON encode/decode to normalise everything to nested arrays so
		// the SSR markup helpers can treat fields uniformly.
		$decoded = json_decode( (string) wp_json_encode( $data ), true );
		return is_array( $decoded ) ? $decoded : array();
	}

	/**
	 * The `<template data-wp-each>` describing how each item is rendered
	 * on the client. Pre-rendered children sit alongside as
	 * `data-wp-each-child` elements so first paint is populated. Composes
	 * the shared row markup with the Wishlist-specific "Add to cart"
	 * action button.
	 *
	 * @return string
	 */
	private function render_template_markup(): string {
		$row_inner = ShopperListRenderer::render_template_common_row()
			. $this->render_template_add_to_cart();
		return ShopperListRenderer::render_each_template( $row_inner );
	}

	/**
	 * Render the SSR markup for each item. JS will reconcile these via
	 * `data-wp-each-child` after hydration.
	 *
	 * @param array<int, array<string, mixed>> $items Schema-shape items.
	 * @return string
	 */
	private function render_items_markup( array $items ): string {
		$markup = '';
		foreach ( $items as $item ) {
			$markup .= $this->render_item_markup( $item );
		}
		return $markup;
	}

	/**
	 * Render a single SSR item. Composes the shared image / name / price
	 * markup with the Wishlist-specific "Add to cart" button.
	 *
	 * @param array<string, mixed> $item Schema-shape item.
	 * @return string
	 */
	private function render_item_markup( array $item ): string {
		$row_inner = ShopperListRenderer::render_ssr_common_row( $item, $this->get_remove_label_template() )
			. $this->render_ssr_add_to_cart( $item );
		return ShopperListRenderer::render_each_child( $item, $row_inner );
	}

	/**
	 * Template-mode markup for the "Add to cart" action button. iAPI
	 * substitutes the per-row state through `data-wp-bind--hidden` and
	 * `data-wp-bind--disabled`.
	 *
	 * @return string
	 */
	private function render_template_add_to_cart(): string {
		ob_start();
		?>
		<div class="wp-block-button wc-block-components-product-button" data-wp-bind--hidden="state.isAddToCartHidden">
			<button
				type="button"
				class="wp-block-button__link wp-element-button add_to_cart_button wc-block-components-product-button__button"
				data-wp-on--click="actions.onClickAddToCart"
				data-wp-bind--disabled="state.isCurrentItemPending"
			>
				<?php echo esc_html( $this->get_add_to_cart_label() ); ?>
			</button>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * SSR-mode markup for the "Add to cart" action button. Always emits
	 * the wrapper so iAPI can toggle `hidden` after hydration without
	 * swapping the row out. Starts hidden when the row isn't purchasable.
	 *
	 * @param array<string, mixed> $item Schema-shape item.
	 * @return string
	 */
	private function render_ssr_add_to_cart( array $item ): string {
		$is_hidden = empty( $item['is_purchasable'] );
		ob_start();
		?>
		<div
			class="wp-block-button wc-block-components-product-button"
			data-wp-bind--hidden="state.isAddToCartHidden"
			<?php
			if ( $is_hidden ) {
				echo 'hidden';
			}
			?>
		>
			<button
				type="button"
				class="wp-block-button__link wp-element-button add_to_cart_button wc-block-components-product-button__button"
				data-wp-on--click="actions.onClickAddToCart"
				data-wp-bind--disabled="state.isCurrentItemPending"
			>
				<?php echo esc_html( $this->get_add_to_cart_label() ); ?>
			</button>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * Wrap the inner-block content (heading + any future siblings) in a
	 * div. Unlike Saved for Later, no `hasShownItems` gating — the header
	 * is always shown when there's content for it. Returns an empty
	 * string when there's no content to wrap, so we don't emit an empty
	 * `<div>`.
	 *
	 * @param string $content Rendered inner-block content (typically the heading HTML).
	 * @return string
	 */
	private function render_header_markup( string $content ): string {
		if ( '' === $content ) {
			return '';
		}
		return '<div class="wc-block-wishlist__header">' . $content . '</div>';
	}

	/**
	 * Render the empty-state markup. Visible on first paint when the
	 * list is empty (no `hasShownItems` gate), then iAPI takes over via
	 * `state.isEmpty` for runtime transitions.
	 *
	 * @param array<int, array<string, mixed>> $items Schema-shape items.
	 * @return string
	 */
	private function render_empty_markup( array $items ): string {
		return ShopperListRenderer::render_empty_state(
			__( 'Your wishlist is empty. Items you add to your wishlist will appear here.', 'woocommerce' ),
			'wc-block-wishlist__empty',
			! empty( $items )
		);
	}

	/**
	 * Sprintf template for the per-row remove button's aria-label. Used
	 * both by PHP SSR and by the JS-side getter (via
	 * `wp_interactivity_config`) so both paths produce the same string
	 * after `%s` interpolation.
	 */
	private function get_remove_label_template(): string {
		/* translators: %s: product name. */
		return __( 'Remove %s from wishlist', 'woocommerce' );
	}

	/**
	 * Visible label for the add-to-cart action button, used by both the
	 * iAPI `<template>` and the SSR per-row markup.
	 */
	private function get_add_to_cart_label(): string {
		return __( 'Add to cart', 'woocommerce' );
	}

	/**
	 * Get the frontend script handle for this block type.
	 *
	 * Scripts are loaded via `viewScriptModule` in block.json.
	 *
	 * @param string|null $key The key of the script to get.
	 * @return null
	 */
	protected function get_block_type_script( $key = null ) {
		return null;
	}

	/**
	 * Get the frontend style handle for this block type.
	 *
	 * Returning null lets WP use the `style` array from block.json, which
	 * lists this block's own stylesheet plus the atomic
	 * product-image / product-price / product-button stylesheets we
	 * borrow class names from.
	 *
	 * @return null
	 */
	protected function get_block_type_style() {
		return null;
	}

	/**
	 * Disable the editor style handle for this block type.
	 *
	 * @return null
	 */
	protected function get_block_type_editor_style() {
		return null;
	}
}
