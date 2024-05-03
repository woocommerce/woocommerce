/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';
import { useState } from '@wordpress/element';
import { useDebounce } from '@wordpress/compose';
import { FormTokenField } from '@wordpress/components';
import { decodeEntities } from '@wordpress/html-entities';
import type { Taxonomy } from '@wordpress/core-data/src/entity-types';

type Term = {
	id: number;
	name: string;
	slug: string;
};

interface TaxonomyItemProps {
	taxonomy: Taxonomy;
	termIds: number[];
	onChange: ( termIds: number[] ) => void;
}

/**
 * The default arguments to use when querying terms.
 */
const DEFAULT_QUERY_ARGS = {
	_fields: 'id,name,slug',
	order: 'asc',
	orderby: 'name',
	context: 'view',
};

/**
 * Given a term this will return a name to display in the FormTokenField. Since the
 * field only allows for string values we need to make sure that the display name
 * has all of the information needed to identify the term object. We do this by
 * encoding the term ID in the name. This also has the added benefit of helping
 * users to identify individual terms when they have the same name.
 *
 * @param {Term} term The term to get the display name for.
 * @return {string} The display name for the term.
 */
const getTermDisplayName = ( term: Term ): string => {
	return `${ term.name } (#${ term.id })`;
};

/**
 * Given a display name created by getTermDisplayName(), this will return the ID
 * that is encoded in the string if one is present.
 *
 * @param {string} displayName The display name of the term.
 * @return {number|null} The ID of the term encoded in the display name.
 */
const getTermIDFromDisplayName = ( displayName: string ): number | false => {
	const matches = displayName.match( /\(#(\d+)\)$/ );
	return matches ? parseInt( matches[ 1 ], 10 ) : false;
};

const TaxonomyItem = ( { taxonomy, termIds, onChange }: TaxonomyItemProps ) => {
	// We need to get the existing terms so that we can store the term object
	// in the map that we used to render the term name in the FormTokenField.
	const { existingTerms, isLoadingExistingTerms } = useSelect(
		( select ) => {
			// There's no need to load any existing terms when there are no terms set.
			if ( ! termIds || ! termIds.length ) {
				return { existingTerms: [], isLoadingExistingTerms: false };
			}

			// @ts-expect-error hasFinishedResolution is untyped.
			const { getEntityRecords, hasFinishedResolution } =
				select( 'core' );

			const selectorArgs: [ string, string, Record< string, unknown > ] =
				[
					'taxonomy',
					taxonomy.slug,
					{
						...DEFAULT_QUERY_ARGS,
						include: termIds,
					},
				];

			return {
				existingTerms: getEntityRecords( ...selectorArgs ) as Term[],
				isLoadingExistingTerms: ! hasFinishedResolution(
					'getEntityRecords',
					selectorArgs
				),
			};
		},
		[ taxonomy, termIds ]
	);

	// A search query will enable us to populate the FormTokenField's suggestion
	// list based on the user supplied search string.
	const [ searchQuery, setSearchQuery ] = useState( '' );
	const { searchTerms } = useSelect(
		( select ) => {
			// The FormTokenField requires at least two characters to start showing
			// the suggestions. Let's not waste a web request since it won't do
			// anything useful.
			if ( searchQuery.length <= 1 ) {
				return { searchTerms: [] };
			}

			const { getEntityRecords } = select( 'core' );
			return {
				searchTerms: getEntityRecords( 'taxonomy', taxonomy.slug, {
					...DEFAULT_QUERY_ARGS,
					exclude: termIds,
					search: searchQuery,
				} ) as Term[],
			};
		},
		[ taxonomy, termIds, searchQuery ]
	);
	const handleSearch = useDebounce( setSearchQuery, 250 );

	// Take care to transform the term objects into
	// display names for the FormTokenField control.
	const existingTermNames = existingTerms
		? existingTerms.map( getTermDisplayName )
		: [];
	const suggestions = searchTerms
		? searchTerms.map( getTermDisplayName )
		: [];

	// Since the FormTokenField has the term ID encoded in the display name
	// we need to pull out the ID in order to update the term IDs.
	const handleChangeTermIDs = ( displayNames: string[] ) => {
		const newTermIds = displayNames
			.map( getTermIDFromDisplayName )
			.filter( Boolean ) as number[];

		onChange( newTermIds );
	};

	// It's possible that a term may have been deleted but still
	// be present in the termIds array. In that case we will
	// display the ID and an indication it was deleted.
	if ( existingTerms && termIds.length !== existingTermNames.length ) {
		// Use a map to make checking for the terms faster.
		const termMap = existingTerms.reduce(
			( acc: Record< string, Term >, term: Term ) => {
				acc[ term.id ] = term;
				return acc;
			},
			{}
		);

		// Add all of the terms that have been deleted.
		termIds.forEach( ( termId ) => {
			if ( termMap[ termId ] ) {
				return;
			}

			existingTermNames.push( `DELETED (#${ termId })` );
		} );
	}

	// Since both the API and React will attempt to encode HTML entities, we need to
	// decode them so that React can render them properly.
	const decodeHTMLEntities = ( value: string ) => {
		return decodeEntities( value ) || '';
	};

	return (
		<div className="wc-block-editor-product-collection-inspector__taxonomy-control">
			<FormTokenField
				label={ taxonomy.name }
				value={ existingTermNames }
				onInputChange={ handleSearch }
				onChange={ handleChangeTermIDs }
				suggestions={ suggestions }
				disabled={ isLoadingExistingTerms }
				displayTransform={ decodeHTMLEntities }
				// @ts-expect-error Using experimental features
				__experimentalShowHowTo={ false }
			/>
		</div>
	);
};

export default TaxonomyItem;
