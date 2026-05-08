# Shipping TreeCombo I1 Feedback Notes

## Purpose

This draft PR is for early feedback on one shipping-first TreeCombo pattern. It is not a production-ready shared component proposal yet.

The prototype may show separate demo wrappers for shipping destinations and product categories. Those are validation scenarios, not a proposal for two different TreeCombo components.

The interactive prototype currently validates three surfaces:

- Shipping destinations: the source case for the "Where do you ship?" setup step.
- Product categories: a reuse stress case with deep category paths.
- Mobile and accessibility: a narrow-width check and an explicit list of known accessibility gaps.

## Current Direction

The intended shape is:

- Keep shipping as the first consumer and source case.
- Discuss the demos as scenarios for the same pattern, not as separate component proposals.
- Keep generic tree selection behavior separate from shipping-specific concepts like zones, custom rates, and "Anywhere else".
- Use product categories as the first non-shipping pressure test, especially for deep nesting and search result path context.
- Treat accessibility as an early design constraint, while being honest that the current prototype is not yet design-system ready.

## Feedback Requested

Please focus review on these questions:

- Does the shipping source case handle grouped regions, custom-rate split-outs, and "Anywhere else" clearly enough for setup?
- Does the product category stress case prove the pattern can support non-shipping use cases, or does it reveal a different interaction should exist for deep product taxonomies?
- On mobile, should this behave as an inline panel, a popover, or a drawer-like surface?
- Are the known accessibility gaps acceptable for an i1 prototype, and what should be required before this moves toward a reusable component?
- Should the next iteration live as an experimental component in `@woocommerce/components`, or remain prototype-only until the accessibility model is stronger?

## Known Accessibility Gaps

The prototype currently covers basic interaction hygiene, but the following work remains before reusable component adoption:

- Define the final combobox/tree semantics.
- Add a complete keyboard model for arrow key movement, selection, and drill-in navigation.
- Announce partial selection state to screen readers.
- Keep search result path context available to assistive technology.
- Confirm focus management for popover, inline, modal, and mobile contexts.

## Suggested P2 Framing

Ann and Sam are exploring a shipping-first TreeCombo pattern for the new shipping setup flow. The current i1 validates shipping destinations, deep product-category nesting, mobile behavior, and early accessibility risks. The goal is to make shipping the first consumer without creating a one-off shipping-only component.
