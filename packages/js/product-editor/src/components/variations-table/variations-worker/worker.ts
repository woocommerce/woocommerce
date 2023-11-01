/**
 * Internal dependencies
 */
import {
	batchUpdate,
	getAllVariations,
	getCurrentVariationsPage,
} from './data';
import { Actions, IncomeMessage } from './types';
import { sendOutcomeMessage } from './utils';

self.onmessage = function onMessage< T extends keyof Actions = any >(
	event: MessageEvent< IncomeMessage< T > >
) {
	console.log( 'onMessage', event.data );

	if ( event.data.action === 'GET_PAGE' ) {
		const { action, payload: request } =
			event.data as IncomeMessage< 'GET_PAGE' >;
		return getCurrentVariationsPage( request ).then( ( response ) => {
			sendOutcomeMessage< 'GET_PAGE' >( {
				action,
				payload: response,
			} );
		} );
	}

	if ( event.data.action === 'GET_ALL' ) {
		const { action, payload: request } =
			event.data as IncomeMessage< 'GET_ALL' >;
		return getAllVariations( request ).then( ( response ) => {
			sendOutcomeMessage< 'GET_ALL' >( {
				action,
				payload: response,
			} );
		} );
	}

	if ( event.data.action === 'BATCH_UPDATE' ) {
		const { payload: request } =
			event.data as IncomeMessage< 'BATCH_UPDATE' >;
		return batchUpdate( request );
	}
};
