/**
 * External dependencies
 */
import { render, screen } from '@testing-library/react';

/**
 * Internal dependencies
 */
import { CheckedCard } from '../components/checked-card';
import { RowLink } from '../components/check-row';
import type { ReadinessView } from '../types';

const firstVisit = {
	last_checked: 'Not checked yet',
	checked_note: null,
	intro: 'Run a check to see what AI assistants get.',
	run: {
		label: 'Check your store',
		pending: false,
		stalled: false,
		message: '',
		link: null,
	},
} as unknown as ReadinessView;

describe( 'CheckedCard', () => {
	it( 'shows Not checked yet and Check your store on the first visit, without the intro', () => {
		render(
			<CheckedCard view={ firstVisit } running={ false } onRun={ jest.fn() } />
		);
		expect( screen.getByText( 'Not checked yet' ) ).toBeInTheDocument();
		expect(
			screen.getByRole( 'button', { name: 'Check your store' } )
		).toBeEnabled();
		expect(
			screen.queryByText( 'Run a check to see what AI assistants get.' )
		).toBeNull();
	} );

	it( 'shows a spinner, Checking your store and a turned-off Check again while a check runs', () => {
		const running = {
			...firstVisit,
			run: {
				...firstVisit.run,
				pending: true,
				message: 'Checking your store',
			},
		};
		const { container } = render(
			<CheckedCard view={ running } running={ false } onRun={ jest.fn() } />
		);
		expect( container.querySelector( '.components-spinner' ) ).not.toBeNull();
		expect( screen.getByRole( 'status' ) ).toHaveTextContent(
			'Checking your store'
		);
		expect( screen.queryByText( 'Not checked yet' ) ).toBeNull();
		expect(
			screen.getByRole( 'button', { name: 'Check again' } )
		).toBeDisabled();
	} );

	it( 'shows the running state as soon as Check your store is clicked', () => {
		render(
			<CheckedCard view={ firstVisit } running={ true } onRun={ jest.fn() } />
		);
		expect( screen.getByRole( 'status' ) ).toHaveTextContent(
			'Checking your store'
		);
	} );
} );

describe( 'RowLink', () => {
	it( 'gives a repeated button text the name screen readers hear', () => {
		render(
			<RowLink
				link={ {
					href: 'https://example.com/wp-admin/post.php?post=7&action=edit',
					text: 'Edit page',
					sr: 'Edit your refund and returns policy page',
				} }
			/>
		);
		expect(
			screen.getByRole( 'link', {
				name: 'Edit your refund and returns policy page',
			} )
		).toHaveTextContent( 'Edit page' );
	} );
} );
