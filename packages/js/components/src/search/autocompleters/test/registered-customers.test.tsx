/**
 * External dependencies
 */
import apiFetch from '@wordpress/api-fetch';
import { getQueryArg } from '@wordpress/url';
import { render, screen } from '@testing-library/react';
import { createElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import registeredCustomers from '../registered-customers';

jest.mock( '@wordpress/api-fetch', () => jest.fn() );

const mockedApiFetch = apiFetch as unknown as jest.Mock;

describe( 'registered customers autocompleter', () => {
	const named = {
		id: 1,
		name: 'Zoe Bloggs',
		username: 'bloggs',
		email: 'zoe@example.test',
	};
	const unnamed = {
		id: 2,
		name: '',
		username: 'zoemarketing',
		email: 'hello@zoeshop.test',
	};

	beforeEach( () => {
		mockedApiFetch.mockReset();
		mockedApiFetch.mockResolvedValue( [] );
	} );

	const getLabelText = ( customer: unknown, query: string ) => {
		const { container } = render(
			<>{ registeredCustomers.getOptionLabel( customer, query ) }</>
		);
		return container.textContent;
	};

	it( 'searches every customer field, and excludes guests', () => {
		registeredCustomers.options( 'zoe' );
		const path = mockedApiFetch.mock.calls[ 0 ][ 0 ].path;

		expect( getQueryArg( path, 'searchby' ) ).toBe( 'all' );
		expect( getQueryArg( path, 'user_type' ) ).toBe( 'registered' );
	} );

	it( 'matches customers on their name, username and email', () => {
		expect( registeredCustomers.getOptionKeywords( named ) ).toEqual( [
			'Zoe Bloggs',
			'bloggs',
			'zoe@example.test',
			// The API matches against the fields joined together, so a search
			// term spanning two of them has to be a keyword as well.
			'Zoe Bloggs bloggs zoe@example.test',
		] );
	} );

	it( 'keeps the joined keyword usable when the customer has no name', () => {
		expect( registeredCustomers.getOptionKeywords( unnamed ) ).toContain(
			'zoemarketing hello@zoeshop.test'
		);
	} );

	it( 'shows the name on its own when it matches the search term', () => {
		expect( getLabelText( named, 'zoe' ) ).toBe( 'Zoe Bloggs' );
	} );

	it( 'shows the matched field alongside the name when the name does not match', () => {
		expect( getLabelText( named, 'example.test' ) ).toBe(
			'Zoe Bloggs (zoe@example.test)'
		);
	} );

	it( 'falls back to the username when the customer has no name', () => {
		expect( getLabelText( unnamed, 'zoemarketing' ) ).toBe(
			'zoemarketing'
		);
		expect( registeredCustomers.getOptionCompletion( unnamed ) ).toEqual( {
			key: 2,
			label: 'zoemarketing',
		} );
	} );

	it( 'highlights the part of the suggestion that matched', () => {
		render( <>{ registeredCustomers.getOptionLabel( named, 'Blog' ) }</> );

		expect( screen.getByText( 'Blog' ).tagName ).toBe( 'STRONG' );
	} );

	it( 'highlights the match inside the appended field', () => {
		render( <>{ registeredCustomers.getOptionLabel( named, 'example' ) }</> );

		expect( screen.getByText( 'example' ).tagName ).toBe( 'STRONG' );
	} );

	it( 'skips the highlight when the match spans two fields', () => {
		// 'Bloggs bloggs' only matches the name and username joined together,
		// so there is no match to highlight in the suggestion.
		const { container } = render(
			<>
				{ registeredCustomers.getOptionLabel( named, 'Bloggs bloggs' ) }
			</>
		);

		expect( container.textContent ).toBe( 'Zoe Bloggs' );
		expect( container.querySelector( 'strong' ) ).toBeNull();
	} );
} );
