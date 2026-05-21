# Product Category TreeSelect Design Notes

## Context

This is a follow-up to the first TreeCombo / TreeSelect breakout. PR #64732 validated a shipping-first TreeSelect direction against the setup shipping source case, a deep product-category stress case, and mobile/accessibility checks.

This pass explores how the same TreeSelect direction applies inside the current WooCommerce product editor category panel, especially where category selection overlaps with the storefront breadcrumb / primary category problem.

Related context:

- The source prototype remains the standalone TreeCombo breakout prototype.
- The first draft feedback PR is #64732.
- GitHub issue #31411 describes a related shopper-facing breadcrumb problem: a product may belong to multiple categories, but the storefront breadcrumb may not use the intended primary category path.

## Goal

Show how a reusable TreeSelect pattern can support product category selection without making the generic component responsible for product-specific breadcrumb rules.

The intended separation is:

- TreeSelect handles hierarchical category search, drill-in, repeated labels, selected chips, suggestions, and path context.
- The product editor owns product-specific category decisions, including the primary category / storefront path field.

## Current Direction

The product-category prototype now validates three connected layers.

### 1. Component-level category stress test

The category stress test asks whether the TreeSelect itself can handle:

- Deep category paths.
- Repeated category labels under different parent paths.
- Search results that preserve full parent path context.
- Selected chips that stay compact.
- Optional path hints for selected chips when ambiguity matters.
- Parent rows where label / chevron opens children and checkbox selects the branch.

This is still the generic component question.

### 2. Product editor fit

The current editor fit places the category interaction back into a Woo product editor-like sidebar so the pattern can be evaluated in the surface merchants already use.

The prototype separates:

- Assigned categories: categories saved to the product.
- Add categories: the TreeSelect entry point for finding and adding categories.
- Suggestions: optional recommendations that can be accepted into assigned categories.
- Primary category: one product-level field chosen from assigned categories.
- Storefront path preview: a preview of what shoppers would see based on the selected primary category.

### 3. Primary category / storefront path decision

Earlier variants repeated a "Use for storefront path" radio control on every assigned category row. Feedback showed this would not scale and made the assigned category list too busy.

The current direction replaces that repeated row control with one `Primary category` field. This field is product-specific and should not be part of the reusable TreeSelect component.

## Why the Primary Category Is Separate

TreeSelect answers:

"How does a merchant find and select the right category from a large tree?"

The product editor primary category answers:

"Once this product belongs to multiple categories, which assigned path should be treated as the primary storefront path?"

Those are related, but they are not the same component responsibility.

Keeping them separate helps avoid a generic TreeSelect API that accidentally includes product, breadcrumb, SEO, or storefront concepts.

## Data Sanity Check

We do not currently have Woo-wide telemetry for categories per product. The available signals point to two different scale concerns:

- Category trees can be large, so TreeSelect still needs search, drill-in navigation, and parent path context.
- A single product usually has only a handful of assigned categories, so the assigned category list should not be designed around repeated controls across many rows as the default state.

A WooCommerce.com catalog proxy showed a typical product with around 3 assigned categories, with 95% of products having 6 or fewer, and 11+ categories appearing rarely. This should be treated as directional context rather than global merchant telemetry.

## Non-Goals For This Pass

- Do not implement final production code for the primary category field.
- Do not claim the TreeSelect is accessibility-complete.
- Do not solve every storefront breadcrumb, SEO, URL, or plugin integration question.
- Do not put primary-category or breadcrumb concepts into the generic TreeSelect component.

## Implementation Notes For A Future Component PR

A production TreeSelect PR should focus on the generic component foundation:

- Tree data types.
- Selection helpers.
- Search helpers with path context.
- Controlled selected values.
- Optional suggestions supplied by the consumer.
- Parent row drill-in vs branch selection behavior.
- Keyboard model and screen reader announcements.
- Mobile / constrained width behavior.

The product editor primary category work should be a separate consumer-level follow-up once product/category data behavior is aligned.
