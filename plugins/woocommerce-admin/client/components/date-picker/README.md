Date Picker (Work in Progress)
===

Select a range of dates or single dates

## Usage

```jsx
<DatePicker period="last_week" compare="previous_year" />
```

### Props

Required props are marked with `*`.

Name | Type | Default | Description
--- | --- | --- | ---
`period`* | `string` | none | Selected period. `today`, `yesterday`, `week`, `last_week`, `month`, `last_month`, `quarter`, `last_quarter`, `year`, `last_year`, `custom`
`compare`* | `string` | none | Selected period to compare. `previous_period`, `previous_year`
`start` | `string` | none | If a `custom` period is selected, this is the start date
`end` | `string` | none | If a `custom` period is selected, this is the end date
