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
import bestSellers from './best-sellers';
import crossSells from './cross-sells';
import featured from './featured';
import handPicked from './hand-picked';
import newArrivals from './new-arrivals';
import onSale from './on-sale';
import productCollection from './product-collection';
import related from './related';
import topRated from './top-rated';
import upsells from './upsells';
import byCategory from './by-category';
import byTag from './by-tag';

// Order in here is reflected in the Collection Chooser in Editor.
const collections: BlockVariation[] = [
	productCollection,
	featured,
	newArrivals,
	onSale,
	bestSellers,
	topRated,
	handPicked,
	byCategory,
	byTag,
	related,
	upsells,
	crossSells,
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
