SummaryList
===

A container element for a list of SummaryNumbers. This component handles detecting & switching to the mobile format on smaller screens.

## Usage

```jsx
<SummaryList>
	{ () => {
		return [
			<SummaryNumber
				key="revenue"
				value={ '$829.40' }
				label="Total Sales"
				delta={ 29 }
				href="/analytics/report"
			>
				<span>27 orders</span>
			</SummaryNumber>,
			<SummaryNumber
				key="refunds"
				value={ '$24.00' }
				label="Refunds"
				delta={ -10 }
				href="/analytics/report"
				selected
			/>,
		];
	} }
</SummaryList>
```

### Props

Name | Type | Default | Description
--- | --- | --- | ---
`children` | Function | `null` | (required) A function returning a list of `<SummaryNumber />`s
`label` | String | `__( 'Performance Indicators', 'woocommerce-admin' )` | An optional label of this group, read to screen reader users


SummaryNumber
===

A component to show a value, label, and optionally a change percentage and children node. Can also act as a link to a specific report focus.

## Usage

See above

### Props

Name | Type | Default | Description
--- | --- | --- | ---
`delta` | Number | `null` | A number to represent the percentage change since the last comparison period - positive numbers will show a green up arrow, negative numbers will show a red down arrow, and zero will show a flat right arrow. If omitted, no change value will display
`href` | String | `''` | An internal link to the report focused on this number
`isOpen` | Boolean | `false` | Boolean describing whether the menu list is open. Only applies in mobile view, and only applies to the toggle-able item (first in the list)
`label` | String | `null` | (required) A string description of this value, ex "Revenue", or "New Customers"
`onToggle` | Function | `null` | A function used to switch the given SummaryNumber to a button, and called on click
`prevLabel` | String | `__( 'Previous Period:', 'woocommerce-admin' )` | A string description of the previous value's timeframe, ex "Previous Year:"
`prevValue` | One of type: number, string | `null` | A string or number value to display - a string is allowed so we can accept currency formatting. If omitted, this section won't display
`reverseTrend` | Boolean | `false` | A boolean used to indicate that a negative delta is "good", and should be styled like a positive (and vice-versa)
`selected` | Boolean | `false` | A boolean used to show a highlight style on this number
`value` | One of type: number, string | `null` | A string or number value to display - a string is allowed so we can accept currency formatting
`onLinkClickCallback` | Function | `noop` | A function to be called after a SummaryNumber, rendered as a link, is clicked


SummaryListPlaceholder
===

`SummaryListPlaceholder` behaves like `SummaryList` but displays placeholder summary items instead of data. This can be used while loading data.

## Usage

```jsx
<SummaryListPlaceholder numberOfItems={ 2 } />
```

### Props

Name | Type | Default | Description
--- | --- | --- | ---
`numberOfItems` | Number | `null` | (required) An integer with the number of summary items to display
`numberOfRows` |  | `5` | 
