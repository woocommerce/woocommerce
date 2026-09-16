/**
 * External dependencies
 */
import {
	getBlockVariations,
	registerBlockVariation,
	unregisterBlockVariation,
} from '@wordpress/blocks';
import { store as coreStore } from '@wordpress/core-data';
import { select } from '@wordpress/data';
import { removeFilter } from '@wordpress/hooks';

/**
 * Internal dependencies
 */
import {
	registerCollections,
	registerEmailCollections,
} from '../../collections';
import { PRODUCT_COLLECTION_BLOCK_NAME } from '../../constants';

type Taxonomy = {
	name: string;
	slug: string;
	visibility: { publicly_queryable: boolean };
};

const getCollectionNames = () =>
	( getBlockVariations( PRODUCT_COLLECTION_BLOCK_NAME ) ?? [] ).map(
		( variation ) => variation.name
	);

/**
 * Registers the chooser and email collections the way the editor does.
 *
 * Returns a function that unregisters the collections it added and restores `window.wp`.
 */
export const registerCoreCollections = (): ( () => void ) => {
	const originalWpDescriptor = Object.getOwnPropertyDescriptor(
		window,
		'wp'
	);
	const existingNames = new Set( getCollectionNames() );

	// registerProductCollection() calls the global wp.blocks.registerBlockVariation.
	Object.defineProperty( window, 'wp', {
		configurable: true,
		writable: true,
		value: {
			...window.wp,
			blocks: {
				...window.wp?.blocks,
				registerBlockVariation,
			},
		},
	} );

	registerCollections();
	registerEmailCollections();

	const registeredNames = getCollectionNames().filter(
		( name ) => ! existingNames.has( name )
	);

	return () => {
		for ( const name of registeredNames ) {
			unregisterBlockVariation( PRODUCT_COLLECTION_BLOCK_NAME, name );
			removeFilter( 'editor.BlockEdit', name );
		}

		if ( originalWpDescriptor ) {
			Object.defineProperty( window, 'wp', originalWpDescriptor );
		} else {
			Reflect.deleteProperty( window, 'wp' );
		}
	};
};

/**
 * Makes the core data store report the product category, tag and brand taxonomies.
 */
export const mockProductTaxonomies = () =>
	jest
		.spyOn(
			select( coreStore ) as unknown as {
				getTaxonomies: () => Taxonomy[];
			},
			'getTaxonomies'
		)
		.mockReturnValue( [
			{
				name: 'product categories',
				slug: 'product_cat',
				visibility: { publicly_queryable: true },
			},
			{
				name: 'product tags',
				slug: 'product_tag',
				visibility: { publicly_queryable: true },
			},
			{
				name: 'product brands',
				slug: 'product_brand',
				visibility: { publicly_queryable: true },
			},
		] );
