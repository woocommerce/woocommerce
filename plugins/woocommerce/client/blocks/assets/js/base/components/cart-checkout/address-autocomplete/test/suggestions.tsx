/**
 * External dependencies
 */
import { render, screen } from '@testing-library/react';
import type { AddressAutocompleteResult } from '@woocommerce/types';

/**
 * Internal dependencies
 */
import { Suggestions } from '../suggestions';

describe( 'Suggestions - standalone component', () => {
	it( 'Shows suggestions based on passed data', async () => {
		const suggestions: AddressAutocompleteResult[] = [
			{
				id: '1',
				label: '123 Main St, Springfield, IL, USA',
				matchedSubstrings: [ { offset: 0, length: 3 } ],
			},
			{
				id: '2',
				label: '456 Elm St, Springfield, IL, USA',
				matchedSubstrings: [ { offset: 0, length: 3 } ],
			},
		];
		const handleSelect = jest.fn();
		render(
			<Suggestions
				suggestions={ suggestions }
				selectedSuggestion={ 0 }
				addressType="billing"
				onSuggestionClick={ handleSelect }
			/>
		);

		// Check that the suggestions are displayed, the matched parts are bolded.
		const firstSuggestion = screen.getByText( '123' );
		expect( firstSuggestion ).toBeInTheDocument();
		expect( firstSuggestion.tagName ).toBe( 'STRONG' );
		expect(
			screen.getByText( 'Main St, Springfield, IL, USA' )
		).toBeInTheDocument();

		const secondSuggestion = screen.getByText( '456' );
		expect( secondSuggestion ).toBeInTheDocument();
		expect( secondSuggestion.tagName ).toBe( 'STRONG' );
		expect(
			screen.getByText( 'Elm St, Springfield, IL, USA' )
		).toBeInTheDocument();
	} );
} );
