Date
===

Use the `Date` component to display accessible dates or times.

## Usage

```jsx
<Date date="2019-01-01" />
```

### Props

Name | Type | Default | Description
--- | --- | --- | ---
`date` | One of type: string, object | `null` | (required) Date to use in the component
`machineFormat` | String | `'Y-m-d H:i:s'` | Date format used in the `datetime` prop of the `time` element
`screenReaderFormat` | String | `'F j, Y'` | Date format used for screen readers
`visibleFormat` | String | `'Y-m-d'` | Date format displayed in the page
