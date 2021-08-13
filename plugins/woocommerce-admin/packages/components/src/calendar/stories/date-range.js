/**
 * External dependencies
 */
import moment from 'moment';
import { withState } from '@wordpress/compose';
import { DateRange, H, Section } from '@woocommerce/components';
import { createElement, Fragment } from '@wordpress/element';

const dateFormat = 'MM/DD/YYYY';

const DateRangeExample = withState( {
	after: null,
	afterText: '',
	before: null,
	beforeText: '',
	afterError: null,
	beforeError: null,
	focusedInput: 'startDate',
} )( ( { after, afterText, before, beforeText, focusedInput, setState } ) => {
	function onRangeUpdate( update ) {
		setState( update );
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
} );

export const Basic = () => <DateRangeExample />;

export default {
	title: 'WooCommerce Admin/components/calendar/DateRange',
	component: DateRange,
};
