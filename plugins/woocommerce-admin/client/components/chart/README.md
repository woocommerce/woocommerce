D3 Chart Component
===

A simple D3 line and bar chart component for timeseries data in React.

## Usage

```jsx
<Chart
	data={ timeseries }
	tooltipFormat={ 'Date is %Y-%m-%d' }
	type={ 'bar' }
	xFormat={ '%d' }
	yFormat={ '.3s' }
/>
```

### Expected Data Format
This component accepts timeseries `data` prop in the following format (with dates following the [ISO 8601 format](https://en.wikipedia.org/wiki/ISO_8601)):
```
[
	{
		date: '%Y-%m-%dT%H:%M:%S', // string
		category1: value, // number
		category2: value, // number
		...
	},
	...
]
```
For example:
```
[
	{
		date: '2018-06-25T00:00:00',
		category1: 1234.56,
		category2: 9876,
		...
	},
	...
]
```

### Props
Required props are marked with `*`.

Name | Type | Default | Description
--- | --- | --- | ---
`data`* | `array` | none | An array of data as specified above(below)
`type`* | `string` | `line` | Chart type of either `line` or `bar`
`title` | `string` | none | Chart title
`tooltipFormat` | `string` | `%Y-%m-%d` | Title and format of the tooltip title
`xFormat` | `string` | `%Y-%m-%d` | d3TimeFormat of the x-axis values
`yFormat` | `string` | `.3s` | d3Format of the y-axis values
