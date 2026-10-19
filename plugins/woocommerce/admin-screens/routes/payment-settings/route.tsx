/**
 * External dependencies
 */
import { store as coreStore } from '@wordpress/core-data';
import { dispatch, resolveSelect, select } from '@wordpress/data';
import { notFound } from '@wordpress/route';

/**
 * Internal dependencies
 */
import { ENTITY_KIND, getCoreEntities } from './entities';

export const route = {
	beforeLoad: async ( { params }: { params: { page?: string } } ) => {
		await dispatch( coreStore ).addEntities( getCoreEntities() );
		if (
			! params.page ||
			! select( coreStore ).getEntityConfig( ENTITY_KIND, params.page )
		) {
			throw notFound();
		}
	},
	loader: async ( { params }: { params: { page?: string } } ) => {
		const { page } = params;
		if ( ! page ) {
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
			.getEntityRecord( ENTITY_KIND, page, page )
			.catch( () => undefined );
	},
};
