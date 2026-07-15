# Proposal: Scoped draft cart items for the global Interactivity API stores

This branch reshapes WooCommerce's storefront Interactivity API stores around a single model — **shopper input lives in draft cart items, organized by scope** — re-registers the reactive cart store off the root `woocommerce` namespace as `woocommerce/cart`, and migrates every core storefront block that consumes today's product/cart stores onto the new surface. It ships a working, tested implementation of the full products + cart surface plus a small bundle-style demo extension, so every claim below is backed by code you can run on this branch.

This document is the proposal we want to align on. It is **not** a merge request for a finished, public API. The stores stay **private and locked** while we validate the model; the branch is **validation-grade, not merge-ready** (the storefront UX is unchanged, but production polish is out of scope). What we are asking reviewers to decide is whether the model — per-domain stores, scope-keyed drafts, an envelope that never guesses, actions-only writes — is the right foundation to build the public stores on. The [store README](./README.md) alongside this file is the precise reference companion; this document argues the design and shows it working.

## What this branch does (at a glance)

-   Registers `woocommerce/cart` (new) next to `woocommerce/products` (today's shape). The root `woocommerce` **reactive store** registration retires. The cross-domain `wp_interactivity_config( 'woocommerce', … )` **config bag** (currency, placeholder image, messages) is not a store and stays where it is.
-   Models shopper input as `state.draftItems`: an object keyed by scope, each scope holding an array of Store API `cart/add-item` payloads.
-   Resolves "which surface group is editing?" through one getter, `state.currentScope`, with a server-side symmetric `CartStore::get_current_scope()`.
-   Reads "the item in context" through a single envelope, `state.itemInContext`, that pairs a draft with its cart line exactly or not at all.
-   Writes drafts only through actions (`upsertDraftItem` / `removeDraftItem`), and posts them with `addItem()`.
-   Seeds scope and initial drafts server-side, so the initial HTML is correct before hydration, and copies seeds into client state **initialize-if-absent** so a re-render never clobbers a live edit.
-   Proves five hard storefront use cases with working code and automated tests, including a `wc-bundle-demo` fixture extension that adds a two-child bundle built on **zero core server changes**.

## Why: the stores we have, and the cases they can't express

The pre-existing stores are private, inconsistent, and structurally unable to express the hardest storefront scenarios. Cart state registered in the **root** `woocommerce` store; product data lived in its own `woocommerce/products` store. There was no shared vocabulary for "the same product configured in two different places on one page", so the following cases had no clean expression:

1.  A **grouped product** form rendered inside another product's template (a Single Product block, a Product Collection card) — it must operate on its own children, not the surrounding product.
2.  **Two grouped products** on one page that share a child product — editing the child in one form must not move the child in the other.
3.  **Two variations of the same parent** (T-Shirt / Green and T-Shirt / Blue) configured side by side — both must land in the cart as independent lines with the correct attributes.
4.  **Multiple page-wide surfaces for one product** (a main add-to-cart form and a sticky add-to-cart bar) — edits on one must reflect on the other, while a scope-isolating surface for the same product does not.
5.  A **bundle-style extension** — several independently configured child products added as one unit carrying the extension's own data, none of the children colliding with each other or with the same products elsewhere on the page.

Every one of these reduces to the same question: *which set of surfaces is editing the same thing, and what would they POST?* That is what "scope" and "draft" answer.

## The model: one idea, a handful of rules

**Shopper input lives in draft cart items, organized by scope.** From that, everything else follows.

-   **A draft is exactly a `cart/add-item` payload.** No mapping layer between what a form collects and what gets posted. Extension props ride at the payload root, namespaced (`my-plugin/gift-note`), exactly as the Store API accepts them. Drafts live **alongside — never inside** — the read-only mirror of the server cart.
-   **One draft per product `id` per scope.** `state.draftItems` maps scope → array of drafts; within a scope, at most one draft per product `id`. There is no per-draft key concept — the scope is the addressing axis.
-   **Sub-scopes instead of draft keys.** When a surface genuinely needs two independent drafts of the same product (two bundle slots offering the same child), it establishes two **sub-scopes** rather than reaching for a second key concept.
-   **Scope is established by context; consumers never set it.** The page establishes a page-wide scope; container blocks that wrap or repeat purchase UI override it for their subtree by emitting a `scope` value in the shared `woocommerce` context namespace. Consumer blocks read the resolved scope and never declare one.
-   **Page scope + container overrides, resolved by one getter.** `state.currentScope` resolves `context.scope ?? pageScope`. That conditional lives in exactly one place, client or server.
-   **Reads never guess line identity.** `state.itemInContext` pairs the in-context product's draft with its cart line **exactly, or not at all**. The server owns cart-line identity; the client never misattributes a line.
-   **Writes go through actions only.** `upsertDraftItem` / `removeDraftItem` are the sole write path; the envelope's `draft` is read-only by convention.
-   **Server renders first; seeds initialize-if-absent.** Scope and initial drafts are seeded server-side so the initial HTML is correct; the client copies a seed into a scope's bucket **only when that scope holds no draft for the product yet**, so a router-region re-render can never overwrite a live edit.

## Two schema questions this settles

### Where cart state lives

Cart state must not remain implicitly in the root `woocommerce` store by accident. It moves to a **dedicated `woocommerce/cart` store**. The root reactive-store registration retires. This is the change that lets the cart domain own its lock, its loading, and its surface, and lets future domains (checkout) slot in the same way.

The bare `woocommerce` **context namespace** stays — but only as a cross-domain vocabulary. Scope rides in it (`data-wp-context='woocommerce::{ "scope": … }'`) precisely because scope is not a cart-only concern: any future domain will read the same scope without depending on the cart store. The `wp_interactivity_config( 'woocommerce', … )` config bag (currency, placeholder image, messages) is likewise cross-domain and is not a store.

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
// An opaque, deterministic, namespaced scope id. Equal strings = same scope.
type Scope = string;

type SelectedAttributes = Omit< CartVariationItem, 'raw_attribute' >;

// A draft IS a cart/add-item payload. Extension props ride at the root, namespaced.
type DraftItem = {
	id: number; // product or variation id; also the per-scope uniqueness key
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

### State

| Member | Type | Notes |
| --- | --- | --- |
| `cart` | `Cart` mirror with optimistic items | Read-only mirror of the Store API `/cart` response; the server owns line identity |
| `draftItems` | `Record< Scope, DraftItem[] >` | Editable, client-only, keyed by scope; at most one draft per product id per scope |
| `pageScope` | `Scope \| undefined` | The page-wide scope, server-seeded once per request; deliberately has no client default |
| `currentScope` | `Scope` | Resolves `context.scope ?? pageScope` — the single place that rule lives |
| `findItem` | `({ scope?, id?, key?, filter? }) => Envelope` | The explicit lookup primitive behind `itemInContext` |
| `itemInContext` | `Envelope` | The in-context product's `currentScope` draft paired with its cart line |
| `inCartQuantity` | `number` | The in-context product's in-cart quantity (grouped aggregates children; variable resolves through the selected variation) |

`state` also carries request plumbing that is not part of the proposed purchase surface: `restUrl`, `nonce`, and `errorMessages` back the mutation/notice machinery.

### Actions

| Action | Signature | Role |
| --- | --- | --- |
| `upsertDraftItem` | `( partial, { scope?, id? }? ) => void` | The write path: create or merge-by-id a draft |
| `removeDraftItem` | `( { id?, scope? }? ) => void` | Remove a scope's draft; prune the bucket when empty |
| `seedDraftIfAbsent` | `() => void` | Copy the server-rendered draft seed into `currentScope` if absent |
| `addItem` | `( payload? ) => Promise< void >` | Post the in-context product's current-scope draft(s), or a payload verbatim |
| `updateItem` | `( { key, quantity } ) => Promise< void >` | Set a cart line's absolute quantity via `update-item` |
| `removeItem` | `( key ) => Promise< void >` | Remove a cart line by key |
| `refresh` | `() => Promise< void >` | Re-fetch the server cart, bypassing the browser cache |
| `addCartItem` | `( args, options? ) => Promise< void >` | The lower-level keyed/keyless add-or-update path, **retained** (see below) |

`actions` also carries internal helpers not part of the proposed purchase surface: `waitForIdle`, `showNoticeError`, and `updateNotices` support the mutation queue and notice dispatch.

### Reading: the in-context envelope

Every display binds to the envelope, so the same block code works on a product page, a collection card, and the mini-cart. `state.itemInContext` resolves the in-context product through `woocommerce/products`' `productInContext`, then pairs its `currentScope` draft with its cart line.

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

### Writing: actions only

Draft state is written exclusively through actions; no block mutates `state.draftItems` directly.

-   `upsertDraftItem( partial, { scope?, id? }? )` resolves the target scope (defaulting to `currentScope`), then merges `partial` into the scope's draft whose `id` matches — `id` from `options.id` when given, else `partial.id` — appending otherwise. It **rejects, leaving state unchanged**, on an invalid scope, an unresolvable numeric `id`, an in-place identity change (`partial.id` disagreeing with the resolved target), or a brand-new draft missing a numeric `quantity`. Each rejection is a dev-build `console.warn` and a production no-op.
-   `removeDraftItem( { id?, scope? }? )` removes a scope's draft and prunes the bucket once empty. It completes the write pair; core purchase blocks on this branch drive the shared draft through `upsertDraftItem`, and this is the symmetric primitive for surfaces that need to retract one.

Usage — mirroring a quantity edit into the current scope's draft, from `blocks/add-to-cart-with-options/frontend.ts`:

```ts
wooActions.upsertDraftItem( { quantity: value }, { id: productId } );
```

**Bystander discipline (why sync actually works).** Watch and init callbacks re-run on **every** surface that shares a scope, not just the one the shopper is using. So only a genuine shopper edit — or a clamp of the shared value itself — may write to a scope's draft. A sibling surface the shopper never touched must not write its stale local default over the shared draft. On this branch that shows up as a guard in the variation selector: a surface that never made a selection of its own does not overwrite "nothing selected" onto a variation another surface resolved, from `blocks/add-to-cart-with-options/variation-selector/frontend.ts`:

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

When building a new surface that shares a scope, gate every draft write behind an actual user action.

### Adding to cart: `addItem`

`addItem( payload? )` is polymorphic:

-   **`addItem()`** (no argument) resolves the in-context product via `woocommerce/products` and posts `currentScope`'s draft(s) for it: a simple or variable product's own single draft, or, for a grouped product, every child's draft (children resolved one-directionally through the products store) whose `quantity` is greater than `0`. Multiple children post as **one auto-batched** request set, not one request per child. It never posts another scope's or another product's draft.
-   **`addItem( payload )`** posts the payload **verbatim** — extension props at its root included — bypassing scope and product resolution entirely. This is the path an extension composing its own `cart/add-item` payload uses.

Every posted item optimistically bumps a matching existing line's quantity in place (unless `sold_individually`) or is pushed as a new line, commits or rolls back through the mutation queue, and fires the legacy added-to-cart event once per call on success. A cycle whose requests all fail rolls the cart back to its pre-cycle snapshot and surfaces a `woocommerce/store-notices` notice. **Today's optimistic behavior is preserved** — the mutation batcher and its reconciliation are unchanged; only the API around it was reshaped.

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

Usage — a keyed line update and a removal, from `blocks/mini-cart/frontend.ts`:

```ts
yield actions.updateItem( {
	key: cartItemState.cartItem.key,
	quantity: cartItemState.cartItem.quantity,
} );
// …
yield actions.removeItem( cartItemState.cartItem.key );
```

**Why `addCartItem` is retained.** The out-of-scope shopper-lists blocks (`wishlist`, `add-to-wishlist-button`, `saved-for-later`) still consume `addCartItem` and the standalone `base/utils/variations/does-cart-item-match-attributes.ts` util, so both stay. New purchase UI should prefer `addItem` / `updateItem`.

### Scope ids

Scopes are deterministic, opaque, namespaced strings. The only guaranteed property is that equal strings denote the same scope; no code parses a scope's internals. Determinism matters: because a scope id derives from stable structural identity rather than a per-render random value, a router-region re-render reproduces the same scope and never orphans a live draft.

| Scope id shape | Minted by | Establishes |
| --- | --- | --- |
| `page/<queried-object-id>` | `SharedStores/CartStore.php` | The page-wide scope (the floor) |
| `collection/<queryId>/<productId>` | `BlockTypes/ProductTemplate.php` | Each Product Collection loop item |
| `single-product/<productId>/<n>` | `BlockTypes/SingleProduct.php` | A Single Product block (`<n>` = per-render occurrence counter, so two blocks for the same product get distinct scopes) |
| `<plugin>/<anything>` | Extensions (e.g. `wc-bundle-demo/slot-1`) | Any surface an extension isolates, including bundle sub-scopes |

## The server half

Server-side rendering seeds both scope and initial drafts, so every visible value is correct in the initial HTML before hydration.

### The scope service

`SharedStores/CartStore.php` mints and tracks scopes. Its static methods are consent-gated with the experimental-API statement (`CartStore::$consent_statement`):

```text
I acknowledge that using experimental APIs means my theme or plugin will inevitably break in the next version of WooCommerce
```

-   `CartStore::mint_page_scope( $consent )` mints `page/<queried-object-id>` once per request and seeds it into `woocommerce/cart` state via `wp_interactivity_state`. Memoized: repeated calls return the same value without re-seeding.
-   `CartStore::push_scope( $consent, $scope )` / `CartStore::pop_scope( $consent )` maintain a render-time scope stack. A container pushes its scope before rendering inner blocks and pops it afterward. The page scope is the floor and is not on the stack, so `pop_scope()` is a no-op once the stack is empty.
-   `CartStore::get_current_scope( $consent )` returns the innermost pushed scope, or the page scope when nothing is pushed — the PHP symmetric of the client `state.currentScope` getter, so purchase-UI PHP nested inside a container resolves the same scope the client will.

### The two core establishers

`ProductTemplate.php` (Product Collection loop items) and `SingleProduct.php` (Single Product block) each mint their scope, push it around their inner-block render, and emit it as context. They are the only two core scope establishers; scope establishment is documented as an open primitive for any other surface — core or extension — that repeats or isolates purchase UI.

Both emit the scope through a **three-hyphen context bag**, `data-wp-context---scope`, in the shared `woocommerce` namespace:

```html
data-wp-context---scope='woocommerce::{"scope":"collection/1/123"}'
```

The three-hyphen form exists because `wp_interactivity_data_wp_context()` always emits an attribute literally named `data-wp-context`; an element that already carries a default context bag (here, the `woocommerce/products` product context) cannot carry a second one under the same attribute name — the HTML parser keeps the first and drops the second. `data-wp-context---<suffix>` is the supported way to add a second, namespaced context bag on one element (the same pattern the shopper-lists blocks already ship for `data-wp-context---notices`).

### Initialize-if-absent draft seeding

A purchase surface seeds its initial draft the same way. `AddToCartWithOptions/Utils.php` emits the initial `cart/add-item` payload as a `woocommerce/cart` draft-seed context bag and wires an init directive:

```html
data-wp-context---draft-seed='woocommerce/cart::{"draftSeed":{"id":123,"quantity":1}}'
data-wp-init--seed-draft='woocommerce/cart::actions.seedDraftIfAbsent'
```

`seedDraftIfAbsent()` reads the **server-rendered** context — `getServerContext< { draftSeed?: DraftItem } >( 'woocommerce/cart' )?.draftSeed`, immune to the reactive proxy's client-side edits — resolves `currentScope`, and copies the seed into that scope's bucket **only when the scope holds no draft for the seed's product `id`**. That rule is what lets a router-region re-render's seed read run harmlessly: a present draft is left untouched, so a shopper's in-progress edit is never clobbered.

## The five use cases, working on this branch

### Use case 1 — a grouped form in any context

A grouped-product form must operate on its **own** children wherever it is rendered, and a simple/variable form rendered inside a grouped product's template must not pick up grouped behavior.

`addItem()` resolves the product from `productsState.productInContext` — the form's own in-context product — and branches on its type. For a grouped product it posts every child's draft in the current scope with `quantity > 0`, auto-batched, from `cart.ts`:

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

Any *second* form for a product arrives wrapped in a scope-establishing container, so the two forms resolve to different scopes (two Single Product blocks → `single-product/<id>/1` and `single-product/<id>/2`; two collection cards → distinct `collection/<queryId>/<productId>`). Child A therefore has an **independent draft in each scope**: `draftItems[scopeA]` and `draftItems[scopeB]` each hold their own A. `upsertDraftItem` is keyed by `(scope, id)`, so editing A in form 1 writes only `draftItems[scopeA]`. When each form submits, `addItem()` posts *its* scope's children. One draft per product per scope, and scope is the only addressing axis — no draft-key bookkeeping is involved.

### Use case 3 — two variations of one parent

Two forms for two variations of the same parent (T-Shirt / Green and T-Shirt / Blue) must both land in the cart as independent lines with the correct attributes.

Each form resolves its own variation and mirrors the selection into that scope's draft, keyed by the **resolved variation id**, with the chosen attributes riding in `draft.variation`, from `variation-selector/frontend.ts`:

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

Green and Blue have distinct variation ids, so they are distinct drafts (distinct even inside one scope, and always distinct across the separate Single Product blocks that render them). `addItem()` posts each draft verbatim; the pairing ladder's attribute comparison (`matchesSelectedAttributes`) keeps the two lines distinct in the cart. Both land with the correct attributes.

### Use case 4 — synced page-wide surfaces (and the sticky bar)

A Single Product Template's main form and a second page-wide surface for the same product (a sticky add-to-cart bar) must stay in sync, while a scope-overriding surface for the same product on the same page must not.

Neither page-wide surface is wrapped in a scope-establishing container, so both resolve `currentScope` to the same `pageScope`. They therefore read and write the **same draft** in `draftItems[pageScope]`. An edit on one surface (`setQuantity` → `upsertDraftItem`) updates the shared draft; the other surface's display reads through the same draft-backed source and repaints immediately, from `quantity-selector/frontend.ts`:

```ts
// Prefers the current scope's cart draft for the id — the value every surface
// sharing the scope writes to and reads from — falling back to this block
// instance's own locally-tracked quantity when the scope holds no draft yet.
const draftQuantity = cartState.itemInContext.draft?.quantity;
```

A **Single Product block** for the same product on the same page establishes `single-product/<id>/<n>` — a different scope — so its form is fully independent and does *not* sync. That is the boundary the model draws: page-wide surfaces share a scope and sync; a scope-isolating surface stands apart.

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

**Sub-scopes per slot.** The `[wc_bundle_demo]` shortcode renders two slot elements plus an "Add bundle to cart" button. Each slot establishes its own sub-scope via the shared `woocommerce` namespace — the same three-hyphen mechanism the core establishers use — from `bundle-demo.php`:

```php
$scope = self::EXTENSION_NAMESPACE . '/' . $slot; // wc-bundle-demo/slot-1, …

$scope_context_directive = 'data-wp-context---scope=\'woocommerce::' . wp_json_encode(
	array( 'scope' => $scope ),
	JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP
) . '\'';
```

**One `upsertDraftItem` per slot.** Each slot's quantity input upserts its own draft. `upsertDraftItem` resolves its target scope from `currentScope`, which the slot's `data-wp-context---scope` override makes the slot's own sub-scope — so picking the **same** product in both slots produces two independent drafts rather than one overwriting the other, from `bundle-demo.js`:

```js
const { childId } = getContext();
cart.actions.upsertDraftItem( { id: childId, quantity } );
```

**One composed `addItem( payload )`.** The button reads each slot's draft directly off `draftItems` (a slot's sub-scope holds at most one draft by construction), composes them into a single `cart/add-item` payload for the bundle product carrying `wc-bundle-demo/children` at the payload root, and posts it verbatim, from `bundle-demo.js`:

```js
const children = SLOT_SCOPES.map(
	( scope ) => cart.state.draftItems[ scope ]?.[ 0 ]
).filter( ( draft ) => draft && draft.quantity > 0 );

yield cart.actions.addItem( {
	id: bundleProductId,
	quantity: 1,
	[ CHILDREN_PROP ]: children, // 'wc-bundle-demo/children'
} );
```

**Read back on `extensions['wc-bundle-demo']`.** Server-side, the fixture registers on today's extension points only — `ExtendSchema::register_endpoint_data()` for the schema/readback and the `woocommerce_store_api_add_to_cart_data` filter to fold the children prop into the line's `cart_item_data` (so core's line-identity hashing sees it and it persists). The children surface back on the cart-item response as `extensions['wc-bundle-demo'].children`, which is exactly what the envelope's pairing-ladder extension-prop comparison reads. No core server change is required for any of it.

Crucially, the fixture accesses `woocommerce/cart` with the **same `universalLock` a real third-party extension gets** — it is denied nothing a real extension will be denied while the store is private. That is what makes it a faithful preview of the extension-author experience.

## What changed against today's stores

-   **The root reactive store retired.** The cart store re-registers as `woocommerce/cart` (off the root `woocommerce` registration). The `woocommerce` context namespace and `wp_interactivity_config( 'woocommerce', … )` config bag are unaffected — only the reactive store moved — because scope must be readable by future domains without a cart dependency.
-   **`mainProductInContext` → `baseProductInContext`.** Renamed for a vocabulary that reads correctly against `productVariationInContext` / `productInContext`. PHP mirror and all readers updated.
-   **Redundant cart members retired.** `batchAddCartItems`, `findItemInCart`, `removeCartItem`, and `refreshCartItems` are gone, folded into the new surface: auto-batching is now internal to `addItem`; line lookup is `findItem` and the envelope; removal is `removeItem`; refresh is `refresh`. Keeping both the old and new spellings would have blurred the one-vocabulary goal.
-   **`isInCart` dropped from the envelope.** It was carried conditionally through the design as the only representation of "in the cart, but no unambiguous line". No migrated consumer needed the tri-state, so the envelope carries only `cartItem` / `draft`.
-   **`addCartItem` and `does-cart-item-match-attributes.ts` retained.** Their only remaining consumers are the out-of-scope shopper-lists blocks; removing them would have broken code this run does not migrate.

Because these are private stores, none of this is a breaking change today — but the whole point of the proposal is to converge on a surface worth making public, so the retirements matter.

## Honest caveats

Three behaviors changed or are knowingly incomplete, and are called out here rather than buried:

-   **Notice-suppression narrowing.** Form and button adds no longer pass a notice-suppression flag. An exact add stays notice-silent (the store proves the server total equals the pre-add total plus the posted delta and suppresses those lines), but a genuinely divergent server commit — a stock cap or a concurrent change — now surfaces a "quantity changed" notice where the previous code path was silent. This is a deliberate narrowing, not a correctness regression.
-   **Remounted variable-product-card presentation.** After an enhanced-pagination remount, a Product Collection card's variation resolution — which lives in client context the remount discards — can present as unconfigured even though the draft still persists in the ledger. Display and action stay in agreement, and the behavior matches the pre-change branch; re-associating scope drafts with product context at mount is future work.
-   **Session-scoped drafts.** Drafts persist across client-side (router-region) navigation such as collection pagination, but a **full page reload discards them**. That is by design, not a gap.

## Status: private, locked, validation-grade

Every store in this folder — `woocommerce/cart` included — is registered with `lock: true` and consumed with the `universalLock` consent string:

```ts
import '@woocommerce/stores/woocommerce/cart';
import type { Store as CartStore } from '@woocommerce/stores/woocommerce/cart';

// Stores are locked to prevent 3PD usage until the API is stable.
const universalLock =
	'I acknowledge that using a private store means my plugin will inevitably break on the next store release.';

const { state, actions } = store< CartStore >(
	'woocommerce/cart',
	{},
	{ lock: universalLock }
);
```

These stores are **not a public API** while the scoped-draft model is being validated. Their members can change or disappear without notice; removing or changing state here is not a breaking change. The `wc-bundle-demo` fixture uses the lock exactly as a third-party extension would, so we can preview the extension-author experience without committing to it. The branch is **validation-grade**: it proves the model and keeps the storefront UX identical to today, but it is not merge-ready production polish. If the model holds up under this review, the next step is hardening it and splitting a public surface off the private core.

## Reference companion

The [store README](./README.md) in this folder is the precise, durable reference for both stores — every state member, action, PHP method, scope-id shape, and consent string, with the patterns-and-pitfalls that consumers need day to day. This proposal argues the design and shows it working; the README is where implementers look up the exact surface.
