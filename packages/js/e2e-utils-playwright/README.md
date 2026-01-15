# End-to-End Test Utilities For WooCommerce

This package contains utilities to help writing e2e tests specific to WooCommerce using Playwright.

> [!WARNING]
>
> This package is still under active development.
> Documentation might not be up-to-date, and the 0.x version can introduce breaking changes.

## Installation

```bash
npm install @woocommerce/e2e-utils-playwright --save-dev
```

## Usage

Example:

```js
import { addAProductToCart } from '@woocommerce/e2e-utils-playwright';

test('can add products to cart', async ({ page }) => {
  const product = {
    id: 1,
    name: 'Test Product',
  };

  await addAProductToCart(page, product.id);
  await page.goto('/cart/');

  await expect(page.locator('td.product-name')).toContainText(product.name);
});
```

## API Client

The package provides an API client utility for making authenticated requests to the WooCommerce REST API.

### Basic Auth Example

```js
import { createClient } from '@woocommerce/e2e-utils-playwright';

const client = createClient('http://localhost:8889/', {
  type: 'basic',
  username: 'admin',
  password: 'password',
});

const response = await client.get('wc/v3/products');
console.log(response.data);
```

### OAuth1 Example

```js
import { createClient } from '@woocommerce/e2e-utils-playwright';

const client = createClient('http://localhost:8889/', {
  type: 'oauth1',
  consumerKey: 'ck_xxx',
  consumerSecret: 'cs_xxx',
});

const response = await client.get('wc/v3/products');
console.log(response.data);
```

Supported methods: `get(path, params, debug)`, `post(path, data, debug)`, `put(path, data, debug)`, `delete(path, params, debug)`

## Contributing to this package

This is an individual package that's part of the WooCommerce project, which is organized as a monorepo.

To find out more about contributing to this package or WooCommerce as a whole, please read the project's
main [contributor guide](https://developer.woocommerce.com/docs/category/contributing/).
