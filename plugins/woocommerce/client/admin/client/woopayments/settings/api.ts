/**
 * External dependencies
 */
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */
import type { WooPaymentsAccountResponse } from './types';

export const WOOPAYMENTS_ACCOUNT_SETTINGS_PATH =
	'/wc-admin/settings/payments/woopayments/account';

export const getWooPaymentsAccountSettings =
	async (): Promise< WooPaymentsAccountResponse > =>
		apiFetch< WooPaymentsAccountResponse >( {
			path: WOOPAYMENTS_ACCOUNT_SETTINGS_PATH,
			method: 'GET',
		} );
