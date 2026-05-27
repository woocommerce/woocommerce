<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Blocks\BlockTypes;

use Automattic\WooCommerce\Blocks\Utils\BlocksSharedState;
use Automattic\WooCommerce\Internal\ShopperLists\ShopperListRenderer;
use Automattic\WooCommerce\Proxies\LegacyProxy;

/**
 * Saved for Later block.
 *
 * Renders the shopper's "Saved for Later" list, wired to the `shopper-lists`
 * Store API endpoints via the shared `woocommerce/shopper-lists` iAPI store.
 * PHP prefetches the list so the first paint is already populated; JS then
 * takes over for adds, removes, and Move-to-cart.
 *
 * The row markup (image, name, price, remove badge, variation overlay) is
 * shared with other shopper-list blocks via `ShopperListRenderer`. This
 * class composes those fragments and adds the bits that are unique to
 * Saved for Later: auto-injection via the Block Hooks API, the
 * `hasShownItems` empty-state gating, the per-row quantity span, and the
 * Move-to-cart action button.
 */
final class SavedForLater extends AbstractBlock {
	/**
	 * The list slug this block renders. Constant — when additional list
	 * types ship as their own blocks (e.g. Wishlist), each one will
	 * hardcode its own slug.
	 */
	private const LIST_SLUG = 'saved-for-later';

	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'saved-for-later';

	/**
	 * Initialize this block type.
	 */
	protected function initialize(): void {
		parent::initialize();

		// We do not use `BlockHooksTrait` currently as it has issues with PHPStan.
		add_filter( 'hooked_block_types', array( $this, 'register_hooked_block' ), 9, 4 );
		add_filter( 'hooked_block_woocommerce/saved-for-later', array( $this, 'set_hooked_block_attributes' ), 10, 4 );
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
	 * Seed a default heading inner block on the auto-injected block.
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

		// Seed a `core/heading` inner block so freshly-injected instances
		// ship with the same heading the editor template seeds. We append
		// unconditionally — extensions are free to hook
		// `hooked_block_woocommerce/saved-for-later` to add their own
		// inner blocks, and gating on `empty( innerBlocks )` would silently
		// suppress our heading whenever any other extension ran first.
		//
		// `core/heading` is a static block, so the serialised markup must
		// match what the editor would have saved (`<h2 class="wp-block-heading">…</h2>`)
		// or it'll fail block validation when the cart page is opened in the
		// editor. `attrs.content` mirrors what the editor's template seeds
		// (`{ content, level }`) so the parsed shape round-trips identically;
		// the value is the raw string because attrs are JSON-encoded into the
		// block comment and `esc_html()` would corrupt translations whose text
		// contains `&`, `<`, etc. The matching `null` push onto `innerContent`
		// is what makes `WP_Block::render()` walk into the heading when
		// building `$content`.
		$list_heading = __( 'Saved for later', 'woocommerce' );
		$heading_html = '<h2 class="wp-block-heading">' . esc_html( $list_heading ) . '</h2>';

		if ( ! isset( $parsed_hooked_block['innerBlocks'] ) || ! is_array( $parsed_hooked_block['innerBlocks'] ) ) {
			$parsed_hooked_block['innerBlocks'] = array();
		}
		$parsed_hooked_block['innerBlocks'][] = array(
			'blockName'    => 'core/heading',
			'attrs'        => array(
				'level'   => 2,
				'content' => $list_heading,
			),
			'innerBlocks'  => array(),
			'innerHTML'    => $heading_html,
			'innerContent' => array( $heading_html ),
		);
		if ( ! isset( $parsed_hooked_block['innerContent'] ) || ! is_array( $parsed_hooked_block['innerContent'] ) ) {
			$parsed_hooked_block['innerContent'] = array();
		}
		$parsed_hooked_block['innerContent'][] = null;

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

		// Set from render() (not Cart::enqueue_data via has_block()) so it works when this
		// block is auto-injected via the Block Hooks API and isn't in stored post_content.
		if ( wc_get_container()->get( LegacyProxy::class )->call_function( 'is_cart' ) ) {
			$this->asset_data_registry->add( 'cartPageHasSavedForLater', true );
		}

		// Clamp to the 2-6 range the SCSS `@for $i from 2 through 6` loop and
		// the editor `RangeControl` both support. `absint()` first defends
		// against a code-editor override (the attribute can be set to any
		// JSON value there); the `min`/`max` then keep the value within the
		// range where a `&.columns-#{$i}` rule actually exists.
		$column_count = min( 6, max( 2, absint( $attributes['columnCount'] ?? 5 ) ) );

		wp_enqueue_script_module( $this->get_full_block_name() );

		$consent = 'I acknowledge that using private APIs means my theme or plugin will inevitably break in the next version of WooCommerce';
		BlocksSharedState::load_store_config( $consent );
		BlocksSharedState::load_placeholder_image( $consent );
		// `Move to cart` calls into the shared cart store, which expects
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

		// Templates flow through `wp_interactivity_config` so the JS-side
		// getters can interpolate them (`%d`, `%s`). Visible strings (empty
		// state, error, action label) are rendered server-side and toggled
		// with directives, so they don't need to ride here too.
		wp_interactivity_config(
			'woocommerce/saved-for-later',
			array(
				'quantityLabelTemplate' => $this->get_quantity_label_template(),
				'removeLabelTemplate'   => $this->get_remove_label_template(),
			)
		);

		// `hasShownItems` seeds the per-block context so the empty message
		// stays hidden for new shoppers who land on a page with nothing
		// saved. The JS-side watcher flips it to `true` the first time the
		// list has any items (whether that's the SSR seed or a runtime add
		// via "Save for later"), and `state.isEmpty` only flips on when the
		// flag is set *and* the list is currently empty. The flag lives in
		// the per-block context, so it naturally resets on every full page
		// load — no extra Store API field or persisted flag needed.
		// `data-wp-context---notices` seeds the store-notices namespace
		// alongside the block's own context on the same wrapper.
		$wrapper_attributes = array(
			'class'                     => 'wc-block-saved-for-later',
			'data-wp-interactive'       => 'woocommerce/saved-for-later',
			'data-wp-context'           => (string) wp_json_encode(
				array(
					'hasShownItems' => ! empty( $items ),
					// `stdClass` so it serialises as `{}`, not `[]` —
					// iAPI's reactive proxy only fires updates on object
					// writes, not array expandos.
					'pendingKeys'   => new \stdClass(),
				)
			),
			'data-wp-context---notices' => 'woocommerce/store-notices::' . (string) wp_json_encode( array( 'notices' => array() ) ),
			'data-wp-watch'             => 'callbacks.trackShownItems',
		);

		$list_class = sprintf( 'wc-block-saved-for-later__list columns-%d', $column_count );

		$ul_inner    = $this->render_template_markup() . $this->render_items_markup( $items ) . $this->render_empty_markup();
		$before_list = $this->render_header_markup( $content, empty( $items ) ) . ShopperListRenderer::render_interactivity_notices_region( 'wc-block-saved-for-later__notices' );

		return ShopperListRenderer::render_grid_wrapper( $wrapper_attributes, $list_class, $ul_inner, $before_list );
	}

	/**
	 * Prefetch the saved-for-later items via `rest_do_request()`. Logged-out
	 * users short-circuit to an empty list — the route requires authentication
	 * and we don't want to fire an API call that's only going to 401.
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
			// for ops to act on. Anyone investigating a regression can
			// flip the WC logger to debug to surface them.
			wc_get_logger()->debug(
				sprintf( 'Saved for Later prefetch failed: %s', $message ),
				array(
					'source' => 'saved-for-later',
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
		// the SSR markup helpers below can treat fields uniformly.
		$decoded = json_decode( (string) wp_json_encode( $data ), true );
		return is_array( $decoded ) ? $decoded : array();
	}

	/**
	 * The `<template data-wp-each>` describing how each item is rendered on
	 * the client. Pre-rendered children sit alongside as `data-wp-each-child`
	 * elements so first paint is populated. Composes the shared row markup
	 * with Saved for Later's quantity span and Move-to-cart action button.
	 *
	 * @return string
	 */
	private function render_template_markup(): string {
		$row_inner = ShopperListRenderer::render_template_common_row()
			. $this->render_template_quantity()
			. $this->render_template_move_to_cart();
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
	 * markup with the SFL-specific quantity span and Move-to-cart button.
	 *
	 * @param array<string, mixed> $item Schema-shape item.
	 * @return string
	 */
	private function render_item_markup( array $item ): string {
		$row_inner = ShopperListRenderer::render_ssr_common_row( $item, $this->get_remove_label_template() )
			. $this->render_ssr_quantity( $item )
			. $this->render_ssr_move_to_cart( $item );
		return ShopperListRenderer::render_each_child( $item, $row_inner );
	}

	/**
	 * Template-mode markup for the quantity span. SFL-specific — Wishlist
	 * has no quantity column.
	 *
	 * @return string
	 */
	private function render_template_quantity(): string {
		return sprintf(
			'<span class="%s__quantity" data-wp-text="state.currentItemQuantityLabel"></span>',
			esc_attr( ShopperListRenderer::ROW_CLASS )
		);
	}

	/**
	 * Template-mode markup for the Move-to-cart action button. SFL-specific.
	 *
	 * @return string
	 */
	private function render_template_move_to_cart(): string {
		ob_start();
		?>
		<div class="wp-block-button wc-block-components-product-button" data-wp-bind--hidden="state.isMoveToCartHidden">
			<button
				type="button"
				class="wp-block-button__link wp-element-button add_to_cart_button wc-block-components-product-button__button"
				data-wp-on--click="actions.onClickMoveToCart"
				data-wp-bind--disabled="state.isCurrentItemPending"
			>
				<?php echo esc_html( $this->get_move_to_cart_label() ); ?>
			</button>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * SSR-mode markup for the quantity span. SFL-specific.
	 *
	 * @param array<string, mixed> $item Schema-shape item.
	 * @return string
	 */
	private function render_ssr_quantity( array $item ): string {
		$quantity       = (int) ( $item['quantity'] ?? 1 );
		$quantity_label = sprintf( $this->get_quantity_label_template(), $quantity );
		return sprintf(
			'<span class="%s__quantity">%s</span>',
			esc_attr( ShopperListRenderer::ROW_CLASS ),
			esc_html( $quantity_label )
		);
	}

	/**
	 * SSR-mode markup for the Move-to-cart action button. SFL-specific.
	 * Always emits the wrapper so iAPI can toggle `hidden` after hydration
	 * without swapping the row out. Starts hidden when the row isn't
	 * purchasable.
	 *
	 * @param array<string, mixed> $item Schema-shape item.
	 * @return string
	 */
	private function render_ssr_move_to_cart( array $item ): string {
		$is_move_to_cart_hidden = empty( $item['is_purchasable'] );
		ob_start();
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
				data-wp-on--click="actions.onClickMoveToCart"
				data-wp-bind--disabled="state.isCurrentItemPending"
			>
				<?php echo esc_html( $this->get_move_to_cart_label() ); ?>
			</button>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * Wrap the inner-block content (heading + any future siblings) in an
	 * element whose visibility mirrors the empty-state gating: hidden when
	 * the shopper has never seen items in this session, revealed once
	 * `context.hasShownItems` flips to `true`. Returns an empty string when
	 * there's no content to wrap (e.g. merchant deleted the heading and
	 * saved), so we don't emit an empty `<div>`.
	 *
	 * @param string $content  Rendered inner-block content (typically the heading HTML).
	 * @param bool   $is_empty Whether the saved-for-later list is empty on initial paint.
	 * @return string
	 */
	private function render_header_markup( string $content, bool $is_empty ): string {
		if ( '' === $content ) {
			return '';
		}
		$hidden_attr = $is_empty ? ' hidden' : '';
		return sprintf(
			'<div class="wc-block-saved-for-later__header" data-wp-bind--hidden="!context.hasShownItems"%s>%s</div>',
			$hidden_attr,
			$content
		);
	}

	/**
	 * Render the empty-state markup. Always present in the DOM so JS can
	 * toggle it on once the last item is removed. Initially hidden: SSR
	 * never shows the message, since `state.isEmpty` requires the JS-side
	 * `hasShownItems` context flag to flip first.
	 *
	 * @return string
	 */
	private function render_empty_markup(): string {
		return ShopperListRenderer::render_empty_state(
			__( 'Nothing saved yet — items you save from the cart will appear here.', 'woocommerce' ),
			'wc-block-saved-for-later__empty',
			true
		);
	}

	/**
	 * Sprintf template for the per-row quantity label. Used both by PHP SSR
	 * (`render_ssr_quantity()`) and by the JS-side getter (via
	 * `wp_interactivity_config`) so both paths produce the same string after
	 * `%d` interpolation.
	 */
	private function get_quantity_label_template(): string {
		/* translators: %d: quantity of saved items. */
		return __( 'Quantity: %d', 'woocommerce' );
	}

	/**
	 * Sprintf template for the per-row remove button's aria-label. Same dual
	 * use as the quantity template.
	 */
	private function get_remove_label_template(): string {
		/* translators: %s: product name. */
		return __( 'Remove %s from Saved for later list', 'woocommerce' );
	}

	/**
	 * Visible label for the move-to-cart action button, used by both the
	 * iAPI `<template>` and the SSR per-row markup.
	 */
	private function get_move_to_cart_label(): string {
		return __( 'Move to cart', 'woocommerce' );
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
	 * to enqueue their CSS whenever Saved for Later renders.
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
