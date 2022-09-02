DateTimePickerControl
===

Add a component to allow selecting of a date and time via a calendar selection or by manual input.

## Usage

```jsx
<DateTimePickerControl
	onChange={ ( date ) => console.log( date ) }
	dateFormat="DD-MM-YYYY H:MM"
/>
```

### Props

Name | Type | Default | Description
--- | --- | --- | ---
`currentDate` | String | `null` | A date in ISO format to be used as the initially set date
`dateTimeFormat` | String | `MM/DD/YYYY h:mm a` | The format used for the datetime
`disabled` | Boolean | `null` | Whether the input is disabled
`is12Hour` | Boolean | `true` | Whether the date time picker should show a 12 or 24 hour format
`onChange` | Function | `undefined` | (required) A function called upon selection of a date or input change
