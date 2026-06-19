/**
 * External dependencies
 */
import { useEffect } from '@wordpress/element';
import { useLocation } from 'react-router-dom';

/**
 * Internal dependencies
 */
import { getWooPaymentsDispute } from './data';
import { getTransactionDetailsRoute } from './utils';
import { getSettingsPaymentsProviderRouteUrl } from '../utils';

export const WooPaymentsDisputeDetailsRedirect = () => {
	const location = useLocation();

	useEffect( () => {
		let isMounted = true;
		const params = new URLSearchParams( location.search );
		const id = params.get( 'id' ) || params.get( 'charge_id' ) || '';
		const chargeId = params.get( 'charge_id' ) || '';

		const redirectTo = ( route: string ) => {
			if ( isMounted ) {
				window.location.assign(
					getSettingsPaymentsProviderRouteUrl( route )
				);
			}
		};

		if ( id && ! id.startsWith( 'ch_' ) && ! id.startsWith( 'py_' ) ) {
			getWooPaymentsDispute( id )
				.then( ( dispute ) => {
					redirectTo( getTransactionDetailsRoute( dispute ) );
				} )
				.catch( () => {
					redirectTo( getTransactionDetailsRoute( { id } ) );
				} );

			return () => {
				isMounted = false;
			};
		}

		redirectTo(
			getTransactionDetailsRoute( { charge_id: chargeId || id } )
		);

		return () => {
			isMounted = false;
		};
	}, [ location.search ] );

	return null;
};
