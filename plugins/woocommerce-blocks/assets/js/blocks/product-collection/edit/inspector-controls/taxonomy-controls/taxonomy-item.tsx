/**
 * External dependencies
 */
import { useEntityRecords } from '@wordpress/core-data';
import { Taxonomy } from '@wordpress/core-data/src/entity-types';
import { useState, useMemo, useRef } from '@wordpress/element';
import { useDebounce } from '@wordpress/compose';
import { FormTokenField } from '@wordpress/components';
import { decodeEntities } from '@wordpress/html-entities';

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

// A constant empty array that is reused throughout the component.
const EMPTY_ARRAY: [] = [];

// Base arguments for querying terms.
const BASE_QUERY_ARGS = {
	order: 'asc',
	_fields: 'id,name,slug',
	context: 'view',
};

// Function to get the term id based on user input in the `FormTokenField`.
const getTermIdByTermValue = (
	searchTerm: Term | string,
	termNameToIdMap: Map< string, number >
): number | undefined => {
	const termId = ( searchTerm as Term )?.id;
	if ( termId ) {
		return termId;
	}

	return (
		termNameToIdMap.get( searchTerm as string ) ||
		termNameToIdMap.get( ( searchTerm as string ).toLocaleLowerCase() )
	);
};

/**
 * Creates a map that keeps track of the count of each term name in the provided list of terms.
 *
 * @param {Term[]} allTerms - Array of all term objects.
 * @return {Map<string, number>} A map with term names as keys and their counts as values.
 */
const createNameCountMap = ( allTerms: Term[] ): Map< string, number > => {
	return allTerms.reduce(
		( accumulator: Map< string, number >, term: Term ) => {
			const termName = term.name;
			if ( accumulator.has( termName ) ) {
				accumulator.set(
					termName,
					( accumulator.get( termName ) as number ) + 1
				);
			} else {
				accumulator.set( termName, 1 );
			}
			return accumulator;
		},
		new Map< string, number >()
	);
};

/**
 * Generates a unique name for the term. If there are multiple terms with the same name,
 * appends the term's slug to the name to distinguish them.
 *
 * @param {string}              termName     - Name of the term.
 * @param {string}              termSlug     - Slug of the term.
 * @param {Map<string, number>} nameCountMap - A map storing count of each term name.
 * @return {string} A unique name for the term.
 */
const generateUniqueName = (
	termName: string,
	termSlug: string,
	nameCountMap: Map< string, number >
): string => {
	return nameCountMap.get( termName ) === 1
		? termName
		: `${ termName } - ${ termSlug }`;
};

/**
 * This function generates and returns two mapping structures (Maps) for terms:
 * 1. termIdToNameMap: Map with term IDs as keys and their corresponding term names as values.
 * 2. termNameToIdMap: Map where the keys are term names and their corresponding values are the term IDs.
 *
 * The primary purpose of these Maps is to facilitate quick lookups in either direction (ID to name, or name to ID).
 *
 * In the case of duplicate term names, to ensure uniqueness, the term's slug is appended to the name.
 * This ensures that when the terms are displayed in the `FormTokenField`, each term name remains unique.
 *
 * An illustrative example of how termIdToNameMap might look is as follows:
 * {
 *    "19": "Accessories",
 *    "37": "category1 - category1",
 *    "38": "category1 - category1-clothing",
 *    "39": "category1 - category1-clothing-2",
 *    "16": "Clothing",
 *    "21": "Decor"
 * }
 * In the example above, "category1" is a duplicated term name, so the term's slug is appended for distinction.
 *
 * termNameToIdMap is the inverse of termIdToNameMap, mapping term names back to their respective IDs.
 */
const useTermMaps = (
	taxonomy: Taxonomy
): {
	termIdToNameMap: Map< number, string >;
	termNameToIdMap: Map< string, number >;
	isResolving: boolean;
} => {
	// Fetch all terms for the given taxonomy.
	const { records: allTerms, isResolving: isResolvingAllTerms } =
		useEntityRecords< Term[] >( 'taxonomy', taxonomy.slug, {
			...BASE_QUERY_ARGS,
		} );

	// Memoize the result to avoid re-renders.
	return useMemo( () => {
		const termIdToNameMap = new Map< number, string >();
		const termNameToIdMap = new Map< string, number >();

		if ( ! allTerms )
			return {
				termIdToNameMap,
				termNameToIdMap,
				isResolving: isResolvingAllTerms,
			};

		// Count the number of times a term name appears.
		const nameCountMap = createNameCountMap( allTerms );

		// Create the map with term ids as keys and term names as values.
		for ( const term of allTerms ) {
			const termId = term.id;
			const termName = term.name;
			const name = generateUniqueName(
				termName,
				term.slug,
				nameCountMap
			);
			termIdToNameMap.set( termId, name );
			termNameToIdMap.set( name, termId );
			// Add lower case version of the term name to the map as well
			// Because the search is case insensitive in FormTokenField.
			termNameToIdMap.set( name.toLocaleLowerCase(), termId );
		}
		return {
			termIdToNameMap,
			termNameToIdMap,
			isResolving: isResolvingAllTerms,
		};
	}, [ allTerms, isResolvingAllTerms ] );
};

const TaxonomyItem = ( { taxonomy, termIds, onChange }: TaxonomyItemProps ) => {
	const [ search, setSearch ] = useState< string | undefined >( undefined );
	const suggestionsRef = useRef< string[] >( EMPTY_ARRAY );
	const currentValueRef = useRef<
		{
			id: number;
			value: string;
		}[]
	>( EMPTY_ARRAY );

	// Search is debounced to limit the number of API calls as the user types
	const debouncedSearch = useDebounce( setSearch, 250 );

	const {
		termIdToNameMap,
		termNameToIdMap,
		isResolving: isResolvingTermMaps,
	} = useTermMaps( taxonomy );

	// Fetch the terms based on the search query.
	const { records: searchResults, hasResolved: searchHasResolved } =
		useEntityRecords(
			'taxonomy',
			taxonomy.slug,
			{
				...BASE_QUERY_ARGS,
				search,
				orderby: 'name',
				exclude: termIds,
				per_page: 20,
			},
			{
				enabled: search !== undefined,
			}
		);

	suggestionsRef.current = useMemo( () => {
		if ( ! searchHasResolved ) return suggestionsRef.current;

		const newSuggestions = searchResults.map(
			( searchResult: Term ) =>
				termIdToNameMap.get( searchResult.id ) || searchResult.name
		);
		return newSuggestions;
	}, [ searchHasResolved, searchResults, termIdToNameMap ] );

	// Fetch the existing terms & set the current value.
	const { records: existingTerms, hasResolved: hasExistingTermsResolved } =
		useEntityRecords< Term >(
			'taxonomy',
			taxonomy.slug,
			{
				...BASE_QUERY_ARGS,
				include: termIds,
			},
			{
				enabled: termIds?.length > 0,
			}
		);

	currentValueRef.current = useMemo( () => {
		if ( hasExistingTermsResolved === false ) {
			return currentValueRef.current;
		}

		if ( ! existingTerms || ! termIds.length ) return EMPTY_ARRAY;

		return existingTerms.map( ( { id, name }: Term ) => ( {
			id,
			value: termIdToNameMap.get( id ) || name,
		} ) );
	}, [ existingTerms, hasExistingTermsResolved, termIdToNameMap, termIds ] );

	// Update the selected terms when the user selects a suggestion.
	const onTermsChange = ( newTermValues: FormTokenField.Value[] ) => {
		const newTermIds = [];
		for ( const termValue of newTermValues ) {
			const termId = getTermIdByTermValue(
				termValue as string | Term,
				termNameToIdMap
			);
			if ( termId ) {
				newTermIds.push( termId );
			}
		}
		onChange( newTermIds );
	};

	const decodeHTMLEntities = ( value: string ) => {
		return decodeEntities( value ) || '';
	};

	return (
		<div className="wc-block-editor-product-collection-inspector__taxonomy-control">
			<FormTokenField
				label={ taxonomy.name }
				value={ currentValueRef.current }
				onInputChange={ debouncedSearch }
				suggestions={ suggestionsRef.current }
				onChange={ onTermsChange }
				disabled={ isResolvingTermMaps }
				// @ts-expect-error Using experimental features
				__experimentalShowHowTo={ false }
				displayTransform={ decodeHTMLEntities }
			/>
		</div>
	);
};

export default TaxonomyItem;
