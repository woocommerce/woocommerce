/**
 * External dependencies
 */
import { useEffect } from '@wordpress/element';
import { useLocation } from 'react-router-dom';

/**
 * Internal dependencies
 */
import { getSettingsPaymentsProviderRouteUrl } from '../overview/utils';

export const WooPaymentsDisputeDetailsRedirect = () => {
	const location = useLocation();

	useEffect( () => {
		const params = new URLSearchParams( location.search );
		const id = params.get( 'id' ) || params.get( 'charge_id' ) || '';

		window.location.assign(
			getSettingsPaymentsProviderRouteUrl(
				`/woopayments/transactions/details${
					id ? `?id=${ encodeURIComponent( id ) }` : ''
				}`
			)
		);
	}, [ location.search ] );

	return null;
};
