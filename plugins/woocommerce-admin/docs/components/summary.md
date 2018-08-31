`SummaryList` (component)
=========================

A container element for a list of SummaryNumbers. This component handles detecting & switching to
the mobile format on smaller screens.



Props
-----

### `children`

- **Required**
- Type: ReactNode
- Default: null

A list of `<SummaryNumber />`s

### `label`

- Type: String
- Default: null

An optional label of this group, read to screen reader users. Defaults to "Performance Indicators".

`SummaryNumber` (component)
===========================

A component to show a value, label, and an optional change percentage. Can also act as a link to a specific report focus.



Props
-----

### `delta`

- Type: Number
- Default: null

A number to represent the percentage change since the last comparison period - positive numbers will show
a green up arrow, negative numbers will show a red down arrow, and zero will show a flat right arrow.
If omitted, no change value will display.

### `href`

- Type: String
- Default: `'/analytics'`

An internal link to the report focused on this number.

### `isOpen`

- Type: Boolean
- Default: `false`

Boolean describing whether the menu list is open. Only applies in mobile view,
and only applies to the toggle-able item (first in the list).

### `label`

- **Required**
- Type: String
- Default: null

A string description of this value, ex "Revenue", or "New Customers"

### `onToggle`

- Type: Function
- Default: null

A function used to switch the given SummaryNumber to a button, and called on click.

### `prevLabel`

- Type: String
- Default: `__( 'Previous Period:', 'wc-admin' )`

A string description of the previous value's timeframe, ex "Previous Year:".

### `prevValue`

- Type: One of type: number, string
- Default: null

A string or number value to display - a string is allowed so we can accept currency formatting.
If omitted, this section won't display.

### `reverseTrend`

- Type: Boolean
- Default: `false`

A boolean used to indicate that a negative delta is "good", and should be styled like a positive (and vice-versa).

### `selected`

- Type: Boolean
- Default: `false`

A boolean used to show a highlight style on this number.

### `value`

- **Required**
- Type: One of type: number, string
- Default: null

A string or number value to display - a string is allowed so we can accept currency formatting.

