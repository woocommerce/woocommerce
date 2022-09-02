Button
===

This component creates simple reusable html `<button></button>` element.

## Usage

```jsx
<Button
	isSecondary
	onClick={ this.onActivateClick }
	disabled={ isLoading }
>
	{ __( 'Activate', 'woocommerce' ) }
</Button>
```

### Props

Name | Type | Default | Description
--- | --- | --- | ---
`className` | String | `null` | Additional class name to style the component
