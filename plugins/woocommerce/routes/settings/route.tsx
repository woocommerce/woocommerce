/**
 * External dependencies
 */
import { store as coreStore } from '@wordpress/core-data';
import { dispatch, resolveSelect, select } from '@wordpress/data';
import { notFound } from '@wordpress/route';

/**
 * Internal dependencies
 */
import { getScreen } from './screen';

type RouteOptions = { params: { page?: string } };

export const route = {
	beforeLoad: async ( { params }: RouteOptions ) => {
		const screen = getScreen( params.page );
		if ( ! screen ) {
			throw notFound();
		}
		const { kind, name } = screen.entity;
		if ( ! select( coreStore ).getEntityConfig( kind, name ) ) {
			await dispatch( coreStore ).addEntities( [
				{ ...screen.entity, key: false },
			] );
		}
	},
	loader: async ( { params }: RouteOptions ) => {
		const screen = getScreen( params.page );
		if ( ! screen ) {
			return;
		}
		const { kind, name } = screen.entity;
		// The stage shows load errors, so a failed request must not reject the route.
		await resolveSelect( coreStore )
			.getEntityRecord( kind, name, undefined )
			.catch( () => undefined );
	},
};
