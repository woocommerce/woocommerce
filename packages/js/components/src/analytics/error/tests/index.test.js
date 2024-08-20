/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';
import { render, screen, fireEvent } from '@testing-library/react';

/**
 * Internal dependencies
 */
import AnalyticsError from '..';

describe( 'AnalyticsError', () => {
	function onError( event ) {
		// Note: this will swallow reports about unhandled errors!
		// Use with extreme caution.
		event.preventDefault();
	}

	// Mock window.location.reload by using a global variable
	const originalLocation = window.location;

	beforeAll( () => {
		// Opt Out of the jsdom error messages
		window.addEventListener( 'error', onError );
		delete window.location;
		window.location = { reload: jest.fn() };
	} );

	afterAll( () => {
		window.location = originalLocation;
		window.removeEventListener( 'error', onError );
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

		expect( screen.getByText( 'Reload' ) ).toBeInTheDocument();
	} );

	it( 'refreshes the page when Reload Page button is clicked', () => {
		const reloadMock = jest.fn();
		Object.defineProperty( window.location, 'reload', {
			configurable: true,
			value: reloadMock,
		} );

		render( <AnalyticsError /> );

		fireEvent.click( screen.getByText( 'Reload' ) );

		expect( reloadMock ).toHaveBeenCalled();
	} );

	it( 'should match snapshot', () => {
		const { container } = render( <AnalyticsError /> );
		expect( container ).toMatchSnapshot();
	} );
} );
