/**
 * External dependencies
 */
import { dispatch } from '@wordpress/data';
import { apiFetch } from '@wordpress/data-controls';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { NAMESPACE } from './constants';
import { setPmPromotions } from './actions';
import type { PmPromotion } from '../types';

const isPmPromotionsResponse = ( value: unknown ): value is PmPromotion[] =>
	Array.isArray( value );

export function* getPmPromotions(): Generator< unknown, void, unknown > {
	try {
		const response = yield apiFetch( {
			path: `${ NAMESPACE }/pm-promotions`,
			method: 'GET',
		} );

		if ( ! isPmPromotionsResponse( response ) ) {
			yield dispatch( 'core/notices' ).createErrorNotice(
				__(
					'Error retrieving payment method promotions.',
					'woocommerce'
				)
			);
			return;
		}

		yield setPmPromotions( response );
	} catch ( e ) {
		yield dispatch( 'core/notices' ).createErrorNotice(
			__( 'Error retrieving payment method promotions.', 'woocommerce' )
		);
	}
}
