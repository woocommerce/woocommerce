# Shipping TreeCombo Breakout Design

## Context

Ann and Sam are exploring a shipping-first TreeCombo pattern for the WooCommerce shipping setup flow. The source of truth for the current interaction is the standalone prototype at:

- `/Users/anntai/Documents/Claude/Projects/Radical speed month/shipping-hub-react/src/components/WizardDestinations.jsx`
- `/Users/anntai/Documents/Claude/Projects/Radical speed month/shipping-hub-react/src/components/TreeCombo.jsx`
- `/Users/anntai/Documents/Claude/Projects/Radical speed month/shipping-hub-react/src/data/countryTree.js`

The first production-facing use case is the setup step titled "Where do you ship?". The long-term concern is that a TreeSelect/TreeCombo is difficult to make accessible and should not become a one-off component only shipping can use.

## Goal

Build a shipping-first TreeCombo implementation that preserves the prototype behavior while keeping the reusable tree selection foundation separate from shipping-specific concepts.

## Assumptions

- Confident: The prototype's "Where do you ship?" step is the behavior source of truth for the first integration.
- Confident: Accessibility must be part of the foundation, even if the first pass documents known gaps instead of claiming the pattern is complete.
- Confident: The component should not expose shipping concepts from the generic layer.
- Assuming: Shipping setup is the first consumer, but product categories are the main reuse pressure test because they can be many levels deep.
- Assuming: The component should support both popover and inline display modes so it can work in normal settings surfaces and constrained/modal contexts.
- Unclear: Whether this should eventually replace the existing `TreeSelectControl` in `@woocommerce/components`, live beside it as a new component, or remain experimental until the pattern is validated.

## Proposed Architecture

Use three layers.

### 1. Generic TreeCombo Foundation

This layer owns tree behavior only:

- Node lookup by id.
- Parent/path lookup.
- Leaf collection.
- Checked, unchecked, and indeterminate selection state.
- Search across all levels.
- Search results that retain path context.
- Breadcrumb drill-in state.
- Select all and deselect all within the current subtree.
- Popover or inline rendering.
- Keyboard and focus behavior.

This layer must not include the words or concepts `shipping`, `country`, `rate`, `zone`, `anywhere else`, or `custom rate`.

### 2. Shipping Destination Adapter

This layer adapts the generic TreeCombo to shipping setup:

- Provides the country and region tree.
- Provides quick groups such as North America, European Union, Asia Pacific, and Latin America.
- Adds "Anywhere else" as a shipping-specific special destination.
- Adds split-out custom-rate behavior for leaf nodes.
- Converts selected destination tags into WooCommerce shipping zone objects.
- Preserves the prototype's default setup: US and Canada selected, Alaska and Hawaii split out as custom rates, and Anywhere else selected.

### 3. Validation Fixtures

Add fixtures that prove the generic layer is not shipping-only:

- Shipping fixture: mirrors the prototype "Where do you ship?" flow.
- Product category fixture: uses a deep category tree with many nested layers.
- Mobile/modal fixture: shows inline mode, narrow width behavior, and breadcrumb overflow handling.

## User Experience Requirements

The shipping setup integration should preserve the prototype behavior:

- Merchants can browse from All Regions into nested regions and countries.
- Merchants can search for regions, countries, or states/provinces.
- Search results show enough path context to disambiguate deep matches.
- Group chips can be clicked to reopen the tree at that group for refinement.
- Group selections show selected counts when useful.
- Leaf nodes can be split out for custom rates when shipping enables that feature.
- "Anywhere else" remains a shipping-specific option outside the generic tree data.
- The selected destinations convert into shipping zones for the next wizard step.

## Accessibility Requirements

The first pass should include:

- A visible text label for the control.
- A programmatic label for the input.
- Keyboard access to open and close the control.
- Escape closes the popover.
- Click outside closes the popover.
- Focus remains visible on input, rows, breadcrumbs, and row actions.
- Rows expose checked or indeterminate state in a screen-reader-friendly way.
- Breadcrumb buttons are keyboard reachable.
- Disabled actions are marked disabled.
- Inline mode avoids clipping inside modals or constrained containers.

Known accessibility risk:

Tree, combobox, and multiselect semantics are difficult to combine cleanly. The first implementation should document what semantics it uses and where follow-up review is needed. It should not claim design-system readiness until keyboard and screen reader behavior have been reviewed.

## Responsive Requirements

The control must work at 320px and 375px widths:

- Tags wrap without overlapping the input.
- The breadcrumb can wrap or truncate without hiding the current location.
- Row actions do not squeeze labels beyond recognition.
- Long labels can wrap to a second line.
- The popover or inline panel does not overflow the viewport.

## Edge Cases

- Empty selection: continue is disabled in the setup step.
- Empty search result: show a short "No matches" state.
- Fully selected group: show the group tag rather than every child tag.
- Partially selected group: show count information.
- Fully selected group with split-out leaves: show the group plus split-out custom-rate tags.
- Deep tree: preserve path context in search and breadcrumbs.
- Long translations: allow wrapping instead of clipping.

## Non-Goals For The First Pass

- Do not replace the existing shared `TreeSelectControl`.
- Do not claim the component is ready for design-system adoption.
- Do not support every possible selection mode before shipping validates the flow.
- Do not move shipping-specific concepts into the generic layer.

## Testing And Review Plan

- Unit test tree helpers: node lookup, path lookup, leaves, selection state, tag computation, and search result paths.
- Component test shipping behavior: group chip opens at the right node, select all and deselect all update selection, split-out leaves produce custom-rate tags, and empty selection disables continue.
- Component test generic fixture behavior: product category tree supports deeper nesting without shipping-specific logic.
- Manual responsive review at 320px, 375px, tablet, and desktop widths.
- Manual keyboard review: tab order, enter/space activation, escape close, focus visibility.
- Designer review before presenting the pattern as a broader component direction.

## P2 Update Framing

If progress is useful enough to share, frame it as:

"Ann and Sam are exploring a shipping-first TreeCombo pattern for the new shipping setup flow. The current work focuses on the 'Where do you ship?' step, including grouped regions, drill-in navigation, custom country rates, and mobile/deep-tree considerations. Accessibility is being treated as part of the foundation, and the component is being structured so shipping is the first consumer rather than the only consumer."
