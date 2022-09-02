DatePicker
===

## Usage

```jsx
<DatePicker
	date={ date }
	text={ text }
	error={ error }
	onUpdate={ ( { date, text, error } ) => setState( { date, text, error } ) }
	dateFormat="MM/DD/YYYY"
/>
```

### Props

Name | Type | Default | Description
--- | --- | --- | ---
`date` | Object | `null` | A moment date object representing the selected date. `null` for no selection
`disabled` | Boolean | `null` | Whether the input is disabled
`text` | String | `null` | The date in human-readable format. Displayed in the text input
`error` | String | `null` | A string error message, shown to the user
`onUpdate` | Function | `null` | (required) A function called upon selection of a date or input change
`dateFormat` | String | `null` | (required) The date format in moment.js-style tokens
`isInvalidDate` | Function | `null` | A function to determine if a day on the calendar is not valid


DateRange
===

This is wrapper for a [react-dates](https://github.com/airbnb/react-dates) powered calendar.

## Usage

```jsx
<DateRange
	after={ after }
	afterText={ afterText }
	before={ before }
	beforeText={ beforeText }
	onUpdate={ ( update ) => setState( update ) }
	shortDateFormat="MM/DD/YYYY"
	focusedInput="startDate"
	isInvalidDate={ date => (
		// not a future date
		moment().isBefore( moment( date ), 'date' )
	) }
/>
```

### Props

Name | Type | Default | Description
--- | --- | --- | ---
`after` | Object | `null` | A moment date object representing the selected start. `null` for no selection
`afterError` | String | `null` | A string error message, shown to the user
`afterText` | String | `null` | The start date in human-readable format. Displayed in the text input
`before` | Object | `null` | A moment date object representing the selected end. `null` for no selection
`beforeError` | String | `null` | A string error message, shown to the user
`beforeText` | String | `null` | The end date in human-readable format. Displayed in the text input
`focusedInput` | String | `null` | String identifying which is the currently focused input (start or end)
`isInvalidDate` | Function | `null` | A function to determine if a day on the calendar is not valid
`onUpdate` | Function | `null` | (required) A function called upon selection of a date
`shortDateFormat` | String | `null` | (required) The date format in moment.js-style tokens
