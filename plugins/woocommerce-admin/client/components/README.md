Components
==========

This folder contains the WooCommerce-created components. These are exported onto a global, `wc.components`, for general use.

## How to use:

For any files not imported into `components/index.js` (`analytics/*`, `layout/*`, `dashboard/*`, etc), we can use `import { Card, etc … } from @woocommerce/components`.

For any `component/*` files, we should import from component-specific paths, not from `component/index.js`, to prevent circular dependencies. See `components/card/index.js` for an example.

```jsx
import { Card, Link } from '@woocommerce/components';

render: function() {
  return (
    <Card title="Card demo">
      Card content with an <Link href="/">example link.</Link>
    </Card>
  );
}
```

## For external development

External developers will need to enqueue the components library, `wc-components`, and then can access them from the global.

```jsx
const { Card, Link } = wc.components;

render: function() {
  return (
    <Card title="Card demo">
      Card content with an <Link href="/">example link.</Link>
    </Card>
  );
}
```
