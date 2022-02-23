/**
 * External dependencies
 */
import moment from 'moment';
import { DatePicker, H, Section } from '@woocommerce/components';
import { useState } from '@wordpress/element';
const dateFormat = 'MM/DD/YYYY';

const DatePickerExample = () => {
	const [ state, setState ] = useState( {
		after: null,
		afterText: '',
		before: null,
		beforeText: '',
		afterError: null,
		beforeError: null,
		focusedInput: 'startDate',
	} );
	const { after, afterText, afterError } = state;

	function onDatePickerUpdate( { date, text, error } ) {
		setState( {
			...state,
			after: date,
			afterText: text,
			afterError: error,
		} );
	}

	return (
		<div>
			<H>Date Picker</H>
			<Section component={ false }>
				<DatePicker
					date={ after }
					text={ afterText }
					error={ afterError }
					onUpdate={ onDatePickerUpdate }
					dateFormat={ dateFormat }
					isInvalidDate={ ( date ) => moment( date ).day() === 1 }
				/>
			</Section>
		</div>
	);
};

export const Basic = () => <DatePickerExample />;

export default {
	title: 'WooCommerce Admin/components/calendar/DatePicker',
	component: DatePicker,
};
