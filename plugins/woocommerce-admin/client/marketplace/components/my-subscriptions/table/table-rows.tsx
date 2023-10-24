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
	autoRenew,
	expiry,
	productName,
	status,
	version,
} from './rows/functions';

export function availableSubscriptionRow( item: Subscription ): TableRow[] {
	return [
		productName( item ),
		status( item ),
		expiry( item ),
		autoRenew( item ),
		version( item ),
		actions( item ),
	];
}

export function installedSubscriptionRow( item: Subscription ): TableRow[] {
	return [
		productName( item ),
		status( item ),
		expiry( item ),
		autoRenew( item ),
		version( item ),
		actions( item ),
	];
}
