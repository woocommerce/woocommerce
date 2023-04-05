# SnackbarList Component <!-- omit in toc -->

A temporary informational UI displayed at the bottom of store pages.

## Table of contents <!-- omit in toc -->

-   [Design Guidelines](#design-guidelines)
-   [Development Guidelines](#development-guidelines)
    -   [Usage](#usage)
    -   [Props](#props)
        -   [`className`: `string`](#classname-string)
        -   [`onRemove`: `( noticeId ) => void`](#onremove--noticeid---void)
        -   [`notices`: `NoticeType[]`](#notices-noticetype)

## Design Guidelines

The buyer notice snackbar is temporary informational UI displayed at the bottom of store pages. WooCommerce blocks, themes, and plugins all use snackbar notices to indicate the result of a successful action.

Snackbar notices work in the same way as the NoticeBanner component, and support the same statuses and styles.

## Development Guidelines

### Usage

To display snackbar notices, pass an array of `notices` to the `SnackbarList` component:

```jsx
import { SnackbarList } from '@woocommerce/base-components';

const notices = [
	{
        id: '1',
        content: 'This is a snackbar notice.',
        status: 'default',
        isDismissible: true,
    }
];

<SnackbarList notices={ notices }>;
```

The component consuming `SnackbarList` is responsible for managing the notices state. The `SnackbarList` component will automatically remove notices from the list when they are dismissed by the user using the provided `onRemove` callback, and also when the notice times out after 10000ms.

### Props

#### `className`: `string`

Additional class name to give to the notice.

#### `onRemove`: `( noticeId ) => void`

Function called when dismissing the notice. When the close icon is clicked or the Escape key is pressed, this function will be called. This is also called when the notice times out after 10000ms.

#### `notices`: `NoticeType[]`

A list of notices to display as snackbars. Each notice must have an `id` and `content` prop.

-   The `id` prop is used to identify the notice and should be unique.
-   The `content` prop is the content to display in the notice.
-   The `status` prop is used to determine the color of the notice and the icon. Acceptable values are 'success', 'error', 'info', 'warning', and 'default'.
-   The `isDismissible` prop determines whether the notice can be dismissed by the user.
-   The `spokenMessage` prop is used to change the spoken message for assistive technology. If not provided, the `content` prop will be used as the spoken message.
