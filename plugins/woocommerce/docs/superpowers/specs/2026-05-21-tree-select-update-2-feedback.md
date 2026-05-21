# TreeSelect Update 2 Feedback Notes

## Purpose

This draft PR is for early feedback on the second TreeSelect breakout pass. It is not a production-ready component proposal yet.

Update 2 focuses on how the TreeSelect pattern could fit into the current WooCommerce product editor category panel, and how it relates to the primary category / storefront breadcrumb decision.

## What Changed Since Update 1

- The category stress test now includes repeated labels, deeper paths, selected chip behavior, and parent path context.
- Parent category rows now separate navigation from selection: clicking the row label or chevron opens children, while the checkbox selects the branch.
- Selected chips are treated as compact labels by default, with path context available when needed.
- Suggestions are treated as optional consumer-provided data, not required TreeSelect behavior.
- Product editor exploration now separates assigned categories, add categories, suggestions, primary category, and storefront path preview.
- The repeated row-level "Use for storefront path" control has been replaced by a single `Primary category` field.

## Feedback Requested

Please focus review on these questions:

- Does the separation feel right: TreeSelect handles selection/search/path context, while the product editor handles the primary category decision?
- Does the `Primary category` field feel like the right way to express the storefront path decision?
- Should the primary category field appear only when a product has more than one assigned category?
- Does the relationship between assigned categories, add categories, and suggestions feel clear enough?
- Are repeated category labels understandable when the category name is shown first and the full parent path is shown underneath?
- Is the long-path behavior acceptable in the constrained product editor sidebar?
- What accessibility requirements should block moving this from prototype to reusable component?

## Known Open Questions

The related primary category / breadcrumb issue should remain a focused follow-up. Open questions include:

- Should WooCommerce introduce a native primary category field?
- What should happen when no primary category is explicitly selected?
- How should this interact with SEO plugins that already provide primary category behavior?
- Should the decision affect breadcrumbs only, or also URLs / SEO metadata?
- Do merchants need bulk-editing tools if many products need primary category decisions?

## Suggested P2 Framing

This update is a bridge between component exploration and product editor fit:

- Category stress test = component-level question. Can TreeSelect handle deep categories, repeated labels, selected chips, suggestions, and path context?
- Current editor fit = product-level question. If TreeSelect handles category selection, how might the existing product editor support assigned categories and a primary storefront path decision?

The prototype does not fully solve the breadcrumb issue yet, but it makes the problem more concrete and keeps the reusable TreeSelect component from swallowing product-specific logic.