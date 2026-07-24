# Proposal: The draft view — direct mutation as the store's sole write, kept in sync with product selection

This branch completes the write model the keyed-global-state proposal established: shopper draft input is recorded by **direct mutation of the resolved draft — through a live, materialize-on-write draft view that is now the store's only write surface** — and that write stays in agreement with the `woocommerce/products` store's own selection automatically, in both directions, with no action call needed on either side. `upsertDraftItem`, the one draft-writing action the prior revision kept as a creation convenience, is removed with **nothing action-shaped replacing it**: creation folds into the same spelling as every other edit. It ships a working, tested implementation of the full products + cart surface — including every core storefront block already migrated onto it — plus a small bundle-style demo extension and a two-page navigation fixture, so every claim below is backed by code you can run on this branch.

This document is the proposal we want to align on. It is **not** a merge request for a finished, public API. The stores stay **private and locked** while we validate the model; the branch is **validation-grade, not merge-ready** (the storefront UX is unchanged, but production polish is out of scope). What we are asking reviewers to decide is whether the model — per-domain stores, a keyed global draft home addressed by server-defined keys, an envelope that pairs through the *effective* payload rather than guessing, direct mutation through the draft view as the **only** write, and automatic two-way sync with the products store's own selection — is the right foundation to build the public stores on. The [store README](./README.md) alongside this file is the precise reference companion; this document argues the design and shows it working.

This revision supersedes an earlier iteration that kept `upsertDraftItem` as a creation/merge convenience alongside direct mutation, and left the cart draft and the products-store selection in sync only where block code did that work explicitly (a watcher and a bystander guard in the variation selector). [Why direct mutation replaces the creation action](#why-direct-mutation-replaces-the-creation-action) and [The sync: one mechanism, one storage, both directions](#the-sync-one-mechanism-one-storage-both-directions) below record what changed and why. The addressing model underneath — opaque, server-defined draft keys in global state — is unchanged from the revision before that; [Why keys, not context-held collections](#why-keys-not-context-held-collections) records that history for readers who have not seen it.

## What this branch does (at a glance)

-   Registers `woocommerce/cart` (new) next to `woocommerce/products` (today's shape). The root `woocommerce` **reactive store** registration retires. The cross-domain `wp_interactivity_config( 'woocommerce', … )` **config bag** (currency, placeholder image, messages) is not a store and stays where it is.
-   Models shopper input as `state.draftItems`: a **keyed map of draft collections**, `Record< DraftKey, DraftItem[] >`, where each `DraftItem` is a Store API `cart/add-item` payload and a `DraftKey` is an opaque address for one collection.
-   Lets any container block isolate its subtree by declaring an opaque, server-minted `draftKey` in its `woocommerce/cart` context; a surface wrapped in no container resolves the reserved `GLOBAL_DRAFT_KEY` (`'woocommerce/global'`). Every read and write resolves the nearest declared key through one internal resolver.
-   Creates collections **lazily**, on a shopper's first write through the **draft view** — the store's only write surface. The server never seeds `state.draftItems`; the map starts empty on a fresh load.
-   Reads "the item in context" through a single envelope, `state.itemInContext`, that pairs a draft with its cart line through the *effective* payload — exactly, or not at all.
-   Makes **direct mutation of a resolved draft, through the draft view, the store's only write**: `upsertDraftItem` is removed, with nothing action-shaped in its place, and posts drafts with `addItem()`.
-   Keeps the cart draft and the `woocommerce/products` store's own selection **in sync automatically, in both directions**: writing the draft's `variation` moves what the products store resolves for every surface sharing the collection, and assigning the products store's `productVariationInContext` writes the same family draft through the same write routine.
-   Resolves cart-line identity and posting fallbacks through the *effective* payload — a draft's specified selection completed from the matching variation's own meta — fixing two defects: a draft addressed directly by variation id now pairs to its own server line, and an untouched surface whose id already resolves to a variation now posts instead of silently doing nothing.
-   Files initial-draft seeds as server-rendered state (`draftSeeds[key][id]`), read on the client **only through `getServerState()`** and consulted at exactly two points — the draft view's materialize-on-write, and `addItem`'s effective-seed fallback — never applied into a collection, so a re-delivered seed can never overwrite a live edit.
-   Makes survival a property of the model, not a mechanism: drafts survive Product Collection region remounts — now re-presenting a variable card's recorded attribute selection, not only its quantity — **and** genuine cross-page client-side navigation, and reset on a hard reload.
-   Proves five hard storefront use cases with working code and automated tests, a `wc-bundle-demo` fixture extension that adds a two-child bundle on **zero core server changes**, and a two-page navigation fixture that drives a real cross-URL `actions.navigate()` round trip.

## Why direct mutation replaces the creation action

The prior revision kept `upsertDraftItem` as a creation/merge convenience alongside first-class direct mutation, explicitly leaving the door open to a later revision that would drop it "in favor of editing `itemInContext.draft` directly." This is that revision, and it goes all the way: `upsertDraftItem` is removed, and **nothing action-shaped replaces it**. Every draft write — the first one for a surface and the hundredth — is the same spelling: mutate the `draft` an envelope hands back.

The spec that authorized this removal also authorized a fallback: the design phase could keep a minimal action surface if full removal turned out to genuinely overcomplicate the model, provided the justification was written down. **That escape hatch was not exercised.** Zero draft-writing actions remain on the `woocommerce/cart` surface, and none were added to make the sync (below) work either.

Full removal did not overcomplicate the model, for three reasons:

-   **Creation folds into the same spelling as every edit.** The `draft` an envelope hands back is now a live view: reading it before a draft exists answers the surface's seed values, and writing it — on that same untouched surface — materializes a new draft composed from the seed in one atomic step. There is no separate "create" operation to design around; the first write *is* the creation, and the view is what makes that possible.
-   **The one invariant that actually changed was wrong.** The removed action used to reject a brand-new draft with no numeric `quantity`, on the theory that a draft must carry one. That was client-side policy contradicting the real Store API contract: `cart/add-item` accepts an omitted quantity and defaults it to the product's own minimum purchase quantity. The invariant is relaxed to warn-and-materialize — `DraftItem.quantity` is optional — because the premise it enforced was never actually true server-side.
-   **Every call site got shorter, not longer.** The two core blocks that used to call the action, and the two e2e fixtures that used to call it for a slot's first edit, all migrate to strictly fewer lines: one `draft.prop = value` assignment replaces both the action call and the direct-mutation branch that used to sit next to it.

Two alternatives were considered and rejected. An **assignable `draft` accessor** (`itemInContext.draft = { ... }`) would have kept two ways to write — assignment for creation, mutation for everything after — and would intercept nothing on a nested write like `draft.variation = attrs`, which is exactly the write the sync (below) needs to intercept in order to migrate a draft's `id`. **Retaining a minimal creation action** would have failed the spec's own zero-action target without the complexity justification the spec required to keep it — and, per the point above, there was no complexity left to justify keeping it against.

The trade this makes: `draft` is now always truthy once an id resolves, so code that used to tell an untouched surface apart from a materialized one by checking whether the draft itself was `undefined` must check the value it actually cares about instead. In exchange, the set trap that intercepts every write is also the single interception point the sync below needs, and the same trap is why creation costs the model nothing new.

## The sync: one mechanism, one storage, both directions

The prior revision recorded a shopper's variation pick twice: the variation selector wrote the products-store selection through a watcher, and — behind a bystander guard — reflected the same pick into the draft's `variation`. This revision keeps the cart draft and the products-store selection in agreement with **one** mechanism, in **one** storage, argued as a single idea rather than two coordinated ones:

-   **Writing the draft moves the selection.** `productVariationInContext`, the getter `woocommerce/products` exposes for "the currently selected variation," now **derives** from the in-context product's family draft in the `woocommerce/cart` collection — read from the raw collection, never from a seed or a cart getter. A draft carrying a resolvable `variation` resolves the matching variation; a draft parked at a variation id with no attributes resolves that variation directly (the id-direct rung, load-bearing for a quantity-first edit — see [Effective-payload identity resolution](#effective-payload-identity-resolution)); an unconfigured or parent-id draft resolves nothing. Writing `itemInContext.draft.variation = attrs` therefore moves what this getter returns — and every price, SKU, stock, gallery, and hidden-input binding derived from it — for **every surface that shares the collection**, with no further call.
-   **Writing the selection moves the draft.** `productVariationInContext` gained a **setter**, the same type as the getter: assign a variation belonging to the in-context product's family, or `null` to clear. It validates family membership, derives the attributes to write from the base product's own `variations[]` entry for the assigned id — never from the assigned object's own `attributes`, which the real Store API serializer leaves empty — and writes the result into the shared family draft through the **exact same write routine** every other draft write goes through.
-   **Both directions are one mechanism because there is only one storage.** The draft is the only place a variation selection actually lives once one has been made; `productVariationInContext`'s getter is a read of it, and its setter is a write to it. There is no second copy to keep synchronized, and therefore nothing that can drift: reading either store's selection getter after either kind of write returns the same answer, because both answers come from the same object.

This is why the sync needed no new action, no watcher, and no reflection code: the write side already existed (the draft view above), and the read side already existed (`productVariationInContext`'s getter); this revision's whole contribution is making the getter read the draft first, and giving the getter a setter that writes through the same routine the getter reads.

An "any" attribute the setter's caller has no recorded value for cannot be invented: the write degrades honestly to a **partial selection** filed at the parent product, with a dev warning naming the missing attribute(s), rather than displaying a variation the shopper could not actually add. The setter's input type — a whole `ProductResponseItem` variation — has no way to carry a value nobody supplied; a prior recorded selection (or the shopper's own subsequent pick) remains the only source for it.

## Effective-payload identity resolution

Two defects surfaced once a draft could be addressed directly by variation id — the case a quantity-first edit on a default-attribute surface produces, and a case the sync above makes routine: a family draft can now be filed at a variation id with an **empty** `variation` (nothing specified; the variation's own attributes apply). Comparing that draft's raw, empty `variation` against a cart line's fully-attributed `variation` never matched — and the same emptiness meant `addItem()`'s no-payload fallback, which only ever looked up a seed filed under the surface's own id, found nothing for a surface whose id had resolved to a variation whose seed lived only under its parent.

Both defects have the same root cause and the same fix: a draft's raw `variation` and a cart line's `variation` are not directly comparable once a specified selection can be legitimately incomplete. The fix mirrors what the server itself already does. `cart/add-item` fills every omitted concrete attribute from the variation's own meta and only *then* computes line identity — so a request naming a variation id with no attributes at all *is*, server-side, identical to a request that spelled out every attribute by hand. The client now asks the server's own question at the two points that compare identity:

-   **Pairing.** `findItem`'s identity rung compares a candidate cart line against the draft's *effective* attributes — the specified selection (always a real array through the draft view; `[]` means nothing specified) completed, attribute by attribute, from the matching variation's own meta wherever the draft leaves one unspecified. This is what lets an **id-direct draft** — a variation id with an empty `variation`, produced by, say, a quantity-first edit on a surface that never touched its attributes — pair to its own server cart line: server and client complete the same missing attributes from the same variation meta and arrive at the same identity, instead of the draft permanently failing to pair with the line it actually represents.
-   **Posting.** `addItem()`'s no-payload fallback resolves the *effective* seed — the family seed re-addressed to the in-context id, not just the seed filed directly under it. An untouched, resolved-variation surface whose seed was only ever filed under its parent id now posts a minimal `{ id, quantity }` the server fills authoritatively, instead of finding no seed under its own id and silently sending nothing.

An attribute that neither the draft specifies nor the variation's own meta fixes — a genuine "any" nobody has resolved — leaves the payload **incomplete** rather than inventing a value: it pairs to nothing, mirroring the 400 the server would return for the equivalent add. What ships is never posted with invented values either: **posted bodies stay verbatim-minimal**, exactly what the draft/seed specifies — this normalization runs only at the comparison boundaries, never on the request body itself, so a client with stale product data never fails a post that the server itself would accept.

Alternatives considered: filling `draft.variation` at write time was rejected because it cannot fix the untouched-surface case (no write ever happens there, so there is nothing to fill) and it would have posted client-derived values instead of the minimal payload the server can fill more authoritatively; relaxing the identity predicate itself was rejected because it is shared, frozen code the shopper-lists blocks also depend on, and an id-alone relaxation would pair "any"-variation states the server itself refuses to equate; filling only on the read side (inside the draft view's own `variation` trap) was rejected because it fixes pairing but not posting, and would create three divergent shapes for one selection (the view's read, the raw draft, and the post) where there should be one.

## Why keys, not context-held collections

This section is carried forward unchanged from the prior revision — the keys-vs-context-held-collections decision is not what this revision revisits. It is kept here for readers who have not seen it; if you have, skip ahead to [The cases today's stores can't express](#the-cases-todays-stores-cant-express).

The shipped model before that revision — rev-1 — addressed drafts by **context-held collections**. Shopper input lived in a `draftItems` array that a container block initialized in its own `woocommerce/cart` context; a flat page-wide `state.draftItems` array was the fallback; and one internal resolver read the nearest context-held collection, falling back to the page-wide one. Declaring a collection was plain markup — no id to mint, no service to call — and the model delivered subtree isolation cleanly.

But context lives and dies with its DOM subtree. When the Interactivity API remounts a region — Product Collection enhanced pagination swaps the grid — or a client-side navigation replaces the page, the subtree is torn down and re-created, and every draft held in that subtree's context is destroyed with it. A shopper's in-progress quantity, variation, or extension input vanished on exactly the interactions the runtime was built to make seamless.

rev-1 papered over **one** instance of this. Product Collection cards under enhanced pagination kept a card's edited draft alive with Product-Collection-specific restore machinery: a **module-private ledger** keyed by a derived card identity, a **register-or-restore init** directive on every loop item that the server resolved by name, and a **render-time bridge** inside the resolver so the first post-remount paint already showed the restored draft. That covered the collection grid and nothing else — a Single Product block, or an extension's own container inside a collection, got isolation but no survival.

The revision after rev-1 moved drafts out of context and into **keyed global state**. A container no longer holds a collection array in context; it declares an opaque **key**, and the collection itself lives in the store's global `state.draftItems` map under that key. Because keys are minted on the server and are **render-stable** within a browsing session, a surface re-renders — after a remount, after a navigation — with the *same* key in fresh markup, and its collection re-attaches by resolution alone. Nothing reconstructs anything.

**Survival becomes a property of where drafts live, and the entire lifecycle apparatus is deleted, not replaced.** The module-private ledger, the register-or-restore init, the render-time bridge, the per-card init directive, and `seedDraftIfAbsent` are gone with **no successor**. There is no restore protocol, because there is nothing to restore: global state outlives the region swaps and navigations that used to destroy context, and render-stable keys re-address the same collection wherever a surface paints. What rev-1 delivered for one surface with bespoke machinery, the model now delivers for every surface for free.

Three premises that revision carried forward or shifted made that work:

-   **Direct mutation stays first-class.** Block and extension code may mutate a resolved draft directly (`itemInContext.draft.quantity = 3`); such writes are reactive and honored by `addItem` posting. `upsertDraftItem` remained as the creation/merge convenience at the time. (The rev-1 `removeDraftItem` companion had no shipped caller and was dropped in the cleanup audit.)
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
-   **Writes go through the draft view — the store's only write surface.** Mutating a resolved draft (`itemInContext.draft.quantity = 3`) is the one way to record shopper input; there is no draft-writing action. The view materializes a draft from the surface's seed on the first write to it and merges onto the live draft on every write after — first edit and hundredth edit are the same spelling.
-   **Collections are lazy; the server never seeds `draftItems`.** A collection materializes on the shopper's first write to its key; the map starts empty on a fresh load. Initial-draft defaults ride server-rendered state (`draftSeeds`) and are consulted only when a write materializes a new draft, and at posting's effective-seed fallback — never applied into a collection — so a re-render can never overwrite a live edit.

## Two schema questions this settles

### Where cart state lives

Cart state must not remain implicitly in the root `woocommerce` store by accident. It moves to a **dedicated `woocommerce/cart` store**. The root reactive-store registration retires. This is the change that lets the cart domain own its lock, its loading, and its surface, and lets future domains (checkout) slot in the same way.

The bare `woocommerce` **context namespace** and the `wp_interactivity_config( 'woocommerce', … )` **config bag** (currency, placeholder image, messages) stay — as cross-domain vocabulary — but they no longer carry any cart addressing. A container's draft key is namespaced to `woocommerce/cart` itself (`data-wp-context---draft-key='woocommerce/cart::{"draftKey":"<key>"}'`), because a draft key is a cart concern; there is nothing here to hoist into the shared namespace.

### One store per domain, not one shared store

The alternative was a single `woocommerce` store with domain sub-trees. We chose **per-domain stores** — `woocommerce/cart` and `woocommerce/products` — with a one-directional coupling: cart consults products, never the reverse. The trade is explicit cross-store plumbing (there is precedent: cart already dispatches to `woocommerce/store-notices`) in exchange for domain ownership, independent locking, and a clean seam for future domains.

`woocommerce/products` mostly keeps today's shape: the rename (`mainProductInContext` → `baseProductInContext`, see below) carries forward from the prior revision, and this revision adds exactly one more change to it — `productVariationInContext` gaining a setter, the products-side half of the sync (see [The sync](#the-sync-one-mechanism-one-storage-both-directions)). Apart from that accessor, all the new machinery is in `woocommerce/cart`.

## The `woocommerce/products` surface

A locked, server-populated cache of product and variation data in Store API format (`ProductResponseItem`). It has **state only, no actions** — it is a read cache; shopper input never lands here directly, and even the one accessor that now accepts a write (below) forwards that write into `woocommerce/cart`'s own draft storage.

| Member | Type | Kind |
| --- | --- | --- |
| `products` | `Record< number, ProductResponseItem >` | Raw data, keyed by product id |
| `productVariations` | `Record< number, ProductResponseItem >` | Raw data, keyed by variation id |
| `productId` | `number` | Selection (global state or per-element context) |
| `variationId` | `number \| null` | Selection (global state or per-element context) |
| `findProduct` | `({ id, selectedAttributes? }) => ProductResponseItem \| null` | Function |
| `baseProductInContext` | `ProductResponseItem \| null` | Derived — the top-level product, **never** a variation |
| `productVariationInContext` | `ProductResponseItem \| null` | Derived / **assignable** — see below |
| `productInContext` | `ProductResponseItem \| null` | Derived — `productVariationInContext ?? baseProductInContext` |

The rename of the anchor getter **`mainProductInContext` → `baseProductInContext`** carries forward unchanged from the prior revision. "Base" reads correctly against "variation": `baseProductInContext` is always the parent product, `productVariationInContext` is the selected variation, and `productInContext` is whichever is currently shown. The PHP mirror (`ProductsStore::register_getters()`) and every reader were renamed with it.

**`productVariationInContext` is this revision's own change to this surface — a get/set pair, not a plain getter.** The getter resolves the in-context product's family draft in the `woocommerce/cart` collection first (a draft carrying a resolvable `variation` resolves the matching variation; a draft parked at a variation id with no attributes resolves that variation directly — the id-direct rung; an unconfigured or parent-id draft resolves nothing), falling back to today's `variationId` context/state resolution when no family draft exists. The setter accepts a `ProductResponseItem` variation belonging to the in-context product's family, or `null` to clear, and writes it into the same family draft. See [The sync](#the-sync-one-mechanism-one-storage-both-directions) for why this is one mechanism, not two.

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
	quantity?: number; // optional; an omitted quantity defaults server-side to the product minimum
	variation?: SelectedAttributes[]; // the SPECIFIED selection; [] means nothing specified
	[ extensionProp: string ]: unknown; // namespaced, e.g. 'my-plugin/gift-note'
};

// The read-only pairing returned by findItem / itemInContext.
type Envelope = {
	cartItem?: CartItem | OptimisticCartItem | undefined;
	draft?: DraftItem | undefined;
};
```

A `DraftKey` is the only draft-addressing type on the surface, and it is a bare `string` — no id, no handle, no parseable structure. A collection is just the `DraftItem[]` filed under a key in `state.draftItems`. `quantity` is optional: a materialized draft with no numeric quantity is still a valid `cart/add-item` payload, and the server defaults the omission to the product's own minimum. `variation` always reads as a real array through the draft view; `[]` means nothing specified, not "no selection" in some absolute sense — for a draft filed under a variation id it means the variation's own attributes apply.

### State

| Member | Type | Notes |
| --- | --- | --- |
| `cart` | `Cart` mirror with optimistic items | Read-only mirror of the Store API `/cart` response; the server owns line identity |
| `draftItems` | `Record< DraftKey, DraftItem[] >` | The keyed draft home; one collection per key, at most one draft per product id within it. Created lazily on a shopper's first write; the server never seeds it |
| `findItem` | `({ id?, key?, filter? }) => Envelope` | The explicit lookup primitive behind `itemInContext` |
| `itemInContext` | `Envelope` | The in-context product's resolved-collection draft paired with its cart line |
| `inCartQuantity` | `number` | The in-context product's in-cart quantity (grouped aggregates children; variable resolves through the selected variation) |

`state` also carries request plumbing that is not part of the proposed purchase surface: `restUrl` and `nonce` back the mutation queue. Note what is **not** here: no flat page-wide `DraftItem[]`, no current-collection getter, no collection-identity type, and — this revision — **no draft-writing action**. Nothing on the state surface exposes a key — the resolver reads the context tree directly, and consumers reach a resolved draft only through the envelope.

### Actions

| Action | Signature | Role |
| --- | --- | --- |
| `addItem` | `( payload? ) => Promise< void >` | Post the in-context product's resolved-collection draft(s), or a payload verbatim |
| `updateItem` | `( { key, quantity } ) => Promise< void >` | Set a cart line's absolute quantity via `update-item` |
| `removeItem` | `( key ) => Promise< void >` | Remove a cart line by key |
| `refresh` | `() => Promise< void >` | Re-fetch the server cart, bypassing the browser cache |
| `addCartItem` | `( args, options? ) => Promise< void >` | The lower-level keyed/keyless add-or-update path, **retained** (see below) |

No action creates or merges a draft. There is no `upsertDraftItem` on this table, and nothing replaces it — see [Why direct mutation replaces the creation action](#why-direct-mutation-replaces-the-creation-action). `findItem` carries no key argument to write through — there is no addressing option to pass, because the resolver reads the calling surface's own context; a lookup's `draft` is written by mutating the object it hands back, not by calling anything. `actions` also carries internal notice helpers not part of the proposed purchase surface: `showNoticeError` and `updateNotices`, which support the mutation queue and notice dispatch.

### Reading: the in-context envelope

Every display binds to the envelope, so the same block code works on a product page, a collection card, and the mini-cart. `state.itemInContext` resolves the in-context product through `woocommerce/products`' `productInContext`, then pairs its resolved-collection draft with its cart line.

**Pairing never guesses.** `cartItem` is populated only when the pairing ladder resolves to exactly one candidate:

1.  A context-known line `key` pairs exactly, no further checks (a mini-cart row; a surface that emits `key`).
2.  Otherwise, product/variation identity — using the draft's *effective* attributes, not its raw `variation` — **plus** a namespaced extension-prop comparison against each candidate line's `extensions[<namespace>]` must resolve to exactly one line. The effective attributes complete the draft's specified selection from the matching variation's own meta wherever the draft leaves an attribute unspecified; see [Effective-payload identity resolution](#effective-payload-identity-resolution) for why this is necessary and what it fixes. A `filter` argument replaces this identity matching entirely, for extensions with their own notion of line identity.

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

### Writing: direct mutation through the draft view

The `draft` an envelope hands back — `itemInContext.draft`, or `findItem( { id } ).draft` — is the store's **only** write surface: a live, per-`(key, id)` view, not a plain object or a copy. There is no draft-writing action anywhere on the store.

-   **Reading it never materializes.** Before a draft exists, the view answers the surface's server-filed seed values (see [The server half](#the-server-half)); reading it only subscribes.
-   **Writing it does one of two things.** Onto an existing draft, the write is a merge — it mutates the live draft in place, notifies every surface that resolves the same key, and is honored by `addItem` posting (which reads the collection at call time). Onto an untouched surface, the write **materializes**: it composes a new draft from the surface's seed (`{ ...seed, [prop]: value, id }`) and files it into the collection in one atomic step, so every other field falls back to its server-rendered default exactly as if the shopper had never touched it. First edit and hundredth edit are the same spelling.
-   **Writing `variation` migrates `id`.** The view re-resolves the family's matching variation against the newly written attributes and re-files the *same* draft under whichever id they resolve to — the matched variation's id, or the base product's own id when nothing matches — with `quantity` and extension props riding along unchanged. This is what keeps at most one draft per product family, and it is what the sync with `woocommerce/products` rides on (see [The sync](#the-sync-one-mechanism-one-storage-both-directions)).
-   **`id` cannot be written directly.** A draft's identity is store-managed — it follows `variation`, never a direct assignment — so an attempted `draft.id = …` is rejected: a dev-build `console.warn`, and a no-op.
-   **`quantity` is optional.** A materializing write whose composed draft carries no numeric `quantity` still materializes, with a dev-build warning — a quantity-less draft is a valid `cart/add-item` payload, and the server defaults an omitted quantity to the product's own minimum.

Usage — recording a quantity edit through the resolved collection's draft, from `blocks/add-to-cart-with-options/frontend.ts`:

```ts
const { draft } = wooState.findItem( { id: productId } );
if ( draft ) {
	draft.quantity = value;
}
```

**Bystander discipline (why sync actually works).** Watch and init callbacks re-run on **every** surface that resolves a shared collection, not just the one the shopper is using. So only a genuine shopper edit — or a clamp of the shared value itself — may write to a draft. A sibling surface the shopper never touched must not write its stale local default over the shared draft.

On this branch that discipline is no longer a runtime guard checked at write time — the reflection watcher, and the guard that gated it against overwriting a sibling's resolved selection, are deleted along with the double-write they protected. Discipline is now **structural**: the variation selector's `toggle` — wired to the shopper's own click/change on the attribute chips/dropdown — is the only code that writes a draft's `variation`, and it always sources this surface's own just-updated selection, so a shared-draft write only ever happens as a direct consequence of a shopper acting on that exact surface. The quantity path is the same shape: the only code that writes a draft's `quantity` from a shopper action is the quantity input's own change handler. The one write that is *not* itself a shopper event — the quantity-constraint watcher's clamp — writes only the shared draft's own out-of-bounds quantity back to a deterministic bound, so it is idempotent across every surface sharing the collection and structurally incapable of overwriting an edit that is already in bounds. There is nothing left to gate at write time, because there is no code path left that writes a draft without one of those two justifications.

When building a new surface that resolves a shared collection, gate every draft write behind an actual shopper action — or, if a write must run outside one (as the clamp does), make sure it corrects only the value's own out-of-bounds state, never a value it is not itself responsible for.

### Adding to cart: `addItem`

`addItem( payload? )` is polymorphic:

-   **`addItem()`** (no argument) resolves the in-context product via `woocommerce/products` and posts the resolved collection's draft(s) for it: a simple or variable product's own live draft, **falling back to its *effective* seed** when no live draft exists yet — the family seed re-addressed to the in-context id, not just the seed filed directly under it, so an untouched surface whose in-context id is a resolved variation still posts its default — or, for a grouped product, every child's draft (children resolved one-directionally through the products store) whose `quantity` is greater than `0` (seeds are not consulted on this rung, so untouched children never post). Multiple children post as **one auto-batched** request set, not one request per child.
-   **`addItem( payload )`** posts the payload **verbatim** — extension props at its root included — bypassing key and product resolution entirely. This is the path an extension composing its own `cart/add-item` payload uses.

**Product-scoped posting is a guarantee, not a side effect.** `addItem` posts only the in-context product's draft (simple/variable), the grouped parent's declared children with `quantity > 0`, or an explicit payload — it **never iterates a collection**. This matters now that a session-global collection accumulates drafts from every page a shopper visited: an add from one surface can never leak an unrelated product's draft that happens to share the same key. When resolution yields no draft (and no effective seed), `addItem` sends nothing.

Every posted item optimistically bumps a matching existing line's quantity in place (unless `sold_individually`) or is pushed as a new line, commits or rolls back through the mutation queue, and fires the legacy added-to-cart event once per call on success. A cycle whose requests all fail rolls the cart back to its pre-cycle snapshot and surfaces a `woocommerce/store-notices` notice. **Today's optimistic behavior is preserved** — the mutation batcher and its reconciliation are unchanged; only the API around it was reshaped.

The grouped branch resolves each child's draft from the resolved collection by id — a raw, live-draft-only lookup, never the always-present draft view, so an untouched child's exposed seed never contributes to the post — from `cart.ts`:

```ts
if ( product.type === 'grouped' ) {
	const drafts = product.grouped_products
		.map( ( childId ) =>
			findDraftInCollection( collection, childId )
		)
		.filter(
			( draft ): draft is DraftItem =>
				!! draft &&
				typeof draft.quantity === 'number' &&
				draft.quantity > 0
		);
	yield* postDraftItems( drafts, actions );
	return;
}
```

The simple/variable branch posts the in-context live draft, falling back to the *effective* seed when none exists, from `cart.ts`:

```ts
const draft = findDraftInCollection( collection, product.id );
const effectiveSeed = resolveEffectiveSeed(
	draftKey,
	product.id
);
const itemToPost = draft ?? effectiveSeed;
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

On the client, seeds are read **only through `getServerState( 'woocommerce/cart' )?.draftSeeds`** — the runtime's intact, per-page, navigation-fresh copy — and consulted at exactly two points: the draft view materializes a new draft composed from the seed on the shopper's first write to an untouched surface, and `addItem` falls back to the *effective* seed (the family seed re-addressed to the in-context id) for an untouched simple/variable surface with no live draft. A seed is **never applied into a collection**, so a re-delivered seed (on a region re-render or client-side navigation) can never replace or inject into an edited draft — the two live in different places. The runtime also auto-merges the incoming server state into a `state.draftSeeds` copy, but that client-side copy is **inert**: the store never reads it; `getServerState()` is the only seed source.

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

Each form resolves its own variation with a single write to the resolved collection's draft: the shopper's attribute pick writes `draft.variation`, and the draft view's write routine migrates the draft's `id` to whichever variation that selection resolves to — `quantity` and any extension props riding along unchanged, no co-written call needed. The write, from `variation-selector/frontend.ts`'s `toggle`:

```ts
const { draft } = cartState.itemInContext;
if ( draft ) {
	draft.variation = context.selectedAttributes;
}
```

Green and Blue have distinct variation ids, so the migration files each selection under a different id — two ids that **coexist in one collection** (and, when rendered in separate containers, in two collections — either way distinct). `addItem()` posts each draft verbatim; the pairing ladder's *effective*-attribute comparison keeps the two lines distinct in the cart. Both land with the correct attributes.

### Use case 4 — synced page-wide surfaces (and the sticky bar)

A Single Product Template's main form and a second page-wide surface for the same product (a sticky add-to-cart bar) must stay in sync, while a surface that isolates its own subtree for the same product on the same page must not.

Neither page-wide surface declares a key, so both resolve the **reserved global collection** (`GLOBAL_DRAFT_KEY`). They therefore read and write the **same draft**. An edit on one surface updates the shared draft through the one write every quantity edit uses — `setQuantity`'s single write to the resolved collection's draft, from `blocks/add-to-cart-with-options/frontend.ts`:

```ts
const { draft } = wooState.findItem( { id: productId } );
if ( draft ) {
	draft.quantity = value;
}
```

the other surface's display (`cartState.itemInContext.draft?.quantity`, read in `quantity-selector/frontend.ts`) resolves through that same shared draft and repaints immediately — no separate write happens on the display side.

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

**Slot writes go through the normal surface.** The slot's quantity input has no init; its `data-wp-on--change` handler resolves the slot's own declared key's draft view and writes `quantity` through it — the same call for the slot's *first* edit (the view materializes the draft from the slot's own context) and every edit after (a **direct mutation** of the now-live draft), never an action call either way, from `bundle-demo.js`:

```js
const { childId } = getContext();
cart.state.findItem( { id: childId } ).draft.quantity = quantity;
```

This makes the fixture the end-to-end vehicle for the one write spelling every core consumer now uses too: the fixture's own quantity write and the variation selector's shopper-edit write both forward to the same draft-view write routine, so the demo proves an extension can rely on exactly the mechanism core blocks rely on — with zero core changes of its own.

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

The fixture's surfaces write drafts exactly as core surfaces do — one write through the draft view, the same spelling for a surface's first edit and every edit after — and their quantity inputs reactively bind `state.quantityText` so a freshly server-rendered instance of a surface (a brand-new DOM node after a cross-page navigation) repaints from the surviving draft on its first frame, with no restore step.

## What changed against today's stores

Deltas from the currently-shipped stores (trunk):

-   **The root reactive store retired.** The cart store re-registers as `woocommerce/cart` (off the root `woocommerce` registration). The `woocommerce` context namespace and `wp_interactivity_config( 'woocommerce', … )` config bag are unaffected — only the reactive store moved.
-   **`mainProductInContext` → `baseProductInContext`.** Renamed for a vocabulary that reads correctly against `productVariationInContext` / `productInContext`. PHP mirror and all readers updated.
-   **Redundant cart members retired.** `batchAddCartItems`, `findItemInCart`, `removeCartItem`, and `refreshCartItems` are gone, folded into the new surface: auto-batching is now internal to `addItem`; line lookup is `findItem` and the envelope; removal is `removeItem`; refresh is `refresh`. Keeping both the old and new spellings would have blurred the one-vocabulary goal.
-   **`isInCart` dropped from the envelope.** No migrated consumer needed the "in the cart, but no unambiguous line" tri-state, so the envelope carries only `cartItem` / `draft`.
-   **`addCartItem` and `does-cart-item-match-attributes.ts` retained.** Their only remaining consumers are the out-of-scope shopper-lists blocks; removing them would have broken code this run does not migrate.

And, against the earlier version of this proposal — the retirement that revision made:

-   **Context-held collections retired in favor of keyed global state.** The page-wide `state.draftItems` array and the per-container context-held collection arrays are replaced by one keyed map, `Record< DraftKey, DraftItem[] >`. A container now declares an opaque `draftKey` rather than initializing a collection array in context; the reserved `GLOBAL_DRAFT_KEY` collection is the fallback; and `resolveDraftKey()` / `resolveCollection()` implement `context.draftKey ?? GLOBAL_DRAFT_KEY`. Nothing a consumer or extension sees addresses a draft by anything but an opaque key.
-   **The Product-Collection-specific lifecycle machinery was deleted with no successor.** The module-private ledger, the register-or-restore init directive, the render-time bridge, the per-card init directive, and `seedDraftIfAbsent` — the entire apparatus that kept one surface's draft alive across a remount — are gone, because survival is now a property of where drafts live. The retired empty-collection and draft-seed context bags and their init directives go with them, along with the unused `removeDraftItem` action and the never-consumed PHP `noticeId` seed.
-   **Seeds re-vehicled onto server state.** Initial-draft defaults moved off per-surface init directives onto `draftSeeds[key][id]` server state, read via `getServerState()` and consulted only at creation and post-time fallback — never applied into a collection. `ProductCollection/Renderer.php` no longer contributes `queryId` to any cart context.
-   **Write policy unchanged in spirit, at the time: direct mutation stayed first-class, and `upsertDraftItem` stayed as the creation convenience.** That combination held only through the revision before this one, which explicitly named its own successor — "a future revision that drops `upsertDraftItem` in favor of editing `itemInContext.draft` directly." This document is that revision; see [Why direct mutation replaces the creation action](#why-direct-mutation-replaces-the-creation-action).

And, against the prior revision of this proposal — the keyed-global-state model this document itself used to describe — the retirement this revision makes:

-   **`upsertDraftItem` retired, with nothing action-shaped replacing it.** There is no draft-writing action anywhere on the `woocommerce/cart` surface; see [Why direct mutation replaces the creation action](#why-direct-mutation-replaces-the-creation-action).
-   **The draft view introduced as the sole write surface.** `itemInContext.draft` / `findItem( { id } ).draft` are now a live, per-`(key, id)` view: reading it answers seed values before a draft exists (never materializing), and writing it materializes a new draft from the seed on the first write, or merges onto the live one thereafter.
-   **`productVariationInContext` gained a setter.** Assigning a variation belonging to the in-context product's family (or `null`, to clear) writes into the same family draft the getter reads, through the same write routine every draft write goes through; see [The sync](#the-sync-one-mechanism-one-storage-both-directions).
-   **Effective-payload identity resolution introduced at the pairing and posting boundaries.** `findItem`'s identity rung and `addItem`'s no-payload fallback now compare and resolve through a draft's *effective* attributes/seed rather than its raw, possibly-incomplete `variation`; see [Effective-payload identity resolution](#effective-payload-identity-resolution).

Because these are private stores, none of this is a breaking change today — but the whole point of the proposal is to converge on a surface worth making public, so the retirements matter. (The eventual PR must still state the backward-compatibility impact of reshaping the private locked store and removing its server markup and seeds, per repo policy; the store sits behind a consent string that declares exactly this instability.)

## Honest caveats

These behaviors changed or are knowingly incomplete, and are called out here rather than buried:

-   **Notice-suppression narrowing.** Form and button adds no longer pass a notice-suppression flag. An exact add stays notice-silent (the store proves the server total equals the pre-add total plus the posted delta and suppresses those lines), but a genuinely divergent server commit — a stock cap or a concurrent change — now surfaces a "quantity changed" notice where the previous code path was silent. This is a deliberate narrowing, not a correctness regression.
-   **Session-lifetime drafts.** Drafts persist across client-side (router-region) navigation such as collection pagination and cross-page moves, but a **full page reload discards them** and every surface re-seeds fresh. That is by design — there is no persistence layer.
-   **A remounted variable-product card re-presents its recorded attribute selection.** This supersedes the prior revision's expectation for this case and is a deliberate, owner-adjudicated behavior change, not an oversight: because `productVariationInContext` now derives from the surviving family draft *ahead of* the products-namespace context a remount discards, the matched variation resolves again on the first post-remount frame with no context to read from — chips/dropdown re-check, and every reader derived from the resolved variation (price, SKU, stock, gallery, hidden-input bindings) repaints to match it. Display and posting stay in agreement, now in the shopper's favor: what re-presents is exactly what would be added.
-   **Cross-page Single Product instance collisions.** Two Single Product blocks for the same product on two different pages each mint `single-product/<productId>/1`, so under client-side navigation they share one collection. Within any single page, isolation is fully preserved (the occurrence counter distinguishes the instances); the collision is observable only across a client-side page navigation, and is directionally consistent with the model's sharing-by-default rule.
-   **The inert `state.draftSeeds` merge byproduct.** The runtime auto-merges incoming server seeds into a `state.draftSeeds` copy on every navigation (`override=false`), so that copy accumulates stale entries across a session. It is never read — `getServerState()` is the only seed source — is invisible outside the locked store, and is bounded by the number of pages visited.
-   **The extension seed contract.** Declaring only the `draftKey` context bag gives an extension correct **client-side** addressing — its subtree resolves its collection, direct mutation through the draft view works, and it can read `state.draftItems[ <its own key> ]`. But **wrapping a core seed-emitting surface additionally requires propagating the key through `render_block_context`** so that surface files its seed under the extension's key; without it, an untouched wrapped surface has no seed under the resolved key and posts nothing — a safe no-data outcome, never wrong data.
-   **Same-`(key, id)` seed filing is last-write-wins.** When one product is filed twice under one key (e.g. standalone and as a grouped child, both unwrapped on one page), the later filing wins — the same order-dependent ambiguity a single shared collection already carried.
-   **Hand-authored collections without a `queryId`** all mint under `collection/0/<productId>` and can therefore share drafts per product across two such collections. Enhanced pagination requires authored `queryId`s, so this affects only hand-rolled markup.
-   **Duplicate-id drafts are possible via direct `push`.** Appending a second draft with an existing `id` straight onto a collection — bypassing the draft view entirely — still bypasses the one-draft-per-`id` invariant (lookups then resolve first-match). This is bounded: no shipped consumer appends directly; every write that goes through the draft view resolves the existing (exact-id or family) draft first and merges onto it rather than appending, which is what maintains the invariant on the supported write path. It remains a documented residual.

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
