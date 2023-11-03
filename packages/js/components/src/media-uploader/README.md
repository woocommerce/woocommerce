# MediaUploader

This component adds an upload button and a dropzone for uploading media to a site.

## Usage

By default this will use the functionality from `@wordpress/media-utils` which provides access and uploads to the WP media library and uses the WP media modal.

```jsx
<MediaUploader
	label={ 'Click the button below to upload' }
	onSelect={ ( file ) => setImages( [ ...images, file ] ) }
	onUpload={ ( files ) => setImages( [ ...images, ...files ] ) }
/>
```

### Props

| Name                   | Type        | Default                               | Description                                                                         |
| ---------------------- | ----------- | ------------------------------------- | ----------------------------------------------------------------------------------- |
| `allowedMediaTypes`    | String[]    | `[ 'image ]`                          | Allowed media types                                                                 |
| `buttonText`           | String      | `Choose images`                       | Text to use for button                                                              |
| `hasDropZone`          | Boolean     | `true`                                | Whether or not to allow the dropzone                                                |
| `label`                | String      | `Drag images here or click to upload` | String to use for the text shown inside the component                               |
| `MediaUploadComponent` | JSX.Element | `MediaModal`                          | The component to use for the media uploader                                         |
| `onError`              | Function    | `() => null`                          | Callback function to run when an error occurs                                       |
| `onUpload`             | Function    | `() => null`                          | Callback function to run when an upload occurs aftering dragging and dropping files |
| `onSelect`             | Function    | `() => null`                          | Callback function to run when selecting media from the opened media modal           |
