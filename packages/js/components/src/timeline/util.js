/**
 * External dependencies
 */
import moment from 'moment';
import { date as formatSiteDate } from '@wordpress/date';

const orderByOptions = {
	ASC: 'asc',
	DESC: 'desc',
};

const groupByOptions = {
	DAY: 'day',
	WEEK: 'week',
	MONTH: 'month',
};

const sortAscending = ( groupA, groupB ) =>
	groupA.date.getTime() - groupB.date.getTime();
const sortDescending = ( groupA, groupB ) =>
	groupB.date.getTime() - groupA.date.getTime();

const sortByDateUsing = ( orderBy ) => {
	switch ( orderBy ) {
		case orderByOptions.ASC:
			return sortAscending;
		case orderByOptions.DESC:
		default:
			return sortDescending;
	}
};

const siteGroupDateFormats = {
	[ groupByOptions.DAY ]: 'Y-m-d',
	[ groupByOptions.WEEK ]: 'o-W',
	[ groupByOptions.MONTH ]: 'Y-m',
};

const getSiteGroupKey = ( date, groupBy ) =>
	formatSiteDate(
		siteGroupDateFormats[ groupBy ] ||
			siteGroupDateFormats[ groupByOptions.DAY ],
		date
	);

const groupItemsUsing =
	( groupBy, timezone = 'browser' ) =>
	( groups, newItem ) => {
		// Helper functions defined to make the logic a bit more readable.
		const hasSameGroupKey = ( group, item ) => {
			if ( timezone === 'site' ) {
				return (
					getSiteGroupKey( group.date, groupBy ) ===
					getSiteGroupKey( item.date, groupBy )
				);
			}

			return moment( group.date ).isSame( moment( item.date ), groupBy );
		};
		const groupIndexExists = ( index ) => index >= 0;
		const groupForItem = groups.findIndex( ( group ) =>
			hasSameGroupKey( group, newItem )
		);

		if ( ! groupIndexExists( groupForItem ) ) {
			// Create new group for newItem.
			return [
				...groups,
				{
					date: newItem.date,
					items: [ newItem ],
				},
			];
		}

		groups[ groupForItem ].items.push( newItem );
		return groups;
	};

export { groupByOptions, groupItemsUsing, orderByOptions, sortByDateUsing };
