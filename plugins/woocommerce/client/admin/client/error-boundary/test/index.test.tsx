/**
 * External dependencies
 */
import { mockBrowserLocation } from '@woocommerce/internal-js-tests';
import { render, screen, fireEvent } from '@testing-library/react';
/**
 * Internal dependencies
 */
import { ErrorBoundary } from '../';

const reload = mockBrowserLocation( 'reload' );

const ThrowError = () => {
	throw new Error( 'Test error' );
	return null;
};

describe( 'ErrorBoundary', () => {
	function onError( event: Event ) {
		// Note: this will swallow reports about unhandled errors!
		// Use with extreme caution.
		event.preventDefault();
	}

	beforeAll( () => {
		// Opt Out of the jsdom error messages
		window.addEventListener( 'error', onError );
	} );

	afterAll( () => {
		window.removeEventListener( 'error', onError );
	} );

	it( 'catches errors and displays an error message', () => {
		render(
			<ErrorBoundary>
				<ThrowError />
			</ErrorBoundary>
		);

		expect(
			screen.getByText( 'Oops, something went wrong' )
		).toBeInTheDocument();
	} );

	it( 'shows refresh and report issue buttons', () => {
		render(
			<ErrorBoundary>
				<ThrowError />
			</ErrorBoundary>
		);

		expect( screen.getByText( 'Reload Page' ) ).toBeInTheDocument();
		expect( screen.getByText( 'Get Support' ) ).toBeInTheDocument();
	} );

	it( 'refreshes the page when Refresh Page button is clicked', () => {
		render(
			<ErrorBoundary>
				<ThrowError />
			</ErrorBoundary>
		);

		fireEvent.click( screen.getByText( 'Reload Page' ) );

		expect( reload ).toHaveBeenCalled();
	} );

	it( 'opens a new issue when Report Issue button is clicked', () => {
		const openSpy = jest
			.spyOn( window, 'open' )
			.mockImplementation( () => null );

		render(
			<ErrorBoundary>
				<ThrowError />
			</ErrorBoundary>
		);

		fireEvent.click( screen.getByText( 'Get Support' ) );

		expect( openSpy ).toHaveBeenCalledWith(
			'https://wordpress.org/support/plugin/woocommerce/',
			'_blank'
		);

		openSpy.mockRestore();
	} );
} );
