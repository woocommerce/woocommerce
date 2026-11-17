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

## PHP debug log checks

Use `readPhpDebugLog` with a WordPress test site at the base URL's origin root. The site needs a test-only handler on `/` that writes a marker and a readable log at `/wp-content/debug.log`. Supply the handler's query parameter and log prefix. Filter the verified log for the plugin paths and PHP messages your suite checks.

```js
import { readPhpDebugLog } from '@woocommerce/e2e-utils-playwright';

test('debug.log contains no PHP issues from the plugin', async ({ request }) => {
  const log = await readPhpDebugLog(request, {
    queryParameter: 'e2e_log_marker',
    logPrefix: 'e2e-log-marker-',
  });
  expect(log).not.toContain('/plugins/my-extension/');
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
