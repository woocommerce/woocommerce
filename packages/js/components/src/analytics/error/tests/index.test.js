/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

/**
 * Internal dependencies
 */
import AnalyticsError from '..';

describe( 'AnalyticsError', () => {
	// Mock window.location.reload by using a global variable
	const originalLocation = window.location;

	beforeAll( () => {
		delete window.location;
		window.location = { reload: jest.fn() };
	} );

	afterAll( () => {
		window.location = originalLocation;
	} );

	it( 'displays an error message', () => {
		render( <AnalyticsError /> );

		expect(
			screen.getByText(
				'There was an error getting your stats. Please try again.'
			)
		).toBeInTheDocument();
	} );

	it( 'shows reload button', () => {
		render( <AnalyticsError /> );

		expect(
			screen.getByRole( 'button', { name: 'Reload' } )
		).toBeInTheDocument();
	} );

	it( 'refreshes the page when Reload Page button is clicked', () => {
		const reloadMock = jest.fn();
		Object.defineProperty( window.location, 'reload', {
			configurable: true,
			value: reloadMock,
		} );

		render( <AnalyticsError /> );

		userEvent.click( screen.getByText( 'Reload' ) );

		expect( reloadMock ).toHaveBeenCalled();
	} );

	it( 'should match snapshot', () => {
		const { container } = render( <AnalyticsError /> );
		expect( container ).toMatchSnapshot();
	} );
} );
