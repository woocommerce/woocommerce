# TreeSelect prototype source reference

This folder keeps a source snapshot from the RSM TreeSelect prototype so the interaction work is easier to inspect from GitHub.

The code here is **not production-ready component code**. It was copied from the standalone React prototype and kept as reference material for future TreeSelect / Combobox implementation work in Woo.

## Why this exists

During the Shipping Native in Woo exploration, TreeSelect became a larger reusable pattern question. Shipping was the source case, but the same interaction shape also surfaced product-category needs.

The earlier PRs captured the design and feedback notes:

- [Shipping-first TreeSelect handoff notes](https://github.com/woocommerce/woocommerce/pull/64732)
- [Product-category TreeSelect follow-up notes](https://github.com/woocommerce/woocommerce/pull/65237)

This source snapshot adds the working prototype code behind those notes, so future implementation work can inspect the behavior rather than reconstructing it from screenshots.

## Included cases

- Shipping source case: regions, countries, selected chips, suggested destinations, and custom-rate exceptions.
- Product-category stress case: deeper trees, repeated labels, selected chips, parent-path context, mobile handling, and the product-specific primary category / storefront path exploration.

## Files

```text
source/
├── treecombo-only.jsx.txt
├── styles.treecombo-excerpt.css
├── components/
│   ├── ProductCategoryTreeCombo.jsx.txt
│   ├── TreeCombo.jsx.txt
│   └── TreeComboLab.jsx.txt
└── data/
    ├── countryTree.js.txt
    └── productCategoryTree.js.txt
```

The JavaScript and JSX snapshots keep their original source filenames before the `.txt` suffix so GitHub can carry the reference without CI treating them as production source files.

## How to read this

- `TreeCombo.jsx.txt` is the shipping-oriented source case.
- `ProductCategoryTreeCombo.jsx.txt` is the category-oriented stress case.
- `TreeComboLab.jsx.txt` wires both cases together and includes the product editor fit exploration.
- `countryTree.js.txt` and `productCategoryTree.js.txt` are prototype fixtures.
- `styles.treecombo-excerpt.css` is a trimmed stylesheet excerpt for the TreeSelect lab and product editor fit views.

## Before production

A production Woo component would still need:

- a final component API and ownership decision;
- accessibility review for combobox / tree keyboard behavior and screen-reader announcements;
- tests for selection, search, nested paths, and bulk actions;
- alignment with the current WordPress / Woo component stack;
- a decision on what stays generic TreeSelect behavior versus product-specific logic.

For example, TreeSelect can own finding and selecting items in a hierarchy. Shipping can layer custom-rate rules on top. Product categories can layer primary category / storefront breadcrumb decisions on top.

## Related P2 notes

- [Shipping Native in Woo update 1](https://radicalupdates.wordpress.com/2026/05/05/shipping-native-in-woo-update-1/)
- [Shipping Native in Woo update 2](https://radicalupdates.wordpress.com/2026/05/11/shipping-native-in-woo-update-2/)
- [Combobox / TreeSelect breakout update 1](https://radicalupdates.wordpress.com/2026/05/14/combobox-treeselect-breakout-update-1/)
- [Combobox / TreeSelect breakout: product category follow-up, update 2](https://radicalupdates.wordpress.com/2026/05/22/combobox-treeselect-breakout-product-category-follow-up-update-2/)
