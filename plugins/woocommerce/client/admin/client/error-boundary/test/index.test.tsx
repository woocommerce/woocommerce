/**
 * External dependencies
 */
import { render, screen, fireEvent } from '@testing-library/react';
/**
 * Internal dependencies
 */
import { ErrorBoundary } from '../';

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

	// Mock window.location.reload by using a global variable
	const originalLocation = window.location;

	beforeAll( () => {
		// Opt Out of the jsdom error messages
		window.addEventListener( 'error', onError );
		// eslint-disable-next-line @typescript-eslint/ban-ts-comment
		// @ts-ignore - Ignore TS error for deleting window.location
		delete window.location;
		// eslint-disable-next-line @typescript-eslint/ban-ts-comment
		// @ts-ignore - Ignore TS error for assigning window.location
		window.location = { reload: jest.fn() };
	} );

	afterAll( () => {
		window.location = originalLocation;
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
		const reloadMock = jest.fn();
		Object.defineProperty( window.location, 'reload', {
			configurable: true,
			value: reloadMock,
		} );

		render(
			<ErrorBoundary>
				<ThrowError />
			</ErrorBoundary>
		);

		fireEvent.click( screen.getByText( 'Reload Page' ) );

		expect( reloadMock ).toHaveBeenCalled();
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
