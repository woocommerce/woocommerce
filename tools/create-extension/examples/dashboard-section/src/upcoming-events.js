/**
 * External dependencies
 */
import { flatten } from 'lodash';

/**
 * WooCommerce dependencies
 */
import { TableCard } from '@woocommerce/components';

export const UpcomingEvents = ( { config } ) => {
	const rows = flatten(
		config.map( ( apple ) => {
			return apple.events.map( ( event ) => {
				return [
					{ display: apple.title, value: 'variety' },
					{ display: event.title, value: 'event' },
					{ display: event.date, value: 'date' },
				];
			} );
		} )
	);
	return (
		<TableCard
			title={ 'Upcoming Events' }
			headers={ [
				{ label: 'Variety', key: 'variety' },
				{ label: 'Event', key: 'event' },
				{ label: 'Date', key: 'date' },
			] }
			rows={ rows }
			rowsPerPage={ 100 }
			totalRows={ 1 }
		/>
	);
};
