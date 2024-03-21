/**
 * External dependencies
 */
import { select } from '@wordpress/data';
import {
	// @ts-expect-error Type definition is missing
	store as blocksStore,
	type BlockVariation,
	BlockAttributes,
} from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import { CollectionName } from '../types';
import blockJson from '../block.json';
import productCollection from './product-collection';
import newArrivals from './new-arrivals';
import topRated from './top-rated';
import bestSellers from './best-sellers';
import onSale from './on-sale';
import featured from './featured';
import registerProductCollection from './register-product-collection';

const collections: BlockVariation[] = [
	productCollection,
	featured,
	topRated,
	onSale,
	bestSellers,
	newArrivals,
];

export const registerCollections = () => {
	collections.forEach( ( collection ) => {
		const isActive = (
			blockAttrs: BlockAttributes,
			variationAttributes: BlockAttributes
		) => {
			return blockAttrs.collection === variationAttributes.collection;
		};

		registerProductCollection( {
			isActive,
			...collection,
		} );
	} );
};

export const getCollectionByName = ( collectionName?: CollectionName ) => {
	if ( ! collectionName ) {
		return null;
	}

	// @ts-expect-error Type definitions are missing
	// https://github.com/DefinitelyTyped/DefinitelyTyped/blob/master/types/wordpress__blocks/store/selectors.d.ts
	const variations = select( blocksStore ).getBlockVariations(
		blockJson.name
	);

	// @ts-expect-error Type definitions are missing
	// https://github.com/DefinitelyTyped/DefinitelyTyped/blob/master/types/wordpress__blocks/store/selectors.d.ts
	return variations.find( ( { name } ) => name === collectionName );
};

export default registerCollections;
