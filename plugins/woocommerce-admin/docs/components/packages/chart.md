`Chart` (component)
===================

A chart container using d3, to display timeseries data with an interactive legend.

Props
-----

### `data`

- Type: Array
- Default: `[]`

An array of data.

### `dateParser`

- Type: String
- Default: `'%Y-%m-%dT%H:%M:%S'`

Format to parse dates into d3 time format

### `itemsLabel`

- Type: String
- Default: null

Label describing the legend items.

### `path`

- Type: String
- Default: null

Current path

### `query`

- Type: Object
- Default: null

The query string represented in object form

### `tooltipLabelFormat`

- Type: One of type: string, func
- Default: `'%B %d, %Y'`

A datetime formatting string or overriding function to format the tooltip label.

### `tooltipValueFormat`

- Type: One of type: string, func
- Default: `','`

A number formatting string or function to format the value displayed in the tooltips.

### `tooltipTitle`

- Type: String
- Default: null

A string to use as a title for the tooltip. Takes preference over `tooltipLabelFormat`.

### `xFormat`

- Type: String
- Default: `'%d'`

A datetime formatting string, passed to d3TimeFormat.

### `x2Format`

- Type: String
- Default: `'%b %Y'`

A datetime formatting string, passed to d3TimeFormat.

### `yFormat`

- Type: String
- Default: `'$.3s'`

A number formatting string, passed to d3Format.

### `mode`

- Type: One of: 'item-comparison', 'time-comparison'
- Default: `'time-comparison'`

`item-comparison` (default) or `time-comparison`, this is used to generate correct
ARIA properties.

### `title`

- Type: String
- Default: null

A title describing this chart.

### `type`

- Type: One of: 'bar', 'line'
- Default: `'line'`

Chart type of either `line` or `bar`.

### `intervalData`

- Type: Object
- Default: null

Information about the currently selected interval, and set of allowed intervals for the chart. See `getIntervalsForQuery`.

### `interval`

- Type: One of: 'hour', 'day', 'week', 'month', 'quarter', 'year'
- Default: `'day'`

Interval specification (hourly, daily, weekly etc).

### `allowedIntervals`

- Type: Array
- Default: null

Allowed intervals to show in a dropdown.

### `valueType`

- Type: String
- Default: null

What type of data is to be displayed? Number, Average, String?

### `isRequesting`

- Type: Boolean
- Default: `false`

Render a chart placeholder to signify an in-flight data request.

`ChartPlaceholder` (component)
==============================

`ChartPlaceholder` displays a large loading indiciator for use in place of a `Chart` while data is loading.

Props
-----

### `height`

- Type: Number
- Default: `0`


