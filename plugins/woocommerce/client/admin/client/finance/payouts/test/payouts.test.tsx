/**
 * External dependencies
 */
import {
	act,
	fireEvent,
	render,
	screen,
	waitFor,
} from '@testing-library/react';
import apiFetch from '@wordpress/api-fetch';
import { useUserPreferences } from '@woocommerce/data';
import type { ReactElement, ReactNode } from 'react';

/**
 * Internal dependencies
 */
import { FinancePayouts } from '..';
import type { FinanceProvider, Payout } from '../../types';

type CapturedProps = {
	view: { page?: number; perPage?: number; fields?: string[] };
	onChangeView: ( view: CapturedProps[ 'view' ] ) => void;
	fields: {
		id: string;
		enableSorting?: boolean;
		enableHiding?: boolean;
		filterBy?: unknown;
		render?: ( props: { item: Payout } ) => ReactElement;
	}[];
	data: Payout[];
	isLoading: boolean;
	paginationInfo: { totalItems: number; totalPages: number };
	search?: boolean;
	empty: ReactNode;
};

let captured: CapturedProps | null = null;

jest.mock( '@wordpress/dataviews/wp', () => ( {
	DataViews: ( props: CapturedProps ) => {
		captured = props;
		return <div data-testid="dataviews" />;
	},
} ) );

jest.mock( '@wordpress/api-fetch' );
jest.mock( '@woocommerce/data', () => ( {
	...jest.requireActual( '@woocommerce/data' ),
	useUserPreferences: jest.fn(),
} ) );
jest.mock( '@woocommerce/settings', () => ( {
	getAdminLink: ( path: string ) => `https://example.test/wp-admin/${ path }`,
	getSetting: () => ( {} ),
} ) );

const mockApiFetch = apiFetch as unknown as jest.Mock;
const mockUseUserPreferences = useUserPreferences as unknown as jest.Mock;
const mockUpdateUserPreferences = jest.fn( () => Promise.resolve() );

const provider = (
	id: string,
	title: string,
	dataTypes: FinanceProvider[ 'data_types' ] = [
		{ type: 'payouts', schema_version: 1 },
	]
): FinanceProvider => ( {
	provider_id: id,
	title,
	icon_url: null,
	data_types: dataTypes,
} );

const payout = ( providerId: string, id: string ): Payout => ( {
	provider_id: providerId,
	id,
	currency: 'USD',
	amount: '10.00',
	bank_account: 'Bank ••••1234',
	date_initiated: '2026-09-01T10:00:00Z',
	date_expected: null,
	status: 'complete',
	provider_status: null,
} );

const getQuery = ( path: string ) =>
	new URL( `https://example.test${ path }` ).searchParams;

const mockApi = ( providers: FinanceProvider[] ) => {
	mockApiFetch.mockImplementation( ( { path }: { path: string } ) => {
		if ( path.endsWith( '/providers' ) ) {
			return Promise.resolve( { providers } );
		}
		const providerId = path.split( '/providers/' )[ 1 ].split( '/' )[ 0 ];
		const cursor = getQuery( path ).get( 'next_cursor' );
		if ( ! cursor ) {
			return Promise.resolve( {
				schema_version: 1,
				items: [
					payout( providerId, 'po_1' ),
					payout( providerId, 'po_2' ),
				],
				has_more: true,
				next_cursor: 'cursor-2',
				prev_cursor: null,
			} );
		}
		return Promise.resolve( {
			schema_version: 1,
			items: [ payout( providerId, 'po_3' ) ],
			has_more: false,
			next_cursor: null,
			prev_cursor: 'cursor-1',
		} );
	} );
};

const payoutRequests = () =>
	mockApiFetch.mock.calls
		.map( ( [ { path } ] ) => path as string )
		.filter( ( path ) => path.includes( '/payouts' ) );

describe( 'FinancePayouts', () => {
	beforeEach( () => {
		jest.clearAllMocks();
		captured = null;
		mockUseUserPreferences.mockReturnValue( {
			updateUserPreferences: mockUpdateUserPreferences,
		} );
	} );

	it( 'shows the empty state when no provider supports payouts', async () => {
		mockApi( [
			provider( 'bacs', 'Bank transfer', [
				{ type: 'balance', schema_version: 1 },
			] ),
		] );

		render( <FinancePayouts /> );

		expect(
			await screen.findByText( 'No payout providers' )
		).toBeInTheDocument();
		expect( screen.queryByTestId( 'dataviews' ) ).not.toBeInTheDocument();
	} );

	it( 'renders a read-only table for the first page of the default provider', async () => {
		mockApi( [ provider( 'bacs', 'Bank transfer' ) ] );

		render( <FinancePayouts /> );

		await waitFor( () => expect( captured?.data ).toHaveLength( 2 ) );

		expect( captured?.search ).toBe( false );
		expect(
			captured?.fields.every( ( field ) => field.enableSorting === false )
		).toBe( true );
		expect(
			captured?.fields.every( ( field ) => field.filterBy === false )
		).toBe( true );
		expect( captured?.view.page ).toBe( 1 );
		expect( captured?.paginationInfo ).toEqual( {
			totalItems: 3,
			totalPages: 2,
		} );
		expect( payoutRequests() ).toEqual( [
			'/wc-admin/payments/finance/providers/bacs/payouts?per_page=10',
		] );
		expect(
			screen.getByRole( 'button', { name: /Payout provider/ } )
		).toBeDisabled();
	} );

	it( 'pins the provider and payout id as the first non-hideable columns', async () => {
		mockApi( [ provider( 'bacs', 'Bank transfer' ) ] );

		render( <FinancePayouts /> );
		await waitFor( () => expect( captured?.data ).toHaveLength( 2 ) );

		expect( captured?.view.fields?.slice( 0, 2 ) ).toEqual( [
			'provider',
			'id',
		] );
		[ 'provider', 'id' ].forEach( ( id ) => {
			expect(
				captured?.fields.find( ( field ) => field.id === id )
					?.enableHiding
			).toBe( false );
		} );

		act(
			() =>
				captured?.onChangeView( {
					...captured.view,
					fields: [ 'amount', 'id', 'status', 'provider' ],
				} )
		);

		expect( captured?.view.fields ).toEqual( [
			'provider',
			'id',
			'amount',
			'status',
		] );
	} );

	it( 'paints the page white only while mounted', async () => {
		mockApi( [ provider( 'bacs', 'Bank transfer' ) ] );

		const { unmount } = render( <FinancePayouts /> );

		expect( document.body ).toHaveClass(
			'woocommerce-finance-payouts-page'
		);
		unmount();
		expect( document.body ).not.toHaveClass(
			'woocommerce-finance-payouts-page'
		);
	} );

	it( 'fetches the next page with the stored cursor and can go back', async () => {
		mockApi( [ provider( 'bacs', 'Bank transfer' ) ] );

		render( <FinancePayouts /> );
		await waitFor( () => expect( captured?.data ).toHaveLength( 2 ) );

		act( () => captured?.onChangeView( { ...captured.view, page: 2 } ) );
		await waitFor( () => expect( captured?.data ).toHaveLength( 1 ) );

		expect( captured?.view.page ).toBe( 2 );
		expect( captured?.paginationInfo ).toEqual( {
			totalItems: 11,
			totalPages: 2,
		} );
		expect( payoutRequests()[ 1 ] ).toBe(
			'/wc-admin/payments/finance/providers/bacs/payouts?per_page=10&next_cursor=cursor-2'
		);

		act( () => captured?.onChangeView( { ...captured.view, page: 1 } ) );
		await waitFor( () => expect( captured?.data ).toHaveLength( 2 ) );

		expect( captured?.view.page ).toBe( 1 );
		expect( payoutRequests()[ 2 ] ).toBe(
			'/wc-admin/payments/finance/providers/bacs/payouts?per_page=10'
		);
	} );

	it( 'ignores a jump to a page without a known cursor', async () => {
		mockApi( [ provider( 'bacs', 'Bank transfer' ) ] );

		render( <FinancePayouts /> );
		await waitFor( () => expect( captured?.data ).toHaveLength( 2 ) );

		act( () => captured?.onChangeView( { ...captured.view, page: 5 } ) );

		expect( captured?.view.page ).toBe( 1 );
		expect( payoutRequests() ).toHaveLength( 1 );
	} );

	it( 'restarts from the first page when the page size changes', async () => {
		mockApi( [ provider( 'bacs', 'Bank transfer' ) ] );

		render( <FinancePayouts /> );
		await waitFor( () => expect( captured?.data ).toHaveLength( 2 ) );
		act( () => captured?.onChangeView( { ...captured.view, page: 2 } ) );
		await waitFor( () => expect( captured?.data ).toHaveLength( 1 ) );

		act(
			() =>
				captured?.onChangeView( {
					...captured.view,
					perPage: 25,
					page: 2,
				} )
		);

		await waitFor( () =>
			expect( payoutRequests()[ 2 ] ).toBe(
				'/wc-admin/payments/finance/providers/bacs/payouts?per_page=25'
			)
		);
		expect( captured?.view.page ).toBe( 1 );
		expect( captured?.view.perPage ).toBe( 25 );
	} );

	it( 'switches provider, restarts pagination and persists the choice', async () => {
		mockApi( [
			provider( 'bacs', 'Bank transfer' ),
			provider( 'cheque', 'Cheque' ),
		] );

		render( <FinancePayouts /> );
		await waitFor( () => expect( captured?.data ).toHaveLength( 2 ) );
		act( () => captured?.onChangeView( { ...captured.view, page: 2 } ) );
		await waitFor( () => expect( captured?.data ).toHaveLength( 1 ) );

		const toggle = screen.getByRole( 'button', {
			name: /Payout provider/,
		} );
		expect( toggle ).toBeEnabled();
		expect( toggle ).toHaveTextContent( 'Bank transfer' );
		fireEvent.click( toggle );
		fireEvent.click(
			await screen.findByRole( 'menuitemradio', { name: 'Cheque' } )
		);

		await waitFor( () =>
			expect( payoutRequests()[ 2 ] ).toBe(
				'/wc-admin/payments/finance/providers/cheque/payouts?per_page=10'
			)
		);
		expect( captured?.view.page ).toBe( 1 );
		expect( mockUpdateUserPreferences ).toHaveBeenCalledWith( {
			payments_finance_last_provider: 'cheque',
		} );
	} );

	it( 'starts on the stored provider', async () => {
		mockUseUserPreferences.mockReturnValue( {
			payments_finance_last_provider: 'cheque',
			updateUserPreferences: mockUpdateUserPreferences,
		} );
		mockApi( [
			provider( 'bacs', 'Bank transfer' ),
			provider( 'cheque', 'Cheque' ),
		] );

		render( <FinancePayouts /> );

		await waitFor( () =>
			expect( payoutRequests()[ 0 ] ).toBe(
				'/wc-admin/payments/finance/providers/cheque/payouts?per_page=10'
			)
		);
	} );

	it( 'opens the details drawer when the payout id is clicked', async () => {
		mockApi( [ provider( 'bacs', 'Bank transfer' ) ] );

		render( <FinancePayouts /> );
		await waitFor( () => expect( captured?.data ).toHaveLength( 2 ) );

		// The DataViews mock renders no cells, so mount the id cell on its own and click it.
		const IdCell = captured?.fields.find( ( field ) => field.id === 'id' )
			?.render as ( props: { item: Payout } ) => ReactElement;
		render( <IdCell item={ captured?.data[ 1 ] as Payout } /> );
		fireEvent.click( screen.getByRole( 'button', { name: 'po_2' } ) );

		const dialog = screen.getByRole( 'dialog', { name: 'Payout details' } );
		expect( dialog ).toHaveTextContent( 'po_2' );
		expect( dialog ).toHaveTextContent( 'Bank ••••1234' );
		expect( dialog ).toHaveTextContent( 'Completed' );
	} );

	it( 'shows a payouts error above the table', async () => {
		mockApiFetch.mockImplementation( ( { path }: { path: string } ) =>
			path.endsWith( '/providers' )
				? Promise.resolve( {
						providers: [ provider( 'bacs', 'Bank transfer' ) ],
				  } )
				: Promise.reject( {
						code: 'woocommerce_rest_payments_finance_provider_error',
						message: 'Bank transfer is unavailable.',
				  } )
		);

		render( <FinancePayouts /> );

		// The message is also announced in a live region, so match the visible notice.
		expect(
			(
				await screen.findAllByText( 'Bank transfer is unavailable.' )
			).some( ( element ) => element.closest( '.components-notice' ) )
		).toBe( true );
		expect( screen.getByTestId( 'dataviews' ) ).toBeInTheDocument();
	} );
} );
