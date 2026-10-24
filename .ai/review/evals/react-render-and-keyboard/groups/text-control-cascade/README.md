# TextControl CSS cascade pair

This optional pair checks whether a reviewer establishes the prior CSS cascade before reporting a new fixed input height.

The original is the complete historical change from WooCommerce PR #69456.
Its parent pins `@wordpress/components` 30.6.5, whose stylesheet already gives TextControl inputs a 32px height.
The dependency version, archive integrity, and stylesheet path are recorded in `sources.json`.
The WooCommerce change adds an explicit 32px declaration to the wrapper stylesheet.
A pre-existing `min-height: 30px` does not establish that the prior height was automatic.

The control changes both height constraints to zero. It collapses text, number, date, and color inputs in the isolated browser check.
This is a layout regression, not a redundant declaration.

The browser check uses native inputs, the pinned upstream stylesheet, and compiled WooCommerce SCSS.
Color and shadow variables use neutral values. Layout declarations remain unchanged.
It does not reproduce a full WordPress 7.1 page or test text-only zoom.

The group is optional. It does not change the default suite.
Run this command from the policy repository. The source repository must contain both pinned commits:

```sh
python3 .ai/review/evals/react-render-and-keyboard/groups/text-control-cascade/prepare.py \
  --policy-repo "$PWD" \
  --source-repo /path/to/woocommerce-with-historical-objects \
  --output /path/to/new-cascade-checkout
```

The script checks source completeness and patch hashes. It refuses to overwrite an existing output directory.

Keep both input repositories available while using the generated checkout.
Preparation does not call a model or modify either input repository.

```sh
a8c-review eval --path /path/to/new-cascade-checkout \
  --guideline react-render-and-keyboard --group text-control-cascade --plan
a8c-review eval --path /path/to/new-cascade-checkout \
  --guideline react-render-and-keyboard --group text-control-cascade
```

The second command uses the configured engines and their allowance.
Follow the main [eval setup instructions](../../../README.md) before running it.
The original's quiet expectation includes suppressed findings.
The control checks the finding's primary file and line.
