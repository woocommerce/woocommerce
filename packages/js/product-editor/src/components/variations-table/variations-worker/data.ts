/**
 * Internal dependencies
 */
import { ProductVariation } from '@woocommerce/data';
import { apiFetch } from './api-fetch';
import {
	BatchUpdateRequest,
	BatchUpdateResponse,
	GetVariationsPageRequest,
	GetVariationsPageResponse,
} from './types';
import { sendOutcomeMessage } from './utils';

export async function getCurrentVariationsPage( {
	product_id,
	...params
}: GetVariationsPageRequest ): Promise< GetVariationsPageResponse > {
	let endpoint = `/wp-json/wc/v3/products/${ product_id }/variations`;

	const response = await apiFetch( endpoint, {
		params,
	} );

	const total = response.headers.get( 'X-Wp-Total' );
	const pages = response.headers.get( 'X-Wp-TotalPages' );
	const items = await response.json();

	return {
		items,
		totalCount:
			total && Number.isInteger( Number( total ) )
				? Number.parseInt( total, 10 )
				: items.length,
		totalPages:
			pages && Number.isInteger( Number( pages ) )
				? Number.parseInt( pages, 10 )
				: Math.ceil( ( params.page ?? 10 ) / items.length ),
	};
}

export async function getAllVariations( product_id: number ) {
	const variations: Record< number, ProductVariation > = {};
	let currentPage = 1;
	let fetchedCount = 0;
	let total = 1;

	while ( fetchedCount < total ) {
		const { items: chunk, totalCount } = await getCurrentVariationsPage( {
			product_id,
			page: currentPage++,
			per_page: 50,
			order: 'asc',
			orderby: 'menu_order',
		} );

		fetchedCount += chunk.length;
		total = totalCount;

		chunk.forEach( ( variation ) => {
			variations[ variation.id ] = variation;
		} );
	}

	return variations;
}

export async function batchUpdate( {
	product_id,
	update,
}: BatchUpdateRequest ) {
	let currentPage = 1;
	const offset = 50;

	const result: ProductVariation[] = [];

	while ( ( currentPage - 1 ) * offset < update.length ) {
		const fromIndex = ( currentPage - 1 ) * offset;
		const toIndex = fromIndex + offset;
		const subset = update.slice( fromIndex, toIndex );

		sendOutcomeMessage< 'IS_UPDATING' >( {
			action: 'IS_UPDATING',
			payload: subset.reduce(
				( current, variation ) => ( {
					...current,
					[ variation.id ]: true,
				} ),
				{}
			),
		} );

		let endpoint = `/wp-json/wc/v3/products/${ product_id }/variations/batch`;

		const response = await apiFetch( endpoint, {
			method: 'POST',
			body: JSON.stringify( { create: [], update: subset, delete: [] } ),
		} );

		const items = await response.json();

		currentPage++;

		result.push( ...( items?.update ?? [] ) );
	}

	sendOutcomeMessage< 'BATCH_UPDATE' >( {
		action: 'BATCH_UPDATE',
		payload: { update: result },
	} );
}
