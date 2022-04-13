ImageUpload
===

ImageUpload - Adds an upload area for selecting or uploading an image from the WordPress media gallery.

## Usage

```jsx
	<ImageUpload image={ image } onChange={ newImage => setState( { url: newImage } ) } />
```

### Props

Name | Type | Default | Description
--- | --- | --- | ---
`image` | Object | `null` | Image information containing media gallery `id` and image `url`
`onChange` | Function | `null` | Function to trigger when the selected image changes
`className` | String | `null` | Additional class name to style the component
