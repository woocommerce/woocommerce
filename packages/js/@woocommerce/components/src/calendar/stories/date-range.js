/**
 * External dependencies
 */
import moment from 'moment';
import { DateRange, H, Section } from '@woocommerce/components';
import { useState } from '@wordpress/element';

const dateFormat = 'MM/DD/YYYY';

const DateRangeExample = () => {
	const [ state, setState ] = useState( {
		after: null,
		afterText: '',
		before: null,
		beforeText: '',
		afterError: null,
		beforeError: null,
		focusedInput: 'startDate',
	} );

	const { after, afterText, before, beforeText, focusedInput } = state;

	function onRangeUpdate( update ) {
		setState( {
			...state,
			...update,
		} );
	}

	return (
		<>
			<H>Date range picker</H>
			<Section component={ false }>
				<DateRange
					after={ after }
					afterText={ afterText }
					before={ before }
					beforeText={ beforeText }
					onUpdate={ onRangeUpdate }
					shortDateFormat={ dateFormat }
					focusedInput={ focusedInput }
					isInvalidDate={ ( date ) =>
						moment().isBefore( moment( date ), 'date' )
					}
				/>
			</Section>
		</>
	);
};

export const Basic = () => <DateRangeExample />;

export default {
	title: 'WooCommerce Admin/components/calendar/DateRange',
	component: DateRange,
};
