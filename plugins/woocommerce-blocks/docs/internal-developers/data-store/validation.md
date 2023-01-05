# Validation data store

See also [third-party developers' validation documentation](../../third-party-developers/extensibility/data-store/validation.md).

The `wc/store/validation` store contains information about the current validity, visibility, and associated error message of _something_.

That _something_ is usually a field in the Checkout form, however the data store can be leveraged in other creative ways.

The data store begins as an empty object. Using the `setValidationErrors` action, errors can be added to the store.

An error is represented by an object and contains two entries, `hidden` and `message`. `hidden` is used by our code to determine whether to show an error or not. Other extensions reading the data store may not honour this and show it anyway regardless of if it is hidden. `message` contains the message to show to the user.

When the Checkout block loads, various entries are added to the data store for each required field that does not already have a value. These errors have the `hidden` property set to `true`.

<img width="478" alt="image" src="https://user-images.githubusercontent.com/5656702/210558764-cc271b30-03a0-444f-8cac-4c16179c8165.png">

The reason for this is so we can show an error if a required field is not completed, but showing these errors immediately on page load would be a bad user experience, we keep them hidden until the user interacts with the field, or tries to submit the form.

When the "Place order" button is pressed, the [checkout processor reads the value of `hasValidationErrors`](https://github.com/woocommerce/woocommerce-blocks/blob/4ead2e9a6ee567a3a868feae988c6c21edd00d12/assets/js/base/context/providers/cart-checkout/checkout-processor.ts#L160) and if it returns true then the checkout process is aborted.

Any fields that were hidden get shown when the "Place order" button is pressed too. This is done using `showAllValidationErrors`. [See the code here](https://github.com/woocommerce/woocommerce-blocks/blob/4ead2e9a6ee567a3a868feae988c6c21edd00d12/assets/js/blocks/checkout/block.tsx#L139)

The Checkout processor does not discriminate between errors added by WooCommerce Blocks, and those added by extensions. If the store contains any errors at all, then the checkout will halt and those errors will be shown.

Errors are explicitly shown. i.e. we don't arbitrarily display errors in the data store. Instead, we render specific errors in the place they belong by using their ID. For example, in the checkout form we use [`ValidatedTextInput`s](https://github.com/woocommerce/woocommerce-blocks/blob/2848a4b11025d9095511c6a92e68f4a2d05d21da/packages/checkout/components/text-input/validated-text-input.tsx) which have a [`ValidationInputError`](https://github.com/woocommerce/woocommerce-blocks/blob/d8ff1ce08a17a29d9f63a6fa4eeb894eea5dd609/packages/checkout/components/validation-input-error/index.tsx) component associated with them. The text input has an ID, and the associated `ValidationInputError` renders the error with that ID.
