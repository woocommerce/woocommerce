/**
 * External dependencies
 */
import { TableRow } from '@woocommerce/components/build-types/table/types';
/**
 * Internal dependencies
 */
import { Subscription } from '../types';
import {
	actions,
	subscriptionStatus,
	expiry,
	nameAndStatus,
	version,
} from './rows/functions';

export function availableSubscriptionRow( item: Subscription ): TableRow[] {
	return [
		nameAndStatus( item ),
		expiry( item ),
		subscriptionStatus( item ),
		version( item ),
		actions( item ),
	];
}

export function installedSubscriptionRow( item: Subscription ): TableRow[] {
	return [
		nameAndStatus( item ),
		expiry( item ),
		subscriptionStatus( item ),
		version( item ),
		actions( item ),
	];
}
