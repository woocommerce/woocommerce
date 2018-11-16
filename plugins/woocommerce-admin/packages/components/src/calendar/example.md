```jsx
import { DateRange } from '@woocommerce/components';

const dateFormat = 'MM/DD/YYYY';

const MyDateRange =  withState( {
	after: wp.date.moment( '2018-09-10' ),
	afterText: '09/10/2018',
	before: wp.date.moment( '2018-09-20' ),
	beforeText: '09/20/2018',
} )( ( { after, afterText, before, beforeText, setState } ) => {
	function onUpdate( { after, afterText, before, beforeText } ) {
		setState( { after, afterText, before, beforeText } );
	}

	return (
		<DateRange
			after={ after }
			afterText={ afterText }
			before={ before }
			beforeText={ beforeText }
			onUpdate={ onUpdate }
			shortDateFormat={ dateFormat }
			focusedInput="startDate"
			invalidDays="none"
		/>
	)
} );
```
