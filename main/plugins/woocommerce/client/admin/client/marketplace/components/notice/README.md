# Notice Component

The `Notice` component is a versatile notification UI element designed for use within the WooCommerce in-app marketplace.
It leverages the `@wordpress/icons` for displaying various icons and provides customizable options for content, appearance, and behavior.

## Usage

To use the Notice component, import it into your React component file and include it in your component's render method. Here is a basic example:

```jsx
import Notice from '../notice/notice';

function MyComponent() {
	return (
		<Notice
			id="unique-notice-id"
			description="This is a notice description."
			icon="info"
			isDismissible={true}
			variant="info"
		>
			<p>Additional content can go here.</p>
		</Notice>
	);
}

export default MyComponent;
```

## Props

The Notice component accepts the following props for customization:

- `id` (string): A unique identifier for the notice. This is used for dismissing and storing the dismissal state.
- `children` (JSX.Element): Optional. Additional React components or HTML elements to be displayed within the notice.
- `description` (string): The content of the notice. Can include HTML.
- `icon` (string): Optional. The name of the icon to display. Available options are info, check, percent.
- `isDismissible` (boolean): Optional. If true, displays a close button that hides the notice. Defaults to true.
- `variant` (string): Determines the style of the notice. Options are info, warning, error, success.

## Styling

The notice component can be styled using the following CSS classes, based on the variant prop:

- `.woocommerce-marketplace__notice-info`: Styles the notice with an info appearance.
- `.woocommerce-marketplace__notice-warning`: Styles the notice with a warning appearance.
- `.woocommerce-marketplace__notice-error`: Styles the notice with an error appearance.
- `.woocommerce-marketplace__notice-success`: Styles the notice with a success appearance.

Icons within the notice adopt the variant prop to determine their color, aligning with the overall style of the notice.

## Dismissal Behavior

Notices can be dismissed if `isDismissible` is set to `true`. The dismissal state is persisted in the browser's localStorage, preventing the notice from reappearing on future visits.

## Examples

Here are more detailed examples, some require that you import the Button component and/or internationalization functions:

```jsx
<Notice
	id="marketplace-sale-march-2024"
	variant="info"
	description={ __(
		'<strong>Limited time sale</strong> Tup to 40% off on extensions and themes. Sale ends March 29 at 2pm UTC.',
		'woocommerce'
	) }
	icon="percent"
	isDismissible
>
	<Button
		variant="secondary"
		onClick={ () => {
			console.log( 'Primary button clicked' );
		} }
		text="Label"
	/>
	<Button
		variant="tertiary"
		onClick={ () => {
			console.log( 'Secondary button clicked' );
		} }
		text="Label"
	/>
</Notice>
```

```jsx
<Notice
	id="success-notice"
	variant="success"
	description={ __(
		'<strong>Congratulations</strong> You successfully installed the plugin.',
		'woocommerce'
	) }
	icon="check"
	isDismissible
>
	<Button
		variant="secondary"
		onClick={ () => {
			console.log( 'Primary button clicked' );
		} }
		text="Label"
	/>
	<Button
		variant="tertiary"
		onClick={ () => {
			console.log( 'Secondary button clicked' );
		} }
		text="Label"
	/>
</Notice>
```

```jsx
<Notice
	id="warning-notice"
	variant="warning"
	description={ __(
		'This is a warning and I cannot be dismissed. Nope.',
		'woocommerce'
	) }
	icon="info"
	isDismissible={ false }
/>

<Notice
	id="error-notice"
	variant="error"
	description={ __(
		'I am red and I cannot be dismissed. Nope. But I support <i>HTML</i> <strong>tags</strong>. So <a href="#">I can have links</a>.',
		'woocommerce'
	) }
	icon="info"
	isDismissible={ false }
/>
```

```jsx
<Notice
	id="success-notice-no-icon"
	variant="success"
	description={ __(
		'I am a success! But I am sad because I do not have an icon.',
		'woocommerce'
	) }
	isDismissible={ false }
/>
```
