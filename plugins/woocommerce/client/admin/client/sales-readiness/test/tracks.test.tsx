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
import { viewedProps } from '../tracks';
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
	support_url: 'https://woocommerce.com/my-account/create-a-ticket/',
	fix_first: {
		title: 'Fix first',
		ranking: 'Ranked by products.',
		items: [
			{
				id: 'refund_policy',
				tier: 'Stops every sale',
				title: 'Publish your refund policy',
				why: 'It is a draft.',
				link: link( 'Edit page' ),
			},
			{
				id: 'image',
				tier: "Products that can't be listed",
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

	it( 'counts a check shown in two cards once', () => {
		const shared = checkedView.sections[ 0 ].rows[ 0 ];
		const view: ReadinessView = {
			...checkedView,
			sections: [
				...checkedView.sections,
				{
					...checkedView.sections[ 0 ],
					id: 'access',
					rows: [ shared ],
					folded: [],
				},
			],
		};

		expect( viewedProps( view, 'page' ) ).toEqual(
			viewedProps( checkedView, 'page' )
		);
	} );

	it( "counts each card's couldn't check row", () => {
		const cant = {
			...checkedView.sections[ 0 ].rows[ 0 ],
			id: 'cant_check',
			state: 'unknown' as const,
		};
		const card = checkedView.sections[ 0 ];
		const view: ReadinessView = {
			...checkedView,
			sections: [
				{ ...card, id: 'find', rows: [ cant ], folded: [] },
				{ ...card, id: 'access', rows: [ cant ], folded: [] },
			],
		};

		expect( viewedProps( view, 'page' ).unknown_count ).toBe( 2 );
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
			'sales_readiness_run_check_clicked',
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
			'sales_readiness_link_clicked',
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
			'sales_readiness_link_clicked',
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
				surface: 'page',
				section: 'find',
				open: true,
			}
		);

		await userEvent.click( screen.getByRole( 'button', { name: 'Hide' } ) );
		expect( mockedRecord ).toHaveBeenCalledWith(
			'sales_readiness_fold_toggle',
			{
				surface: 'page',
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
			'sales_readiness_link_clicked',
			{
				surface: 'overview',
				area: 'view_all',
			}
		);
		expect( mockedRecord ).toHaveBeenCalledWith(
			'sales_readiness_link_clicked',
			{
				surface: 'overview',
				area: 'shopping',
			}
		);
		expect( mockedRecord ).toHaveBeenCalledWith(
			'sales_readiness_link_clicked',
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

	it( 'does not claim full readiness when Fix first is empty but a check is open', () => {
		render(
			<SalesReadinessOverview
				initialView={ { ...checkedView, fix_first: null } }
			/>
		);
		expect(
			screen.getByText( /Nothing ranked to fix right now/ )
		).toBeInTheDocument();
		expect(
			screen.queryByText( /everything AI assistants need/ )
		).not.toBeInTheDocument();
	} );

	it( 'claims full readiness when every check passed', () => {
		const passed = {
			...checkedView,
			fix_first: null,
			sections: [
				{
					...checkedView.sections[ 0 ],
					rows: [
						{
							...checkedView.sections[ 0 ].rows[ 0 ],
							state: 'pass' as const,
							title: 'llms.txt is present',
						},
					],
					folded: [],
				},
			],
		};
		render( <SalesReadinessOverview initialView={ passed } /> );
		expect(
			screen.getByText( /everything AI assistants need/ )
		).toBeInTheDocument();
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
		).toContain( 'The check didn’t start. Try again in a few minutes.' );
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

describe( 'AI tools overview connection card', () => {
	beforeEach( () => mockedApiFetch.mockReset() );

	it( 'offers Connect while the readiness view is still loading', () => {
		mockedApiFetch.mockReturnValue( new Promise( () => {} ) );
		render(
			<SalesReadinessOverview
				connect={ {
					connected: false,
					notice: '',
					url: 'https://example.com/wp-admin/admin-post.php',
					action: 'woocommerce_ai_tools_connect',
					nonce: 'abc',
				} }
			/>
		);

		expect(
			screen.getByRole( 'button', { name: 'Connect to WordPress.com' } )
		).toBeInTheDocument();
	} );
} );

describe( 'Sales readiness page', () => {
	beforeEach( () => mockedApiFetch.mockReset() );

	it( 'has no Fix first card when there is nothing to fix', async () => {
		mockedApiFetch.mockResolvedValue( { ...checkedView, fix_first: null } );
		render( <SalesReadiness /> );

		await screen.findByText( 'Can AI assistants find your store?' );
		expect( screen.queryByText( 'Fix first' ) ).not.toBeInTheDocument();
		expect(
			screen.queryByText( 'Ranked by products.' )
		).not.toBeInTheDocument();
	} );
} );
