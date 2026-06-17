/**
 * External dependencies
 */
import { render, screen } from '@testing-library/react';
import React from 'react';

/**
 * Internal dependencies
 */
import { AppErrorBoundary } from './app-error-boundary';
import { FEEDBACK_URL, GITHUB_ISSUES_URL } from './constants';

jest.mock( '@wordpress/i18n', () => ( {
	__: jest.fn( ( message ) => message ),
} ) );

jest.mock( '@wordpress/components', () => ( {
	Button: ( {
		children,
		href,
		onClick,
		rel,
		target,
	}: React.PropsWithChildren< {
		href?: string;
		onClick?: () => void;
		rel?: string;
		target?: string;
	} > ) =>
		href ? (
			<a href={ href } rel={ rel } target={ target }>
				{ children }
			</a>
		) : (
			<button onClick={ onClick }>{ children }</button>
		),
} ) );

jest.mock( '@wordpress/ui', () => ( {
	EmptyState: {
		Root: ( {
			children,
			className,
		}: React.PropsWithChildren< { className?: string } > ) => (
			<section className={ className }>{ children }</section>
		),
		Title: ( { children }: React.PropsWithChildren ) => (
			<h2>{ children }</h2>
		),
		Description: ( {
			children,
			className,
		}: React.PropsWithChildren< { className?: string } > ) => (
			<p className={ className }>{ children }</p>
		),
		Actions: ( { children }: React.PropsWithChildren ) => (
			<div>{ children }</div>
		),
	},
	Stack: ( { children }: React.PropsWithChildren ) => <div>{ children }</div>,
} ) );

function BrokenComponent(): React.ReactElement {
	throw new Error( 'Broken component' );
}

describe( 'AppErrorBoundary', () => {
	let consoleErrorSpy: jest.SpyInstance;

	beforeEach( () => {
		consoleErrorSpy = jest
			.spyOn( console, 'error' )
			.mockImplementation( () => {} );
	} );

	afterEach( () => {
		consoleErrorSpy.mockRestore();
	} );

	it( 'renders children when there is no error', () => {
		render(
			<AppErrorBoundary>
				<div>Products app</div>
			</AppErrorBoundary>
		);

		expect( screen.getByText( 'Products app' ) ).toBeInTheDocument();
	} );

	it( 'renders recovery actions when a child crashes', () => {
		render(
			<AppErrorBoundary>
				<BrokenComponent />
			</AppErrorBoundary>
		);

		expect(
			screen.getByText(
				'Oops, the experimental products experience ran into a problem'
			)
		).toBeInTheDocument();
		expect(
			screen.getByRole( 'link', {
				name: 'Report an issue on GitHub',
			} )
		).toHaveAttribute( 'href', GITHUB_ISSUES_URL );
		expect(
			screen.getByRole( 'link', {
				name: 'Share feedback in survey',
			} )
		).toHaveAttribute( 'href', FEEDBACK_URL );
		expect(
			screen.getByRole( 'button', { name: 'Reload page' } )
		).toBeInTheDocument();
	} );
} );
