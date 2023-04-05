# NoticeBanner Component <!-- omit in toc -->

An informational UI displayed near the top of the store pages.

## Table of contents <!-- omit in toc -->

-   [Design Guidelines](#design-guidelines)
-   [Development Guidelines](#development-guidelines)
    -   [Usage](#usage)
    -   [Props](#props)
        -   [`children`: `React.ReactNode`](#children-reactreactnode)
        -   [`className`: `string`](#classname-string)
        -   [`isDismissible`: `boolean`](#isdismissible-boolean)
        -   [`onRemove`: `() => void`](#onremove---void)
        -   [`politeness`: `'polite' | 'assertive'`](#politeness-polite--assertive)
        -   [`spokenMessage`: `string`](#spokenmessage-string)
        -   [`status`: `'success' | 'error' | 'info' | 'warning' | 'default'`](#status-success--error--info--warning--default)
        -   [`summary`: `string`](#summary-string)
            -   [Example](#example)

## Design Guidelines

Notices are informational UI displayed near the top of store pages. Notices are used to indicate the result of an action, or to draw the user’s attention to necessary information.

Notices are color-coded to indicate the type of message being communicated, and also show an icon to reinforce the meaning of the message. The color and icon used for a notice are determined by the `status` prop.

### Informational

Blue notices used for general information for buyers that are not blocking and do not require action.

![Informational notice](./screenshots/info.png)

### Error

Red notices to show that an error has occurred and that the user needs to take action.

![Error notice](./screenshots/error.png)

### Success

Green notices that show an action was successful.

![Success notice](./screenshots/success.png)

### Warning

Yellow notices that show that the user may need to take action, or needs to be aware of something important.

![Warning notice](./screenshots/warning.png)

### Default

Gray notice, similar to info, but used for less important messaging.

![Default notice](./screenshots/default.png)

## Development Guidelines

### Usage

To display a plain notice, pass the notice message as a string:

```jsx
import { NoticeBanner } from '@woocommerce/base-components';

<NoticeBanner status="info">Your message here</NoticeBanner>;
```

For more complex markup, you can pass any JSX element:

```jsx
import { NoticeBanner } from '@woocommerce/base-components';

<NoticeBanner status="error">
	<p>
		An error occurred: <code>{ errorDetails }</code>.
	</p>
</NoticeBanner>;
```

### Props

#### `children`: `React.ReactNode`

The displayed message of a notice. Also used as the spoken message for assistive technology, unless `spokenMessage` is provided as an alternative message.

#### `className`: `string`

Additional class name to give to the notice.

#### `isDismissible`: `boolean`

Determines whether the notice can be dismissed by the user. When set to true, a close icon will be displayed on the banner.

#### `onRemove`: `() => void`

Function called when dismissing the notice. When the close icon is clicked or the Escape key is pressed, this function will be called.

#### `politeness`: `'polite' | 'assertive'`

Determines the level of politeness for the notice for assistive technology. Acceptable values are 'polite' and 'assertive'. Default is 'polite'.

#### `spokenMessage`: `string`

Optionally provided to change the spoken message for assistive technology. If not provided, the `children` prop will be used as the spoken message.

#### `status`: `'success' | 'error' | 'info' | 'warning' | 'default'`

Status determines the color of the notice and the icon. Acceptable values are `success`, `error`, `info`, `warning`, and `default`.

#### `summary`: `string`

Optional summary text shown above notice content, used when several notices are listed together.

##### Example

```tsx
import { NoticeBanner } from '@woocommerce/base-components';

const errorMessages = [
	'First error message',
	'Second error message',
	'Third error message',
];

<NoticeBanner
	status="error"
	summary="There are errors in your form submission:"
>
	<ul>
		{ errorMessages.map( ( message ) => (
			<li key={ message }>{ message }</li>
		) ) }
	</ul>
</NoticeBanner>;
```

In this example, the summary prop is used to indicate to the user that there are errors in the form submission. The list of error messages is rendered within the NoticeBanner component using an unordered list (`<ul>`) and list items (`<li>`). The `status` prop is set to `error` to indicate that the notice represents an error message.
