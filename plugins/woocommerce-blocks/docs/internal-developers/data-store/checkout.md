# Checkout data store

The checkout data store is used to track the status of the **checkout** (not including payment), it stores things like the order ID, the customer ID, the order notes, whether the "use shipping as billing" box is checked etc.

The initial state of the checkout store is:

```js
{
	redirectUrl: '',
	status: STATUS.PRISTINE,
	hasError: false,
	orderId: checkoutData.order_id,
	customerId: checkoutData.customer_id,
	calculatingCount: 0,
	orderNotes: '',
	useShippingAsBilling: isSameAddress(
	checkoutData.billing_address,
	checkoutData.shipping_address
	),
	shouldCreateAccount: false,
	extensionData: {},
};
```

## Properties

- `redirectUrl` - Set when the checkout is completed. The payment method being used can set this, and it will be sent back to the block in the checkout response.
- `status` - one of:
    - `PRISTINE` (Checkout is in its initialized state.)
    - `IDLE` (When checkout state has changed but there is no activity happening.)
    - `COMPLETE` (After the `AFTER_PROCESSING` event emitters have completed. This status triggers the checkout redirect.)
    - `BEFORE_PROCESSING` (This is the state before checkout processing begins after the checkout button has been pressed/submitted.)
    - `PROCESSING` (After `BEFORE_PROCESSING` status emitters have finished successfully. Payment processing is started on this checkout status.)
  `AFTER_PROCESSING` (After server side checkout processing is completed this status is set.)
- `calculatingCount` - This is used to track when a request is being made to the server, for example to update the shipping method selection or to update the customer's address. When the request begins, `calculatingCount` increases, and when it completes, `calculatingCount` decreases.
- `orderNotes` - the value of the order notes textarea.
- `useShippingAsBilling` - Whether the `Use same address for billing` checkbox is checked.
- `shouldCreateAccount` - whether the `Create account` checkbox is checked.
- `extensionData` - data added to the data store by extensions.


## Observers

Extensions can register "observers" which will respond to specific events in the Checkout flow. More information on these can be found in the [checkout flow and events documentation](../../internal-developers/block-client-apis/checkout/checkout-flow-and-events.md). Thar documentation also contains information about the general flow of the checkout system, whereas this documentation only describes how the data is affected during checkout.

## Status changes

When the checkout loads, the items above are populated as described. When the user presses the "Place order" button, the checkout status changes to `BEFORE_PROCESSING`. The observers fire as per the above documentation, and if they all succeed, the status changes to `PROCESSING`. At this point the request is made to the server, and on its return (successful or not) it changes to `AFTER_PROCESSING`. If there was an error, the status returns to `IDLE`, and `hasError` is set to true. If there was not an error, and the checkout was successful, the status changes to `COMPLETE` and the redirect is triggered.
