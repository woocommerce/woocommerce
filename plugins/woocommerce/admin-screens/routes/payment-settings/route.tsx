/**
 * External dependencies
 */
import { store as coreStore } from '@wordpress/core-data';
import { dispatch, resolveSelect, select } from '@wordpress/data';
import { notFound } from '@wordpress/route';

/**
 * Internal dependencies
 */
import { getScreen, NO_KEY } from './screen';

interface RouteOptions {
	params: { page?: string };
}

export const route = {
	beforeLoad: async ( { params }: RouteOptions ) => {
		const screen = getScreen( params.page );
		if ( ! screen ) {
			// eslint-disable-next-line @typescript-eslint/only-throw-error -- The router expects notFound() to be thrown.
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
		// resolveSelect() is untyped in this version of @wordpress/data.
		const resolver = (
			resolveSelect as unknown as ( store: typeof coreStore ) => {
				getEntityRecord: (
					kind: string,
					name: string,
					key: string
				) => Promise< unknown >;
			}
		 )( coreStore );
		await resolver
			.getEntityRecord( kind, name, NO_KEY )
			.catch( () => undefined );
	},
};
