/**
 * Internal dependencies
 */
import { ComboboxControlOption } from '.';
import { normalizeTextString } from '../../utils/string';

// Find an option based on whether it's label is an exact match to the search term.
export const findExactMatchBy = (
	by: keyof ComboboxControlOption,
	searchTerm: string,
	options: ComboboxControlOption[]
) => {
	return options.find(
		( option ) =>
			normalizeTextString( option[ by ] ) ===
			normalizeTextString( searchTerm )
	);
};

// Find an option based on whether it's label starts with the search term.
export const findBestMatchByLabel = (
	searchTerm: string,
	options: ComboboxControlOption[]
) => {
	// String startsWith empty string always returns true, so we need to check for searchTerm length.
	return searchTerm.length
		? options.find( ( option ) => {
				return normalizeTextString( option.label ).startsWith(
					normalizeTextString( searchTerm )
				);
		  } )
		: undefined;
};

export const findMatchingSuggestions = (
	searchTerm: string,
	options: ComboboxControlOption[]
) => {
	const startsWithMatch: ComboboxControlOption[] = [];
	const containsMatch: ComboboxControlOption[] = [];
	const match = normalizeTextString( searchTerm );

	options.forEach( ( option ) => {
		const index = normalizeTextString( option.label ).indexOf( match );
		if ( index === 0 ) {
			startsWithMatch.push( option );
		} else if ( index > 0 ) {
			containsMatch.push( option );
		}
	} );

	const matches = startsWithMatch.concat( containsMatch );

	// If we have an exact match already don't show the one redundant suggestion.
	if ( matches.length === 1 && matches[ 0 ].label === searchTerm ) {
		return [];
	}

	return matches;
};
