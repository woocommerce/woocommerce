/**
 * External dependencies
 */
import type { TableRow } from '@woocommerce/components/build-types/table/types';
/**
 * Internal dependencies
 */
import { Subscription, MySubscriptionsTable } from '../types';
import {
	actions,
	autoUpdates,
	subscriptionStatus,
	expiry,
	nameAndStatus,
	version,
} from './rows/functions';

export function subscriptionRow(
	item: Subscription,
	table: MySubscriptionsTable
): TableRow[] {
	const row = [
		nameAndStatus( item ),
		expiry( item ),
		subscriptionStatus( item, table ),
		version( item, table ),
	];

	// Rows in "Available to use" are not the copy running on this store, so their auto-update
	// state says nothing useful.
	if ( table === 'installed' ) {
		row.push( autoUpdates( item ) );
	}

	row.push( actions( item ) );

	return row;
}
