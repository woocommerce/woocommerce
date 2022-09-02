WebPreview
===

WebPreview component to display an iframe of another page.

## Usage

```jsx
<WebPreview
    title="My Web Preview"
    src="https://themes.woocommerce.com/?name=galleria"
/>
```

### Props

Name | Type | Default | Description
--- | --- | --- | ---
`className` | String | `null` | Additional class name to style the component
`loadingContent` | ReactNode | `<Spinner />` | Content shown when iframe is still loading
`onLoad` | Function | `noop` | Function to fire when iframe content is loaded
`src` | String | `null` | (required) Iframe src to load
`title` | String | `null` | (required) Iframe title
