# OrderStatus

Use `OrderStatus` to display a badge with human-friendly text describing the current order status.

## Usage

```jsx
const order = { status: 'processing' }; // Use a real WooCommerce Order here.

<OrderStatus order={ order } />
```

### Props

Name | Type | Default | Description
--- | --- | --- | ---
`order` | Object | `null` | (required) The order to display a status for. See the [Order properties documentation](https://developer.woocommerce.com/docs/apis/rest-api/v3/orders/#order-properties).
`className` | String | `null` | Additional CSS classes
`orderStatusMap` | Object | {} | A map of order status to human-friendly label.
