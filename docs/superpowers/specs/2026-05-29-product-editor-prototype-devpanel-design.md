# Product Editor Prototype Dev Panel

**Date:** 2026-05-29
**Branch:** poligilad/product-editor-improvements
**Status:** Approved, pending implementation

## Purpose

A floating dev tools panel for the product editor that allows toggling experimental improvements on and off during prototyping. Not intended for merge — this is a prototype-only artifact.

## Constraints

- Must not modify the `@woocommerce/product-editor` package
- Must be trivially removable (one directory + one import)
- Flags must persist across page refreshes
- No external dependencies beyond React

## Directory Structure

```
plugins/woocommerce/client/admin/client/products/prototype/
├── index.ts                  # Re-exports for clean imports
├── PrototypeFlagsContext.tsx  # Context, provider, and flag registry
├── usePrototypeFlags.ts       # Hook for reading/writing flags
└── DevPanel.tsx               # Floating UI panel component
```

## Integration

`product-page.tsx` receives two additions:

1. `<PrototypeFlagsProvider>` wraps the existing page content.
2. `<DevPanel />` is rendered alongside the editor (sibling, not child).

No other files in the product editor or its package are modified.

## Flag Registry

Flags are defined as an array in `PrototypeFlagsContext.tsx`:

```ts
const FLAG_DEFINITIONS = [
  { key: 'exampleFeature', label: 'Example feature', defaultValue: false },
];
```

Adding a new improvement = one new entry in this array.

## Persistence

`usePrototypeFlags` reads from and writes to `localStorage` under the key `wc_prototype_flags`. State survives page refreshes. Defaults are applied for any flag not yet in storage.

## Consuming Flags

From any component in the product editor tree:

```ts
const { flags } = usePrototypeFlags();
if (flags.myImprovement) { /* new behavior */ }
```

The context wraps the full product page, so the hook resolves anywhere in the component tree — including components inside `packages/js/product-editor`.

## UI

- **Position:** `fixed`, bottom-right corner, high `z-index`.
- **Collapsed state:** Small dark semi-transparent pill button labeled "Dev". Click to expand.
- **Expanded state:** Compact card above the button. Each flag renders as a row: label on the left, checkbox on the right. Click "Dev" again or a close affordance to collapse.
- **Styling:** Plain inline styles. No WordPress component library dependency. Intentionally prototype-looking.
- **No animations.**

## Removal

To remove everything: delete `prototype/` directory and revert the two lines added to `product-page.tsx`.
