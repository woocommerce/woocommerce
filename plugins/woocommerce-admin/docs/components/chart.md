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

### `tooltipFormat`

- Type: String
- Default: `'%B %d, %Y'`

A datetime formatting string to format the date displayed as the title of the toolip
if `tooltipTitle` is missing, passed to d3TimeFormat.

### `tooltipTitle`

- Type: String
- Default: null

A string to use as a title for the tooltip. Takes preference over `tooltipFormat`.

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

### `layout`

- Type: One of: 'standard', 'comparison', 'compact'
- Default: `'standard'`

`standard` (default) legend layout in the header or `comparison` moves legend layout
to the left or 'compact' has the legend below

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

`D3Chart` (component)
=====================

A simple D3 line and bar chart component for timeseries data in React.

Props
-----

### `className`

- Type: String
- Default: null

Additional CSS classes.

### `colorScheme`

- Type: Function
- Default: null

A chromatic color function to be passed down to d3.

### `data`

- Type: Array
- Default: `[]`

An array of data.

### `dateParser`

- Type: String
- Default: `'%Y-%m-%dT%H:%M:%S'`

Format to parse dates into d3 time format

### `height`

- Type: Number
- Default: `200`

Relative viewpoirt height of the `svg`.

### `interval`

- Type: One of: 'hour', 'day', 'week', 'month', 'quarter', 'year'
- Default: null

Interval specification (hourly, daily, weekly etc.)

### `layout`

- Type: One of: 'standard', 'comparison', 'compact'
- Default: `'standard'`

`standard` (default) legend layout in the header or `comparison` moves legend layout
to the left or 'compact' has the legend below

### `margin`

- Type: Object
  - bottom: Number
  - left: Number
  - right: Number
  - top: Number
- Default: `{
    bottom: 30,
    left: 40,
    right: 0,
    top: 20,
}`

Margins for axis and chart padding.

### `orderedKeys`

- Type: Array
- Default: null

The list of labels for this chart.

### `tooltipFormat`

- Type: String
- Default: `'%B %d, %Y'`

A datetime formatting string to format the date displayed as the title of the toolip
if `tooltipTitle` is missing, passed to d3TimeFormat.

### `tooltipTitle`

- Type: String
- Default: null

A string to use as a title for the tooltip. Takes preference over `tooltipFormat`.

### `type`

- Type: One of: 'bar', 'line'
- Default: `'line'`

Chart type of either `line` or `bar`.

### `width`

- Type: Number
- Default: `600`

Relative viewport width of the `svg`.

### `xFormat`

- Type: String
- Default: `'%Y-%m-%d'`

A datetime formatting string, passed to d3TimeFormat.

### `x2Format`

- Type: String
- Default: `''`

A datetime formatting string, passed to d3TimeFormat.

### `yFormat`

- Type: String
- Default: `'.3s'`

A number formatting string, passed to d3Format.

`Legend` (component)
====================

A legend specifically designed for the WooCommerce admin charts.

Props
-----

### `className`

- Type: String
- Default: null

Additional CSS classes.

### `colorScheme`

- Type: Function
- Default: null

A chromatic color function to be passed down to d3.

### `data`

- **Required**
- Type: Array
- Default: null

An array of `orderedKeys`.

### `handleLegendToggle`

- Type: Function
- Default: null

Handles `onClick` event.

### `handleLegendHover`

- Type: Function
- Default: null

Handles `onMouseEnter`/`onMouseLeave` events.

### `legendDirection`

- Type: One of: 'row', 'column'
- Default: `'row'`

Display legend items as a `row` or `column` inside a flex-box.

`ChartPlaceholder` (component)
==============================

`ChartPlaceholder` displays a large loading indiciator for use in place of a `Chart` while data is loading.


