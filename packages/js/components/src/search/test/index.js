/**
 * External dependencies
 */
import { render, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { createElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { Search } from '../index';
import { computeSuggestionMatch } from '../autocompleters/utils';

const delay = ( timeout ) =>
	new Promise( ( resolve ) => setTimeout( resolve, timeout ) );

describe( 'Search', () => {
	it( 'shows the free text search option', () => {
		const { getByRole, queryAllByRole } = render(
			<Search type="products" allowFreeTextSearch />
		);
		userEvent.type( getByRole( 'combobox' ), 'Product Query' );
		expect( queryAllByRole( 'option' ) ).toHaveLength( 1 );

		userEvent.clear( getByRole( 'combobox' ) );
		expect( queryAllByRole( 'option' ) ).toHaveLength( 0 );
	} );

	describe( 'with `type="custom"`', () => {
		let sampleOptions, sampleAutocompleter;
		beforeEach( () => {
			sampleOptions = [
				{ name: 'Apple', id: 1 },
				{ name: 'Orange', id: 2 },
				{ name: 'Grapes', id: 3 },
			];
			sampleAutocompleter = {
				options: sampleOptions,
				getOptionIdentifier: ( fruit ) => fruit.id,
				getOptionLabel: ( option ) => (
					<nicer-label>{ option.name }</nicer-label>
				),
				getOptionKeywords: ( option ) => [ option.name ],
				getOptionCompletion: ( attribute ) => ( {
					key: attribute.id,
					label: attribute.name,
				} ),
			};
		} );
		describe( 'renders options given in `autocompleter.options`', () => {
			it( 'as a static array', async () => {
				const { getByRole, queryAllByRole } = render(
					<Search
						type="custom"
						autocompleter={ sampleAutocompleter }
					/>
				);
				// Emulate typing to render available options.
				userEvent.type( getByRole( 'combobox' ), 'A' );
				// Wait for async options processing.
				await waitFor( () => {
					expect( queryAllByRole( 'option' ) ).toHaveLength( 3 );
				} );
			} );

			it( 'being a function that for the given query returns an array', async () => {
				const optionsSpy = jest
					.fn()
					.mockName( 'autocompleter.options' );

				const customAutocompleter = {
					...sampleAutocompleter,
					// Set the options as a function that returns an array.
					options: ( ...args ) => {
						optionsSpy( ...args );
						return sampleOptions;
					},
				};

				const { getByRole, queryAllByRole } = render(
					<Search
						type="custom"
						autocompleter={ customAutocompleter }
					/>
				);
				// Emulate typing to render available options.
				userEvent.type( getByRole( 'combobox' ), 'A' );
				// Wait for async options processing.
				await waitFor( () => {
					expect( optionsSpy ).toHaveBeenCalledWith( 'A' );
				} );
				await waitFor( () => {
					expect( queryAllByRole( 'option' ) ).toHaveLength( 3 );
				} );
			} );

			it( 'being a function that for the given query returns a promise for an array', async () => {
				const optionsSpy = jest
					.fn()
					.mockName( 'autocompleter.options' );

				const customAutocompleter = {
					...sampleAutocompleter,
					// Set the options as a function that returns a promise for an array.
					options: async ( ...args ) => {
						optionsSpy( ...args );
						await delay( 1 );
						return sampleOptions;
					},
				};

				const { getByRole, queryAllByRole } = render(
					<Search
						type="custom"
						autocompleter={ customAutocompleter }
					/>
				);
				// Emulate typing to render available options.
				userEvent.type( getByRole( 'combobox' ), 'A' );
				// Wait for async options processing.
				await waitFor( () => {
					expect( optionsSpy ).toHaveBeenCalledWith( 'A' );
				} );
				await waitFor( () => {
					expect( queryAllByRole( 'option' ) ).toHaveLength( 3 );
				} );
			} );
		} );
	} );
	it( 'returns an object with decoded text', () => {
		const decodedText = computeSuggestionMatch(
			'A test &amp; a &#116;&#101;&#115;&#116;',
			'test'
		);
		const expected =
			'{"suggestionBeforeMatch":"A ","suggestionMatch":"test","suggestionAfterMatch":" & a test"}';
		expect( JSON.stringify( decodedText ) ).toBe( expected );
	} );
} );
