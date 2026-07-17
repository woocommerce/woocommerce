# Proposal: Context-held draft collections for the global Interactivity API stores

This branch reshapes WooCommerce's storefront Interactivity API stores around a single model — **shopper input lives in draft cart items, held in context-established draft collections** — re-registers the reactive cart store off the root `woocommerce` namespace as `woocommerce/cart`, and migrates every core storefront block that consumes today's product/cart stores onto the new surface. It ships a working, tested implementation of the full products + cart surface plus a small bundle-style demo extension, so every claim below is backed by code you can run on this branch.

This document is the proposal we want to align on. It is **not** a merge request for a finished, public API. The stores stay **private and locked** while we validate the model; the branch is **validation-grade, not merge-ready** (the storefront UX is unchanged, but production polish is out of scope). What we are asking reviewers to decide is whether the model — per-domain stores, context-held draft collections, an envelope that never guesses, direct draft mutation as a first-class write — is the right foundation to build the public stores on. The [store README](./README.md) alongside this file is the precise reference companion; this document argues the design and shows it working.

This is a revision of an earlier version of this proposal, which organized drafts by **scope** rather than by collection. [Why collections, not scope](#why-collections-not-scope) below records what changed and why; if you reviewed the earlier version, start there.

## What this branch does (at a glance)

-   Registers `woocommerce/cart` (new) next to `woocommerce/products` (today's shape). The root `woocommerce` **reactive store** registration retires. The cross-domain `wp_interactivity_config( 'woocommerce', … )` **config bag** (currency, placeholder image, messages) is not a store and stays where it is.
-   Models shopper input as `state.draftItems`: a flat `DraftItem[]` — the page-wide draft collection — where each `DraftItem` is a Store API `cart/add-item` payload.
-   Lets any container block isolate its subtree by initializing an empty `draftItems` array in its `woocommerce/cart` context; every read and write resolves the nearest such collection, falling back to the page-wide one, through one internal resolver.
-   Reads "the item in context" through a single envelope, `state.itemInContext`, that pairs a draft with its cart line exactly or not at all.
-   Treats **direct mutation of a resolved draft as a first-class write** (reactive, honored by posting), with `upsertDraftItem` / `removeDraftItem` retained as creation/removal conveniences, and posts drafts with `addItem()`.
-   Seeds initial drafts server-side, so the initial HTML is correct before hydration, and copies seeds into client state **initialize-if-absent** so a re-render never clobbers a live edit.
-   Proves five hard storefront use cases with working code and automated tests, including a `wc-bundle-demo` fixture extension that adds a two-child bundle built on **zero core server changes**.

## Why collections, not scope

The earlier version of this proposal addressed drafts by **scope**. Shopper input lived in a single global map keyed by opaque, deterministic scope ids; container blocks minted those ids and emitted them through context; a store getter resolved which id was current for any given surface; and a consent-gated PHP service mirrored that same resolution during server render, so purchase-UI PHP nested inside a container resolved the same id the client would.

That machinery worked, but it exposed an **addressing concept as public surface**. A consumer or extension could see the ids, read the current-id getter, and call into the PHP service — and, having seen them, could come to depend on them. Yet the only thing the model actually delivered was two guarantees: **subtree isolation** (a surface that wraps or repeats purchase UI keeps its subtree's drafts separate from the rest of the page) and **one lifecycle guarantee** (an edited draft on a Product Collection card survives an enhanced-pagination remount). An entire addressable id space, a resolver getter, and a render-time service — to deliver isolation plus one remount behavior.

This revision makes **the draft collections themselves the API**. A container isolates its subtree by initializing `draftItems: []` in its `woocommerce/cart` context — plain markup, no id to mint, no service to call. The page-wide `state.draftItems` is the fallback. The one implicit behavior the id space provided — remount survival — moved into machinery no consumer can reach, even with lock consent. Nothing a consumer sees names or addresses a scope, because there is no scope; there is only the context tree and the collections it holds.

Three premises this revision accepts make that possible:

-   **Subtree-only reads.** A container's collection lives in its own `woocommerce/cart` context and is readable only from within that subtree — the Interactivity API's own context-inheritance rule. We accept that, and drop the cross-subtree / global draft addressing the previous model offered (reading another surface's drafts by id, or a `scope` argument on `findItem`). No shipped consumer needed it; the one place that did — the bundle demo reading across slots — is redesigned to compose from its own registry instead ([use case 5](#use-case-5--a-bundle-style-extension-zero-core-changes)).
-   **Direct mutation is first-class.** Block and extension code may mutate a resolved draft directly (`itemInContext.draft.quantity = 3`); such writes are reactive and honored by `addItem` posting. `upsertDraftItem` / `removeDraftItem` remain as creation/removal conveniences, but drafts are no longer read-only and writes are no longer "actions only". This keeps the door open for a planned future revision that drops `upsertDraftItem` in favor of editing the envelope's draft directly.
-   **Lifecycle machinery is internal and unexposed.** Remount survival is delivered by three cooperating pieces inside `cart.ts` — a module-private ledger, a register-or-restore init the server resolves by name, and a render-time bridge — none of which is a member of the store surface. Consumers get the guarantee without an addressable handle to it.

## The cases today's stores can't express

The pre-existing stores are private, inconsistent, and structurally unable to express the hardest storefront scenarios. Cart state registered in the **root** `woocommerce` store; product data lived in its own `woocommerce/products` store. There was no shared vocabulary for "the same product configured in two different places on one page", so the following cases had no clean expression:

1.  A **grouped product** form rendered inside another product's template (a Single Product block, a Product Collection card) — it must operate on its own children, not the surrounding product.
2.  **Two grouped products** on one page that share a child product — editing the child in one form must not move the child in the other.
3.  **Two variations of the same parent** (T-Shirt / Green and T-Shirt / Blue) configured side by side — both must land in the cart as independent lines with the correct attributes.
4.  **Multiple page-wide surfaces for one product** (a main add-to-cart form and a sticky add-to-cart bar) — edits on one must reflect on the other, while a surface that isolates its own subtree for the same product does not.
5.  A **bundle-style extension** — several independently configured child products added as one unit carrying the extension's own data, none of the children colliding with each other or with the same products elsewhere on the page.

Every one of these reduces to the same question: *which set of surfaces is editing the same thing, and what would they POST?* Draft collections answer it: surfaces that resolve the same collection edit the same thing; a container that initializes its own collection separates its subtree from the rest.

## The model: one idea, a handful of rules

**Shopper input lives in draft cart items, held in context-established draft collections.** From that, everything else follows.

-   **A draft is exactly a `cart/add-item` payload.** No mapping layer between what a form collects and what gets posted. Extension props ride at the payload root, namespaced (`my-plugin/gift-note`), exactly as the Store API accepts them. Drafts live **alongside — never inside** — the read-only mirror of the server cart.
-   **`state.draftItems` is the page-wide collection.** A flat `DraftItem[]`; within any collection, at most one draft per product `id`. There is no per-draft key concept — the collection is the isolation boundary.
-   **A container isolates its subtree by declaring its own collection.** A block that wraps or repeats purchase UI initializes an empty `draftItems` array in its `woocommerce/cart` context; every surface nested inside it then resolves that collection instead of the page-wide one. Consumer blocks never declare a collection — they read the resolved one.
-   **One collection per isolation boundary.** When a surface genuinely needs two independent drafts of the same product (two bundle slots offering the same child), it initializes **two collections** rather than reaching for a second addressing concept — one draft per product per collection is the invariant, and the collection is the only isolation axis.
-   **Resolution lives in exactly one place.** A module-private `resolveDraftItems()` implements `context.draftItems ?? state.draftItems`. That conditional lives in one place, client-side; no consumer writes it. Consumers read a resolved draft through the envelope, and a container's own subtree code may read the collection it initialized straight from its `woocommerce/cart` context.
-   **Reads never guess line identity.** `state.itemInContext` pairs the in-context product's draft with its cart line **exactly, or not at all**. The server owns cart-line identity; the client never misattributes a line.
-   **Writes are direct, or through the two conveniences.** Mutating a resolved draft (`itemInContext.draft.quantity = 3`) is first-class and reactive; `upsertDraftItem` / `removeDraftItem` remain for creating and removing drafts.
-   **Server renders first; seeds initialize-if-absent.** Initial drafts are seeded server-side so the initial HTML is correct; the client copies a seed into the resolved collection **only when that collection holds no draft for the product yet**, so a router-region re-render can never overwrite a live edit.

## Two schema questions this settles

### Where cart state lives

Cart state must not remain implicitly in the root `woocommerce` store by accident. It moves to a **dedicated `woocommerce/cart` store**. The root reactive-store registration retires. This is the change that lets the cart domain own its lock, its loading, and its surface, and lets future domains (checkout) slot in the same way.

The bare `woocommerce` **context namespace** and the `wp_interactivity_config( 'woocommerce', … )` **config bag** (currency, placeholder image, messages) stay — as cross-domain vocabulary — but they no longer carry any cart addressing. A container's draft collection is namespaced to `woocommerce/cart` itself (`data-wp-context---draft-items='woocommerce/cart::{"draftItems":[]}'`), because a collection is a cart concern; there is nothing here to hoist into the shared namespace.

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

There is no draft-collection identity type on the surface — no id, no handle. A collection is just the `DraftItem[]` a container holds in its `woocommerce/cart` context, or the page-wide `state.draftItems`.

### State

| Member | Type | Notes |
| --- | --- | --- |
| `cart` | `Cart` mirror with optimistic items | Read-only mirror of the Store API `/cart` response; the server owns line identity |
| `draftItems` | `DraftItem[]` | The page-wide draft collection; at most one draft per product id. A container initializes its own `draftItems` in context to isolate its subtree; every read/write resolves the nearest one, falling back to this |
| `findItem` | `({ id?, key?, filter? }) => Envelope` | The explicit lookup primitive behind `itemInContext` |
| `itemInContext` | `Envelope` | The in-context product's resolved-collection draft paired with its cart line |
| `inCartQuantity` | `number` | The in-context product's in-cart quantity (grouped aggregates children; variable resolves through the selected variation) |

`state` also carries request plumbing that is not part of the proposed purchase surface: `restUrl`, `nonce`, and `errorMessages` back the mutation/notice machinery.

Note what is **not** here: no page-wide addressing state, no current-collection getter, no collection-identity type. Nothing on the state surface identifies a collection — the resolver reads the context tree directly, and consumers reach a resolved draft only through the envelope.

### Actions

| Action | Signature | Role |
| --- | --- | --- |
| `upsertDraftItem` | `( partial, { id? }? ) => void` | Creation/merge convenience: create or merge-by-id a draft in the resolved collection |
| `removeDraftItem` | `( { id? }? ) => void` | Remove the resolved collection's draft for a product |
| `seedDraftIfAbsent` | `() => void` | Copy the server-rendered draft seed into the resolved collection if absent |
| `addItem` | `( payload? ) => Promise< void >` | Post the in-context product's resolved-collection draft(s), or a payload verbatim |
| `updateItem` | `( { key, quantity } ) => Promise< void >` | Set a cart line's absolute quantity via `update-item` |
| `removeItem` | `( key ) => Promise< void >` | Remove a cart line by key |
| `refresh` | `() => Promise< void >` | Re-fetch the server cart, bypassing the browser cache |
| `addCartItem` | `( args, options? ) => Promise< void >` | The lower-level keyed/keyless add-or-update path, **retained** (see below) |

Neither `upsertDraftItem` nor `removeDraftItem` nor `findItem` carries a collection argument — there is no addressing option to pass, because the resolver reads the calling surface's own context. `actions` also carries internal helpers not part of the proposed purchase surface: `registerOrRestoreDraftCollection` (draft-lifecycle machinery, described under [The server half](#the-server-half)), and `waitForIdle`, `showNoticeError`, and `updateNotices` (which support the mutation queue and notice dispatch).

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

Usage — `inCartQuantity` backing a button's label, from `atomic/blocks/product-elements/button/frontend.ts`:

```ts
const quantity = showTemporaryNumber
	? tempQuantity || 0
	: wooState.inCartQuantity;
```

Usage — `findItem` pairing a known mini-cart row, from `blocks/mini-cart/frontend.ts`:

```ts
const {
	cartItem: { id, key },
} = getContext< CartItemContext >( 'woocommerce/cart' );

const cartItem = ( woocommerceState.findItem( { id, key } )
	.cartItem || {} ) as CartItem;
```

### Writing: direct mutation, plus two conveniences

Draft state is written **directly, or through the two conveniences** — whichever is clearer at the call site.

-   **Direct mutation is first-class.** The `draft` an envelope hands back is the live reactive object from the resolved collection, not a copy. Mutating it — `itemInContext.draft.quantity = 3`, or `findItem( { id } ).draft` — is supported, notifies every surface that resolves the same collection, and is honored by `addItem` posting (which reads the collection at call time). The same is true of the resolved collection itself: a container's own subtree code may mutate the collection it initialized.
-   `upsertDraftItem( partial, { id? }? )` resolves the nearest draft collection, then merges `partial` into the draft whose `id` matches — `id` from `options.id` when given, else `partial.id` — appending otherwise. It **rejects, leaving state unchanged**, on an unresolvable numeric `id`, an in-place identity change (`partial.id` disagreeing with the resolved target), or a brand-new draft missing a numeric `quantity`. Each rejection is a dev-build `console.warn` and a production no-op.
-   `removeDraftItem( { id? }? )` removes the resolved collection's draft for a product and prunes it once gone. It rejects (state unchanged) on a non-numeric `id`; naming a product with no matching draft is a silent no-op.

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

-   **`addItem()`** (no argument) resolves the in-context product via `woocommerce/products` and posts the resolved collection's draft(s) for it: a simple or variable product's own single draft, or, for a grouped product, every child's draft (children resolved one-directionally through the products store) whose `quantity` is greater than `0`. Multiple children post as **one auto-batched** request set, not one request per child. It never posts another collection's or another product's draft, and sends nothing when the resolution yields no draft.
-   **`addItem( payload )`** posts the payload **verbatim** — extension props at its root included — bypassing collection and product resolution entirely. This is the path an extension composing its own `cart/add-item` payload uses.

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

A container isolates its subtree by initializing an empty draft collection in its `woocommerce/cart` context — plain markup, with no id to mint and no service to call:

```html
data-wp-context---draft-items='woocommerce/cart::{"draftItems":[]}'
```

Any surface nested inside that container then resolves that collection; a surface with no such ancestor resolves the page-wide `state.draftItems`. The `draftItems` key is what creates the boundary — other `woocommerce/cart` context keys (a draft seed, a mini-cart row's `id`/`key`) do **not**.

The two core containers WooCommerce ships are:

-   `ProductTemplate.php` — emits the empty-collection bag on each Product Collection loop item (`<li>`), isolating every card in the grid.
-   `SingleProduct.php` — emits the same bag on the Single Product block wrapper, isolating that block from the page-wide surfaces.

The **three-hyphen** attribute name is required because `wp_interactivity_data_wp_context()` always emits an attribute literally named `data-wp-context`; an element that already carries a default context bag (here, the `woocommerce/products` product context) cannot carry a second one under the same attribute name — the HTML parser keeps the first and drops the second. `data-wp-context---<suffix>` is the supported way to add a second, namespaced context bag on one element (the same pattern the shopper-lists blocks already ship for `data-wp-context---notices`). Declaring a collection boundary is documented as an open primitive for any surface — core or extension — that repeats or isolates purchase UI.

## The server half

Server-side rendering declares collection boundaries and seeds initial drafts, so every visible value is correct in the initial HTML before hydration. **Nothing on the server addresses a draft**: the context tree in the delivered markup is the whole story — there is no minting, no push/pop, no render-time resolver service.

### Container boundaries

`ProductTemplate.php` (Product Collection loop items) and `SingleProduct.php` (Single Product block) each emit the empty-collection bag documented under [The container primitive](#the-container-primitive). That is the entire server-side isolation mechanism: an empty `data-wp-context---draft-items` bag, nothing else.

`ProductCollection/Renderer.php` adds a `queryId` to the collection root's own `woocommerce/product-collection` context bag (the same value already exposed in that element's `data-wp-router-region` attribute). This is product-collection domain data, not a cart concept; the cart store's internal draft-lifecycle machinery reads it to keep a card's edited draft alive across an enhanced-pagination remount (see [Draft lifecycle machinery](#draft-lifecycle-machinery)).

### Initialize-if-absent draft seeding

A purchase surface seeds its initial draft as a context bag consumed by an init directive. `AddToCartWithOptions/Utils.php` emits the initial `cart/add-item` payload as a `woocommerce/cart` draft-seed context bag and wires an init directive:

```html
data-wp-context---draft-seed='woocommerce/cart::{"draftSeed":{"id":123,"quantity":1}}'
data-wp-init--seed-draft='woocommerce/cart::actions.seedDraftIfAbsent'
```

`seedDraftIfAbsent()` reads the **server-rendered** context — `getServerContext< { draftSeed?: DraftItem } >( 'woocommerce/cart' )?.draftSeed`, immune to the reactive proxy's client-side edits — resolves the nearest draft collection, and copies the seed into it **only when that collection holds no draft for the seed's product `id`**. That rule is what lets a router-region re-render's seed read run harmlessly: a present draft is left untouched, so a shopper's in-progress edit is never clobbered.

### Cart-state seeding

`BlocksSharedState::load_cart_state()` seeds the read-only cart mirror (`state.cart`), the REST URL, and the notice id into `woocommerce/cart` state. It seeds **no page-wide addressing state** — the collection tree is established entirely by container markup, and the page-wide `state.draftItems` starts empty on the client.

### Draft lifecycle machinery

An edited draft on a Product Collection card survives an enhanced-pagination away-and-back remount. The machinery that makes that hold is **internal to the store and unreachable by any consumer, even one holding lock consent** — three cooperating pieces, none a member of the store surface:

-   a **module-private ledger** (a plain module variable, never a store member) that holds each collection card's live collection, keyed by an internally derived `(queryId, productId)` identity;
-   a **register-or-restore init** the loop item's server markup resolves by name (`data-wp-init="woocommerce/cart::actions.registerOrRestoreDraftCollection"`), which registers a card's collection on first render and restores it from the ledger on a remount;
-   a **render-time bridge** inside the resolver, so the first post-remount paint already shows the restored draft.

`registerOrRestoreDraftCollection` appears on `actions` **only** so the server-emitted directive string can resolve it; it is not part of the documented surface, derives its own card identity from context, and is a no-op wherever that identity cannot be derived. Extensions should not call it. This is the successor to the retired remount guarantee — the same behavior, with no addressable id anywhere in the delivered markup or the store surface.

## The five use cases, working on this branch

### Use case 1 — a grouped form in any context

A grouped-product form must operate on its **own** children wherever it is rendered, and a simple/variable form rendered inside a grouped product's template must not pick up grouped behavior.

`addItem()` resolves the product from `productsState.productInContext` — the form's own in-context product — and branches on its type. For a grouped product it posts every child's draft in the resolved collection with `quantity > 0`, auto-batched, from `cart.ts`:

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

Because the branch keys entirely off the in-context product, the same form works whether it is standalone, inside a Single Product block, or on a Product Collection card — and a simple form inside a grouped template resolves *its own* simple product, never the grouped one. Grouped behavior is a property of the product in context, not of the form's position.

### Use case 2 — two grouped products sharing a child

Two grouped forms on one page, each containing child product A: changing A's quantity in the first form must leave the second form's A untouched, and adding each must yield each form's own lines.

Any *second* form for a product arrives wrapped in a container that initializes its own collection (a Single Product block, or a Product Collection card), so the two forms resolve to **different collections**. Child A therefore has an independent draft in each: each collection holds its own A. A quantity edit routes through the resolving surface's own collection, so editing A in form 1 leaves form 2's collection untouched. When each form submits, `addItem()` posts *its* resolved collection's children. One draft per product per collection, and the collection is the only isolation axis — no draft-key bookkeeping is involved.

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

Green and Blue have distinct variation ids, so they are distinct drafts (distinct even within one collection, and always distinct across the separate containers that render them). `addItem()` posts each draft verbatim; the pairing ladder's attribute comparison keeps the two lines distinct in the cart. Both land with the correct attributes.

### Use case 4 — synced page-wide surfaces (and the sticky bar)

A Single Product Template's main form and a second page-wide surface for the same product (a sticky add-to-cart bar) must stay in sync, while a surface that isolates its own subtree for the same product on the same page must not.

Neither page-wide surface initializes its own collection, so both resolve the **page-wide `state.draftItems`**. They therefore read and write the **same draft**. An edit on one surface (`setQuantity` → `upsertDraftItem`) updates the shared draft; the other surface's display reads through the same draft-backed source and repaints immediately, from `quantity-selector/frontend.ts`:

```ts
// Prefers the resolved collection's draft for the id — the value every surface
// sharing the collection writes to and reads from — falling back to this block
// instance's own locally-tracked quantity when the collection holds no draft yet.
const draftQuantity = cartState.itemInContext.draft?.quantity;
```

A **Single Product block** for the same product on the same page initializes its own collection, so its form is fully independent and does *not* sync. That is the boundary the model draws: page-wide surfaces resolve the same collection and sync; a container that declares its own stands apart.

Making this genuinely reciprocal for **variable** products took the bystander discipline above: a never-edited sibling surface's watches must not write its stale local state back over the shared draft (which would destroy the editing surface's quantity), and a surface must validate its submit against the same draft-backed selection it displays. `watchQuantityConstraints` clamps the **draft's own** quantity rather than resetting it to a bystander's local default, from `variation-selector/frontend.ts`:

```ts
const draftQuantity = cartState.itemInContext.draft?.quantity;
const currentValue =
	typeof draftQuantity === 'number'
		? draftQuantity
		: quantity[ variation.id ];
```

With those guards, the sticky bar reflects the main form's resolved quantity and attributes, nothing reverts during an idle hold, and the sticky bar's own button posts exactly what it displays.

### Use case 5 — a bundle-style extension, zero core changes

The `wc-bundle-demo` fixture (`tests/e2e/test-plugins/blocks/bundle-demo.php` and `bundle-demo.js`) is the worked extension-author example: a "bundle" of two independently configurable child products, added to the cart as one unit carrying the extension's own data, built on **nothing but today's Store API extension points and the public (locked) `woocommerce/cart` surface — no WooCommerce core file is changed.**

**Each slot is a real container.** The `[wc_bundle_demo]` shortcode renders two slot elements plus an "Add bundle to cart" button. Each slot declares its **own empty `woocommerce/cart` collection** — the same container primitive core blocks use — via the three-hyphen bag, from `bundle-demo.php`:

```php
$draft_items_context_directive = 'data-wp-context---draft-items=\'woocommerce/cart::' . wp_json_encode(
	array( 'draftItems' => array() ),
	JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP
) . '\'';
```

**A slot init seeds one draft, then registers its collection.** Each slot's quantity input wires a `data-wp-init` that resolves the slot's own collection (from its `woocommerce/cart` context), seeds its single draft with the store's public `upsertDraftItem` (a creation convenience — the fixture's only call to it), and records the slot's live collection in the demo's own **module-scope registry**, keyed by slot id, from `bundle-demo.js`:

```js
slotCollections.set( slotId, collection );

const { ref } = getElement();
const quantity = Number( ref.value );

if ( ! Number.isFinite( quantity ) || quantity < 0 ) {
	return;
}

cart.actions.upsertDraftItem( { id: childId, quantity } );
```

Because each slot resolves its own collection, picking the **same** product in both slots produces two independent drafts rather than one overwriting the other — the collection boundary is exactly what makes that safe.

**Every later edit is a direct mutation.** Once the draft exists, a shopper's quantity change is a **direct write** of the resolved draft object — no action call — and the slot's bound `<span>` (`state.slotQuantityText`) reads the same draft, so the write's re-render is observable, from `bundle-demo.js`:

```js
const draft = resolveSlotDraft();

if ( ! draft ) {
	return;
}

// Direct mutation of the resolved draft object — reactive per the
// store's live envelope, honored by `addBundleToCart`'s compose below,
// deliberately not routed through `upsertDraftItem`.
draft.quantity = quantity;
```

This makes the fixture the end-to-end vehicle for direct mutation: no shipped core consumer mutates a draft directly, so the demo proves the write policy holds.

**One composed `addItem( payload )`.** The button composes both slots' current drafts from the demo's own registry — reading the registry's live collections at click time, so any direct write is honored — into a single `cart/add-item` payload for the bundle product carrying `wc-bundle-demo/children` at the payload root, and posts it verbatim, from `bundle-demo.js`:

```js
const children = [ ...slotCollections.values() ]
	.map( ( collection ) => collection[ 0 ] )
	.filter( ( draft ) => draft && draft.quantity > 0 );

yield cart.actions.addItem( {
	id: bundleProductId,
	quantity: 1,
	[ CHILDREN_PROP ]: children, // 'wc-bundle-demo/children'
} );
```

Composing from the demo's own registry rather than reading across the store is the redesign the subtree-only premise forced: with collection reads confined to their own subtree, an out-of-slot button cannot read another slot's collection through the store, so the demo keeps its own live references. This is exactly the pattern an extension with cross-surface composition needs.

**Read back on `extensions['wc-bundle-demo']`.** Server-side, the fixture registers on today's extension points only — `ExtendSchema::register_endpoint_data()` for the schema/readback and the `woocommerce_store_api_add_to_cart_data` filter to fold the children prop into the line's `cart_item_data` (so core's line-identity hashing sees it and it persists). The children surface back on the cart-item response as `extensions['wc-bundle-demo'].children`, which is exactly what the envelope's pairing-ladder extension-prop comparison reads. No core server change is required for any of it.

Crucially, the fixture accesses `woocommerce/cart` with the **same `universalLock` a real third-party extension gets** — it is denied nothing a real extension will be denied while the store is private. That is what makes it a faithful preview of the extension-author experience.

## What changed against today's stores

Deltas from the currently-shipped stores (trunk):

-   **The root reactive store retired.** The cart store re-registers as `woocommerce/cart` (off the root `woocommerce` registration). The `woocommerce` context namespace and `wp_interactivity_config( 'woocommerce', … )` config bag are unaffected — only the reactive store moved.
-   **`mainProductInContext` → `baseProductInContext`.** Renamed for a vocabulary that reads correctly against `productVariationInContext` / `productInContext`. PHP mirror and all readers updated.
-   **Redundant cart members retired.** `batchAddCartItems`, `findItemInCart`, `removeCartItem`, and `refreshCartItems` are gone, folded into the new surface: auto-batching is now internal to `addItem`; line lookup is `findItem` and the envelope; removal is `removeItem`; refresh is `refresh`. Keeping both the old and new spellings would have blurred the one-vocabulary goal.
-   **`isInCart` dropped from the envelope.** No migrated consumer needed the "in the cart, but no unambiguous line" tri-state, so the envelope carries only `cartItem` / `draft`.
-   **`addCartItem` and `does-cart-item-match-attributes.ts` retained.** Their only remaining consumers are the out-of-scope shopper-lists blocks; removing them would have broken code this run does not migrate.

And, against the earlier version of this proposal (the changes this revision makes):

-   **Scope retired in favor of collections.** The scope id space, the current-id resolver getter, the page-wide addressing state, and the consent-gated PHP service that mirrored resolution at render time are all deleted. In their place: a container declares an empty collection in its `woocommerce/cart` context, the page-wide `state.draftItems` is the fallback, one module-private resolver implements `context.draftItems ?? state.draftItems`, and the remount guarantee moved into machinery no consumer can reach. Nothing a consumer or extension sees addresses a draft by id.
-   **Write policy: actions-only → direct mutation first-class.** Drafts are no longer read-only and writes are no longer "actions only". Mutating a resolved draft is a supported, reactive write honored by posting; `upsertDraftItem` / `removeDraftItem` stay as creation/removal conveniences. This does not preclude — it sets up — a future revision that drops `upsertDraftItem` in favor of editing `itemInContext.draft` directly.

Because these are private stores, none of this is a breaking change today — but the whole point of the proposal is to converge on a surface worth making public, so the retirements matter. (The eventual PR must still state the backward-compatibility impact of deleting the consent-gated PHP methods and reshaping the private locked store, per repo policy; both sit behind consent strings that declare exactly this instability.)

## Honest caveats

These behaviors changed or are knowingly incomplete, and are called out here rather than buried:

-   **Notice-suppression narrowing.** Form and button adds no longer pass a notice-suppression flag. An exact add stays notice-silent (the store proves the server total equals the pre-add total plus the posted delta and suppresses those lines), but a genuinely divergent server commit — a stock cap or a concurrent change — now surfaces a "quantity changed" notice where the previous code path was silent. This is a deliberate narrowing, not a correctness regression.
-   **Extension containers inside router regions get isolation but not remount survival.** The remount-survival machinery is wired only for Product Collection loop items — the surface the lifecycle guarantee names. An extension's own container inside a collection (or a Single Product block nested inside a collection card) isolates its subtree correctly, but its draft is not preserved across an enhanced-pagination remount; such a surface owns its own persistence.
-   **Duplicate-id drafts are possible via direct `push`.** Now that direct mutation is first-class, appending a second draft with an existing `id` straight onto a collection bypasses the one-draft-per-`id` invariant (lookups then resolve first-match). This is bounded — no shipped consumer appends directly; creation flows through `upsertDraftItem`, which maintains the invariant — and is a documented residual until a future revision designs direct creation.
-   **A raw `context.draftItems` reader can see one pre-reconciliation frame after a card remount.** Core getter-driven surfaces are bridged and paint correctly on the first post-remount render; only an extension binding that reads `context.draftItems` raw can observe a single stale frame, for one effect-cycle on one navigation edge.
-   **A remounted variable-product card presents its server-seeded default.** A variable product's variation selection lives in client context the remount discards, so a remounted card resolves against — and displays — its server-seeded default rather than the prior selection. Display and action stay in agreement, and the behavior matches the pre-change branch.
-   **Session-lifetime drafts.** Drafts persist across client-side (router-region) navigation such as collection pagination, but a **full page reload discards them**. That is by design, not a gap.

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

These stores are **not a public API** while the draft-collection model is being validated. Their members can change or disappear without notice; removing or changing state here is not a breaking change. Unlike the products store, the cart store has **no consent-gated PHP surface** — its server side is plain container and seed markup, so `universalLock` (JS, for the store lock) is the only consent string it involves. The `wc-bundle-demo` fixture uses that lock exactly as a third-party extension would, so we can preview the extension-author experience without committing to it. The branch is **validation-grade**: it proves the model and keeps the storefront UX identical to today, but it is not merge-ready production polish. If the model holds up under this review, the next step is hardening it and splitting a public surface off the private core.

## Reference companion

The [store README](./README.md) in this folder is the precise, durable reference for both stores — every state member, action, PHP-side surface, the container primitive, and the consent string, with the patterns-and-pitfalls that consumers need day to day. This proposal argues the design and shows it working; the README is where implementers look up the exact surface.
