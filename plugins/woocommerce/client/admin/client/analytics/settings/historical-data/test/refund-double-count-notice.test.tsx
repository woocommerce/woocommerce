/**
 * External dependencies
 */
import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */
import RefundDoubleCountNotice from '../refund-double-count-notice';

jest.mock( '@wordpress/api-fetch' );

const mockCreateNotice = jest.fn();
jest.mock( '@wordpress/data', () => ( {
	useDispatch: jest.fn( () => ( {
		createNotice: mockCreateNotice,
	} ) ),
} ) );

jest.mock( '@wordpress/components', () => ( {
	Notice: ( { children }: { children: React.ReactNode } ) => (
		<div role="status">{ children }</div>
	),
	Button: ( {
		children,
		onClick,
		disabled,
		'aria-disabled': ariaDisabled,
	}: {
		children: React.ReactNode;
		onClick?: () => void;
		disabled?: boolean;
		'aria-disabled'?: boolean;
	} ) => (
		<button
			onClick={ onClick }
			disabled={ disabled }
			aria-disabled={ ariaDisabled }
		>
			{ children }
		</button>
	),
} ) );

const mockedApiFetch = apiFetch as jest.MockedFunction< typeof apiFetch >;

const status = ( overrides = {} ) => ( {
	refund_double_count: 0,
	refund_double_count_scan_complete: false,
	refund_double_count_fix_in_progress: false,
	...overrides,
} );

describe( 'RefundDoubleCountNotice', () => {
	beforeEach( () => {
		jest.clearAllMocks();
	} );

	it( 'renders nothing until the scan is complete', async () => {
		mockedApiFetch.mockResolvedValue(
			status( { refund_double_count: 3 } )
		);

		const { container } = render( <RefundDoubleCountNotice /> );

		await waitFor( () =>
			expect( mockedApiFetch ).toHaveBeenCalledWith( {
				path: '/wc-analytics/imports/status',
			} )
		);
		expect( container ).toBeEmptyDOMElement();
	} );

	it( 'renders nothing when the scan found no affected orders', async () => {
		mockedApiFetch.mockResolvedValue(
			status( {
				refund_double_count: 0,
				refund_double_count_scan_complete: true,
			} )
		);

		const { container } = render( <RefundDoubleCountNotice /> );

		await waitFor( () => expect( mockedApiFetch ).toHaveBeenCalled() );
		expect( container ).toBeEmptyDOMElement();
	} );

	it( 'renders nothing while a fix is already in progress', async () => {
		mockedApiFetch.mockResolvedValue(
			status( {
				refund_double_count: 3,
				refund_double_count_scan_complete: true,
				refund_double_count_fix_in_progress: true,
			} )
		);

		const { container } = render( <RefundDoubleCountNotice /> );

		await waitFor( () => expect( mockedApiFetch ).toHaveBeenCalled() );
		expect( container ).toBeEmptyDOMElement();
	} );

	it( 'renders nothing when the status request fails', async () => {
		mockedApiFetch.mockRejectedValue( new Error( 'request failed' ) );

		const { container } = render( <RefundDoubleCountNotice /> );

		await waitFor( () => expect( mockedApiFetch ).toHaveBeenCalled() );
		expect( container ).toBeEmptyDOMElement();
	} );

	it( 'uses the singular message for a single affected order', async () => {
		mockedApiFetch.mockResolvedValue(
			status( {
				refund_double_count: 1,
				refund_double_count_scan_complete: true,
			} )
		);

		render( <RefundDoubleCountNotice /> );

		expect(
			await screen.findByText(
				/1 order has refunds that are counted twice/
			)
		).toBeInTheDocument();
	} );

	it( 'shows the count and a re-import button', async () => {
		mockedApiFetch.mockResolvedValue(
			status( {
				refund_double_count: 5,
				refund_double_count_scan_complete: true,
			} )
		);

		render( <RefundDoubleCountNotice /> );

		expect(
			await screen.findByText(
				/5 orders have refunds that are counted twice/
			)
		).toBeInTheDocument();
		expect(
			screen.getByRole( 'button', { name: 'Re-import affected orders' } )
		).toBeInTheDocument();
	} );

	it( 'schedules the fix and shows a success notice', async () => {
		mockedApiFetch.mockImplementation( ( options ) => {
			if (
				( options as { path?: string } ).path ===
				'/wc-analytics/imports/fix-refund-double-counting'
			) {
				return Promise.resolve( {
					success: true,
					message: 'Re-importing affected orders.',
				} );
			}
			return Promise.resolve(
				status( {
					refund_double_count: 5,
					refund_double_count_scan_complete: true,
				} )
			);
		} );

		render( <RefundDoubleCountNotice /> );

		await userEvent.click(
			await screen.findByRole( 'button', {
				name: 'Re-import affected orders',
			} )
		);

		await waitFor( () =>
			expect( mockedApiFetch ).toHaveBeenCalledWith( {
				path: '/wc-analytics/imports/fix-refund-double-counting',
				method: 'POST',
			} )
		);
		await waitFor( () =>
			expect( mockCreateNotice ).toHaveBeenCalledWith(
				'success',
				'Re-importing affected orders.'
			)
		);
	} );

	it( 'shows the server error message when the fix request rejects', async () => {
		mockedApiFetch.mockImplementation( ( options ) => {
			if (
				( options as { path?: string } ).path ===
				'/wc-analytics/imports/fix-refund-double-counting'
			) {
				return Promise.reject( {
					code: 'woocommerce_rest_analytics_refund_fix_in_progress',
					message: 'A fix is already in progress.',
				} );
			}
			return Promise.resolve(
				status( {
					refund_double_count: 5,
					refund_double_count_scan_complete: true,
				} )
			);
		} );

		render( <RefundDoubleCountNotice /> );

		await userEvent.click(
			await screen.findByRole( 'button', {
				name: 'Re-import affected orders',
			} )
		);

		await waitFor( () =>
			expect( mockCreateNotice ).toHaveBeenCalledWith(
				'error',
				'A fix is already in progress.'
			)
		);
	} );

	it( 'shows the busy label and disables the button while fixing', async () => {
		let resolveFix: ( value: unknown ) => void = () => {};
		mockedApiFetch.mockImplementation( ( options ) => {
			if (
				( options as { path?: string } ).path ===
				'/wc-analytics/imports/fix-refund-double-counting'
			) {
				return new Promise( ( resolve ) => {
					resolveFix = resolve;
				} );
			}
			return Promise.resolve(
				status( {
					refund_double_count: 5,
					refund_double_count_scan_complete: true,
				} )
			);
		} );

		render( <RefundDoubleCountNotice /> );

		const button = await screen.findByRole( 'button', {
			name: 'Re-import affected orders',
		} );
		await userEvent.click( button );

		expect(
			screen.getByRole( 'button', {
				name: 'Re-importing affected orders…',
			} )
		).toBeDisabled();

		resolveFix( { success: true, message: 'done' } );

		await waitFor( () =>
			expect(
				screen.getByRole( 'button', {
					name: 'Re-import affected orders',
				} )
			).not.toBeDisabled()
		);
	} );
} );
