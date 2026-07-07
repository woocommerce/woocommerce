<?php
/**
 * Plugin Name: WooCommerce Gift Note Demo (Shared iAPI stores reference extension)
 * Description: Reference extension proving the shared iAPI stores extension contract (T8). A gift-note field on the Add to Cart + Options form that travels to the cart line, splits lines by note, and demos the findItem({ filter }) escape hatch. Contains ZERO submission code and ZERO core changes.
 * Plugin URI: https://github.com/woocommerce/woocommerce
 * Author: WooCommerce
 * Text Domain: wc-gift-note-demo
 *
 * @package wc-gift-note-demo
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The extension namespace. It is used verbatim as the BARE-NAMESPACE extension
 * prop, and the identity convention requires ONE shape everywhere — the prop's
 * value is always `array( WC_GIFT_NOTE_DEMO_ITEM_KEY => <string> )`:
 *
 * 1. the draft's payload-root prop (`draft[ NS ] = array( 'gift-note' => ... )`,
 *    written client-side by the field module),
 * 2. the `add-item` request prop the server reads (`$request[ NS ]['gift-note']`),
 * 3. the cart-item `extensions[ NS ]` machine-readable projection (same shape),
 *    which core deep-compares against the draft prop, and
 * 4. the iAPI store the field/badge modules register.
 */
const WC_GIFT_NOTE_DEMO_NS       = 'wc-gift-note-demo';
const WC_GIFT_NOTE_DEMO_ITEM_KEY = 'gift-note';

/**
 * Register the two demo blocks from their block.json manifests.
 *
 * `register_block_type` reads `viewScriptModule` and registers the buildless ES
 * module for us. We only need to add the shared-store module dependencies (which
 * cannot be expressed in block.json for an arbitrary module id) so the cart
 * store is registered before our field/badge modules run.
 */
add_action(
	'init',
	function () {
		$field_dir = __DIR__ . '/wc-gift-note-demo/gift-note-field';
		$badge_dir = __DIR__ . '/wc-gift-note-demo/gift-note-badge';

		if ( file_exists( $field_dir . '/block.json' ) ) {
			register_block_type( $field_dir );
		}
		if ( file_exists( $badge_dir . '/block.json' ) ) {
			register_block_type( $badge_dir );
		}
	}
);

/**
 * Make our buildless view modules depend on the shared cart store module, so
 * `store('woocommerce/cart')` is registered by the time they evaluate.
 *
 * block.json's `viewScriptModule` cannot declare a dependency on an arbitrary
 * module id, so we amend the registered module dependencies here. The module id
 * WordPress assigns to a `file:` view module is the block name (e.g.
 * `wc-gift-note-demo/field`).
 */
add_action(
	'init',
	function () {
		if ( ! function_exists( 'wp_register_script_module' ) ) {
			return;
		}

		// Re-registering with the same id + deps is how core lets us amend a
		// module's dependency list; the src is resolved from the block.json
		// registration, so we pass the same file path.
		$deps = array( '@woocommerce/stores/woocommerce/cart', '@wordpress/interactivity' );

		wp_register_script_module(
			'wc-gift-note-demo/field',
			plugins_url( 'wc-gift-note-demo/gift-note-field/view.js', __FILE__ ),
			$deps,
			filemtime( __DIR__ . '/wc-gift-note-demo/gift-note-field/view.js' )
		);

		wp_register_script_module(
			'wc-gift-note-demo/badge',
			plugins_url( 'wc-gift-note-demo/gift-note-badge/view.js', __FILE__ ),
			$deps,
			filemtime( __DIR__ . '/wc-gift-note-demo/gift-note-badge/view.js' )
		);
	},
	20
);

/**
 * Inject the gift-note field into the Add to Cart + Options form.
 *
 * DX FINDING (the single biggest friction of this task): the CANONICAL way to
 * put a field into the form is a `blockHooks` entry (`before` the quantity
 * selector) in block.json. It does not work reliably here, for TWO reasons:
 *
 * 1. The Add to Cart + Options block renders its template PART via a bare
 *    `do_blocks( $template_part_contents )`, and a bare `do_blocks()` does NOT
 *    apply Block Hooks (verified in isolation: `get_hooked_blocks()` lists the
 *    field, `apply_block_hooks_to_content()` inserts it, plain `do_blocks()`
 *    does not). So a field hooked to a block INSIDE the form is not inserted by
 *    the form's own render path.
 * 2. When the surrounding template IS run through the block-hooks pass, the
 *    field renders on the first request but is SUPPRESSED on subsequent requests
 *    within the same PHP worker (WP's `ignoredHookedBlocks` / hooked-block
 *    bookkeeping persists in the worker). Verified across e2e tests: with a
 *    `blockHooks` placement the field appears in the first test and vanishes in
 *    every test after it, even though the Add to Cart + Options form still
 *    renders. This makes `blockHooks` unusable for a stable form field here.
 *
 * WORKAROUND (zero core changes): `render_block` fires for EVERY block rendered
 * inside the form's `do_blocks` (verified), it is stateless, and it cannot be
 * suppressed by hooked-block bookkeeping. We prepend the field's rendered markup
 * when the quantity-selector anchor renders — the exact placement a `blockHooks`
 * "before" would produce, done deterministically. The field lands inside the
 * form, which resolves its product identity through the `woocommerce/products`
 * context / global state (T12), so its iAPI module resolves the right draft with
 * no extra wiring.
 *
 * The proper core fix is to run the Add to Cart + Options template-part content
 * through `apply_block_hooks_to_content()` (once, at assembly time) so that
 * `blockHooks` placements against the form's inner blocks work the standard way.
 * See the T8 report.
 */
add_filter(
	'render_block',
	function ( $block_content, $block ) {
		if (
			isset( $block['blockName'] ) &&
			'woocommerce/add-to-cart-with-options-quantity-selector' === $block['blockName'] &&
			WP_Block_Type_Registry::get_instance()->is_registered( 'wc-gift-note-demo/field' )
		) {
			$field = render_block(
				array(
					'blockName'    => 'wc-gift-note-demo/field',
					'attrs'        => array(),
					'innerBlocks'  => array(),
					'innerHTML'    => '',
					'innerContent' => array(),
				)
			);
			return $field . $block_content;
		}

		return $block_content;
	},
	10,
	2
);

/**
 * HOOK 1 — request -> cart_item_data.
 *
 * Read our bare-namespace extension prop off the raw Store API `add-item`
 * request (`$request['wc-gift-note-demo']['gift-note']` — the SAME shape the
 * draft holds and the cart line echoes), sanitize it, and stash it in
 * `cart_item_data`. Because `cart_item_data` feeds `WC_Cart::generate_cart_id`,
 * two adds of the same product with DIFFERENT notes hash to DIFFERENT line keys —
 * the server splits them into two cart lines. Two adds with the SAME note merge.
 * This is the whole "split lines by note" behavior, and it is 100% server-owned
 * identity (rule 1).
 */
add_filter(
	'woocommerce_store_api_add_to_cart_data',
	function ( array $add_to_cart_data, $request ) {
		$ns_data = $request[ WC_GIFT_NOTE_DEMO_NS ] ?? array();
		$raw     = is_array( $ns_data ) ? ( $ns_data[ WC_GIFT_NOTE_DEMO_ITEM_KEY ] ?? '' ) : '';

		if ( is_string( $raw ) && '' !== trim( $raw ) ) {
			if ( ! isset( $add_to_cart_data['cart_item_data'] ) || ! is_array( $add_to_cart_data['cart_item_data'] ) ) {
				$add_to_cart_data['cart_item_data'] = array();
			}
			$add_to_cart_data['cart_item_data'][ WC_GIFT_NOTE_DEMO_NS ] = array(
				WC_GIFT_NOTE_DEMO_ITEM_KEY => sanitize_text_field( wp_unslash( $raw ) ),
			);
		}

		return $add_to_cart_data;
	},
	10,
	2
);

/**
 * HOOK 2 — cart_item_data -> extensions[ ns ] (the identity convention).
 *
 * Expose the note on the cart-item `extensions` under our namespace, in the SAME
 * SHAPE the request accepts and the draft holds (`{ 'gift-note': <string> }`).
 * This machine-readable projection is what core's generic narrowing deep-compares
 * against the draft's bare-namespace `wc-gift-note-demo` prop, so
 * `itemInContext.cart` resolves to the line whose note matches the draft.
 * Extensions that skip this degrade safely to ambiguity (cart undefined), never
 * to wrong pairing.
 */
add_action(
	'woocommerce_blocks_loaded',
	function () {
		if ( ! function_exists( 'woocommerce_store_api_register_endpoint_data' ) ) {
			return;
		}

		woocommerce_store_api_register_endpoint_data(
			array(
				'endpoint'        => \Automattic\WooCommerce\StoreApi\Schemas\V1\CartItemSchema::IDENTIFIER,
				'namespace'       => WC_GIFT_NOTE_DEMO_NS,
				'data_callback'   => function ( $cart_item ) {
					$note = '';
					if ( isset( $cart_item[ WC_GIFT_NOTE_DEMO_NS ][ WC_GIFT_NOTE_DEMO_ITEM_KEY ] ) ) {
						$note = (string) $cart_item[ WC_GIFT_NOTE_DEMO_NS ][ WC_GIFT_NOTE_DEMO_ITEM_KEY ];
					}

					// When the line carries no note, echo an EMPTY OBJECT — not
					// `{ 'gift-note': '' }`. The machine-readable projection must be
					// truly ABSENT for a note-less line so it reads as "no content under
					// this namespace": core's matcher normalizes absent/empty, so an
					// empty `extensions[ns]` pairs with a draft that has no (or an empty)
					// gift-note prop. Echoing a present-but-empty `gift-note` key would
					// instead advertise a real field the matcher must reconcile.
					if ( '' === $note ) {
						return array();
					}

					// Same shape as the request/draft prop: a `gift-note` string.
					return array(
						WC_GIFT_NOTE_DEMO_ITEM_KEY => $note,
					);
				},
				'schema_callback' => function () {
					return array(
						WC_GIFT_NOTE_DEMO_ITEM_KEY => array(
							'description' => 'The gift note attached to this cart line.',
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
					);
				},
			)
		);
	}
);

/**
 * HOOK 3 — cart_item_data -> item_data (shopper-facing display).
 *
 * Render the note in the cart/checkout/order line item list. `item_data` is
 * display-only and NEVER machine-compared by core (rule 4); it is purely what
 * the shopper sees. The machine-readable pairing uses `extensions` (hook 2).
 */
add_filter(
	'woocommerce_get_item_data',
	function ( array $item_data, array $cart_item ) {
		if ( isset( $cart_item[ WC_GIFT_NOTE_DEMO_NS ][ WC_GIFT_NOTE_DEMO_ITEM_KEY ] ) ) {
			$note = (string) $cart_item[ WC_GIFT_NOTE_DEMO_NS ][ WC_GIFT_NOTE_DEMO_ITEM_KEY ];
			if ( '' !== $note ) {
				$item_data[] = array(
					'key'   => __( 'Gift note', 'wc-gift-note-demo' ),
					'value' => wc_clean( $note ),
				);
			}
		}

		return $item_data;
	},
	10,
	2
);

/**
 * Persist the note onto order line items so it survives into the order (and the
 * order-received / emails display). This is the standard companion to a
 * cart_item_data split; without it the note would be cart-only. It is NOT part
 * of the shared-store contract per se, but a real gift-note extension needs it,
 * and it keeps the E23/E50 "travels to the order" acceptance honest.
 */
add_action(
	'woocommerce_checkout_create_order_line_item',
	function ( $item, $cart_item_key, $values ) {
		if ( isset( $values[ WC_GIFT_NOTE_DEMO_NS ][ WC_GIFT_NOTE_DEMO_ITEM_KEY ] ) ) {
			$note = (string) $values[ WC_GIFT_NOTE_DEMO_NS ][ WC_GIFT_NOTE_DEMO_ITEM_KEY ];
			if ( '' !== $note ) {
				$item->add_meta_data( __( 'Gift note', 'wc-gift-note-demo' ), wc_clean( $note ), true );
			}
		}
	},
	10,
	3
);
