/**
 * Interactivity API frontend for the classic cart shortcode.
 *
 * Enabled by the `experimental-iapi-cart` feature flag. The cart stays
 * PHP-rendered and hook-driven; this module only replaces the legacy jQuery
 * interaction layer (assets/js/frontend/cart.js):
 *   - mutations go through the shared Mini-Cart `woocommerce` store (optimistic
 *     + Store API), or directly to Store API for coupons/shipping which the
 *     shared store does not (yet) expose;
 *   - after a mutation settles we re-render the server HTML via the
 *     Interactivity Router so every PHP hook/filter fires again, then move
 *     focus to any notice for screen-reader parity with the legacy script;
 *   - a global `isProcessing` flag drives a busy state on the wrapper;
 *   - legacy jQuery events are bridged in and re-emitted for back-compat.
 *
 * See docs/internal-developers/iapi-classic-cart-migration/migration-plan.md.
 *
 * TODO: the new mutation actions (applyCoupon/removeCoupon/selectShippingRate/
 * updateCustomer/restoreItem) should ultimately live in the shared `woocommerce`
 * store (owned by Billow), not here.
 */

/**
 * External dependencies
 */
import { store, getContext, getElement } from '@wordpress/interactivity';
import { actions as router } from '@wordpress/interactivity-router';
import '@woocommerce/stores/woocommerce/cart'; // Side-effect: registers shared store.

type ClassicCartContext = {
	cartItemKey: string;
	coupon: string;
};

interface WooCartState {
	restUrl: string;
	nonce: string;
}

// Lock for our own private classic-cart store, so its state is mutable from
// actions. The shared `woocommerce` store is accessed without a lock (its
// public actions are reachable that way).
const universalLock =
	'I acknowledge that using a private store means my plugin will inevitably break on the next store release.';

const { state: wooState, actions: wooActions } = store< {
	state: WooCartState;
	actions: {
		addCartItem: ( args: {
			key?: string;
			id?: number;
			quantity: number;
		} ) => Promise< unknown >;
		removeCartItem: ( key: string ) => Promise< unknown >;
		refreshCartItems: () => Promise< unknown >;
	};
} >( 'woocommerce' );

// Declare the classic-cart store's own state up front (locked, since we own
// this store) so the actions defined below can reference and mutate it. This
// "declare beforehand" step mirrors the Mini-Cart store; without it (or without
// the matching lock) the state mutation silently fails.
const { state } = store< { state: { isProcessing: boolean } } >(
	'woocommerce/classic-cart',
	{},
	{ lock: universalLock }
);

/**
 * Move focus to the first notice after a re-render so screen readers announce
 * it, matching what the legacy cart script did with scroll_to_notices.
 */
function focusNotices(): void {
	const notice = document.querySelector(
		'.woocommerce-notices-wrapper .woocommerce-message, ' +
			'.woocommerce-notices-wrapper .woocommerce-error, ' +
			'.woocommerce-notices-wrapper .woocommerce-info'
	);
	if ( notice instanceof HTMLElement ) {
		if ( ! notice.hasAttribute( 'tabindex' ) ) {
			notice.setAttribute( 'tabindex', '-1' );
		}
		notice.focus();
	}
}

/**
 * Re-render the server-rendered cart region, preserving all PHP hooks/filters.
 * The router fetches the current URL; the server re-renders cart.php /
 * cart-totals.php (and the directive injection runs again), then the
 * `data-wp-router-region` is morphed in place.
 */
function* rerenderCart(): Generator< unknown > {
	yield router.navigate( window.location.href, { force: true } );
	focusNotices();
}

/**
 * Emit a legacy jQuery event so cart-fragments.js and existing extensions keep
 * working. No-op when jQuery is absent.
 */
function emitLegacyEvent( name: string, ...args: unknown[] ): void {
	const w = window as unknown as {
		jQuery?: ( target: unknown ) => {
			trigger: ( n: string, a?: unknown[] ) => void;
		};
	};
	if ( w.jQuery ) {
		w.jQuery( document.body ).trigger( name, args );
	}
}

/**
 * Minimal Store API POST helper. Mirrors what the shared store does internally;
 * kept local for the spike for the endpoints the shared store does not expose.
 */
async function storeApiPost(
	path: string,
	data: Record< string, unknown >
): Promise< unknown > {
	const response = await fetch(
		`${ wooState.restUrl }wc/store/v1/${ path }`,
		{
			method: 'POST',
			headers: {
				'Content-Type': 'application/json',
				Nonce: wooState.nonce,
			},
			body: JSON.stringify( data ),
		}
	);
	return response.json();
}

const { actions } = store(
	'woocommerce/classic-cart',
	{
		state: { isProcessing: false },
		actions: {
			// Quantity changed on a cart row -> update via shared store, re-render.
			*changeQuantity(): Generator< unknown > {
				const { cartItemKey } = getContext< ClassicCartContext >();
				const { ref } = getElement();
				const quantity = parseInt(
					( ref as HTMLInputElement ).value,
					10
				);
				state.isProcessing = true;
				try {
					yield wooActions.addCartItem( {
						key: cartItemKey,
						quantity,
					} );
					yield* rerenderCart();
				} finally {
					state.isProcessing = false;
				}
			},

			// Remove item link.
			*removeItem( event: Event ): Generator< unknown > {
				event.preventDefault();
				const { cartItemKey } = getContext< ClassicCartContext >();
				state.isProcessing = true;
				try {
					yield wooActions.removeCartItem( cartItemKey );
					emitLegacyEvent( 'item_removed_from_classic_cart' );
					yield* rerenderCart();
				} finally {
					state.isProcessing = false;
				}
			},

			// Undo / restore a removed item.
			// OPEN QUESTION: Store API has no "restore" endpoint. For the spike we
			// fall back to the server restore URL on the link, then re-render.
			*restoreItem( event: Event ): Generator< unknown > {
				event.preventDefault();
				const { ref } = getElement();
				const href = ( ref as HTMLAnchorElement ).href;
				state.isProcessing = true;
				try {
					yield fetch( href, { credentials: 'same-origin' } );
					yield wooActions.refreshCartItems();
					yield* rerenderCart();
				} finally {
					state.isProcessing = false;
				}
			},

			*applyCoupon( event: Event ): Generator< unknown > {
				event.preventDefault();
				const input = document.getElementById(
					'coupon_code'
				) as HTMLInputElement | null;
				const code = input?.value?.trim();
				if ( ! code ) {
					return;
				}
				state.isProcessing = true;
				try {
					yield storeApiPost( 'cart/apply-coupon', { code } );
					yield wooActions.refreshCartItems();
					emitLegacyEvent( 'applied_coupon', code );
					yield* rerenderCart();
				} finally {
					state.isProcessing = false;
				}
			},

			*removeCoupon( event: Event ): Generator< unknown > {
				event.preventDefault();
				const { coupon } = getContext< ClassicCartContext >();
				state.isProcessing = true;
				try {
					yield storeApiPost( 'cart/remove-coupon', {
						code: coupon,
					} );
					yield wooActions.refreshCartItems();
					emitLegacyEvent( 'removed_coupon', coupon );
					yield* rerenderCart();
				} finally {
					state.isProcessing = false;
				}
			},

			*selectShippingRate(): Generator< unknown > {
				const { ref } = getElement();
				const rateId = ( ref as HTMLInputElement ).value;
				// `index` maps to the shipping package on the classic markup.
				const packageId = parseInt(
					( ref as HTMLElement ).dataset.index ?? '0',
					10
				);
				state.isProcessing = true;
				try {
					yield storeApiPost( 'cart/select-shipping-rate', {
						package_id: packageId,
						rate_id: rateId,
					} );
					yield wooActions.refreshCartItems();
					emitLegacyEvent( 'updated_shipping_method' );
					yield* rerenderCart();
				} finally {
					state.isProcessing = false;
				}
			},

			// Shipping calculator submit -> set customer address, re-render.
			*calculateShipping( event: Event ): Generator< unknown > {
				event.preventDefault();
				const form = getElement().ref as HTMLFormElement;
				const data = new FormData( form );
				state.isProcessing = true;
				try {
					yield storeApiPost( 'cart/update-customer', {
						shipping_address: {
							country: data.get( 'calc_shipping_country' ) ?? '',
							state: data.get( 'calc_shipping_state' ) ?? '',
							city: data.get( 'calc_shipping_city' ) ?? '',
							postcode:
								data.get( 'calc_shipping_postcode' ) ?? '',
						},
					} );
					yield wooActions.refreshCartItems();
					yield* rerenderCart();
				} finally {
					state.isProcessing = false;
				}
			},

			// Pure-UI: toggle the shipping calculator panel.
			toggleShippingCalculator(): void {
				const context = getContext< {
					shippingCalculatorOpen: boolean;
				} >();
				context.shippingCalculatorOpen =
					! context.shippingCalculatorOpen;
			},

			// With JS active, quantities apply on change so the full-form POST is
			// redundant. Prevent it; without JS the native submit still posts to the
			// legacy WC_Form_Handler::update_cart_action handler.
			preventFormSubmit( event: Event ): void {
				event.preventDefault();
			},
		},
		callbacks: {
			// Bridge inbound legacy events so add-to-cart elsewhere refreshes us.
			setupLegacyBridge(): void {
				const w = window as unknown as {
					jQuery?: ( t: unknown ) => {
						on: ( e: string, cb: () => void ) => void;
					};
				};
				if ( ! w.jQuery ) {
					return;
				}
				w.jQuery( document ).on( 'added_to_cart wc_update_cart', () => {
					void wooActions.refreshCartItems().then( () =>
						router.navigate( window.location.href, {
							force: true,
						} )
					);
				} );
			},
		},
	},
	{ lock: universalLock }
);

export type ClassicCartStore = typeof actions;
