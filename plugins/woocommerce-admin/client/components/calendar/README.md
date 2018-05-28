Calendar
============

This is wrapper for a [react-dates](https://github.com/airbnb/react-dates) powered calendar.

## How to use:

```jsx
import { DateRange } from 'components/calendar';

render: function() {
  return (
   <DateRangePicker
		start={ moment( 2018-01-01 ) }
		end={ moment( 2020-01-01 ) }
		onSelect={ this.onSelect }
		inValidDays="past"
	/>
  );
}
```

## Props

* `start`: A moment date object representing the selected start. `null` for no selection
* `end`: A moment date object representing the selected end. `null` for no selection
* `onSelect`: A function called upon selection of a date
* `inValidDays`: Optionally invalidate certain days. `past`, `future`, `none`, or function are accepted. A function will be passed to react-dates' `isOutsideRange` prop
