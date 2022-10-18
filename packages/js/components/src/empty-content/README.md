EmptyContent
===

A component to be used when there is no data to show.
It can be used as an opportunity to provide explanation or guidance to help a user progress.

## Usage

```jsx
<EmptyContent
	title="Nothing here"
	message="Some descriptive text"
	actionLabel="Reload page"
	actionURL="#"
/>
```

### Props

Name | Type | Default | Description
--- | --- | --- | ---
`title` | String | `null` | (required) The title to be displayed
`message` | String | `null` | An additional message to be displayed
`illustration` | String | `'/empty-content.svg'` | The url string of an image path. Prefix with `/` to load an image relative to the plugin directory
`illustrationHeight` | Number | `null` | Height to use for the illustration
`illustrationWidth` | Number | `400` | Width to use for the illustration
`actionLabel` | String | `null` | (required) Label to be used for the primary action button
`actionURL` | String | `null` | URL to be used for the primary action button
`actionCallback` | Function | `null` | Callback to be used for the primary action button
`secondaryActionLabel` | String | `null` | Label to be used for the secondary action button
`secondaryActionURL` | String | `null` | URL to be used for the secondary action button
`secondaryActionCallback` | Function | `null` | Callback to be used for the secondary action button
`className` | String | `null` | Additional CSS classes
