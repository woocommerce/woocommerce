Date Range Picker
===

Select a range of dates or single dates

## Usage

```jsx

<DateRangeFilterPicker
	key="daterange"
	onRangeSelect={ () => {} }
/>
```

### Props

Required props are marked with `*`.

Name    | Type     | Default | Description
------- | -------- | ------- | ---
`query` | Object | `{}` | The query string represented in object form
`onRangeSelect` | Function | `null` | Callback called when selection is made

## URL as the source of truth

The Date Range Picker reads parameters from the URL querystring and updates them by creating a link to reflect newly selected parameters, which is rendered as the "Update" button.

URL Parameter | Default | Possible Values
--- | --- | ---
`period` | `today` | `today`, `yesterday`, `week`, `last_week`, `month`, `last_month`, `quarter`, `last_quarter`, `year`, `last_year`, `custom`
`compare` | `previous_period` | `previous_period`, `previous_year`
`start` | none | start date for custom periods `2018-04-15`. [ISO 8601 format](https://en.wikipedia.org/wiki/ISO_8601)
`end` | none | end date for custom periods `2018-04-15`. [ISO 8601 format](https://en.wikipedia.org/wiki/ISO_8601)
