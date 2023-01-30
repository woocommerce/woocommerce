Flag
===

Use the `Flag` component to display a country's flag using the operating system's emojis.

 React component.

## Usage

```jsx
<Flag code="VU" size={ 48 } />
```

### Props

Name | Type | Default | Description
--- | --- | --- | ---
`code` | String | `null` | Two letter, three letter or three digit country code
`order` | Object | `null` | An order can be passed instead of `code` and the code will automatically be pulled from the billing or shipping data
`className` | String | `null` | Additional CSS classes
`size` | Number | `null` | Supply a font size to be applied to the emoji flag
