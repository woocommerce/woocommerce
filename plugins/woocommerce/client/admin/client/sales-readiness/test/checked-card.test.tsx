/**
 * External dependencies
 */
import { fireEvent, render, screen } from '@testing-library/react';

/**
 * Internal dependencies
 */
import { CheckedCard } from '../components/checked-card';
import { RowLink } from '../components/check-row';
import type { ReadinessView } from '../types';

const firstVisit = {
	last_checked: 'Not checked yet',
	checked_note: null,
	checked: false,
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
			<CheckedCard
				view={ firstVisit }
				running={ false }
				onRun={ jest.fn() }
				onDownloadError={ jest.fn() }
			/>
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
			<CheckedCard
				view={ running }
				running={ false }
				onRun={ jest.fn() }
				onDownloadError={ jest.fn() }
			/>
		);
		expect(
			container.querySelector( '.components-spinner' )
		).not.toBeNull();
		expect( screen.getByRole( 'status' ) ).toHaveTextContent(
			'Checking your store'
		);
		expect( screen.queryByText( 'Not checked yet' ) ).toBeNull();
		// Turned off but still focusable, so focus stays on it after the click.
		expect(
			screen.getByRole( 'button', { name: 'Check again' } )
		).toHaveAttribute( 'aria-disabled', 'true' );
	} );

	it( 'shows the stalled message, not a spinner, when the check cannot start', () => {
		const stalled = {
			...firstVisit,
			run: {
				...firstVisit.run,
				pending: true,
				stalled: true,
				message:
					'Checks can’t start because scheduled actions aren’t running.',
			},
		};
		const { container } = render(
			<CheckedCard
				view={ stalled }
				running={ false }
				onRun={ jest.fn() }
				onDownloadError={ jest.fn() }
			/>
		);

		expect( container.querySelector( '.components-spinner' ) ).toBeNull();
		expect( screen.getByRole( 'status' ) ).toHaveTextContent(
			'Checks can’t start'
		);
	} );

	it( 'shows the running state as soon as Check your store is clicked', () => {
		render(
			<CheckedCard
				view={ firstVisit }
				running={ true }
				onRun={ jest.fn() }
				onDownloadError={ jest.fn() }
			/>
		);
		expect( screen.getByRole( 'status' ) ).toHaveTextContent(
			'Checking your store'
		);
	} );

	it( 'offers the daily check switch once the store has been checked, and reports a change', () => {
		const onDailyChecks = jest.fn();
		const checked = {
			...firstVisit,
			last_checked: 'Last checked today at 10:42 am.',
			checked: true,
			daily_checks: true,
			support_url: 'https://example.com/support',
			run: { ...firstVisit.run, label: 'Check again' },
		};
		const { rerender } = render(
			<CheckedCard
				view={ checked }
				running={ false }
				onRun={ jest.fn() }
				onDownloadError={ jest.fn() }
				onDailyChecks={ onDailyChecks }
			/>
		);
		const toggle = screen.getByRole( 'checkbox', {
			name: 'Automatically run checks daily',
		} );
		expect( toggle ).toBeChecked();
		expect( screen.queryByText( /select Check again above/ ) ).toBeNull();
		fireEvent.click( toggle );
		expect( onDailyChecks ).toHaveBeenCalledWith( false );

		rerender(
			<CheckedCard
				view={ { ...checked, daily_checks: false } }
				running={ false }
				onRun={ jest.fn() }
				onDownloadError={ jest.fn() }
				onDailyChecks={ onDailyChecks }
			/>
		);
		expect(
			screen.getByText(
				'For an updated report on how well AI assistants can find and shop your store, select Check again above.'
			)
		).toBeInTheDocument();

		rerender(
			<CheckedCard
				view={ checked }
				running={ false }
				onRun={ jest.fn() }
				onDownloadError={ jest.fn() }
				onDailyChecks={ onDailyChecks }
				savingDailyChecks
			/>
		);
		expect(
			screen.getByRole( 'checkbox', {
				name: 'Automatically run checks daily',
			} )
		).toBeDisabled();

		rerender(
			<CheckedCard
				view={ firstVisit }
				running={ false }
				onRun={ jest.fn() }
				onDownloadError={ jest.fn() }
				onDailyChecks={ onDailyChecks }
			/>
		);
		expect(
			screen.queryByRole( 'checkbox', {
				name: 'Automatically run checks daily',
			} )
		).toBeNull();
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
		const link = screen.getByRole( 'link', {
			name: 'Edit page',
			description: 'Edit your refund and returns policy page',
		} );
		expect( link ).toHaveAccessibleName( 'Edit page' );
		expect( link ).toHaveTextContent( 'Edit page' );
	} );
} );
