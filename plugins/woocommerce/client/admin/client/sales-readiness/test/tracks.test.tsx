/**
 * External dependencies
 */
import { act, render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import apiFetch from '@wordpress/api-fetch';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import SalesReadiness from '../index';
import SalesReadinessOverview from '../overview';
import type { ReadinessView } from '../types';

jest.mock( '@wordpress/api-fetch', () => jest.fn() );
jest.mock( '@woocommerce/tracks', () => ( { recordEvent: jest.fn() } ) );

const link = ( text: string ) => ( { href: '#' + text, text } );

const checkedView: ReadinessView = {
	title: 'Sales readiness',
	description: 'See how AI assistants see your store.',
	last_checked: 'Last checked today',
	checked_note: null,
	intro: null,
	run: {
		label: 'Check again',
		pending: false,
		stalled: false,
		message: '',
		link: null,
	},
	fix_first: {
		title: 'Fix first',
		ranking: 'Ranked by products.',
		items: [
			{
				id: 'refund_policy',
				tier: 'Stops every sale',
				required: true,
				title: 'Publish your refund policy',
				why: 'It is a draft.',
				link: link( 'Edit page' ),
			},
			{
				id: 'image',
				tier: "Products that can't be listed",
				required: false,
				title: '3 products have no main image',
				why: 'Add an image.',
				link: link( 'View 3 products' ),
			},
		],
	},
	sections: [
		{
			id: 'find',
			title: 'Can AI assistants find your store?',
			badge: { intent: 'medium', text: '2 issues' },
			note: null,
			channels: [],
			rows: [
				{
					id: 'llms_txt',
					state: 'open',
					title: 'No llms.txt file',
					affects: '',
					lines: [],
					link: link( 'Open SEO settings' ),
					seen_at: null,
				},
				{
					id: 'guest_checkout',
					state: 'required',
					title: 'Allow guest checkout',
					affects: '',
					lines: [],
					link: null,
					seen_at: null,
				},
			],
			folded: [
				{
					id: 'robots',
					state: 'pass',
					title: 'Robots allow agents',
					affects: '',
					lines: [],
					link: null,
					seen_at: null,
				},
				{
					id: 'sitemap',
					state: 'unknown',
					title: 'Sitemap',
					affects: '',
					lines: [],
					link: null,
					seen_at: null,
				},
			],
			empty: null,
		},
	],
	declared: null,
	overview: {
		shopping: {
			title: 'AI shopping',
			badge: null,
			text: '4 steps left.',
			button: link( 'Manage' ),
		},
		view_all: link( 'View all checks' ),
		based_on: null,
	},
};

const mockedApiFetch = apiFetch as unknown as jest.Mock;
const mockedRecord = recordEvent as jest.Mock;

const events = () => mockedRecord.mock.calls.map( ( call ) => call[ 0 ] );

describe( 'Sales readiness tracking', () => {
	beforeEach( () => {
		mockedApiFetch.mockReset();
		mockedRecord.mockReset();
	} );

	it( 'records one viewed event on the page, with the counts per state', async () => {
		mockedApiFetch.mockResolvedValue( checkedView );
		render( <SalesReadiness /> );

		await screen.findByText( 'Fix first' );
		expect( mockedRecord ).toHaveBeenCalledTimes( 1 );
		expect( mockedRecord ).toHaveBeenCalledWith( 'sales_readiness_viewed', {
			surface: 'page',
			checked: true,
			pending: false,
			fix_first_count: 2,
			open_count: 1,
			required_count: 1,
			unknown_count: 1,
			pass_count: 1,
		} );
	} );

	it( 'records Run checks, without a second viewed event after the reload', async () => {
		mockedApiFetch.mockImplementation( ( { method } ) =>
			Promise.resolve(
				method === 'POST'
					? {}
					: { ...checkedView, intro: 'Run a check.' }
			)
		);
		render( <SalesReadiness /> );

		await userEvent.click(
			await screen.findByRole( 'button', { name: 'Check again' } )
		);

		await waitFor( () =>
			expect( mockedApiFetch ).toHaveBeenCalledTimes( 3 )
		);
		expect( mockedRecord ).toHaveBeenCalledWith(
			'sales_readiness_run_check_click',
			{
				surface: 'page',
				first_check: true,
			}
		);
		expect(
			events().filter( ( name ) => name === 'sales_readiness_viewed' )
		).toHaveLength( 1 );
	} );

	it( 'records fix links with the check id, and the fold toggle', async () => {
		mockedApiFetch.mockResolvedValue( checkedView );
		render( <SalesReadiness /> );

		await userEvent.click(
			await screen.findByRole( 'link', { name: 'View 3 products' } )
		);
		expect( mockedRecord ).toHaveBeenCalledWith(
			'sales_readiness_link_click',
			{
				surface: 'page',
				area: 'fix_first',
				check_id: 'image',
				position: 2,
			}
		);

		await userEvent.click(
			screen.getByRole( 'link', { name: 'Open SEO settings' } )
		);
		expect( mockedRecord ).toHaveBeenCalledWith(
			'sales_readiness_link_click',
			{
				surface: 'page',
				area: 'section',
				section: 'find',
				check_id: 'llms_txt',
				state: 'open',
			}
		);

		await userEvent.click(
			screen.getByRole( 'button', { name: 'Show 2 more checks' } )
		);
		expect( mockedRecord ).toHaveBeenCalledWith(
			'sales_readiness_fold_toggle',
			{
				section: 'find',
				open: true,
			}
		);

		await userEvent.click( screen.getByRole( 'button', { name: 'Hide' } ) );
		expect( mockedRecord ).toHaveBeenCalledWith(
			'sales_readiness_fold_toggle',
			{
				section: 'find',
				open: false,
			}
		);
	} );

	it( 'records the Overview view and its links', async () => {
		mockedApiFetch.mockResolvedValue( checkedView );
		render( <SalesReadinessOverview /> );

		await userEvent.click(
			await screen.findByRole( 'link', { name: 'View all checks' } )
		);
		await userEvent.click(
			screen.getByRole( 'link', { name: 'Edit page' } )
		);
		await userEvent.click( screen.getByRole( 'link', { name: 'Manage' } ) );

		expect( mockedRecord ).toHaveBeenCalledWith(
			'sales_readiness_viewed',
			expect.objectContaining( { surface: 'overview', checked: true } )
		);
		expect( mockedRecord ).toHaveBeenCalledWith(
			'sales_readiness_link_click',
			{
				surface: 'overview',
				area: 'view_all',
			}
		);
		expect( mockedRecord ).toHaveBeenCalledWith(
			'sales_readiness_link_click',
			{
				surface: 'overview',
				area: 'shopping',
			}
		);
		expect( mockedRecord ).toHaveBeenCalledWith(
			'sales_readiness_link_click',
			{
				surface: 'overview',
				area: 'fix_first',
				check_id: 'refund_policy',
				position: 1,
			}
		);
	} );

	it( 'records the Overview view once when the view comes with the page', async () => {
		render( <SalesReadinessOverview initialView={ checkedView } /> );

		expect(
			await screen.findByRole( 'link', { name: 'View all checks' } )
		).toBeInTheDocument();
		expect( mockedApiFetch ).not.toHaveBeenCalled();
		expect(
			mockedRecord.mock.calls.filter(
				( [ name ] ) => name === 'sales_readiness_viewed'
			)
		).toHaveLength( 1 );
		expect( mockedRecord ).toHaveBeenCalledWith(
			'sales_readiness_viewed',
			expect.objectContaining( { surface: 'overview', checked: true } )
		);
	} );

	it( 'keeps the Overview cards when starting a check fails', async () => {
		const first = {
			...checkedView,
			intro: 'Run a check.',
			fix_first: null,
			run: {
				label: 'Run checks',
				pending: false,
				stalled: false,
				message: '',
				link: null,
			},
		};
		mockedApiFetch.mockImplementation( ( { method } ) =>
			method === 'POST'
				? Promise.reject( new Error( 'nope' ) )
				: Promise.resolve( first )
		);
		render( <SalesReadinessOverview /> );

		const run = await screen.findByRole( 'button', { name: 'Run checks' } );
		// eslint-disable-next-line testing-library/no-unnecessary-act -- the rejected scan settles after click returns
		await act( async () => {
			await userEvent.click( run );
			await Promise.resolve();
			await Promise.resolve();
		} );
		expect(
			document.querySelector(
				'.wc-sales-readiness--overview .components-notice'
			)?.textContent
		).toContain( 'The check didn’t start. Reload the page to try again.' );
		expect( screen.getByText( 'AI shopping' ) ).toBeTruthy();
		expect(
			screen.getByRole( 'button', { name: 'Run checks' } )
		).toBeEnabled();
	} );

	it( 'records a check that cannot start', async () => {
		mockedApiFetch.mockImplementation( ( { method } ) =>
			method === 'POST'
				? Promise.reject( new Error( 'nope' ) )
				: Promise.resolve( checkedView )
		);
		render( <SalesReadiness /> );

		await userEvent.click(
			await screen.findByRole( 'button', { name: 'Check again' } )
		);

		await waitFor( () =>
			expect( mockedRecord ).toHaveBeenCalledWith(
				'sales_readiness_error',
				{ surface: 'page', action: 'run' }
			)
		);
	} );

	it( 'records a load failure', async () => {
		mockedApiFetch.mockRejectedValue( new Error( 'nope' ) );
		render( <SalesReadiness /> );

		await waitFor( () =>
			expect( mockedRecord ).toHaveBeenCalledWith(
				'sales_readiness_error',
				{
					surface: 'page',
					action: 'load',
				}
			)
		);
		expect( events() ).not.toContain( 'sales_readiness_viewed' );
	} );
} );
