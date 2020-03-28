Button
===

This component creates simple reusable html `<button></button>` element.

## Usage

```jsx
<Button
	isDefault
	onClick={ this.onActivateClick }
	disabled={ isLoading }
>
	{ __( 'Activate', 'woocommerce-admin' ) }
</Button>
```

### Props

Name | Type | Default | Description
--- | --- | --- | ---
`className` | String | `null` | Additional class name to style the component
