`Chart` (component)
===================

A chart container using d3, to display timeseries data with an interactive legend.

Props
-----

### `allowedIntervals`

- Type: Array
- Default: null

Allowed intervals to show in a dropdown.

### `baseValue`

- Type: Number
- Default: `0`

Base chart value. If no data value is different than the baseValue, the
`emptyMessage` will be displayed if provided.

### `chartType`

- Type: One of: 'bar', 'line'
- Default: `'line'`

Chart type of either `line` or `bar`.

### `data`

- Type: Array
- Default: `[]`

An array of data.

### `dateParser`

- Type: String
- Default: `'%Y-%m-%dT%H:%M:%S'`

Format to parse dates into d3 time format

### `emptyMessage`

- Type: String
- Default: null

The message to be displayed if there is no data to render. If no message is provided,
nothing will be displayed.

### `filterParam`

- Type: String
- Default: null

Name of the param used to filter items. If specified, it will be used, in combination
with query, to detect which elements are being used by the current filter and must be
displayed even if their value is 0.

### `itemsLabel`

- Type: String
- Default: null

Label describing the legend items.

### `mode`

- Type: One of: 'item-comparison', 'time-comparison'
- Default: `'time-comparison'`

`item-comparison` (default) or `time-comparison`, this is used to generate correct
ARIA properties.

### `path`

- Type: String
- Default: null

Current path

### `query`

- Type: Object
- Default: null

The query string represented in object form

### `interactiveLegend`

- Type: Boolean
- Default: `true`

Whether the legend items can be activated/deactivated.

### `interval`

- Type: One of: 'hour', 'day', 'week', 'month', 'quarter', 'year'
- Default: `'day'`

Interval specification (hourly, daily, weekly etc).

### `intervalData`

- Type: Object
- Default: null

Information about the currently selected interval, and set of allowed intervals for the chart. See `getIntervalsForQuery`.

### `isRequesting`

- Type: Boolean
- Default: `false`

Render a chart placeholder to signify an in-flight data request.

### `legendPosition`

- Type: One of: 'bottom', 'side', 'top'
- Default: null

Position the legend must be displayed in. If it's not defined, it's calculated
depending on the viewport width and the mode.

### `screenReaderFormat`

- Type: One of type: string, func
- Default: `'%B %-d, %Y'`

A datetime formatting string or overriding function to format the screen reader labels.

### `showHeaderControls`

- Type: Boolean
- Default: `true`

Wether header UI controls must be displayed.

### `title`

- Type: String
- Default: null

A title describing this chart.

### `tooltipLabelFormat`

- Type: One of type: string, func
- Default: `'%B %-d, %Y'`

A datetime formatting string or overriding function to format the tooltip label.

### `tooltipValueFormat`

- Type: One of type: string, func
- Default: `','`

A number formatting string or function to format the value displayed in the tooltips.

### `tooltipTitle`

- Type: String
- Default: null

A string to use as a title for the tooltip. Takes preference over `tooltipLabelFormat`.

### `valueType`

- Type: String
- Default: null

What type of data is to be displayed? Number, Average, String?

### `xFormat`

- Type: String
- Default: `'%d'`

A datetime formatting string, passed to d3TimeFormat.

### `x2Format`

- Type: String
- Default: `'%b %Y'`

A datetime formatting string, passed to d3TimeFormat.

### `yBelow1Format`

- Type: String
- Default: null

A number formatting string, passed to d3Format.

### `yFormat`

- Type: String
- Default: null

A number formatting string, passed to d3Format.

`ChartPlaceholder` (component)
==============================

`ChartPlaceholder` displays a large loading indiciator for use in place of a `Chart` while data is loading.

Props
-----

### `height`

- Type: Number
- Default: `0`


