/**
 * External dependencies
 */
import { render, screen, waitFor } from '@testing-library/react';
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */
import FailedOrdersNotice from '../failed-orders-notice';
import RefundDoubleCountNotice from '../refund-double-count-notice';

jest.mock( '@wordpress/api-fetch' );

jest.mock( '@wordpress/data', () => ( {
	useDispatch: jest.fn( () => ( {
		createNotice: jest.fn(),
	} ) ),
} ) );

jest.mock( '@woocommerce/settings', () => ( {
	getAdminLink: jest.fn(
		( path: string ) => `https://example.com/wp-admin/${ path }`
	),
} ) );

jest.mock( '@wordpress/components', () => ( {
	Notice: ( { children }: { children: React.ReactNode } ) => (
		<div role="status">{ children }</div>
	),
	Button: ( { children }: { children: React.ReactNode } ) => (
		<button>{ children }</button>
	),
} ) );

const mockedApiFetch = apiFetch as jest.MockedFunction< typeof apiFetch >;

describe( 'useImportStatus', () => {
	beforeEach( () => {
		jest.clearAllMocks();
	} );

	it( 'issues a single status request when both notices mount together', async () => {
		mockedApiFetch.mockResolvedValue( {
			failed_count: 2,
			failed_overflow_count: 0,
			refund_double_count: 3,
			refund_double_count_scan_complete: true,
			refund_double_count_fix_in_progress: false,
		} );

		render(
			<>
				<FailedOrdersNotice />
				<RefundDoubleCountNotice />
			</>
		);

		expect(
			await screen.findByText( /2 orders failed to import/ )
		).toBeInTheDocument();
		expect(
			await screen.findByText(
				/3 orders have refunds that are counted twice/
			)
		).toBeInTheDocument();

		await waitFor( () =>
			expect( mockedApiFetch ).toHaveBeenCalledTimes( 1 )
		);
		expect( mockedApiFetch ).toHaveBeenCalledWith( {
			path: '/wc-analytics/imports/status',
		} );
	} );
} );
