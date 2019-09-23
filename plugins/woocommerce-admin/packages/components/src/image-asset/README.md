ImageAsset
===

A component that loads an image, allowing images to be loaded relative to the main asset/plugin folder.
Props are passed through to `<img />`

## Usage

```jsx
<ImageAsset
	src="https://cldup.com/6L9h56D9Bw.jpg"
	alt="An illustration of sunglasses"
/>
```

### Props

Name | Type | Default | Description
--- | --- | --- | ---
`src` | String | `null` | (required) Image location to pass through to `<img />`
`alt` | String | `null` | (required) Alt text to pass through to `<img />`
