/**
 * External dependencies
 */
import { TableRow } from '@woocommerce/components/build-types/table/types';
/**
 * Internal dependencies
 */
import { Subscription, MySubscriptionsTable } from '../types';
import {
	actions,
	subscriptionStatus,
	expiry,
	nameAndStatus,
	version,
} from './rows/functions';

export function subscriptionRow(
	item: Subscription,
	table: MySubscriptionsTable
): TableRow[] {
	return [
		nameAndStatus( item ),
		expiry( item ),
		subscriptionStatus( item, table ),
		version( item, table ),
		actions( item ),
	];
}
