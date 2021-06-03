# Components

This packages includes a library of components that can be used to create pages in the WooCommerce dashboard and reports pages.

## Installation

Install the module

```bash
npm install @woocommerce/components --save
```

View [the full Component documentation](https://woocommerce.github.io/woocommerce-admin/#/components/) for usage information.

## Usage

```jsx
/**
 * Woocommerce dependencies
 */
import { Badge } from '@woocommerce/components';

export default function MyBadge() {
	return <Badge count={ 15 } />;
}
```

Many components include CSS to add style, you will need to add in order to appear correctly. Within WooCommerce, add the `wc-components` stylesheet as a dependency of your plugin's stylesheet. See [wp_enqueue_style documentation](https://developer.wordpress.org/reference/functions/wp_enqueue_style/#parameters) for how to specify dependencies.

In non-WordPress projects, link to the `build-style/card/style.css` file directly, it is located at `node_modules/@woocommerce/components/build-style/<component_name>/style.css`.
