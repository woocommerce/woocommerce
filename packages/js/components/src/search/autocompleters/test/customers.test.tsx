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
import customerNames from '../customer-names';
import customers from '../customers';

jest.mock( '@wordpress/api-fetch', () => jest.fn() );

const mockedApiFetch = apiFetch as unknown as jest.Mock;

describe( 'customers autocompleter', () => {
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
			<>{ customers.getOptionLabel( customer, query ) }</>
		);
		return container.textContent;
	};

	it( 'searches every customer field, unlike the name-only completer', () => {
		customers.options( 'zoe' );
		expect(
			getQueryArg( mockedApiFetch.mock.calls[ 0 ][ 0 ].path, 'searchby' )
		).toBe( 'all' );

		mockedApiFetch.mockReset();
		mockedApiFetch.mockResolvedValue( [] );

		customerNames.options( 'zoe' );
		expect(
			getQueryArg( mockedApiFetch.mock.calls[ 0 ][ 0 ].path, 'searchby' )
		).toBe( 'name' );
	} );

	it( 'matches customers on their name, username and email', () => {
		expect( customers.getOptionKeywords( named ) ).toEqual( [
			'Zoe Bloggs',
			'bloggs',
			'zoe@example.test',
		] );
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
		expect( customers.getOptionCompletion( unnamed ) ).toEqual( {
			key: 2,
			label: 'zoemarketing',
		} );
	} );

	it( 'highlights the part of the suggestion that matched', () => {
		render( <>{ customers.getOptionLabel( named, 'Blog' ) }</> );

		expect( screen.getByText( 'Blog' ).tagName ).toBe( 'STRONG' );
	} );
} );
