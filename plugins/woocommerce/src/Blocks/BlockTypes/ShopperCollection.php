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
	 * Initialize this block type.
	 */
	protected function initialize(): void {
		parent::initialize();

		// We do not use `BlockHooksTrait` currently as it has issues with PHPStan.
		add_filter( 'hooked_block_types', array( $this, 'register_hooked_block' ), 9, 4 );
		add_filter( 'hooked_block_woocommerce/shopper-collection', array( $this, 'set_hooked_block_attributes' ), 10, 4 );
	}

	/**
	 * Auto-inject this block after `woocommerce/cart`, scoped to the cart page.
	 *
	 * @param array                                  $hooked_block_types Block names hooked at this position.
	 * @param string                                 $relative_position  Position of the insertion point.
	 * @param string                                 $anchor_block_type  Anchor block name.
	 * @param array|\WP_Post|\WP_Block_Template|null $context            Where the block is being embedded.
	 * @return array
	 */
	public function register_hooked_block( $hooked_block_types, $relative_position, $anchor_block_type, $context ) {
		if ( 'after' !== $relative_position || 'woocommerce/cart' !== $anchor_block_type ) {
			return $hooked_block_types;
		}

		// `wc_get_page_id()` returns -1 when the page option isn't set.
		$cart_page_id = (int) wc_get_page_id( 'cart' );
		if ( $cart_page_id <= 0 || ! ( $context instanceof \WP_Post ) || (int) $context->ID !== $cart_page_id ) {
			return $hooked_block_types;
		}

		// Don't double-inject if the block is already in the cart page
		// content.
		if ( has_block( $this->get_full_block_name(), $context ) ) {
			return $hooked_block_types;
		}

		$hooked_block_types[] = $this->get_full_block_name();
		return $hooked_block_types;
	}

	/**
	 * Set the `listName` attribute on the auto-injected block.
	 *
	 * @param array|null $parsed_hooked_block The parsed hooked block array, or null to suppress insertion.
	 * @param string     $hooked_block_type   The hooked block type name.
	 * @param string     $relative_position   Position of the insertion point.
	 * @param array      $parsed_anchor_block The anchor block, in parsed block array format.
	 * @return array|null
	 */
	public function set_hooked_block_attributes( $parsed_hooked_block, $hooked_block_type, $relative_position, $parsed_anchor_block ) {
		if ( null === $parsed_hooked_block || 'after' !== $relative_position ) {
			return $parsed_hooked_block;
		}
		if ( ! isset( $parsed_anchor_block['blockName'] ) || 'woocommerce/cart' !== $parsed_anchor_block['blockName'] ) {
			return $parsed_hooked_block;
		}
		$parsed_hooked_block['attrs']['listName'] = 'saved-for-later';
		return $parsed_hooked_block;
	}

	/**
	 * Render the block.
	 *
	 * @param array     $attributes Block attributes.
	 * @param string    $content    Block content.
	 * @param \WP_Block $block      Block instance.
	 * @return string Rendered block type output.
	 */
	protected function render( $attributes, $content, $block ) {
		// Guests have no personal list — bail before enqueuing assets or seeding state.
		if ( ! is_user_logged_in() ) {
			return '';
		}

		// `listName` is declared with a default in block.json, so WP core's
		// `prepare_attributes_for_render` guarantees it's set as a string by
		// the time we get here. `sanitize_title` is *not* redundant though:
		// the schema only enforces `type: string`, and the block editor's
		// "Edit as HTML" path lets anyone override the attribute with an
		// arbitrary string. The slug then flows into a REST URL, into the
		// `data-wp-context` JSON, into `data-wp-key`, and into the
		// `wc-block-shopper-collection--{slug}` CSS modifier class — so we
		// normalize it to `[a-z0-9_-]+` here. Empty result falls back to the
		// declared default.
		$list_slug = sanitize_title( $attributes['listName'] );
		if ( '' === $list_slug ) {
			$list_slug = 'saved-for-later';
		}

		// `layout` comes from `supports.layout`, not a declared attribute, so WP doesn't guarantee every nested key is set — `??` covers a partial layout object.
		$column_count = max( 1, (int) ( $attributes['layout']['columnCount'] ?? 3 ) );

		$variation = $this->get_variation_config();

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
		// phpcs:disable Generic.Commenting.Todo.TaskFound
		// TODO: scope these labels per list type once a second variation
		// (Wishlist, etc.) lands. Two shopper-collection blocks on the
		// same page would otherwise share one set of templates — the
		// last one rendered wins, so a Wishlist row would say
		// "Quantity: 3" using Saved-for-later's wording (or the other
		// way around). Move into `data-wp-context` keyed by listSlug,
		// or namespace the config keys (e.g.
		// `quantityLabelTemplate.{slug}`) and update the JS getters to
		// pick the right one off the per-row context.
		// phpcs:enable Generic.Commenting.Todo.TaskFound
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
			$this->render_error_markup()
		);
	}

	/**
	 * Per-list-type rendering config. Centralises every string and
	 * behaviour switch that differs across list types so the renderer
	 * stays generic.
	 *
	 * Today only `saved-for-later` exists, so the config is returned
	 * verbatim. When a second list type lands (Wishlist, Recently
	 * Viewed, etc.), turn this into a lookup keyed by the list slug
	 * and add the slug as a parameter. Unknown slugs in that future
	 * lookup should render the empty state or surface an error rather
	 * than silently borrow another list type's copy.
	 *
	 * @return array<string, mixed>
	 */
	private function get_variation_config(): array {
		return array(
			'modifierSlug'          => 'saved-for-later',
			'emptyMessage'          => __( 'Nothing saved yet — items you save from the cart will appear here.', 'woocommerce' ),
			/* translators: %s: product name. */
			'removeLabelTemplate'   => __( 'Remove %s from Saved for later list', 'woocommerce' ),
			/* translators: %d: quantity of saved items. */
			'quantityLabelTemplate' => __( 'Quantity: %d', 'woocommerce' ),
			'actionLabel'           => __( 'Move to cart', 'woocommerce' ),
			'actionDirective'       => 'actions.onClickMoveToCart',
			'showAction'            => true,
		);
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
			// Logged at debug level on purpose: prefetch failures are
			// often transient (network blips, auth refresh races) and
			// the user-visible behaviour is the empty state — nothing
			// for ops to act on. Anyone investigating a regression can
			// flip the WC logger to debug to surface them.
			wc_get_logger()->debug(
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
					<button
						type="button"
						class="wc-block-shopper-collection-item__remove"
						data-wp-on--click="actions.onClickRemove"
						data-wp-bind--aria-label="state.currentItemRemoveLabel"
					>
						<?php echo $this->get_remove_icon_svg(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG markup. ?>
					</button>
					<span class="wc-block-shopper-collection-item__variation" data-wp-bind--hidden="!state.currentItemVariationLabel" data-wp-text="state.currentItemVariationLabel"></span>
				</div>
				<h2 class="wp-block-post-title has-text-align-center has-medium-font-size">
					<a data-wp-bind--href="context.listItem.permalink" data-wp-text="state.currentItemDisplayName"></a>
				</h2>
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
				<button
					type="button"
					class="wc-block-shopper-collection-item__remove"
					aria-label="<?php echo esc_attr( $remove_aria ); ?>"
					data-wp-on--click="actions.onClickRemove"
					data-wp-bind--aria-label="state.currentItemRemoveLabel"
				>
					<?php echo $this->get_remove_icon_svg(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG markup. ?>
				</button>
				<span
					class="wc-block-shopper-collection-item__variation"
					data-wp-bind--hidden="!state.currentItemVariationLabel"
					data-wp-text="state.currentItemVariationLabel"
					<?php
					if ( '' === $variation_label ) {
						echo 'hidden';
					}
					?>
				><?php echo esc_html( $variation_label ); ?></span>
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
	 * by the JS-side `state.hasError` getter when a request fails. The text
	 * content is left empty here on purpose: errors only happen post-hydration,
	 * and `data-wp-text="state.errorMessage"` writes the actual server-supplied
	 * message — surfacing that is more useful than a generic fallback.
	 *
	 * phpcs:disable Generic.Commenting.Todo.TaskFound
	 *
	 * TODO: replace this in-list error row with a `store-notices` notice
	 * rendered above the list. Mini-cart's pattern (cart.ts → showNoticeError →
	 * `@woocommerce/stores/store-notices`) is the right reference: dispatch
	 * the server message there from the JS catch blocks in shopper-lists.ts
	 * instead of holding it on `list.error`. That gives users a dismissible,
	 * stylistically-aligned notice rather than a row tucked into the grid.
	 *
	 * phpcs:enable Generic.Commenting.Todo.TaskFound
	 *
	 * @return string
	 */
	private function render_error_markup(): string {
		return '<li class="wc-block-shopper-collection__error" role="alert" data-wp-bind--hidden="!state.hasError" data-wp-text="state.errorMessage" hidden></li>';
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
	private function get_remove_icon_svg(): string {
		return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" aria-hidden="true" focusable="false"><path fill="currentColor" fill-rule="evenodd" clip-rule="evenodd" d="M12 5.5A2.25 2.25 0 0 0 9.878 7h4.244A2.251 2.251 0 0 0 12 5.5ZM12 4a3.751 3.751 0 0 0-3.675 3H5v1.5h1.27l.818 8.997a2.75 2.75 0 0 0 2.739 2.501h4.347a2.75 2.75 0 0 0 2.738-2.5L17.73 8.5H19V7h-3.325A3.751 3.751 0 0 0 12 4Zm4.224 4.5H7.776l.806 8.861a1.25 1.25 0 0 0 1.245 1.137h4.347a1.25 1.25 0 0 0 1.245-1.137l.805-8.861Z"/></svg>';
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
	 * Returning null lets WP use the `style` array from block.json, which
	 * lists this block's own stylesheet plus the atomic
	 * product-image / product-price / product-button stylesheets we
	 * borrow class names from. We can't render those atomic blocks as
	 * inner blocks (they rely on WP_Query / $post loop context, which
	 * this block doesn't have — it hydrates from a Store API call), so
	 * declaring them as style dependencies is the only way to get WP
	 * to enqueue their CSS whenever Shopper Collection renders.
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
