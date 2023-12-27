/**
 * External dependencies
 */
import {
	type BlockVariation,
	registerBlockVariation,
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

		registerBlockVariation( blockJson.name, {
			isActive,
			...collection,
		} );
	} );
};

export const getCollectionByName = ( collectionName?: CollectionName ) => {
	if ( ! collectionName ) {
		return null;
	}

	return collections.find( ( { name } ) => name === collectionName );
};

export default registerCollections;
