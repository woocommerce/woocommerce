/**
 * External dependencies
 */
import { store as coreStore } from '@wordpress/core-data';
import { dispatch, resolveSelect } from '@wordpress/data';
import { notFound } from '@wordpress/route';

/**
 * Internal dependencies
 */
import {
	ENTITY_KIND,
	SUPPORT_ENTITIES,
	getOfflineEntities,
	getOfflineGateway,
} from './entities';

export const route = {
	beforeLoad: async ( { params }: { params: { page?: string } } ) => {
		if ( ! getOfflineGateway( params.page ) ) {
			throw notFound();
		}
		await dispatch( coreStore ).addEntities( [
			...getOfflineEntities(),
			...SUPPORT_ENTITIES,
		] );
	},
	loader: async ( { params }: { params: { page?: string } } ) => {
		const gateway = getOfflineGateway( params.page );
		if ( ! gateway ) {
			throw notFound();
		}
		// The pinned data package does not type resolveSelect().
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
			.getEntityRecord( ENTITY_KIND, gateway.id, gateway.id )
			.catch( () => undefined );
	},
};
