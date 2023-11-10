/**
 * External dependencies
 */
import type { BlockInstance } from '@wordpress/blocks';
import type { ProductCollectionQuery } from '@woocommerce/blocks/product-collection/types';

function getInnerBlocksParams( block: BlockInstance, initial = {} ) {
	return block.innerBlocks.reduce(
		( acc, innerBlock ): Record< string, unknown > => {
			acc = { ...acc, ...innerBlock.attributes?.queryParam };
			return getInnerBlocksParams( innerBlock, acc );
		},
		initial
	);
}

export function getQueryParams( block: BlockInstance | null ) {
	if ( ! block ) return {};

	return getInnerBlocksParams( block );
}

export const sharedParams: Array< keyof ProductCollectionQuery > = [
	'exclude',
	'offset',
	'search',
];

/**
 * There is an open dicussion around the shape of this object. Check it out on GH.
 *
 * @see {@link https://github.com/woocommerce/woocommerce-blocks/pull/11218#discussion_r1365171167 | #11218 review comment}.
 */
export const mappedParams: {
	key: keyof ProductCollectionQuery;
	map: string;
}[] = [
	{ key: 'woocommerceStockStatus', map: 'stock_status' },
	{ key: 'woocommerceOnSale', map: 'on_sale' },
	{ key: 'woocommerceHandPickedProducts', map: 'include' },
];

function mapTaxonomy( taxonomy: string ) {
	const map = {
		product_tag: 'tag',
		product_cat: 'cat',
	};

	return map[ taxonomy as keyof typeof map ] || `_unstable_tax_${ taxonomy }`;
}

function getTaxQueryMap( taxQuery: ProductCollectionQuery[ 'taxQuery' ] ) {
	return Object.entries( taxQuery ).map( ( [ taxonomy, terms ] ) => ( {
		[ mapTaxonomy( taxonomy ) ]: terms,
	} ) );
}

function getAttributeQuery(
	woocommerceAttributes: ProductCollectionQuery[ 'woocommerceAttributes' ]
) {
	if ( ! woocommerceAttributes ) {
		return {};
	}
	return woocommerceAttributes.map( ( attribute ) => ( {
		attribute: attribute.taxonomy,
		term_id: attribute.termId,
	} ) );
}

export function formatQuery( query: ProductCollectionQuery ) {
	if ( ! query ) {
		return {};
	}

	return Object.assign(
		{
			attributes: getAttributeQuery( query.woocommerceAttributes ),
			catalog_visibility: 'visible',
		},
		...sharedParams.map(
			( key ) => key in query && { [ key ]: query[ key ] }
		),
		...mappedParams.map(
			( param ) =>
				param.key in query && { [ param.map ]: query[ param.key ] }
		),
		...getTaxQueryMap( query.taxQuery )
	);
}
