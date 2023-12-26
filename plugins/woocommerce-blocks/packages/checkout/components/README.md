# Components <!-- omit in toc -->

A library of components to be used for creating common UI elements shared between features of the WooCommerce Cart and Checkout Blocks.

## Usage

When WooCommerce Blocks is installed and activated, these components can be accessed by importing from the `blocks-checkout` package.

```ts
// Aliased import
import { Button } from '@woocommerce/blocks-components';

// Global import
// const { Button } = wc.blocksCheckout;

export default function MyButton() {
	return <Button>Click Me!</Button>;
}
```

These components are here so they can be consumed by extensions.

<!-- FEEDBACK -->

---

[We're hiring!](https://woocommerce.com/careers/) Come work with us!

🐞 Found a mistake, or have a suggestion? [Leave feedback about this document here.](https://github.com/woocommerce/woocommerce-blocks/issues/new?assignees=&labels=type%3A+documentation&template=--doc-feedback.md&title=Feedback%20on%20./packages/checkout/components/README.md)

<!-- /FEEDBACK -->
