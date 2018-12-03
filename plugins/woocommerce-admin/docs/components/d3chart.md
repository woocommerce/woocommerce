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

Height of the `svg`.

### `interval`

- Type: One of: 'hour', 'day', 'week', 'month', 'quarter', 'year'
- Default: null

Interval specification (hourly, daily, weekly etc.)

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

### `mode`

- Type: One of: 'item-comparison', 'time-comparison'
- Default: `'time-comparison'`

`items-comparison` (default) or `time-comparison`, this is used to generate correct
ARIA properties.

### `orderedKeys`

- Type: Array
- Default: null

The list of labels for this chart.

### `tooltipLabelFormat`

- Type: One of type: string, func
- Default: `'%B %d, %Y'`

A datetime formatting string or overriding function to format the tooltip label.

### `tooltipValueFormat`

- Type: One of type: string, func
- Default: `','`

A number formatting string or function to format the value displayed in the tooltips.

### `tooltipPosition`

- Type: One of: 'below', 'over'
- Default: `'over'`

The position where to render the tooltip can be `over` the chart or `below` the chart.

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

Width of the `svg`.

### `xFormat`

- Type: One of type: string, func
- Default: `'%Y-%m-%d'`

A datetime formatting string or function, passed to d3TimeFormat.

### `x2Format`

- Type: One of type: string, func
- Default: `''`

A datetime formatting string or function, passed to d3TimeFormat.

### `yFormat`

- Type: One of type: string, func
- Default: `'.3s'`

A number formatting string or function, passed to d3Format.

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

### `itemsLabel`

- Type: String
- Default: null

Label to describe the legend items. It will be displayed in the legend of
comparison charts when there are many.

### `valueType`

- Type: String
- Default: null

What type of data is to be displayed? Number, Average, String?

