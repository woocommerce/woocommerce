/**
 * External dependencies
 */

import { render, screen, fireEvent } from '@testing-library/react';
import React, { createElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { ErrorBoundary } from '..';

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
			screen.getByText( 'Oops, something went wrong. Please try again' )
		).toBeInTheDocument();
	} );

	it( 'shows reload button', () => {
		render(
			<ErrorBoundary>
				<ThrowError />
			</ErrorBoundary>
		);

		expect( screen.getByText( 'Reload' ) ).toBeInTheDocument();
	} );

	it( 'refreshes the page when Reload Page button is clicked', () => {
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

		fireEvent.click( screen.getByText( 'Reload' ) );

		expect( reloadMock ).toHaveBeenCalled();
	} );

	it( 'triggers on error callback when provided', () => {
		const onError = jest.fn();

		render(
			<ErrorBoundary onError={ onError }>
				<ThrowError />
			</ErrorBoundary>
		);

		expect( onError ).toHaveBeenCalled();
	} );

	it( 'triggers custom action callback when provided', () => {
		const customActionCallback = jest.fn();

		render(
			<ErrorBoundary actionCallback={ customActionCallback }>
				<ThrowError />
			</ErrorBoundary>
		);

		fireEvent.click( screen.getByText( 'Reload' ) );

		expect( customActionCallback ).toHaveBeenCalledWith(
			new Error( 'Test error' )
		);
	} );

	it( 'does not display the action button when showActionButton is false', () => {
		render(
			<ErrorBoundary showActionButton={ false }>
				<ThrowError />
			</ErrorBoundary>
		);

		expect( screen.queryByText( 'Reload' ) ).not.toBeInTheDocument();
	} );

	it( 'resets error boundary state after action is performed when resetErrorAfterAction is true', () => {
		const { rerender } = render(
			<ErrorBoundary>
				<ThrowError />
			</ErrorBoundary>
		);

		rerender(
			<ErrorBoundary>
				<div>Child Component</div>
			</ErrorBoundary>
		);

		fireEvent.click( screen.getByText( 'Reload' ) );

		expect( screen.getByText( 'Child Component' ) ).toBeInTheDocument();
	} );

	it( 'does not reset error boundary state after action is performed when resetErrorAfterAction is false', () => {
		const { rerender } = render(
			<ErrorBoundary resetErrorAfterAction={ false }>
				<ThrowError />
			</ErrorBoundary>
		);

		fireEvent.click( screen.getByText( 'Reload' ) );

		rerender(
			<ErrorBoundary resetErrorAfterAction={ false }>
				<div>Child Component</div>
			</ErrorBoundary>
		);

		expect(
			screen.queryByText( 'Child Component' )
		).not.toBeInTheDocument();
	} );
} );
