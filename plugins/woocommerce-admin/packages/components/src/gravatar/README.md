Gravatar
===

Display a users Gravatar.

## Usage

```jsx
<Gravatar
	user="email@example.org"
	size={ 48 }
/>
```

### Props

Name | Type | Default | Description
--- | --- | --- | ---
`user` | One of type: object, string | `null` | The address to hash for displaying a Gravatar. Can be an email address or WP-API user object
`alt` | String | `null` | Text to display as the image alt attribute
`title` | String | `null` | Text to use for the image's title
`size` | Number | `60` | Default 60. The size of Gravatar to request
`className` | String | `null` | Additional CSS classes
