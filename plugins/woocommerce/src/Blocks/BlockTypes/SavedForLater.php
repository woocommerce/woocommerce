<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Blocks\BlockTypes;

use Automattic\WooCommerce\Blocks\Utils\BlocksSharedState;
use Automattic\WooCommerce\Internal\ShopperLists\ShopperListRenderer;
use Automattic\WooCommerce\Proxies\LegacyProxy;

/**
 * Saved for Later block. Renders the shopper's Saved for Later list from the `shopper-lists` Store API,
 * sharing state with the cart via the `woocommerce/shopper-lists` iAPI store. Shared row markup lives in
 * {@see ShopperListRenderer}. Adds Block Hooks auto-injection, the empty-state guard, the quantity span,
 * and the Move-to-cart action.
 */
final class SavedForLater extends AbstractBlock {
	/**
	 * Slug of the shopper list this block renders.
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

		// `BlockHooksTrait` is not used here because of PHPStan issues with the trait's annotations.
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

		// Skip injection if the block is already present in the cart page content.
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

		// Seed a `core/heading` inner block so auto-injected instances carry the editor template's heading.
		// Append unconditionally: checking `empty( $parsed_hooked_block['innerBlocks'] )` would suppress this
		// heading whenever another extension already hooked into `hooked_block_woocommerce/saved-for-later`.
		//
		// `core/heading` is a static block, so the serialised markup must match the editor-saved form
		// (`<h2 class="wp-block-heading">…</h2>`) or block validation will fail when the cart page is opened
		// in the editor. `attrs.content` mirrors the editor template's seed (`{ content, level }`) so the
		// parsed shape round-trips identically. The value is the raw string because attrs are JSON-encoded
		// into the block comment delimiter, and `esc_html()` would corrupt translations containing `&`, `<`,
		// etc. The matching `null` push onto `innerContent` is required for `WP_Block::render()` to descend
		// into the heading when assembling `$content`.
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
		// Guests have no personal list. Bail before enqueuing assets or seeding state.
		if ( ! is_user_logged_in() ) {
			return '';
		}

		if ( wc_get_container()->get( LegacyProxy::class )->call_function( 'is_cart' ) ) {
			$this->asset_data_registry->add( 'cartPageHasSavedForLater', true );
		}

		// Clamp to the 2-6 range supported by the SCSS.
		$column_count = min( 6, max( 2, absint( $attributes['columnCount'] ?? 5 ) ) );

		wp_enqueue_script_module( $this->get_full_block_name() );

		$consent = 'I acknowledge that using private APIs means my theme or plugin will inevitably break in the next version of WooCommerce';
		BlocksSharedState::load_store_config( $consent );
		BlocksSharedState::load_placeholder_image( $consent );
		// Required so the Move-to-cart action has a hydrated cart store to dispatch into.
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

		// Sprintf templates passed through `wp_interactivity_config` for JS-side interpolation.
		wp_interactivity_config(
			'woocommerce/saved-for-later',
			array(
				'quantityLabelTemplate' => $this->get_quantity_label_template(),
				'removeLabelTemplate'   => $this->get_remove_label_template(),
			)
		);

		// `hasShownItems` seeds the per-block context that controls the empty message.
		$wrapper_attributes = array(
			'class'                     => 'wc-block-saved-for-later',
			'data-wp-interactive'       => 'woocommerce/saved-for-later',
			'data-wp-context'           => (string) wp_json_encode(
				array(
					'hasShownItems' => ! empty( $items ),
					// `stdClass` so JSON serializes as `{}` rather than `[]`.
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
	 * Prefetch the saved-for-later items via `rest_do_request()`. Returns an empty
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
			. $this->render_template_quantity()
			. $this->render_template_move_to_cart();
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
	 * Render a single SSR item, combining the shared row markup with the quantity span and Move-to-cart button.
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
	 * Template-mode markup for the quantity span.
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
	 * Template-mode markup for the Move-to-cart action button.
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
	 * SSR-mode markup for the quantity span.
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
	 * SSR-mode markup for the Move-to-cart action button. The wrapper is always emitted so iAPI can toggle
	 * `hidden` after hydration. Starts hidden when the row is not purchasable.
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
	 * Wrap the inner-block content in a wrapper that mirrors the empty-state visibility. Hidden until
	 * `context.hasShownItems` flips to `true`. Returns an empty string when no content needs wrapping.
	 *
	 * @param string $content  Rendered inner-block content (usually the heading HTML).
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
	 * Render the empty-state markup. Always present in the DOM so iAPI can reveal it once the last item is
	 * removed. Initially hidden: `state.isEmpty` requires the `hasShownItems` context flag to flip first.
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
	 * Sprintf template for the per-row quantity label. Shared between PHP SSR and the JS-side getter
	 * (seeded via `wp_interactivity_config`) so both paths produce identical output.
	 */
	private function get_quantity_label_template(): string {
		/* translators: %d: quantity of saved items. */
		return __( 'Quantity: %d', 'woocommerce' );
	}

	/**
	 * Sprintf template for the per-row remove button's aria-label. Shared between PHP SSR and JS.
	 */
	private function get_remove_label_template(): string {
		/* translators: %s: product name. */
		return __( 'Remove %s from Saved for later list', 'woocommerce' );
	}

	/**
	 * Visible label for the Move-to-cart action button. Used by the iAPI `<template>` and the SSR markup.
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
