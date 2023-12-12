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
import { CollectionName, FilterName } from '../types';
import blockJson from '../block.json';
import productCatalog from './product-catalog';
import newArrivals from './new-arrivals';
import topRated from './top-rated';
import bestSellers from './best-sellers';
import onSale from './on-sale';
import featured from './featured';

export const collections = {
	productCatalog,
	newArrivals,
	topRated,
	bestSellers,
	onSale,
	featured,
};

const collectionsToRegister: BlockVariation[] = [
	featured,
	topRated,
	onSale,
	bestSellers,
	newArrivals,
];

export const registerCollections = () => {
	collectionsToRegister.forEach( ( collection ) => {
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

export const getCollectionByName = ( collectionName: CollectionName ) => {
	return Object.values( collections ).find(
		( { name } ) => name === collectionName
	);
};

export const getUnchangeableFilters = (
	collectionName?: CollectionName
): FilterName[] => {
	if ( ! collectionName ) {
		return [];
	}

	const collection = getCollectionByName( collectionName );
	return collection ? collection.unchangeableFilters : [];
};

export default registerCollections;
