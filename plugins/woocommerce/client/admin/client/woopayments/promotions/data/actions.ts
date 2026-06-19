/**
 * External dependencies
 */
import { dispatch } from '@wordpress/data';
import { apiFetch } from '@wordpress/data-controls';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import ACTION_TYPES from './action-types';
import { NAMESPACE } from './constants';
import { STORE_NAME } from './store-name';
import type { PmPromotion } from '../types';

type PmPromotionActionResponse = {
	success?: boolean;
};

export function setPmPromotions( promotions: PmPromotion[] ) {
	return {
		type: ACTION_TYPES.SET_PM_PROMOTIONS,
		promotions,
	};
}

export function* activatePmPromotion(
	id: string
): Generator< unknown, boolean, PmPromotionActionResponse > {
	try {
		const response = yield apiFetch( {
			path: `${ NAMESPACE }/pm-promotions/${ encodeURIComponent(
				id
			) }/activate`,
			method: 'POST',
		} );

		dispatch( STORE_NAME ).invalidateResolution( 'getPmPromotions', [] );
		dispatch( 'core/notices' ).createSuccessNotice(
			__( 'Promotion activated successfully.', 'woocommerce' )
		);

		return response.success !== false;
	} catch ( e ) {
		yield dispatch( 'core/notices' ).createErrorNotice(
			__( 'Error activating payment method promotion.', 'woocommerce' )
		);
		return false;
	}
}

export function* dismissPmPromotion(
	id: string
): Generator< unknown, boolean, PmPromotionActionResponse > {
	try {
		const response = yield apiFetch( {
			path: `${ NAMESPACE }/pm-promotions/${ encodeURIComponent(
				id
			) }/dismiss`,
			method: 'POST',
		} );

		dispatch( STORE_NAME ).invalidateResolution( 'getPmPromotions', [] );
		dispatch( 'core/notices' ).createSuccessNotice(
			__( 'Promotion dismissed.', 'woocommerce' )
		);

		return response.success !== false;
	} catch ( e ) {
		yield dispatch( 'core/notices' ).createErrorNotice(
			__( 'Error dismissing payment method promotion.', 'woocommerce' )
		);
		return false;
	}
}
