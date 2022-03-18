Chart
===

A chart container using d3, to display timeseries data with an interactive legend.

## Usage

```jsx
const data = [
	{
		date: '2018-05-30T00:00:00',
		Hoodie: {
			label: 'Hoodie',
			value: 21599,
		},
		Sunglasses: {
			label: 'Sunglasses',
			value: 38537,
		},
		Cap: {
			label: 'Cap',
			value: 106010,
		},
	},
	{
		date: '2018-05-31T00:00:00',
		Hoodie: {
			label: 'Hoodie',
			value: 14205,
		},
		Sunglasses: {
			label: 'Sunglasses',
			value: 24721,
		},
		Cap: {
			label: 'Cap',
			value: 70131,
		},
	},
	{
		date: '2018-06-01T00:00:00',
		Hoodie: {
			label: 'Hoodie',
			value: 10581,
		},
		Sunglasses: {
			label: 'Sunglasses',
			value: 19991,
		},
		Cap: {
			label: 'Cap',
			value: 53552,
		},
	},
	{
		date: '2018-06-02T00:00:00',
		Hoodie: {
			label: 'Hoodie',
			value: 9250,
		},
		Sunglasses: {
			label: 'Sunglasses',
			value: 16072,
		},
		Cap: {
			label: 'Cap',
			value: 47821,
		},
	},
];

<Chart data={ data } title="Example Chart" layout="item-comparison" />
```

### Props

Name | Type | Default | Description
--- | --- | --- | ---
`allowedIntervals` | Array | `null` | Allowed intervals to show in a dropdown
`baseValue` | Number | `0` | Base chart value. If no data value is different than the baseValue, the `emptyMessage` will be displayed if provided
`chartType` | One of: 'bar', 'line' | `'line'` | Chart type of either `line` or `bar`
`data` | Array | `[]` | An array of data
`dateParser` | String | `'%Y-%m-%dT%H:%M:%S'` | Format to parse dates into d3 time format
`emptyMessage` | String | `null` | The message to be displayed if there is no data to render. If no message is provided, nothing will be displayed
`filterParam` | String | `null` | Name of the param used to filter items. If specified, it will be used, in combination with query, to detect which elements are being used by the current filter and must be displayed even if their value is 0
`itemsLabel` | String | `null` | Label describing the legend items
`mode` | One of: 'item-comparison', 'time-comparison' | `'time-comparison'` | `item-comparison` (default) or `time-comparison`, this is used to generate correct ARIA properties
`path` | String | `null` | Current path
`query` | Object | `null` | The query string represented in object form
`interactiveLegend` | Boolean | `true` | Whether the legend items can be activated/deactivated
`interval` | One of: 'hour', 'day', 'week', 'month', 'quarter', 'year' | `'day'` | Interval specification (hourly, daily, weekly etc)
`intervalData` | Object | `null` | Information about the currently selected interval, and set of allowed intervals for the chart. See `getIntervalsForQuery`
`isRequesting` | Boolean | `false` | Render a chart placeholder to signify an in-flight data request
`legendPosition` | One of: 'bottom', 'side', 'top', 'hidden' | `null` | Position the legend must be displayed in. If it's not defined, it's calculated depending on the viewport width and the mode
`legendTotals` | Object | `null` | Values to overwrite the legend totals. If not defined, the sum of all line values will be used
`screenReaderFormat` | One of type: string, func | `'%B %-d, %Y'` | A datetime formatting string or overriding function to format the screen reader labels
`showHeaderControls` | Boolean | `true` | Wether header UI controls must be displayed
`title` | String | `null` | A title describing this chart
`tooltipLabelFormat` | One of type: string, func | `'%B %-d, %Y'` | A datetime formatting string or overriding function to format the tooltip label
`tooltipValueFormat` | One of type: string, func | `','` | A number formatting string or function to format the value displayed in the tooltips
`tooltipTitle` | String | `null` | A string to use as a title for the tooltip. Takes preference over `tooltipLabelFormat`
`valueType` | String | `null` | What type of data is to be displayed? Number, Average, String?
`xFormat` | String | `'%d'` | A datetime formatting string, passed to d3TimeFormat
`x2Format` | String | `'%b %Y'` | A datetime formatting string, passed to d3TimeFormat
`yBelow1Format` | String | `null` | A number formatting string, passed to d3Format
`yFormat` | String | `null` | A number formatting string, passed to d3Format
`currency` | Object | `{}` | An object with currency properties for usage in the chart. The following properties are expected: `decimal`, `symbol`, `symbolPosition`, `thousands`. This is passed to d3Format.

## Overriding chart colors

Char colors can be overridden by hooking into the filter `woocommerce_admin_chart_item_color`. For example:

```js
const colorScales = [
  "#0A2F51",
  "#0E4D64",
  "#137177",
  "#188977",
];
addFilter( 'woocommerce_admin_chart_item_color', 'example', ( index, key, orderedKeys ) => colorScales[index] );
```
