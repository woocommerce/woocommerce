`Chart` (component)
===================

A chart container using d3, to display timeseries data with an interactive legend.

Props
-----

### `data`

- Type: Array
- Default: `[]`

An array of data.

### `title`

- Type: String
- Default: null

A title describing this chart.

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

### `height`

- Type: Number
- Default: `200`

Relative viewpoirt height of the `svg`.

### `legend`

- Type: Array
- Default: null

@todo Remove – not used?

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

### `yFormat`

- Type: String
- Default: `',.0f'`

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

