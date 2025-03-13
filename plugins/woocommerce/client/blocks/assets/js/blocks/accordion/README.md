# Accordion

_Note: This block is a copy of an upstream implementation ( [PR](https://github.com/WordPress/gutenberg/pull/64119) ) Please keep changes to a minimum. This block is namespaced under WooCommerce._

## Accordion Group

A group of headers and associated expandable content. ([Source](../accordion/accordion-group/))

-   **Name:** woocommerce/accordion-group
-   **Experimental:** true
-   **Category:** design
-   **Allowed Blocks:** woocommerce/accordion-item
-   **Supports:** align (full, wide), background (backgroundImage, backgroundSize), color (background, gradient, text), interactivity, layout, shadow, spacing (blockGap, margin, padding), ~~html~~
-   **Attributes:** allowedBlocks, autoclose, iconPosition

## Accordion Header

Accordion header. ([Source](../accordion/inner-blocks/accordion-header))

-   **Name:** woocommerce/accordion-header
-   **Experimental:** true
-   **Category:** design
-   **Parent:** woocommerce/accordion-item
-   **Supports:** anchor, border, color (background, gradient, text), interactivity, layout, shadow, spacing (margin, padding), typography (fontSize, textAlign), ~~align~~
-   **Attributes:** icon, iconPosition, level, levelOptions, openByDefault, textAlignment, title

## Accordion

A single accordion that displays a header and expandable content. ([Source](../accordion/inner-blocks/accordion-item))

-   **Name:** woocommerce/accordion-item
-   **Experimental:** true
-   **Category:** design
-   **Parent:** woocommerce/accordion-group
-   **Allowed Blocks:** woocommerce/accordion-header, woocommerce/accordion-panel
-   **Supports:** align (full, wide), color (background, gradient, text), interactivity, layout, shadow, spacing (blockGap, margin)
-   **Attributes:** openByDefault

## Accordion Panel

Accordion Panel ([Source](../accordion/inner-blocks/accordion-panel))

-   **Name:** woocommerce/accordion-panel
-   **Experimental:** true
-   **Category:** design
-   **Parent:** woocommerce/accordion-item
-   **Supports:** border, color (background, gradient, text), interactivity, layout, shadow, spacing (blockGap, margin, padding), typography (fontSize, lineHeight)
-   **Attributes:** allowedBlocks, isSelected, openByDefault, templateLock
