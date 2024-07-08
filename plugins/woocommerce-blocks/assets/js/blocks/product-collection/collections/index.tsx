/**
 * External dependencies
 */
import { select } from '@wordpress/data';
import { __experimentalRegisterProductCollection as registerProductCollection } from '@woocommerce/blocks-registry';
import {
	// @ts-expect-error Type definition is missing
	store as blocksStore,
	type BlockVariation,
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

const collections: BlockVariation[] = [
	productCollection,
	featured,
	topRated,
	onSale,
	bestSellers,
	newArrivals,
];

export const registerCollections = () => {
	collections.forEach( ( collection ) =>
		registerProductCollection( collection )
	);
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
