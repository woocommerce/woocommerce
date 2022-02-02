# Frontend notices

## useStoreNotices()

The `useStoreNotices()` hook allows reading and manipulating notices in the frontend.

Please refer to the `useStoreSnackbarNotices()` section for information on handling snackbar notices.

### API

> _Note: if the context is not specified in `noticeProps` or `ctxt` params (depending on the method), the current context is used._

#### `addDefaultNotice( text = '', noticeProps = {} )`

Create a new notice with `default` status.

| Argument      | Type   | Description                                        |
| ------------- | ------ | -------------------------------------------------- |
| `text`        | string | Text to be displayed in the notice.                |
| `noticeProps` | Object | Object with the [notice options](#notice-options). |

#### `addErrorNotice( text = '', noticeProps = {} )`

Create a new error notice.

| Argument      | Type   | Description                                        |
| ------------- | ------ | -------------------------------------------------- |
| `text`        | string | Text to be displayed in the notice.                |
| `noticeProps` | Object | Object with the [notice options](#notice-options). |

#### `addWarningNotice( text = '', noticeProps = {} )`

Create a new warning notice.

| Argument      | Type   | Description                                        |
| ------------- | ------ | -------------------------------------------------- |
| `text`        | string | Text to be displayed in the notice.                |
| `noticeProps` | Object | Object with the [notice options](#notice-options). |

#### `addInfoNotice( text = '', noticeProps = {} )`

Create a new info notice.

| Argument      | Type   | Description                                        |
| ------------- | ------ | -------------------------------------------------- |
| `text`        | string | Text to be displayed in the notice.                |
| `noticeProps` | Object | Object with the [notice options](#notice-options). |

#### `addSuccessNotice( text = '', noticeProps = {} )`

Create a new success notice.

| Argument      | Type   | Description                                        |
| ------------- | ------ | -------------------------------------------------- |
| `text`        | string | Text to be displayed in the notice.                |
| `noticeProps` | Object | Object with the [notice options](#notice-options). |

#### `hasNoticesOfType( type )`

Check whether there are notices of the provided type in the current context.

| Argument | Type   | Description                   |
| -------- | ------ | ----------------------------- |
| `type`   | string | Type of the notices to check. |

#### `notices`

An array of the notices in the current context.

#### `removeNotice( id, ctx )`

Remove an existing notice.

| Argument | Type   | Description                                   |
| -------- | ------ | --------------------------------------------- |
| `id`     | string | Id of the notice to remove.                   |
| `ctx`    | string | Context where the notice to remove is stored. |

#### `removeNotices( status = null )`

Remove all notices from the current context. If a `status` is provided, only the notices with that status are removed.

| Argument | Type   | Description                                                                                           |
| -------- | ------ | ----------------------------------------------------------------------------------------------------- |
| `status` | string | Status that notices must match to be removed. If not provided, all notices of any status are removed. |

#### `setIsSuppressed( val )`

Whether notices are suppressed. If true, it will hide the notices from the frontend.

| Argument | Type    | Description                 |
| -------- | ------- | --------------------------- |
| `val`    | boolean | Id of the notice to remove. |

## StoreNoticesProvider

The `StoreNoticesProvider` allows managing notices in the frontend. Notices are rendered before React Children.

Internally, it uses the `StoreNoticesContext` which relies on the [`notices` package](https://github.com/WordPress/gutenberg/tree/master/packages/notices) from Gutenberg.

### Actions

#### `createNotice( status = 'default', content = '', options = {} )`

This action creates a new notice. If the context is not specified in the `options` object, the current context is used.

| Argument  | Type   | Description                                        |
| --------- | ------ | -------------------------------------------------- |
| `status`  | string | One of the statuses listed below.                  |
| `content` | string | Text to be displayed in the notice.                |
| `options` | Object | Object with the [notice options](#notice-options). |

#### `createSnackbarNotice( content = '', options = {} )`

This action creates a new snackbar notice. If the context is not specified in the `options` object, the current context is used.

| Argument  | Type   | Description                                        |
| --------- | ------ | -------------------------------------------------- |
| `content` | string | Text to be displayed in the notice.                |
| `options` | Object | Object with the [notice options](#notice-options). |

#### `removeNotice( id, ctx )`

This action removes an existing notice. If the context is not specified, the current context is used.

| Argument | Type   | Description                                                                                                 |
| -------- | ------ | ----------------------------------------------------------------------------------------------------------- |
| `id`     | string | Id of the notice to remove.                                                                                 |
| `ctx`    | string | Context where the notice to remove is stored. If the context is not specified, the current context is used. |

#### `setIsSuppressed( val )`

Whether notices are suppressed. If true, it will hide the notices from the frontend.

| Argument | Type    | Description                 |
| -------- | ------- | --------------------------- |
| `val`    | boolean | Id of the notice to remove. |

### Statuses

All notices must have one of the following statuses: `default`, `error`, `success`, `info`, `warning`.

### Notice options

Object of the form:

```JS
{
	id: 'checkout',
	type: string,
	isDismissible: false,
}
```

Refer to the [Gutenberg docs](https://github.com/WordPress/gutenberg/blob/master/packages/notices/src/store/actions.js#L46) to know the available options.

## useStoreSnackbarNotices()

The `useStoreNotices()` hook allows reading and manipulating snackbar notices in the frontend.

The snackbar is a small toast-like notification that appears at the bottom of a user's screen.

<img width="300" src="https://user-images.githubusercontent.com/5656702/124294673-dd803b80-db4f-11eb-81ec-02fb962d04ed.png" />

### API

#### `addSnackbarNotice( text = '', noticeProps = {} )`

Create a new snackbar notice.

| Argument      | Type   | Description                                        |
| ------------- | ------ | -------------------------------------------------- |
| `text`        | string | Text to be displayed in the notice.                |
| `noticeProps` | Object | Object with the [notice options](#notice-options). |

#### `notices`

An array of the notices in the current context.

#### `removeNotices( status = null )`

Remove all notices from the current context. If a `status` is provided, only the notices with that status are removed.

| Argument | Type   | Description                                                                                           |
| -------- | ------ | ----------------------------------------------------------------------------------------------------- |
| `status` | string | Status that notices must match to be removed. If not provided, all notices of any status are removed. |


## StoreSnackbarNoticesProvider

The `StoreSnackbarNoticesProvider` allows managing snackbar notices in the frontend. Snackbar notices are displayed in the bottom left corner and disappear after a certain time.

Internally, it uses the `StoreNoticesContext` which relies on the [`notices` package](https://github.com/WordPress/gutenberg/tree/master/packages/notices) from Gutenberg.

### Actions

#### `createSnackbarNotice( content = '', options = {} )`

This action creates a new snackbar notice. If the context is not specified in the `options` object, the current context is used.

| Argument  | Type   | Description                                        |
| --------- | ------ | -------------------------------------------------- |
| `content` | string | Text to be displayed in the notice.                |
| `options` | Object | Object with the [notice options](#notice-options). |

#### `removeSnackbarNotice( id, ctx )`

This action removes an existing notice. If the context is not specified, the current context is used.

| Argument | Type   | Description                                                                                                 |
| -------- | ------ | ----------------------------------------------------------------------------------------------------------- |
| `id`     | string | Id of the notice to remove.                                                                                 |
| `ctx`    | string | Context where the notice to remove is stored. If the context is not specified, the current context is used. |

#### `setIsSuppressed( val )`

Whether notices are suppressed. If true, it will hide the notices from the frontend.

| Argument | Type    | Description                 |
| -------- | ------- | --------------------------- |
| `val`    | boolean | Id of the notice to remove. |


## Example usage

The following example shows a `CheckoutProcessor` component that displays an error notice when the payment process fails and it removes it every time the payment is started. When the payment is completed correctly, it shows a snackbar notice.

```JSX
const CheckoutProcessor = () => {
	const { addErrorNotice, removeNotice } = useStoreNotices();
	const { addSnackbarNotice } = useStoreSnackbarNotices();
	// ...
	const paymentFail = () => {
		addErrorNotice( 'Something went wrong.', { id: 'checkout' } );
	};
	const paymentStart = () => {
		removeNotice( 'checkout' );
	};
	const paymentSuccess = () => {
		addSnackbarNotice( 'Payment successfully completed.' );
	};
	// ...
};
```

```JSX
<StoreNoticesSnackbarProvider context="wc/checkout">
  <StoreNoticesProvider context="wc/checkout">
      // ...
      <CheckoutProcessor />
  </StoreNoticesProvider>
</StoreSnackbarNoticesProvider>
```

<!-- FEEDBACK -->
---

[We're hiring!](https://woocommerce.com/careers/) Come work with us!

🐞 Found a mistake, or have a suggestion? [Leave feedback about this document here.](https://github.com/woocommerce/woocommerce-gutenberg-products-block/issues/new?assignees=&labels=type%3A+documentation&template=--doc-feedback.md&title=Feedback%20on%20./docs/block-client-apis/notices.md)
<!-- /FEEDBACK -->

