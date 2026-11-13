/**
 * External dependencies
 */
import { render, screen } from '@testing-library/react';

/**
 * Internal dependencies
 */
import { CheckRow } from '../components/check-row';
import type { CheckRow as CheckRowData } from '../types';

const row = ( fields: Partial< CheckRowData > ): CheckRowData => ( {
	id: 'llms_txt',
	state: 'open',
	title: 'Add an llms.txt file',
	badge: null,
	text: 'We found no file at /llms.txt.',
	fix: '',
	link: null,
	seen_at: null,
	...fields,
} );

const renderRow = ( data: CheckRowData ) =>
	render(
		<ul>
			<CheckRow row={ data } section="find" />
		</ul>
	);

describe( 'CheckRow', () => {
	it( 'shows a failing check as what to do, what was found, and no fix line when there is none', () => {
		const { container } = renderRow( row( {} ) );

		expect(
			screen.getByText( 'Add an llms.txt file' )
		).toBeInTheDocument();
		expect(
			screen.getByText( 'We found no file at /llms.txt.' )
		).toBeInTheDocument();
		expect(
			container.querySelector( '.wc-sales-readiness-row__fix' )
		).toBeNull();
		expect( screen.queryByRole( 'link' ) ).toBeNull();
		expect(
			container.querySelector( '.wc-sales-readiness-row__marker' )
		).toHaveAttribute( 'aria-hidden', 'true' );
	} );

	it( 'marks a blocker Required, with its fix line and where to fix it', () => {
		renderRow(
			row( {
				state: 'required',
				title: 'Let visitors open store pages without signing in',
				badge: { intent: 'low', text: 'Required' },
				fix: 'Turn off the sign-in requirement.',
				link: {
					href: '/wp-admin/visibility',
					text: 'Go to Site visibility',
				},
			} )
		);

		expect( screen.getByText( 'Required' ) ).toBeInTheDocument();
		expect(
			screen.getByText( 'Turn off the sign-in requirement.' )
		).toBeInTheDocument();
		expect(
			screen.getByRole( 'link', { name: 'Go to Site visibility' } )
		).toHaveAttribute( 'href', '/wp-admin/visibility' );
	} );

	it( 'says when a check could not run, with what happened', () => {
		renderRow(
			row( {
				state: 'unknown',
				title: 'Add an llms.txt file',
				text: "This couldn't be checked. Try again later.",
			} )
		);

		expect( screen.getByText( "Couldn't check:" ) ).toBeInTheDocument();
		expect(
			screen.getByText( "This couldn't be checked. Try again later." )
		).toBeInTheDocument();
		expect( screen.queryByRole( 'link' ) ).toBeNull();
	} );

	it( 'shows a passed check by its passing title and why it matters', () => {
		renderRow(
			row( {
				state: 'pass',
				title: 'Your store has an llms.txt file',
				text: 'llms.txt gives AI assistants a short summary of your store.',
			} )
		);

		expect( screen.getByText( 'Passed:' ) ).toBeInTheDocument();
		expect(
			screen.getByText( 'Your store has an llms.txt file' )
		).toBeInTheDocument();
		expect(
			screen.getByText(
				'llms.txt gives AI assistants a short summary of your store.'
			)
		).toBeInTheDocument();
	} );
} );
