/**
 * External dependencies
 */
import { TableRow } from '@woocommerce/components/build-types/table/types';
/**
 * Internal dependencies
 */
import { Subscription } from '../types';
import {
	productName,
	status,
	expiry,
	autoRenew,
	version,
	install,
	actions,
} from './rows/functions';

export function availableSubscriptionRow( item: Subscription ): TableRow[] {
	return [
		productName( item ),
		status( item ),
		expiry( item ),
		autoRenew( item ),
		version( item ),
		install( item ),
		actions(),
	];
}

export function installedSubscriptionRow( item: Subscription ): TableRow[] {
	return [
		productName( item ),
		status( item ),
		expiry( item ),
		autoRenew( item ),
		version( item ),
		install( item ),
		actions(),
	];
}
