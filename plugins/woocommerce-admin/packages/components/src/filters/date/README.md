Date Picker
===

Select a range of dates or single dates

## Usage

```jsx
<DatePicker key={ JSON.stringify( query ) } path={ path } query={ query } />
```

### Props

Required props are marked with `*`.

Name    | Type     | Default | Description
------- | -------- | ------- | ---
`key`*  | string   | none    |  Force a recalculation or reset of internal state when this key changes. Useful for a url change, for instance
`path`* | `string` | none    | The `path` parameter supplied by React-Router
`query` | `object` | `{}`    | The query string represented in object form

## URL as the source of truth

The Date Picker reads parameters from the URL querystring and updates them by creating a link to reflect newly selected parameters, which is rendered as the "Update" button.

 URL Parameter | Default | Possible Values
 --- | --- | ---
 `period` | `today` | `today`, `yesterday`, `week`, `last_week`, `month`, `last_month`, `quarter`, `last_quarter`, `year`, `last_year`, `custom`
 `compare` | `previous_period` | `previous_period`, `previous_year`
 `start` | none | start date for custom periods `2018-04-15`. [ISO 8601 format](https://en.wikipedia.org/wiki/ISO_8601)
 `end` | none | end date for custom periods `2018-04-15`. [ISO 8601 format](https://en.wikipedia.org/wiki/ISO_8601)
