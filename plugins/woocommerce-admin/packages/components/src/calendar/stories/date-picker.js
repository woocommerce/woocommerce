/**
 * External dependencies
 */
import moment from 'moment';
import { withState } from '@wordpress/compose';
import { DatePicker, H, Section } from '@woocommerce/components';
import { createElement } from '@wordpress/element';

const dateFormat = 'MM/DD/YYYY';

const DatePickerExample = withState( {
	after: null,
	afterText: '',
	before: null,
	beforeText: '',
	afterError: null,
	beforeError: null,
	focusedInput: 'startDate',
} )( ( { after, afterText, afterError, setState } ) => {
	function onDatePickerUpdate( { date, text, error } ) {
		setState( {
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
} );

export const Basic = () => <DatePickerExample />;

export default {
	title: 'WooCommerce Admin/components/calendar/DatePicker',
	component: DatePicker,
};
