# Proposal: Server-defined draft keys in global state for the Interactivity API stores

This branch reshapes WooCommerce's storefront Interactivity API stores around a single model — **shopper draft input lives in global state, addressed by opaque, server-defined draft keys** — re-registers the reactive cart store off the root `woocommerce` namespace as `woocommerce/cart`, and migrates every core storefront block that consumes today's product/cart stores onto the new surface. It ships a working, tested implementation of the full products + cart surface plus a small bundle-style demo extension and a two-page navigation fixture, so every claim below is backed by code you can run on this branch.

This document is the proposal we want to align on. It is **not** a merge request for a finished, public API. The stores stay **private and locked** while we validate the model; the branch is **validation-grade, not merge-ready** (the storefront UX is unchanged, but production polish is out of scope). What we are asking reviewers to decide is whether the model — per-domain stores, a keyed global draft home addressed by server-defined keys, an envelope that never guesses, direct draft mutation as a first-class write — is the right foundation to build the public stores on. The [store README](./README.md) alongside this file is the precise reference companion; this document argues the design and shows it working.

This revision supersedes an earlier iteration that held drafts in **context-held collections** rather than in keyed global state. [Why keys, not context-held collections](#why-keys-not-context-held-collections) below records what changed and why; if you reviewed the earlier version, start there.

## What this branch does (at a glance)

-   Registers `woocommerce/cart` (new) next to `woocommerce/products` (today's shape). The root `woocommerce` **reactive store** registration retires. The cross-domain `wp_interactivity_config( 'woocommerce', … )` **config bag** (currency, placeholder image, messages) is not a store and stays where it is.
-   Models shopper input as `state.draftItems`: a **keyed map of draft collections**, `Record< DraftKey, DraftItem[] >`, where each `DraftItem` is a Store API `cart/add-item` payload and a `DraftKey` is an opaque address for one collection.
-   Lets any container block isolate its subtree by declaring an opaque, server-minted `draftKey` in its `woocommerce/cart` context; a surface wrapped in no container resolves the reserved `GLOBAL_DRAFT_KEY` (`'woocommerce/global'`). Every read and write resolves the nearest declared key through one internal resolver.
-   Creates collections **lazily**, on a shopper's first write to a key. The server never seeds `state.draftItems`; the map starts empty on a fresh load.
-   Reads "the item in context" through a single envelope, `state.itemInContext`, that pairs a draft with its cart line exactly or not at all.
-   Treats **direct mutation of a resolved draft as a first-class write** (reactive, honored by posting), with `upsertDraftItem` retained as the creation/merge convenience, and posts drafts with `addItem()`.
-   Files initial-draft seeds as server-rendered state (`draftSeeds[key][id]`), read on the client **only through `getServerState()`** and consulted at exactly two points — never applied into a collection — so a re-delivered seed can never overwrite a live edit.
-   Makes survival a property of the model, not a mechanism: drafts survive Product Collection region remounts **and** genuine cross-page client-side navigation, and reset on a hard reload.
-   Proves five hard storefront use cases with working code and automated tests, a `wc-bundle-demo` fixture extension that adds a two-child bundle on **zero core server changes**, and a two-page navigation fixture that drives a real cross-URL `actions.navigate()` round trip.

## Why keys, not context-held collections

The shipped model before this revision — rev-1 — addressed drafts by **context-held collections**. Shopper input lived in a `draftItems` array that a container block initialized in its own `woocommerce/cart` context; a flat page-wide `state.draftItems` array was the fallback; and one internal resolver read the nearest context-held collection, falling back to the page-wide one. Declaring a collection was plain markup — no id to mint, no service to call — and the model delivered subtree isolation cleanly.

But context lives and dies with its DOM subtree. When the Interactivity API remounts a region — Product Collection enhanced pagination swaps the grid — or a client-side navigation replaces the page, the subtree is torn down and re-created, and every draft held in that subtree's context is destroyed with it. A shopper's in-progress quantity, variation, or extension input vanished on exactly the interactions the runtime was built to make seamless.

rev-1 papered over **one** instance of this. Product Collection cards under enhanced pagination kept a card's edited draft alive with Product-Collection-specific restore machinery: a **module-private ledger** keyed by a derived card identity, a **register-or-restore init** directive on every loop item that the server resolved by name, and a **render-time bridge** inside the resolver so the first post-remount paint already showed the restored draft. That covered the collection grid and nothing else — a Single Product block, or an extension's own container inside a collection, got isolation but no survival.

This revision moves drafts out of context and into **keyed global state**. A container no longer holds a collection array in context; it declares an opaque **key**, and the collection itself lives in the store's global `state.draftItems` map under that key. Because keys are minted on the server and are **render-stable** within a browsing session, a surface re-renders — after a remount, after a navigation — with the *same* key in fresh markup, and its collection re-attaches by resolution alone. Nothing reconstructs anything.

**Survival becomes a property of where drafts live, and the entire lifecycle apparatus is deleted, not replaced.** The module-private ledger, the register-or-restore init, the render-time bridge, the per-card init directive, and `seedDraftIfAbsent` are gone with **no successor**. There is no restore protocol, because there is nothing to restore: global state outlives the region swaps and navigations that used to destroy context, and render-stable keys re-address the same collection wherever a surface paints. What rev-1 delivered for one surface with bespoke machinery, the model now delivers for every surface for free.

Three premises this revision carries forward or shifts make that work:

-   **Direct mutation stays first-class.** Block and extension code may mutate a resolved draft directly (`itemInContext.draft.quantity = 3`); such writes are reactive and honored by `addItem` posting. `upsertDraftItem` remains as the creation/merge convenience. (The rev-1 `removeDraftItem` companion had no shipped caller and was dropped in the cleanup audit.) This keeps the door open for a planned future revision that drops `upsertDraftItem` in favor of editing the envelope's draft directly.
-   **Seeds are consult-only, never applied.** Initial-draft defaults ride server-rendered state and are read through `getServerState()`, the runtime's per-page, navigation-fresh copy. The store consults a seed at exactly two moments — composing a new draft on the shopper's first write, and falling back for an untouched surface at post time — and never writes a seed *into* a collection. Because a seed and an edited draft never share an object path, a re-delivered seed (on a remount or navigation) can never replace or inject into the shopper's edit; idempotency is structural rather than guarded.
-   **The context tree still does the addressing** — the nearest declared value wins by the runtime's own context inheritance, exactly as before. What a container declares changed (an opaque key, not a collection array), but no consumer reads it: the store resolves the key internally on every read and write.

## The cases today's stores can't express

The pre-existing stores are private, inconsistent, and structurally unable to express the hardest storefront scenarios. Cart state registered in the **root** `woocommerce` store; product data lived in its own `woocommerce/products` store. There was no shared vocabulary for "the same product configured in two different places on one page", so the following cases had no clean expression:

1.  A **grouped product** form rendered inside another product's template (a Single Product block, a Product Collection card) — it must operate on its own children, not the surrounding product.
2.  **Two grouped products** on one page that share a child product — editing the child in one form must not move the child in the other.
3.  **Two variations of the same parent** (T-Shirt / Green and T-Shirt / Blue) configured side by side — both must land in the cart as independent lines with the correct attributes.
4.  **Multiple page-wide surfaces for one product** (a main add-to-cart form and a sticky add-to-cart bar) — edits on one must reflect on the other, while a surface that isolates its own subtree for the same product does not.
5.  A **bundle-style extension** — several independently configured child products added as one unit carrying the extension's own data, none of the children colliding with each other or with the same products elsewhere on the page.

Every one of these reduces to the same question: *which set of surfaces is editing the same thing, and what would they POST?* Draft keys answer it: surfaces that resolve the same key edit the same collection; a container that declares its own key separates its subtree from the rest.

## The model: one idea, a handful of rules

**Shopper draft input lives in global state, addressed by opaque server-defined draft keys.** From that, everything else follows.

-   **A draft is exactly a `cart/add-item` payload.** No mapping layer between what a form collects and what gets posted. Extension props ride at the payload root, namespaced (`my-plugin/gift-note`), exactly as the Store API accepts them. Drafts live **alongside — never inside** — the read-only mirror of the server cart.
-   **`state.draftItems` is a keyed map of collections.** `Record< DraftKey, DraftItem[] >`; within any one collection, at most one draft per product `id`. The **key** is the isolation boundary; the collection is the `DraftItem[]` filed under it.
-   **A key is opaque.** A `DraftKey` is a plain string whose only contract is equality: the same key resolves the same collection, and nothing else is promised — no parseable format, no stability beyond a single browsing session. A consumer never parses or constructs one; it only ever declares a key it was handed (or, for an extension, one it chose) and lets the store resolve it. Because a surface's key is identical across successive server renders of that surface, its drafts re-attach after region remounts and client-side navigations.
-   **A container isolates its subtree by declaring a key.** A block that wraps or repeats purchase UI declares an opaque, server-minted `draftKey` in its `woocommerce/cart` context; every surface nested inside it then resolves that key's collection. A surface with no such ancestor resolves the reserved session-global collection under `GLOBAL_DRAFT_KEY` (`'woocommerce/global'`). Consumer blocks never declare a key — they read the resolved collection.
-   **One collection per key.** When a surface genuinely needs two independent drafts of the same product (two bundle slots offering the same child), its containers declare **two keys** rather than reaching for a second addressing concept — one draft per product per collection is the invariant, and the key is the only isolation axis.
-   **Resolution lives in exactly one place.** A module-private `resolveDraftKey()` implements `context.draftKey ?? GLOBAL_DRAFT_KEY`; a companion `resolveCollection( key )` returns `state.draftItems[ key ]`. Those two functions live in one place, client-side; no consumer writes a key or that fallback conditional. Consumers read a resolved draft through the envelope.
-   **Reads never guess line identity.** `state.itemInContext` pairs the in-context product's draft with its cart line **exactly, or not at all**. The server owns cart-line identity; the client never misattributes a line.
-   **Writes are direct, or through the creation convenience.** Mutating a resolved draft (`itemInContext.draft.quantity = 3`) is first-class and reactive; `upsertDraftItem` remains for creating and merging drafts.
-   **Collections are lazy; the server never seeds `draftItems`.** A collection materializes on the shopper's first write to its key; the map starts empty on a fresh load. Initial-draft defaults ride server-rendered state (`draftSeeds`) and are consulted only at creation and at post-time fallback — never applied into a collection — so a re-render can never overwrite a live edit.

## Two schema questions this settles

### Where cart state lives

Cart state must not remain implicitly in the root `woocommerce` store by accident. It moves to a **dedicated `woocommerce/cart` store**. The root reactive-store registration retires. This is the change that lets the cart domain own its lock, its loading, and its surface, and lets future domains (checkout) slot in the same way.

The bare `woocommerce` **context namespace** and the `wp_interactivity_config( 'woocommerce', … )` **config bag** (currency, placeholder image, messages) stay — as cross-domain vocabulary — but they no longer carry any cart addressing. A container's draft key is namespaced to `woocommerce/cart` itself (`data-wp-context---draft-key='woocommerce/cart::{"draftKey":"<key>"}'`), because a draft key is a cart concern; there is nothing here to hoist into the shared namespace.

### One store per domain, not one shared store

The alternative was a single `woocommerce` store with domain sub-trees. We chose **per-domain stores** — `woocommerce/cart` and `woocommerce/products` — with a one-directional coupling: cart consults products, never the reverse. The trade is explicit cross-store plumbing (there is precedent: cart already dispatches to `woocommerce/store-notices`) in exchange for domain ownership, independent locking, and a clean seam for future domains.

`woocommerce/products` keeps today's shape; the only change is a rename (`mainProductInContext` → `baseProductInContext`, see below). All the new machinery is in `woocommerce/cart`.

## The `woocommerce/products` surface

A locked, server-populated cache of product and variation data in Store API format (`ProductResponseItem`). It has **state only, no actions** — it is a read cache; shopper input never lands here, it lands in `woocommerce/cart`.

| Member | Type | Kind |
| --- | --- | --- |
| `products` | `Record< number, ProductResponseItem >` | Raw data, keyed by product id |
| `productVariations` | `Record< number, ProductResponseItem >` | Raw data, keyed by variation id |
| `productId` | `number` | Selection (global state or per-element context) |
| `variationId` | `number \| null` | Selection (global state or per-element context) |
| `findProduct` | `({ id, selectedAttributes? }) => ProductResponseItem \| null` | Function |
| `baseProductInContext` | `ProductResponseItem \| null` | Derived — the top-level product, **never** a variation |
| `productVariationInContext` | `ProductResponseItem \| null` | Derived — the selected variation, or `null` |
| `productInContext` | `ProductResponseItem \| null` | Derived — `productVariationInContext ?? baseProductInContext` |

The one surface change on this branch is the rename of the anchor getter **`mainProductInContext` → `baseProductInContext`**. "Base" reads correctly against "variation": `baseProductInContext` is always the parent product, `productVariationInContext` is the selected variation, and `productInContext` is whichever is currently shown. The PHP mirror (`ProductsStore::register_getters()`) and every reader were renamed with it.

Usage — resolving the base product and its variation ids, from `blocks/add-to-cart-with-options/frontend.ts`:

```ts
const { baseProductInContext: productFromStore } = productsState;
const variationIds = productFromStore?.variations?.map( ( v ) => v.id ) ?? [];
```

Usage — resolving a variation by attributes, from `blocks/add-to-cart-with-options/variation-selector/frontend.ts`:

```ts
const result = productsState.findProduct( {
	id: product.id,
	selectedAttributes,
} );
// findProduct returns the parent when no variation matches — only accept an actual variation.
const matchedVariation =
	result && result.id !== product.id ? result : null;
```

## The `woocommerce/cart` surface

### Types

```ts
type SelectedAttributes = Omit< CartVariationItem, 'raw_attribute' >;

// An opaque address for one draft collection. Only contract: string equality.
type DraftKey = string;

// A draft IS a cart/add-item payload. Extension props ride at the root, namespaced.
type DraftItem = {
	id: number; // product or variation id; also the per-collection uniqueness key
	quantity: number;
	variation?: SelectedAttributes[]; // for a variation draft
	[ extensionProp: string ]: unknown; // namespaced, e.g. 'my-plugin/gift-note'
};

// The read-only pairing returned by findItem / itemInContext.
type Envelope = {
	cartItem?: CartItem | OptimisticCartItem | undefined;
	draft?: DraftItem | undefined;
};
```

A `DraftKey` is the only draft-addressing type on the surface, and it is a bare `string` — no id, no handle, no parseable structure. A collection is just the `DraftItem[]` filed under a key in `state.draftItems`.

### State

| Member | Type | Notes |
| --- | --- | --- |
| `cart` | `Cart` mirror with optimistic items | Read-only mirror of the Store API `/cart` response; the server owns line identity |
| `draftItems` | `Record< DraftKey, DraftItem[] >` | The keyed draft home; one collection per key, at most one draft per product id within it. Created lazily on a shopper's first write; the server never seeds it |
| `findItem` | `({ id?, key?, filter? }) => Envelope` | The explicit lookup primitive behind `itemInContext` |
| `itemInContext` | `Envelope` | The in-context product's resolved-collection draft paired with its cart line |
| `inCartQuantity` | `number` | The in-context product's in-cart quantity (grouped aggregates children; variable resolves through the selected variation) |

`state` also carries request plumbing that is not part of the proposed purchase surface: `restUrl` and `nonce` back the mutation queue. Note what is **not** here: no flat page-wide `DraftItem[]`, no current-collection getter, no collection-identity type. Nothing on the state surface exposes a key — the resolver reads the context tree directly, and consumers reach a resolved draft only through the envelope.

### Actions

| Action | Signature | Role |
| --- | --- | --- |
| `upsertDraftItem` | `( partial, { id? }? ) => void` | Creation/merge convenience: create or merge-by-id a draft in the resolved collection |
| `addItem` | `( payload? ) => Promise< void >` | Post the in-context product's resolved-collection draft(s), or a payload verbatim |
| `updateItem` | `( { key, quantity } ) => Promise< void >` | Set a cart line's absolute quantity via `update-item` |
| `removeItem` | `( key ) => Promise< void >` | Remove a cart line by key |
| `refresh` | `() => Promise< void >` | Re-fetch the server cart, bypassing the browser cache |
| `addCartItem` | `( args, options? ) => Promise< void >` | The lower-level keyed/keyless add-or-update path, **retained** (see below) |

Neither `upsertDraftItem` nor `findItem` carries a key argument — there is no addressing option to pass, because the resolver reads the calling surface's own context. `actions` also carries internal notice helpers not part of the proposed purchase surface: `showNoticeError` and `updateNotices`, which support the mutation queue and notice dispatch.

### Reading: the in-context envelope

Every display binds to the envelope, so the same block code works on a product page, a collection card, and the mini-cart. `state.itemInContext` resolves the in-context product through `woocommerce/products`' `productInContext`, then pairs its resolved-collection draft with its cart line.

**Pairing never guesses.** `cartItem` is populated only when the pairing ladder resolves to exactly one candidate:

1.  A context-known line `key` pairs exactly, no further checks (a mini-cart row; a surface that emits `key`).
2.  Otherwise, product/variation identity (using the draft's own `variation`, when one exists) **plus** a namespaced extension-prop comparison against each candidate line's `extensions[<namespace>]` must resolve to exactly one line. A `filter` argument replaces this identity matching entirely, for extensions with their own notion of line identity.

Any remaining ambiguity leaves `cartItem` **`undefined`**. Consumers must handle that as "no known line", not "not in cart".

There is deliberately **no `isInCart`** member (or any third member). The envelope was validated against the real migrated consumers; none needed the "in the cart, but no single identifiable line" tri-state, so the envelope carries only `cartItem` and `draft`.

Usage — a display reading the in-context draft's quantity, from `blocks/add-to-cart-with-options/quantity-selector/frontend.ts`:

```ts
const draftQuantity = cartState.itemInContext.draft?.quantity;

if ( typeof draftQuantity === 'number' ) {
	return draftQuantity;
}
```

Usage — `findItem` pairing a known mini-cart row, from `blocks/mini-cart/frontend.ts`:

```ts
const {
	cartItem: { id, key },
} = getContext< CartItemContext >( 'woocommerce/cart' );

const cartItem = ( woocommerceState.findItem( { id, key } )
	.cartItem || {} ) as CartItem;
```

### Writing: direct mutation, plus the creation convenience

Draft state is written **directly, or through `upsertDraftItem`** — whichever is clearer at the call site.

-   **Direct mutation is first-class.** The `draft` an envelope hands back is the live reactive object from the resolved collection, not a copy. Mutating it — `itemInContext.draft.quantity = 3`, or `findItem( { id } ).draft` — is supported, notifies every surface that resolves the same key, and is honored by `addItem` posting (which reads the collection at call time).
-   `upsertDraftItem( partial, { id? }? )` resolves the nearest key's draft collection, then merges `partial` into the draft whose `id` matches — `id` from `options.id` when given, else `partial.id` — appending otherwise. **Creation composes the new draft from the surface's server-filed seed** (`{ ...seed, ...partial }`, read via `getServerState()`), so an untouched field falls back to its server-rendered default, and the collection is materialized lazily on this first write. It **rejects, leaving state unchanged**, on an unresolvable numeric `id`, an in-place identity change (`partial.id` disagreeing with the resolved target), or a brand-new draft missing a numeric `quantity` (from either `partial` or the seed). Each rejection is a dev-build `console.warn` and a production no-op.

The composition of a new draft from its seed, from `cart.ts`:

```ts
const seed =
	getServerState< CartServerState >( 'woocommerce/cart' )
		?.draftSeeds?.[ draftKey ]?.[ targetId ];
const draft = {
	...seed,
	...partial,
	id: targetId,
} as DraftItem;
```

Usage — mirroring a quantity edit into the resolved collection's draft, from `blocks/add-to-cart-with-options/frontend.ts`:

```ts
wooActions.upsertDraftItem( { quantity: value }, { id: productId } );
```

**Bystander discipline (why sync actually works).** Watch and init callbacks re-run on **every** surface that resolves a shared collection, not just the one the shopper is using. So only a genuine shopper edit — or a clamp of the shared value itself — may write to a draft. A sibling surface the shopper never touched must not write its stale local default over the shared draft. On this branch that shows up as a guard in the variation selector: a surface that never made a selection of its own does not overwrite "nothing selected" onto a variation another surface resolved, from `blocks/add-to-cart-with-options/variation-selector/frontend.ts`:

```ts
if (
	selectedAttributes.length === 0 &&
	! context.hasSelectedAttribute &&
	currentVariationId !== null &&
	currentVariationId !== undefined
) {
	return;
}
```

When building a new surface that resolves a shared collection, gate every draft write behind an actual user action.

### Adding to cart: `addItem`

`addItem( payload? )` is polymorphic:

-   **`addItem()`** (no argument) resolves the in-context product via `woocommerce/products` and posts the resolved collection's draft(s) for it: a simple or variable product's own single draft — **falling back to the surface's server-filed seed** (`draft ?? seed`) so an untouched form still posts its default — or, for a grouped product, every child's draft (children resolved one-directionally through the products store) whose `quantity` is greater than `0` (seeds are not consulted on this rung, so untouched children never post). Multiple children post as **one auto-batched** request set, not one request per child.
-   **`addItem( payload )`** posts the payload **verbatim** — extension props at its root included — bypassing key and product resolution entirely. This is the path an extension composing its own `cart/add-item` payload uses.

**Product-scoped posting is a guarantee, not a side effect.** `addItem` posts only the in-context product's draft (simple/variable), the grouped parent's declared children with `quantity > 0`, or an explicit payload — it **never iterates a collection**. This matters now that a session-global collection accumulates drafts from every page a shopper visited: an add from one surface can never leak an unrelated product's draft that happens to share the same key. When resolution yields no draft (and no seed), `addItem` sends nothing.

Every posted item optimistically bumps a matching existing line's quantity in place (unless `sold_individually`) or is pushed as a new line, commits or rolls back through the mutation queue, and fires the legacy added-to-cart event once per call on success. A cycle whose requests all fail rolls the cart back to its pre-cycle snapshot and surfaces a `woocommerce/store-notices` notice. **Today's optimistic behavior is preserved** — the mutation batcher and its reconciliation are unchanged; only the API around it was reshaped.

The grouped branch keys entirely off the in-context product, resolving each child's draft from the resolved collection by id, from `cart.ts`:

```ts
if ( product.type === 'grouped' ) {
	const drafts = product.grouped_products
		.map( ( childId ) => state.findItem( { id: childId } ).draft )
		.filter(
			( draft ): draft is DraftItem =>
				!! draft && draft.quantity > 0
		);
	yield* postDraftItems( drafts, actions );
	return;
}
```

The simple/variable branch posts the in-context draft, or the seed when the surface was never touched, from `cart.ts`:

```ts
const { draft } = state.itemInContext;
const seed =
	getServerState< CartServerState >( 'woocommerce/cart' )
		?.draftSeeds?.[ resolveDraftKey() ]?.[ product.id ];
const itemToPost = draft ?? seed;
if ( ! itemToPost ) {
	return;
}
```

Usage — the form's submit handler, letting `addItem()` resolve what to post, from `blocks/add-to-cart-with-options/frontend.ts`:

```ts
// `addItem()` resolves what to post itself: the in-context product's single
// draft for a simple/variable product, or every grouped child's draft
// (auto-batched) for a grouped product.
yield wooActions.addItem();
```

Usage — a standalone button posting an explicit delta payload, from `atomic/blocks/product-elements/button/frontend.ts`:

```ts
yield actions.addItem( {
	id: product.id,
	quantity: context.quantityToAdd,
} );
```

**Why `addCartItem` is retained.** The out-of-scope shopper-lists blocks (`wishlist`, `add-to-wishlist-button`, `saved-for-later`) still consume `addCartItem` and the standalone `base/utils/variations/does-cart-item-match-attributes.ts` util, so both stay. New purchase UI should prefer `addItem` / `updateItem`.

### The container primitive

A container isolates its subtree by declaring an opaque, server-minted draft key in its `woocommerce/cart` context — a single `draftKey`:

```html
data-wp-context---draft-key='woocommerce/cart::{"draftKey":"<key>"}'
```

Any surface nested inside that container then resolves that key's collection; a surface with no such ancestor resolves the reserved session-global collection under `GLOBAL_DRAFT_KEY` (`'woocommerce/global'`). The `draftKey` key is what creates the boundary — other `woocommerce/cart` context keys (a mini-cart row's `id`/`key`) do **not**.

The two core containers WooCommerce ships mint their key server-side:

-   `ProductTemplate.php` — emits a `draftKey` bag on each Product Collection loop item (`<li>`), minting `collection/<queryId>/<productId>`, isolating every card in the grid. `queryId` is a static block attribute unchanged by pagination, so the card's key is stable across successive renders.
-   `SingleProduct.php` — emits a `draftKey` bag on the Single Product block wrapper, minting `single-product/<productId>/<n>`, where `<n>` is a per-request, per-product document-order occurrence counter. The counter is what keeps two Single Product blocks for the same product on one page mutually isolated.

An extension gets the same primitive from markup alone: it declares a namespaced key of its own (e.g. `data-wp-context---draft-key='woocommerce/cart::{"draftKey":"my-plugin/slot-1"}'`) on its container element, with zero core changes. Key formats are internal and unpromised; the only contract is equality.

The **three-hyphen** attribute name is required because `wp_interactivity_data_wp_context()` always emits an attribute literally named `data-wp-context`; an element that already carries a default context bag (here, the `woocommerce/products` product context) cannot carry a second one under the same attribute name — the HTML parser keeps the first and drops the second. `data-wp-context---<suffix>` is the supported way to add a second, namespaced context bag on one element (the same pattern the shopper-lists blocks already ship for `data-wp-context---notices`). Declaring a key boundary is documented as an open primitive for any surface — core or extension — that repeats or isolates purchase UI.

## The server half

Server-side rendering mints each collection's key and files each surface's initial draft as server state, so every visible value is correct in the initial HTML before hydration. **Nothing on the server writes a draft**: containers declare keys, purchase surfaces file seeds, and the client resolves and materializes drafts from there. Seeds no longer ride a per-surface init directive — the whole init-directive seeding path is gone; they ride server state, consulted only on the client.

### Container boundaries

`ProductTemplate.php` (Product Collection loop items) and `SingleProduct.php` (Single Product block) each mint a key and emit the `data-wp-context---draft-key` bag documented under [The container primitive](#the-container-primitive). Each also **injects the same `draftKey` into its existing `render_block_context` filter**, so descendant purchase surfaces render with the container's key in their block context and can file their seeds under it. That is the entire server-side isolation mechanism: a `draftKey` context bag plus block-context propagation.

The key each container mints is derived from identity the mint point already holds, from `ProductTemplate.php`:

```php
// `queryId` is a static parsed block attribute, unchanged by pagination,
// so this key is stable across successive renders of the same card.
$query_id  = $block->context['queryId'] ?? '0';
$draft_key = 'collection/' . $query_id . '/' . $product_id;
```

`ProductCollection/Renderer.php` contributes **nothing** to the cart store. `queryId` lives only on the Product Collection block's own context (via `providesContext`) and on the collection root's `data-wp-router-region` attribute; `ProductTemplate.php` reads it from block context to mint each card's key. The cart store never sees `queryId`.

### Draft seeding

Each purchase surface files its initial `cart/add-item` payload as server state, under its collection's key. The form-level emitter (`AddToCartWithOptions.php`) and the shared quantity-stepper emitter (`Utils::make_quantity_input_interactive()`, reached by the quantity-selector and grouped-product child-row blocks) each call:

```php
wp_interactivity_state( 'woocommerce/cart', array(
	'draftSeeds' => array(
		$draft_key => array(
			$product_id => $seed_payload, // an initial cart/add-item payload
		),
	),
) );
```

`$draft_key` is read from the surface's block context (`$block->context['draftKey'] ?? 'woocommerce/global'`) — the key its container injected, or the reserved global key when no container wraps it. The three seed-emitting blocks (`add-to-cart-with-options` and its quantity-selector and grouped-product child-row selectors) declare `draftKey` in their `usesContext` so the injected key actually reaches their render context. Seeds accumulate across surfaces into one `draftSeeds` payload and print once.

On the client, seeds are read **only through `getServerState( 'woocommerce/cart' )?.draftSeeds`** — the runtime's intact, per-page, navigation-fresh copy — and consulted at exactly two points: `upsertDraftItem` composes a new draft from the seed on the shopper's first write, and `addItem` falls back to the seed for an untouched simple/variable surface. A seed is **never applied into a collection**, so a re-delivered seed (on a region re-render or client-side navigation) can never replace or inject into an edited draft — the two live in different places. The runtime also auto-merges the incoming server state into a `state.draftSeeds` copy, but that client-side copy is **inert**: the store never reads it; `getServerState()` is the only seed source.

Grouped-product child rows seed at quantity `0` (each is optional), so an untouched grouped form posts none of them; a grouped parent seeds nothing at the form level (it has no single id to add). A directly-referenced variation carries its own `{ attribute, value }` pairs in its seed, so an untouched direct-variation surface posts a line the cart-line pairing ladder can match.

### Cart-state seeding

`BlocksSharedState::load_cart_state()` seeds the read-only cart mirror (`state.cart`) and the REST URL (`state.restUrl`) into `woocommerce/cart` state. It seeds **no draft addressing** — no keys, no seeds, and no notice id — so the client's `state.draftItems` starts empty on a fresh load; every collection is established by a shopper write against a container-declared or global key.

## The five use cases, working on this branch

### Use case 1 — a grouped form in any context

A grouped-product form must operate on its **own** children wherever it is rendered, and a simple/variable form rendered inside a grouped product's template must not pick up grouped behavior.

`addItem()` resolves the product from `productsState.productInContext` — the form's own in-context product — and branches on its type. For a grouped product it posts every child's draft in the resolved collection with `quantity > 0`, auto-batched. The children's drafts all resolve the **nearest declared key**: the global collection on the product's own template, the block's key inside a Single Product block, the card's key on a Product Collection card. Because the branch keys entirely off the in-context product, the same form works whether it is standalone, inside a Single Product block, or on a Product Collection card — and a simple form inside a grouped template resolves *its own* simple product, never the grouped one. Grouped behavior is a property of the product in context, not of the form's position.

### Use case 2 — two grouped products sharing a child

Two grouped forms on one page, each containing child product A: changing A's quantity in the first form must leave the second form's A untouched, and adding each must yield each form's own lines.

Any *second* form for a product arrives wrapped in a container that declares its own key (a Single Product block, or a Product Collection card), so the two forms resolve **different keys** and therefore different collections. Child A has an independent draft in each. A quantity edit routes through the resolving surface's own key, so editing A in form 1 leaves form 2's collection untouched. When each form submits, `addItem()` posts *its* resolved collection's children. Two same-product Single Product blocks stay isolated the same way — their containers mint keys distinguished by the occurrence counter's distinct `<n>`. One draft per product per key, and the key is the only isolation axis.

### Use case 3 — two variations of one parent

Two forms for two variations of the same parent (T-Shirt / Green and T-Shirt / Blue) must both land in the cart as independent lines with the correct attributes.

Each form resolves its own variation and mirrors the selection into the resolved collection's draft, keyed by the **resolved variation id**, with the chosen attributes riding in `draft.variation`, from `variation-selector/frontend.ts`:

```ts
const currentProductId = variationId ?? product.id;
wooActions.upsertDraftItem(
	{
		quantity: quantity[ currentProductId ],
		variation: selectedAttributes,
	},
	{ id: currentProductId }
);
```

Green and Blue have distinct variation ids, so they are two ids that **coexist in one collection** (and, when rendered in separate containers, in two collections — either way distinct). `addItem()` posts each draft verbatim; the pairing ladder's attribute comparison keeps the two lines distinct in the cart. Both land with the correct attributes.

### Use case 4 — synced page-wide surfaces (and the sticky bar)

A Single Product Template's main form and a second page-wide surface for the same product (a sticky add-to-cart bar) must stay in sync, while a surface that isolates its own subtree for the same product on the same page must not.

Neither page-wide surface declares a key, so both resolve the **reserved global collection** (`GLOBAL_DRAFT_KEY`). They therefore read and write the **same draft**. An edit on one surface (`setQuantity` → `upsertDraftItem`) updates the shared draft; the other surface's display reads through the same draft-backed source and repaints immediately, from `quantity-selector/frontend.ts`:

```ts
// Prefers the resolved collection's draft for the id — the value every surface
// sharing the collection writes to and reads from — falling back to this block
// instance's own locally-tracked quantity when the collection holds no draft yet.
const draftQuantity = cartState.itemInContext.draft?.quantity;
```

A plain Group block wrapping purchase UI declares no key, so it too shares the global collection — the boundary is drawn only by declaring a key. A **Single Product block** for the same product on the same page declares its own key, so its form is fully independent and does *not* sync. That is the boundary the model draws: surfaces that declare no key share the global collection and sync; a container that declares its own key stands apart.

Making this genuinely reciprocal for **variable** products took the bystander discipline above: a never-edited sibling surface's watches must not write its stale local state back over the shared draft, and a surface must validate its submit against the same draft-backed selection it displays. With those guards, the sticky bar reflects the main form's resolved quantity and attributes, nothing reverts during an idle hold, and the sticky bar's own button posts exactly what it displays.

### Use case 5 — a bundle-style extension, zero core changes

The `wc-bundle-demo` fixture (`tests/e2e/test-plugins/blocks/bundle-demo.php` and `bundle-demo.js`) is the worked extension-author example: a "bundle" of two independently configurable child products, added to the cart as one unit carrying the extension's own data, built on **nothing but today's Store API extension points and the private (locked) `woocommerce/cart` surface — no WooCommerce core file is changed.**

**Each slot declares its own literal key.** The `[wc_bundle_demo]` shortcode renders two slot elements plus an "Add bundle to cart" button. Each slot declares its **own literal, namespaced `woocommerce/cart` draft key** — `wc-bundle-demo/slot-1` / `wc-bundle-demo/slot-2` — the same container primitive core blocks use, via the three-hyphen bag, from `bundle-demo.php`:

```php
$draft_key_context_directive = 'data-wp-context---draft-key=\'woocommerce/cart::' . wp_json_encode(
	array( 'draftKey' => self::EXTENSION_NAMESPACE . '/' . $slot ),
	JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP
) . '\'';
```

Because each slot resolves its own key, picking the **same** product in both slots produces two independent drafts rather than one overwriting the other — the key boundary is exactly what makes that safe. There is **no module-scope registry and no slot-level init**: the demo addresses its own collections by the keys it declared, straight from markup.

**Slot writes go through the normal surface.** The slot's quantity input has no init; its `data-wp-on--change` handler creates the slot's one draft on the *first* edit with the store's public `upsertDraftItem` (its only call to it), and every edit after that is a **direct mutation** of the already-resolved draft object, from `bundle-demo.js`:

```js
const draft = resolveSlotDraft();

if ( draft ) {
	// Direct mutation of the resolved draft object — reactive per the
	// store's live envelope, honored by `addBundleToCart`'s compose
	// below, deliberately not routed through `upsertDraftItem`.
	draft.quantity = quantity;
	return;
}

// First edit for this slot: create its one draft via the store's
// public creation convenience, addressed by the slot's own declared
// context (`childId`), not by any registry.
const { childId } = getContext();
cart.actions.upsertDraftItem( { id: childId, quantity } );
```

This makes the fixture the end-to-end vehicle for both write paths: no shipped core consumer mutates a draft directly, so the demo proves the write policy holds.

**One composed `addItem( payload )`.** The button composes both slots' current drafts by reading `state.draftItems` **directly at the two declared keys** — keyed global state means an extension reads its own collections by key, with no registry bookkeeping and no cross-collection plumbing beyond the lock consent it already holds — into a single `cart/add-item` payload for the bundle product carrying `wc-bundle-demo/children` at the payload root, and posts it verbatim, from `bundle-demo.js`:

```js
const children = SLOT_DRAFT_KEYS.flatMap(
	( key ) => cart.state.draftItems[ key ] ?? []
).filter( ( draft ) => draft.quantity > 0 );

yield cart.actions.addItem( {
	id: bundleProductId,
	quantity: 1,
	[ CHILDREN_PROP ]: children, // 'wc-bundle-demo/children'
} );
```

Because the compose reads the live collections at click time, any direct write is honored, and a slot never edited has no collection at all — it contributes nothing, the safe, expected outcome for an untouched slot.

**Read back on `extensions['wc-bundle-demo']`.** Server-side, the fixture registers on today's extension points only — `ExtendSchema::register_endpoint_data()` for the schema/readback and the `woocommerce_store_api_add_to_cart_data` filter to fold the children prop into the line's `cart_item_data` (so core's line-identity hashing sees it and it persists). The children surface back on the cart-item response as `extensions['wc-bundle-demo'].children`, which is exactly what the envelope's pairing-ladder extension-prop comparison reads. No core server change is required for any of it.

Crucially, the fixture accesses `woocommerce/cart` with the **same `universalLock` a real third-party extension gets** — it is denied nothing a real extension will be denied while the store is private. That is what makes it a faithful preview of the extension-author experience.

## Cross-page navigation survival, on the stock router

The behavior the earlier model could not deliver — a draft surviving a page-to-page move — is now a property of the model, and this branch proves it against the **stock, supported region-based router**, with no experimental full-page navigation mode and no runtime patch. Stock WooCommerce performs no cross-*template* client-side navigation, so the proof rides a representative two-page fixture that drives the identical runtime path with fewer confounds: `wc-navigation-survival` (`tests/e2e/test-plugins/blocks/navigation-survival.php` and `navigation-survival.js`).

Two ordinary block-theme pages each wrap their content in one top-level `data-wp-router-region` sharing the same id. Each page renders an **unwrapped** purchase surface (declaring no key, so it resolves the reserved global collection, exactly like a plain container-free form) for the same product; page A additionally renders a **keyed** surface wrapped in the fixture's own declared key (`wc-navigation-survival/keyed`). A link on each page drives a genuine client-side navigation, reusing WooCommerce's own shipped pattern verbatim — dynamically import `@wordpress/interactivity-router` and call `actions.navigate()` on the link's `href`, from `navigation-survival.js`:

```js
const { actions } = yield import(
	'@wordpress/interactivity-router'
);

yield actions.navigate( ref.href );
```

Because both pages share one region id, the router matches and swaps that region in place: the JS runtime, its script modules, and the cart store's global draft state all stay alive across the move; the document never reloads. The suite drives four behaviors:

-   A draft **edited on a purchase surface survives** the cross-URL `actions.navigate()` round trip — page A → page B → back to page A shows the edited value.
-   An **unwrapped surface's edit is shared across pages**: because both pages' unwrapped surfaces resolve the identical global collection for the identical product id, an edit made on one appears on the other after navigating.
-   **Opt-in keyed isolation holds**: the keyed surface's own collection, addressed only by the fixture's declared key, is never resolved by either unwrapped surface, so its edit never leaks.
-   A **hard reload resets** every surface to its server-seeded default — drafts are client-side only, with no persistence layer.

The fixture's surfaces write drafts exactly as core surfaces do — `upsertDraftItem` on the first edit, direct mutation thereafter — and their quantity inputs reactively bind `state.quantityText` so a freshly server-rendered instance of a surface (a brand-new DOM node after a cross-page navigation) repaints from the surviving draft on its first frame, with no restore step.

## What changed against today's stores

Deltas from the currently-shipped stores (trunk):

-   **The root reactive store retired.** The cart store re-registers as `woocommerce/cart` (off the root `woocommerce` registration). The `woocommerce` context namespace and `wp_interactivity_config( 'woocommerce', … )` config bag are unaffected — only the reactive store moved.
-   **`mainProductInContext` → `baseProductInContext`.** Renamed for a vocabulary that reads correctly against `productVariationInContext` / `productInContext`. PHP mirror and all readers updated.
-   **Redundant cart members retired.** `batchAddCartItems`, `findItemInCart`, `removeCartItem`, and `refreshCartItems` are gone, folded into the new surface: auto-batching is now internal to `addItem`; line lookup is `findItem` and the envelope; removal is `removeItem`; refresh is `refresh`. Keeping both the old and new spellings would have blurred the one-vocabulary goal.
-   **`isInCart` dropped from the envelope.** No migrated consumer needed the "in the cart, but no unambiguous line" tri-state, so the envelope carries only `cartItem` / `draft`.
-   **`addCartItem` and `does-cart-item-match-attributes.ts` retained.** Their only remaining consumers are the out-of-scope shopper-lists blocks; removing them would have broken code this run does not migrate.

And, against the earlier version of this proposal — the retirement this revision makes:

-   **Context-held collections retired in favor of keyed global state.** The page-wide `state.draftItems` array and the per-container context-held collection arrays are replaced by one keyed map, `Record< DraftKey, DraftItem[] >`. A container now declares an opaque `draftKey` rather than initializing a collection array in context; the reserved `GLOBAL_DRAFT_KEY` collection is the fallback; and `resolveDraftKey()` / `resolveCollection()` implement `context.draftKey ?? GLOBAL_DRAFT_KEY`. Nothing a consumer or extension sees addresses a draft by anything but an opaque key.
-   **The Product-Collection-specific lifecycle machinery was deleted with no successor.** The module-private ledger, the register-or-restore init directive, the render-time bridge, the per-card init directive, and `seedDraftIfAbsent` — the entire apparatus that kept one surface's draft alive across a remount — are gone, because survival is now a property of where drafts live. The retired empty-collection and draft-seed context bags and their init directives go with them, along with the unused `removeDraftItem` action and the never-consumed PHP `noticeId` seed.
-   **Seeds re-vehicled onto server state.** Initial-draft defaults moved off per-surface init directives onto `draftSeeds[key][id]` server state, read via `getServerState()` and consulted only at creation and post-time fallback — never applied into a collection. `ProductCollection/Renderer.php` no longer contributes `queryId` to any cart context.
-   **Write policy unchanged in spirit: direct mutation stays first-class.** Mutating a resolved draft is a supported, reactive write honored by posting; `upsertDraftItem` stays as the creation convenience. This does not preclude — it sets up — a future revision that drops `upsertDraftItem` in favor of editing `itemInContext.draft` directly.

Because these are private stores, none of this is a breaking change today — but the whole point of the proposal is to converge on a surface worth making public, so the retirements matter. (The eventual PR must still state the backward-compatibility impact of reshaping the private locked store and removing its server markup and seeds, per repo policy; the store sits behind a consent string that declares exactly this instability.)

## Honest caveats

These behaviors changed or are knowingly incomplete, and are called out here rather than buried:

-   **Notice-suppression narrowing.** Form and button adds no longer pass a notice-suppression flag. An exact add stays notice-silent (the store proves the server total equals the pre-add total plus the posted delta and suppresses those lines), but a genuinely divergent server commit — a stock cap or a concurrent change — now surfaces a "quantity changed" notice where the previous code path was silent. This is a deliberate narrowing, not a correctness regression.
-   **Session-lifetime drafts.** Drafts persist across client-side (router-region) navigation such as collection pagination and cross-page moves, but a **full page reload discards them** and every surface re-seeds fresh. That is by design — there is no persistence layer.
-   **A remounted variable-product card presents unconfigured attributes.** Post-navigation display on every surface is whatever the generic key + global-state model yields; there is no display-reconstruction machinery. A variable product's attribute-selection UI lives in products-namespace context the remount discards, so a remounted card shows unconfigured attributes with whatever quantity the surviving draft holds. Display and posting stay in agreement, and the e2e assertions were updated to this actual outcome.
-   **Cross-page Single Product instance collisions.** Two Single Product blocks for the same product on two different pages each mint `single-product/<productId>/1`, so under client-side navigation they share one collection. Within any single page, isolation is fully preserved (the occurrence counter distinguishes the instances); the collision is observable only across a client-side page navigation, and is directionally consistent with the model's sharing-by-default rule.
-   **The inert `state.draftSeeds` merge byproduct.** The runtime auto-merges incoming server seeds into a `state.draftSeeds` copy on every navigation (`override=false`), so that copy accumulates stale entries across a session. It is never read — `getServerState()` is the only seed source — is invisible outside the locked store, and is bounded by the number of pages visited.
-   **The extension seed contract.** Declaring only the `draftKey` context bag gives an extension correct **client-side** addressing — its subtree resolves its collection, direct mutation and `upsertDraftItem` work, and it can read `state.draftItems[ <its own key> ]`. But **wrapping a core seed-emitting surface additionally requires propagating the key through `render_block_context`** so that surface files its seed under the extension's key; without it, an untouched wrapped surface has no seed under the resolved key and posts nothing — a safe no-data outcome, never wrong data.
-   **Same-`(key, id)` seed filing is last-write-wins.** When one product is filed twice under one key (e.g. standalone and as a grouped child, both unwrapped on one page), the later filing wins — the same order-dependent ambiguity a single shared collection already carried.
-   **Hand-authored collections without a `queryId`** all mint under `collection/0/<productId>` and can therefore share drafts per product across two such collections. Enhanced pagination requires authored `queryId`s, so this affects only hand-rolled markup.
-   **Duplicate-id drafts are possible via direct `push`.** Now that direct mutation is first-class, appending a second draft with an existing `id` straight onto a collection bypasses the one-draft-per-`id` invariant (lookups then resolve first-match). This is bounded — no shipped consumer appends directly; creation flows through `upsertDraftItem`, which maintains the invariant — and is a documented residual until a future revision designs direct creation.

## Status: private, locked, validation-grade

Every store in this folder — `woocommerce/cart` included — is registered with `lock: true` and consumed with the `universalLock` consent string:

```ts
import '@woocommerce/stores/woocommerce/cart';
import type { Store } from '@woocommerce/stores/woocommerce/cart';

// Stores are locked to prevent 3PD usage until the API is stable.
const universalLock =
	'I acknowledge that using a private store means my plugin will inevitably break on the next store release.';

const { state, actions } = store< Store >(
	'woocommerce/cart',
	{},
	{ lock: universalLock }
);
```

These stores are **not a public API** while the keyed draft model is being validated. Their members can change or disappear without notice; removing or changing state here is not a breaking change. Unlike the products store, the cart store has **no consent-gated PHP surface** — its server side is plain container-key and seed markup, so `universalLock` (JS, for the store lock) is the only consent string it involves. The `wc-bundle-demo` fixture uses that lock exactly as a third-party extension would, so we can preview the extension-author experience without committing to it. The branch is **validation-grade**: it proves the model and keeps the storefront UX identical to today, but it is not merge-ready production polish. If the model holds up under this review, the next step is hardening it and splitting a public surface off the private core.

## Reference companion

The [store README](./README.md) in this folder is the precise, durable reference for both stores — every state member, action, PHP-side surface, the container primitive, and the consent string, with the patterns-and-pitfalls that consumers need day to day. This proposal argues the design and shows it working; the README is where implementers look up the exact surface.
