# TreeSelect prototype direction

This document captures the current design-spike direction for a reusable TreeSelect / TreeCombo pattern in WooCommerce admin. It is intentionally scoped as prototype context, not a production API contract.

## Problem

Several Woo admin surfaces need to help merchants select items from a hierarchy:

- Shipping destinations can be grouped by region while still allowing country or state-level exceptions.
- Product categories can be deeply nested, include repeated labels, and need enough path context to avoid ambiguous selections.
- Product editing may need category selection and a separate product-specific decision about which assigned category should be treated as the primary storefront path.

A normal flat combobox does not carry enough hierarchy context for these cases. A traditional tree can expose the hierarchy, but becomes heavy when users already know what they want to search for.

## Proposed component scope

The reusable TreeSelect should focus on the generic selection behavior:

- Render hierarchical options with parent/child navigation.
- Support searching across the tree.
- Preserve parent path context in search results.
- Support leaf and group selection.
- Represent partial selection for parent rows.
- Keep parent-row behavior predictable: row label or chevron opens children; checkbox selects the branch.
- Support selected chips inside the input area, with optional path context for ambiguous labels.
- Allow consumers to provide optional suggestions, but do not require suggestions as part of the base component.

The component should not own product-specific rules such as storefront breadcrumb behavior, SEO settings, or primary category persistence.

## Consumer examples

### Shipping destinations

The shipping use case validates region/country selection and custom-rate exceptions. It is the first source case because merchants need to pick broad regions quickly while still making local exceptions.

### Product categories

The category stress case validates deeper trees, repeated labels, long parent paths, and selected-chip behavior. This is useful for checking whether the component can handle product taxonomy scale without making every consumer invent its own category picker.

### Product editor primary category

The product editor can reuse TreeSelect for adding assigned categories. A separate product-editor control can then choose the primary category from the assigned categories. That keeps the generic selector focused on selection while letting the product editor own the storefront path decision.

## Related follow-up

The primary category / storefront breadcrumb behavior is related but should be handled as its own product-category follow-up. See woocommerce/woocommerce#31411 for an example of the shopper-facing breadcrumb problem when products belong to multiple categories.

Open questions for the follow-up:

- Should WooCommerce provide a native primary category field?
- What default should be used when no primary category is selected?
- How should this interact with SEO plugins that already provide primary category behavior?
- Should the decision affect breadcrumbs only, or URLs and SEO metadata too?

## Notes for implementation

This is a design-spike checkpoint. Before moving the component toward production, the next pass should define the keyboard model, screen reader announcements, and component API in more detail.