<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Blocks\BlockTypes;

use Automattic\WooCommerce\Blocks\Utils\BlocksSharedState;
use Automattic\WooCommerce\Internal\ShopperLists\ShopperListRenderer;

/**
 * Wishlist block. Renders the shopper's wishlist via the `shopper-lists` Store API and the shared
 * `woocommerce/shopper-lists` iAPI store. Merchant-placed (no Block Hooks integration), and also rendered
 * by the `/my-account/wishlist/` endpoint when the `product_wishlist` feature flag is enabled. The Add to
 * cart action adds the product to the cart and removes the row from the wishlist on confirmed success.
 */
final class Wishlist extends AbstractBlock {
	/**
	 * Slug of the shopper list this block renders.
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
		// Guests have no personal list. The My Account endpoint is unreachable for guests, and the same
		// guard is needed when a merchant places this block on any other page.
		if ( ! is_user_logged_in() ) {
			return '';
		}

		// Clamp to the 2-6 range supported by the SCSS.
		$column_count = min( 6, max( 2, absint( $attributes['columnCount'] ?? 5 ) ) );

		wp_enqueue_script_module( $this->get_full_block_name() );

		$consent = 'I acknowledge that using private APIs means my theme or plugin will inevitably break in the next version of WooCommerce';
		BlocksSharedState::load_store_config( $consent );
		BlocksSharedState::load_placeholder_image( $consent );
		// Required so the Add to cart action has a hydrated cart store to dispatch into.
		BlocksSharedState::load_cart_state( $consent );

		$items = $this->prefetch_items();

		// Seed the shared shopper-lists store with the REST URL, prefetched items, and a bootstrap nonce.
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

		// Only the remove-button aria-label template needs JS-side interpolation.
		wp_interactivity_config(
			'woocommerce/wishlist',
			array(
				'removeLabelTemplate' => $this->get_remove_label_template(),
			)
		);

		// No `hasShownItems` flag here, unlike Saved for Later: the empty message shows immediately.
		$wrapper_attributes = array(
			'class'                     => 'wc-block-wishlist',
			'data-wp-interactive'       => 'woocommerce/wishlist',
			'data-wp-context'           => (string) wp_json_encode(
				array(
					// `stdClass` so JSON serializes as `{}` rather than `[]`.
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
	 * Prefetch the wishlist items via `rest_do_request()`. Returns an empty
	 * list for logged-out users, since the route requires authentication.
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
			// Logged for diagnostics.
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

		// The schema casts `prices` and image entries to stdClass so JSON renders them as objects.
		// Round-trip through JSON to normalise everything to nested arrays for the SSR markup helpers.
		$decoded = json_decode( (string) wp_json_encode( $data ), true );
		return is_array( $decoded ) ? $decoded : array();
	}

	/**
	 * Render the `<template data-wp-each>` used by iAPI to render rows on the client. Pre-rendered
	 * `data-wp-each-child` elements sit alongside to populate first paint.
	 *
	 * @return string
	 */
	private function render_template_markup(): string {
		$row_inner = ShopperListRenderer::render_template_common_row()
			. $this->render_template_add_to_cart();
		return ShopperListRenderer::render_each_template( $row_inner );
	}

	/**
	 * Render the SSR markup for each item. Reconciled by iAPI via `data-wp-each-child` after hydration.
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
	 * Render a single SSR item, combining the shared row markup with the Add to cart button.
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
	 * Template-mode markup for the Add to cart action button. iAPI substitutes the per-row state through
	 * `data-wp-bind--hidden` and `data-wp-bind--disabled`.
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
	 * SSR-mode markup for the Add to cart action button. The wrapper is always emitted so iAPI can toggle
	 * `hidden` after hydration. Starts hidden when the row is not purchasable.
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
	 * Wrap the inner-block content in a div. The header is always shown when content is present, with no
	 * `hasShownItems` guard. Returns an empty string when there is no content, to avoid an empty `<div>`.
	 *
	 * @param string $content Rendered inner-block content (usually the heading HTML).
	 * @return string
	 */
	private function render_header_markup( string $content ): string {
		if ( '' === $content ) {
			return '';
		}
		return '<div class="wc-block-wishlist__header">' . $content . '</div>';
	}

	/**
	 * Render the empty-state markup. Visible on first paint when the list is empty. iAPI handles runtime
	 * transitions via `state.isEmpty`.
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
	 * Sprintf template for the per-row remove button aria-label. Shared between PHP SSR and the JS-side
	 * getter (seeded via `wp_interactivity_config`) so both paths produce identical output.
	 */
	private function get_remove_label_template(): string {
		/* translators: %s: product name. */
		return __( 'Remove %s from wishlist', 'woocommerce' );
	}

	/**
	 * Visible label for the Add to cart action button. Used by the iAPI `<template>` and the SSR markup.
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
	 * Frontend style handle. Returns null so WP loads the `style` array from block.json, which lists this
	 * block's stylesheet and the atomic product-image/price/button stylesheets whose classes it reuses.
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
