/**
 * `wc-navigation-survival`: a minimal fixture Interactivity API store
 * proving that a draft held in the public `woocommerce/cart` store's keyed
 * global state survives a genuine client-side navigation between two
 * pages, on the stock, supported region-based router — no experimental
 * full-page navigation mode, no runtime patch.
 *
 * Each page (rendered by the companion `navigation-survival.php`) carries
 * an "unwrapped" purchase surface — it declares no `woocommerce/cart`
 * context of its own, so the store resolves its usual fallback collection
 * for it, exactly like a plain, container-free Add to Cart with Options
 * form — and page A additionally carries a "keyed" surface wrapped in this
 * fixture's own literal, namespaced draft key
 * (`wc-navigation-survival/keyed`, see that file's
 * `data-wp-context---draft-key` markup). Because both pages' unwrapped
 * surfaces resolve the identical fallback collection for the identical
 * product id, an edit made on one is visible on the other once the router
 * swaps the shared region — while the keyed surface's own collection,
 * addressed only by this fixture's own declared key, is never resolved by
 * either unwrapped surface.
 *
 * A surface's quantity input has no init. Its `data-wp-on--change` resolves
 * the surface's own key (this fixture's declared key, if any; otherwise
 * the store's own fallback) and, on the *first* edit — the resolved
 * collection holds no draft for this product yet — creates the draft via
 * the store's public `upsertDraftItem` (a creation convenience — this is
 * the only place this fixture calls it); every edit after that is a
 * **direct mutation** of the already-resolved draft object
 * (`draft.quantity = value`), never an action call. Each surface renders a
 * binding onto the same draft so either write's re-render is directly
 * observable.
 *
 * The navigation link's `data-wp-on--click` reuses WooCommerce's own
 * shipped client-side navigation pattern verbatim (see
 * `product-collection/frontend.ts`): it dynamically imports
 * `@wordpress/interactivity-router` and calls `actions.navigate()` on the
 * link's own `href`, which — because both pages share one top-level
 * `data-wp-router-region` id — swaps that region's content without
 * reloading the document, keeping the JS runtime (and this store's global
 * draft state) alive across the navigation.
 *
 * This is a plain, unbundled ES module (no build step): `@wordpress/interactivity`
 * and `@woocommerce/stores/woocommerce/cart` are both script modules that
 * WordPress/WooCommerce already register, so a third-party extension can
 * depend on them directly. The cart store is accessed with its
 * private-store consent lock, exactly as a real extension will while the
 * store stays private.
 */

import { store, getContext, getElement } from '@wordpress/interactivity';
// This resolves at runtime via WordPress's script-module import map (see
// `navigation-survival.php`'s `register_script_module()`), not via static
// bundling, so ESLint's module resolver cannot find it on disk.
// eslint-disable-next-line import/no-unresolved
import '@woocommerce/stores/woocommerce/cart';

/**
 * The consent string gating access to the `woocommerce/cart` store while it
 * is private. Kept identical to the store's own lock string so this fixture
 * is denied nothing a real third-party extension will be denied once the
 * store is public.
 */
const universalLock =
	'I acknowledge that using a private store means my plugin will inevitably break on the next store release.';

/** The namespace shared by this client store and its own default context. */
const NAMESPACE = 'wc-navigation-survival';

/**
 * The store's own reserved fallback draft key — the collection any
 * purchase surface declaring no `woocommerce/cart` context of its own
 * resolves to (see `cart.ts`'s `GLOBAL_DRAFT_KEY`, and the identical
 * literal PHP emitters fall back to, e.g.
 * `AddToCartWithOptions.php:195`). This is a pinned, cross-cutting literal
 * shared by the client store and every server emitter — not a private
 * implementation detail this fixture is reaching past — so the
 * "unwrapped" surface below addresses it directly, exactly like any other
 * container-free purchase surface does implicitly by declaring no key.
 */
const GLOBAL_DRAFT_KEY = 'woocommerce/global';

/**
 * The quantity inputs' rendered default (`navigation-survival.php`'s
 * `render_surface()` hardcodes `value="1"`) — the value
 * `state.quantityText` falls back to before a surface's first edit, when
 * its resolved collection holds no draft for this product yet.
 */
const RENDERED_DEFAULT_QUANTITY = 1;

const cart = store( 'woocommerce/cart', {}, { lock: universalLock } );

/**
 * Resolves the current element's own surface's draft key: this fixture's
 * own declared `woocommerce/cart` draft key for the "keyed" surface
 * (`navigation-survival.php`'s `data-wp-context---draft-key`), or
 * {@link GLOBAL_DRAFT_KEY} for the "unwrapped" surface, which declares no
 * such context anywhere in its ancestry — the identical absent-context
 * degrade the store's own resolver applies.
 *
 * @return {string} The surface's resolved draft key.
 */
function resolveDraftKey() {
	return getContext( 'woocommerce/cart' )?.draftKey ?? GLOBAL_DRAFT_KEY;
}

/**
 * Resolves the current element's own surface's draft: the entry matching
 * this fixture's `productId` context inside whichever collection
 * {@link resolveDraftKey} resolves.
 *
 * `undefined` before the surface's first edit — a surface's draft does not
 * exist until its resolved collection's first `upsertDraftItem` write (see
 * {@link onQuantityChange}); nothing seeds it up front.
 *
 * @return {Object|undefined} This surface's draft, or `undefined` when none
 *                             is resolved yet.
 */
function resolveDraft() {
	const { productId } = getContext();
	const collection = cart.state.draftItems[ resolveDraftKey() ] ?? [];
	return collection.find( ( item ) => item.id === productId );
}

/**
 * Writes a shopper's quantity edit to this surface's resolved draft.
 *
 * Bound to the quantity input's `data-wp-on--change`. A surface's resolved
 * collection does not hold a draft for this product until its first edit —
 * nothing seeds it up front — so the *first* call creates it via the
 * store's public `upsertDraftItem` (a creation convenience, addressed by
 * this surface's own declared key, or the store's own fallback when none is
 * declared), and every call after that is a **direct mutation** of the
 * already-resolved draft object, never an action call. The surface's
 * `<span>` binding (`state.quantityText`) reads the same draft, so either
 * write's re-render is observable.
 */
function onQuantityChange() {
	const { ref } = getElement();
	const quantity = Number( ref.value );

	if ( ! Number.isFinite( quantity ) || quantity < 0 ) {
		return;
	}

	const draft = resolveDraft();

	if ( draft ) {
		// Direct mutation of the resolved draft object — reactive per the
		// store's live envelope, deliberately not routed through
		// `upsertDraftItem`.
		draft.quantity = quantity;
		return;
	}

	// First edit for this surface: create its draft via the store's public
	// creation convenience. `upsertDraftItem` resolves the same key this
	// surface's own `woocommerce/cart` context declares (or the store's
	// own fallback when it declares none), addressed by this fixture's own
	// declared `productId` context, not by any registry.
	const { productId } = getContext();
	cart.actions.upsertDraftItem( { id: productId, quantity } );
}

/**
 * Ref-equality helper: is the clicked element a same-origin, same-tab link
 * with an `href`? Mirrors `product-collection/frontend.ts`'s own
 * `isValidLink()` guard for its client-side pagination links verbatim.
 *
 * @param {HTMLElement|null} ref The clicked element.
 * @return {boolean} Whether `ref` is a navigable same-origin link.
 */
function isValidLink( ref ) {
	return (
		ref !== null &&
		ref instanceof window.HTMLAnchorElement &&
		!! ref.href &&
		( ! ref.target || ref.target === '_self' ) &&
		ref.origin === window.location.origin
	);
}

/**
 * Ref-equality helper: was this a plain left click with no modifier key and
 * no earlier handler already calling `preventDefault()`? Mirrors
 * `product-collection/frontend.ts`'s own `isValidEvent()` guard verbatim.
 *
 * @param {MouseEvent} event The click event.
 * @return {boolean} Whether this click should trigger client-side
 *                     navigation.
 */
function isValidEvent( event ) {
	return (
		event.button === 0 && // Left clicks only.
		! event.metaKey && // Open in new tab (Mac).
		! event.ctrlKey && // Open in new tab (Windows).
		! event.altKey && // Download.
		! event.shiftKey &&
		! event.defaultPrevented
	);
}

store(
	NAMESPACE,
	{
		state: {
			/**
			 * The current element's surface's draft quantity, as text.
			 *
			 * A getter, re-evaluated on every render; reads the same
			 * resolved draft {@link onQuantityChange} writes — by creation
			 * or direct mutation — so either write's re-render is
			 * observable through this binding with no action call
			 * involved. Falls back to the quantity input's rendered
			 * default when this surface's resolved collection holds no
			 * draft for this product yet.
			 */
			get quantityText() {
				return String(
					resolveDraft()?.quantity ?? RENDERED_DEFAULT_QUANTITY
				);
			},
		},
		actions: {
			onQuantityChange,

			/**
			 * Drives a genuine client-side navigation to the link's own
			 * `href`, reusing WooCommerce's own shipped pattern verbatim
			 * (`product-collection/frontend.ts`): dynamically import
			 * `@wordpress/interactivity-router` and call
			 * `actions.navigate()`. Because both pages share one top-level
			 * `data-wp-router-region` id, the router matches and swaps
			 * that region's content in place — the JS runtime, its script
			 * modules, and this store's global draft state all stay alive
			 * across the navigation; the document itself never reloads.
			 *
			 * @param {MouseEvent} event The click event.
			 */
			*navigate( event ) {
				const { ref } = getElement();

				if ( isValidLink( ref ) && isValidEvent( event ) ) {
					event.preventDefault();

					const { actions } = yield import(
						'@wordpress/interactivity-router'
					);

					yield actions.navigate( ref.href );
				}
			},
		},
	},
	{ lock: universalLock }
);
