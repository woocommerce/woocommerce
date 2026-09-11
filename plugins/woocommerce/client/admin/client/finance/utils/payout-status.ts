/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import type { PayoutStatus } from '../types';

export type PayoutStatusVariant = 'success' | 'warning' | 'error' | 'info';

export const PAYOUT_STATUSES: PayoutStatus[] = [
	'pending',
	'complete',
	'failed',
];

export const getPayoutStatusLabel = ( status: string ): string => {
	switch ( status ) {
		case 'pending':
			return __( 'Pending', 'woocommerce' );
		case 'complete':
			return __( 'Completed', 'woocommerce' );
		case 'failed':
			return __( 'Failed', 'woocommerce' );
		default:
			return status;
	}
};

export const getPayoutStatusVariant = (
	status: string
): PayoutStatusVariant => {
	switch ( status ) {
		case 'pending':
			return 'warning';
		case 'complete':
			return 'success';
		case 'failed':
			return 'error';
		default:
			return 'info';
	}
};
