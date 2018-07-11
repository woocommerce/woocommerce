D3 Chart Component
===

A simple D3 line and bar chart component for timeseries data in React.

## Usage

```jsx
<D3Chart
	className="woocommerce-dashboard__*"
	data={ timeseries }
	height={ 200 }
	margin={ { bottom: 30, left: 40, right: 0, top: 20 } }
	type={ 'bar' }
	width={ 600 }
/>
```

### Expected Data Format
This component accepts timeseries `data` prop in the following format (with dates following the [ISO 8601 format](https://en.wikipedia.org/wiki/ISO_8601)):
```
[
	{
		date: 'YYYY-mm-dd', // string
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
		date: '2018-06-25',
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
`data`* | `array` | none | An array of data as specified above
`height` | `number` | `200` | Relative viewpoirt height of the `svg`
`margin` | `object` | `{ bottom: 30, left: 40, right: 0, top: 20 }` | Margins for axis and chart padding
`type`* | `string` | `line` | Chart type of either `line` or `bar`
`width` | `number` | `600` | Relative viewport width of the `svg`
