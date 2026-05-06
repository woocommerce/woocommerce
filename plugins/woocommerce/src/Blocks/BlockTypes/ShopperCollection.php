<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Blocks\BlockTypes;

use Automattic\WooCommerce\Blocks\Utils\BlocksSharedState;

/**
 * Shopper Collection block.
 *
 * Renders a collection curated by the shopper (e.g. Saved for Later) wired to
 * the `shopper-lists` Store API endpoints via the shared `woocommerce/shopper-lists`
 * iAPI store. PHP prefetches the active list so the first paint is already
 * populated; JS then takes over for adds, removes, and Move-to-cart.
 */
final class ShopperCollection extends AbstractBlock {
	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'shopper-collection';

	/**
	 * Render the block.
	 *
	 * @param array     $attributes Block attributes.
	 * @param string    $content    Block content.
	 * @param \WP_Block $block      Block instance.
	 * @return string Rendered block type output.
	 */
	protected function render( $attributes, $content, $block ) {
		$list_slug = isset( $attributes['listName'] ) && is_string( $attributes['listName'] ) && '' !== $attributes['listName']
			? sanitize_title( $attributes['listName'] )
			: 'saved-for-later';

		$column_count = isset( $attributes['layout']['columnCount'] ) && is_numeric( $attributes['layout']['columnCount'] )
			? max( 1, (int) $attributes['layout']['columnCount'] )
			: 3;

		$variation = $this->get_variation_config( $list_slug );

		wp_enqueue_script_module( $this->get_full_block_name() );

		$consent = 'I acknowledge that using private APIs means my theme or plugin will inevitably break in the next version of WooCommerce';
		BlocksSharedState::load_store_config( $consent );
		BlocksSharedState::load_placeholder_image( $consent );
		// `Move to cart` calls into the shared cart store, which expects
		// `state.cart.items` and friends. Without this load the cart store
		// would have no hydrated cart and the action would throw on the
		// first click.
		BlocksSharedState::load_cart_state( $consent );

		$items = $this->prefetch_list_items( $list_slug );

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
					$list_slug => array(
						'items'     => $items,
						'isLoading' => false,
						'error'     => null,
					),
				),
			)
		);

		// Only the templates the JS getters consume need to flow through
		// `wp_interactivity_config`. Visible strings (empty / error /
		// action label) are rendered server-side and toggled with
		// directives, so no need to duplicate them here.
		// NOTE: this config is global per namespace, so multiple
		// shopper-collection blocks on the same page would clobber each
		// other's labels. v1 ships with one collection per page; if we
		// later need multi-instance, move these into per-block context.
		// TODO: scope these labels per list type once a second variation
		// (Wishlist, etc.) lands. Two shopper-collection blocks on the
		// same page would otherwise share one set of templates — the
		// last one rendered wins, so a Wishlist row would say
		// "Quantity: 3" using Saved-for-later's wording (or the other
		// way around). Move into `data-wp-context` keyed by listSlug,
		// or namespace the config keys (e.g.
		// `quantityLabelTemplate.{slug}`) and update the JS getters to
		// pick the right one off the per-row context.
		wp_interactivity_config(
			'woocommerce/shopper-collection',
			array(
				'quantityLabelTemplate' => $variation['quantityLabelTemplate'],
				'removeLabelTemplate'   => $variation['removeLabelTemplate'],
			)
		);

		$wrapper_class = sprintf(
			'wc-block-shopper-collection wc-block-shopper-collection--%s',
			$variation['modifierSlug']
		);

		$wrapper_attributes = array(
			'class'               => $wrapper_class,
			'data-wp-interactive' => 'woocommerce/shopper-collection',
			'data-wp-context'     => (string) wp_json_encode( array( 'listSlug' => $list_slug ) ),
			// Deterministic key derived from the list slug so iAPI router
			// navigations land on the same block identity across renders.
			'data-wp-key'         => $this->get_full_block_name() . '-' . $list_slug,
			'style'               => sprintf( '--wc-shopper-collection-columns:%d;', $column_count ),
		);

		$is_empty = empty( $items );

		return sprintf(
			'<ul %1$s>%2$s%3$s%4$s%5$s</ul>',
			get_block_wrapper_attributes( $wrapper_attributes ),
			$this->render_template_markup( $variation ),
			$this->render_items_markup( $items, $variation ),
			$this->render_empty_markup( $is_empty, $variation ),
			$this->render_error_markup( $variation )
		);
	}

	/**
	 * Per-list-type rendering config. Centralises every string and
	 * behaviour switch that differs across list types so the renderer
	 * stays generic.
	 *
	 * Adding a new list type = add a new entry here. Future variations
	 * that need behaviours the existing JS actions don't cover should
	 * also add a new `actions.onClickXyz` in `frontend.ts` and point
	 * `actionDirective` at it.
	 *
	 * Unknown slugs fall back to `saved-for-later` so the block still
	 * renders, with a generic copy.
	 *
	 * @param string $list_slug The list slug.
	 * @return array<string, mixed>
	 */
	private function get_variation_config( string $list_slug ): array {
		$variations = array(
			'saved-for-later' => array(
				'modifierSlug'          => 'saved-for-later',
				'emptyMessage'          => __( 'Nothing saved yet — items you save from the cart will appear here.', 'woocommerce' ),
				'errorMessage'          => __( 'Something went wrong — please try again.', 'woocommerce' ),
				'removeButtonLabel'     => __( 'Remove', 'woocommerce' ),
				/* translators: %s: product name. */
				'removeLabelTemplate'   => __( 'Remove %s from saved items', 'woocommerce' ),
				/* translators: %d: quantity of saved items. */
				'quantityLabelTemplate' => __( 'Quantity: %d', 'woocommerce' ),
				'actionLabel'           => __( 'Move to cart', 'woocommerce' ),
				'actionDirective'       => 'actions.onClickMoveToCart',
				'showAction'            => true,
			),
		);

		return $variations[ $list_slug ] ?? $variations['saved-for-later'];
	}

	/**
	 * Prefetch the items in a list via `rest_do_request()`. Logged-out users
	 * short-circuit to an empty list — the route requires authentication and
	 * we don't want to fire an API call that's only going to 401.
	 *
	 * @param string $list_slug The list slug.
	 * @return array<int, array<string, mixed>> Items in the schema response shape.
	 */
	private function prefetch_list_items( string $list_slug ): array {
		if ( ! is_user_logged_in() ) {
			return array();
		}

		$request  = new \WP_REST_Request( 'GET', '/wc/store/v1/shopper-lists/' . $list_slug . '/items' );
		$response = rest_do_request( $request );

		if ( $response->is_error() ) {
			$error   = $response->as_error();
			$message = $error instanceof \WP_Error ? $error->get_error_message() : 'Unknown error';
			wc_get_logger()->warning(
				sprintf( 'Shopper Collection prefetch failed: %s', $message ),
				array(
					'source' => 'shopper-collection',
					'data'   => array( 'slug' => $list_slug ),
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
		// the SSR markup helpers below can treat fields uniformly.
		$decoded = json_decode( (string) wp_json_encode( $data ), true );
		return is_array( $decoded ) ? $decoded : array();
	}

	/**
	 * The `<template data-wp-each>` describing how each item is rendered on
	 * the client. Pre-rendered children sit alongside as `data-wp-each-child`
	 * elements so first paint is populated.
	 *
	 * @param array<string, mixed> $variation Per-list-type rendering config.
	 * @return string
	 */
	private function render_template_markup( array $variation ): string {
		$item_class = sprintf(
			'wc-block-shopper-collection-item wc-block-shopper-collection-item--%s',
			$variation['modifierSlug']
		);

		ob_start();
		?>
		<template
			data-wp-each--list-item="state.currentItems"
			data-wp-each-key="context.listItem.key"
		>
			<li class="<?php echo esc_attr( $item_class ); ?>">
				<?php
				// Single anchor for both live products and tombstones. For
				// tombstones `permalink` is empty, so iAPI removes the `href`
				// attribute and the anchor degrades to non-clickable.
				// Image and price markup come pre-formatted from the schema
				// (`image_html`, `price_html`) — `data-wp-watch` callbacks
				// swap each slot's innerHTML on hydrate / state change so we
				// don't reimplement WC's `wc_price` / `wp_get_attachment_image`
				// in JS.
				?>
				<div class="wc-block-components-product-image wc-block-components-product-image--aspect-ratio-auto">
					<a data-wp-bind--href="context.listItem.permalink">
						<span class="wc-block-shopper-collection-item__image-slot" data-wp-context='{"htmlField":"image_html"}' data-wp-watch="callbacks.updateInnerHtml"></span>
					</a>
					<div class="wc-block-components-product-image__inner-container">
						<button
							type="button"
							class="wc-block-shopper-collection-item__remove"
							data-wp-on--click="actions.onClickRemove"
							data-wp-bind--aria-label="state.currentItemRemoveLabel"
						>
							<?php echo esc_html( $variation['removeButtonLabel'] ); ?>
						</button>
					</div>
				</div>
				<h2 class="wp-block-post-title has-text-align-center has-medium-font-size">
					<a data-wp-bind--href="context.listItem.permalink" data-wp-text="state.currentItemDisplayName"></a>
				</h2>
				<span class="wc-block-shopper-collection-item__variation" data-wp-bind--hidden="!state.currentItemVariationLabel" data-wp-text="state.currentItemVariationLabel"></span>
				<div class="price wc-block-components-product-price has-text-align-center has-small-font-size" data-wp-bind--hidden="state.isPriceHidden" data-wp-context='{"htmlField":"price_html"}' data-wp-watch="callbacks.updateInnerHtml"></div>
				<span class="wc-block-shopper-collection-item__quantity" data-wp-text="state.currentItemQuantityLabel"></span>
				<?php if ( ! empty( $variation['showAction'] ) ) : ?>
					<div class="wp-block-button wc-block-components-product-button" data-wp-bind--hidden="state.isMoveToCartHidden">
						<button
							type="button"
							class="wp-block-button__link wp-element-button add_to_cart_button wc-block-components-product-button__button"
							data-wp-on--click="<?php echo esc_attr( $variation['actionDirective'] ); ?>"
						>
							<?php echo esc_html( $variation['actionLabel'] ); ?>
						</button>
					</div>
				<?php endif; ?>
			</li>
		</template>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * Render the SSR markup for each item. JS will reconcile these via
	 * `data-wp-each-child` after hydration.
	 *
	 * @param array<int, array<string, mixed>> $items     Schema-shape items.
	 * @param array<string, mixed>             $variation Per-list-type rendering config.
	 * @return string
	 */
	private function render_items_markup( array $items, array $variation ): string {
		$markup = '';
		foreach ( $items as $item ) {
			$markup .= $this->render_item_markup( $item, $variation );
		}
		return $markup;
	}

	/**
	 * Render a single SSR item.
	 *
	 * @param array<string, mixed> $item      Schema-shape item.
	 * @param array<string, mixed> $variation Per-list-type rendering config.
	 * @return string
	 */
	private function render_item_markup( array $item, array $variation ): string {
		$product_exists  = ! empty( $item['product_exists'] );
		$name            = (string) ( $item['name'] ?? '' );
		$permalink       = (string) ( $item['permalink'] ?? '' );
		$quantity        = (int) ( $item['quantity'] ?? 1 );
		$alt             = html_entity_decode( $name, ENT_QUOTES, 'UTF-8' );
		$image_html      = (string) ( $item['image_html'] ?? '' );
		$price_html      = (string) ( $item['price_html'] ?? '' );
		$variation_label = $this->get_variation_label( $item );

		$quantity_label = sprintf( $variation['quantityLabelTemplate'], $quantity );
		$remove_aria    = sprintf( $variation['removeLabelTemplate'], $alt );

		$is_price_hidden = '' === $price_html;

		$item_class = sprintf(
			'wc-block-shopper-collection-item wc-block-shopper-collection-item--%s',
			$variation['modifierSlug']
		);

		$context = array(
			'listItem' => $item,
		);

		ob_start();
		?>
		<li
			class="<?php echo esc_attr( $item_class ); ?>"
			data-wp-each-child
			<?php // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<?php echo wp_interactivity_data_wp_context( $context ); ?>
		>
			<?php
			// Emit the same structure as the `<template>` so iAPI
			// hydration is a no-op diff. For tombstones the anchor's
			// href is omitted (the anchor degrades to non-clickable
			// rather than self-linking back to the current page).
			// Image and price markup come pre-formatted from the
			// schema (`image_html`, `price_html`) and are echoed into
			// the watcher slots — the JS-side `updateInnerHtml`
			// callback takes over after hydration to swap the slot's
			// innerHTML when the row's context.listItem changes.
			?>
			<div class="wc-block-components-product-image wc-block-components-product-image--aspect-ratio-auto">
				<a <?php echo $product_exists && '' !== $permalink ? 'href="' . esc_url( $permalink ) . '"' : ''; ?> data-wp-bind--href="context.listItem.permalink">
					<span
						class="wc-block-shopper-collection-item__image-slot"
						data-wp-context='{"htmlField":"image_html"}'
						data-wp-watch="callbacks.updateInnerHtml"
					>
						<?php echo wp_kses_post( $image_html ); ?>
					</span>
				</a>
				<div class="wc-block-components-product-image__inner-container">
					<button
						type="button"
						class="wc-block-shopper-collection-item__remove"
						aria-label="<?php echo esc_attr( $remove_aria ); ?>"
						data-wp-on--click="actions.onClickRemove"
						data-wp-bind--aria-label="state.currentItemRemoveLabel"
					>
						<?php echo esc_html( $variation['removeButtonLabel'] ); ?>
					</button>
				</div>
			</div>
			<?php
			// Render the decoded display name as plain text so the SSR
			// output matches what `data-wp-text="state.currentItemDisplayName"`
			// will write after hydration. Names like "Tom &amp; Jerry"
			// must show as "Tom & Jerry" both before and after JS.
			// Same single-anchor pattern as the image — href omitted
			// for tombstones.
			?>
			<h2 class="wp-block-post-title has-text-align-center has-medium-font-size">
				<a <?php echo $product_exists && '' !== $permalink ? 'href="' . esc_url( $permalink ) . '"' : ''; ?> data-wp-bind--href="context.listItem.permalink" data-wp-text="state.currentItemDisplayName"><?php echo esc_html( $alt ); ?></a>
			</h2>
			<?php if ( '' !== $variation_label ) : ?>
				<span class="wc-block-shopper-collection-item__variation"><?php echo esc_html( $variation_label ); ?></span>
			<?php endif; ?>
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
			<span class="wc-block-shopper-collection-item__quantity"><?php echo esc_html( $quantity_label ); ?></span>
			<?php if ( ! empty( $variation['showAction'] ) ) : ?>
				<?php
				// Always emit the wrapper with the same `data-wp-bind--hidden`
				// the template uses, and start it hidden when the SSR-side
				// rule (the row is a tombstone) says so. That way a state
				// change after hydration (e.g. the product is later flagged
				// as deleted) hides the button without iAPI having to swap
				// the entire row out.
				$is_move_to_cart_hidden = ! $product_exists;
				?>
			<div
				class="wp-block-button wc-block-components-product-button"
				data-wp-bind--hidden="state.isMoveToCartHidden"
				<?php
				if ( $is_move_to_cart_hidden ) {
					echo 'hidden';
				}
				?>
			>
				<button
					type="button"
					class="wp-block-button__link wp-element-button add_to_cart_button wc-block-components-product-button__button"
					data-wp-on--click="<?php echo esc_attr( $variation['actionDirective'] ); ?>"
				>
					<?php echo esc_html( $variation['actionLabel'] ); ?>
				</button>
			</div>
			<?php endif; ?>
		</li>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * Render the empty-state markup. Always present in the DOM so JS can
	 * toggle it on once the last item is removed.
	 *
	 * @param bool                 $is_empty  Whether the list is empty on initial paint.
	 * @param array<string, mixed> $variation Per-list-type rendering config.
	 * @return string
	 */
	private function render_empty_markup( bool $is_empty, array $variation ): string {
		$hidden_attr = $is_empty ? '' : ' hidden';
		return sprintf(
			'<li class="wc-block-shopper-collection__empty" data-wp-bind--hidden="!state.isEmpty"%1$s>%2$s</li>',
			$hidden_attr,
			esc_html( $variation['emptyMessage'] )
		);
	}

	/**
	 * Render the error-state markup. Hidden on initial paint and toggled on
	 * by the JS-side `state.hasError` getter when a request fails.
	 *
	 * @param array<string, mixed> $variation Per-list-type rendering config.
	 * @return string
	 */
	private function render_error_markup( array $variation ): string {
		return sprintf(
			'<li class="wc-block-shopper-collection__error" role="alert" data-wp-bind--hidden="!state.hasError" data-wp-text="state.errorMessage" hidden>%s</li>',
			esc_html( $variation['errorMessage'] )
		);
	}

	/**
	 * Build a comma-separated variation label like "Color: Blue, Size: M".
	 *
	 * @param array<string, mixed> $item Schema-shape item.
	 * @return string
	 */
	private function get_variation_label( array $item ): string {
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
