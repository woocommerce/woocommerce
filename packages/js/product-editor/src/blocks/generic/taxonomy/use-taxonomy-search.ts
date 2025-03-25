/**
 * External dependencies
 */
import { useState } from '@wordpress/element';
import { resolveSelect } from '@wordpress/data';
import { escapeHTML } from '@woocommerce/components';
/**
 * Internal dependencies
 */
import type { Taxonomy } from '../../../types';

async function getTaxonomiesMissingParents(
	taxonomies: Taxonomy[],
	taxonomyName: string
): Promise< Taxonomy[] > {
	// Retrieve the missing parent objects incase not all of them were included.
	const missingParentIds: number[] = [];

	const taxonomiesLookup: Record< number, Taxonomy > = {};
	taxonomies.forEach( ( taxonomy ) => {
		taxonomiesLookup[ taxonomy.id ] = taxonomy;
	} );
	taxonomies.forEach( ( taxonomy ) => {
		if ( taxonomy.parent > 0 && ! taxonomiesLookup[ taxonomy.parent ] ) {
			missingParentIds.push( taxonomy.parent );
		}
	} );
	if ( missingParentIds.length > 0 ) {
		return (
			resolveSelect( 'core' )
				.getEntityRecords( 'taxonomy', taxonomyName, {
					include: missingParentIds,
				} )
				// @ts-expect-error TODO react-18-upgrade: getEntityRecords type is not correctly typed yet
				.then( ( parentTaxonomies: Taxonomy[] ) => {
					return getTaxonomiesMissingParents(
						[ ...parentTaxonomies, ...taxonomies ],
						taxonomyName
					);
				} )
		);
	}
	return taxonomies;
}

const PAGINATION_SIZE = 30;

interface UseTaxonomySearchOptions {
	fetchParents?: boolean;
}

const useTaxonomySearch = (
	taxonomyName: string,
	options: UseTaxonomySearchOptions = { fetchParents: true }
): {
	searchEntity: ( search: string ) => Promise< Taxonomy[] >;
	isResolving: boolean;
} => {
	const [ isSearching, setIsSearching ] = useState( false );
	async function searchEntity( search: string ): Promise< Taxonomy[] > {
		setIsSearching( true );
		let taxonomies: Taxonomy[] = [];
		try {
			// @ts-expect-error TODO react-18-upgrade: getEntityRecords type is not correctly typed yet
			taxonomies = await resolveSelect( 'core' ).getEntityRecords(
				'taxonomy',
				taxonomyName,
				{
					per_page: PAGINATION_SIZE,
					search: escapeHTML( search ),
				}
			);
			if ( options?.fetchParents ) {
				taxonomies = await getTaxonomiesMissingParents(
					taxonomies,
					taxonomyName
				);
			}
		} finally {
			setIsSearching( false );
		}
		return taxonomies;
	}

	return {
		searchEntity,
		isResolving: isSearching,
	};
};

export default useTaxonomySearch;
