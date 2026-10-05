import {
	beforeEach,
	describe,
	expect,
	it,
	vi,
	type MockedFunction,
} from 'vitest';
const { mockCreateNotice } = vi.hoisted( () => {
	const mockCreateNotice = vi.fn();
	return {
		mockCreateNotice,
	};
} );

/**
 * External dependencies
 */
import { render, screen, waitFor, act } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */
import FailedOrdersNotice from '../failed-orders-notice';
vi.mock( '@wordpress/api-fetch' );
vi.mock( '@wordpress/data', () => {
	const mock = {
		useDispatch: vi.fn( () => ( {
			createNotice: mockCreateNotice,
		} ) ),
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
vi.mock( '@woocommerce/settings', () => {
	const mock = {
		getAdminLink: vi.fn(
			( path: string ) => `https://example.com/wp-admin/${ path }`
		),
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
vi.mock( '@wordpress/components', () => {
	const mock = {
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
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
const mockedApiFetch = apiFetch as MockedFunction< typeof apiFetch >;
describe( 'FailedOrdersNotice', () => {
	beforeEach( () => {
		vi.clearAllMocks();
	} );
	it( 'renders nothing when there are no failed orders', async () => {
		mockedApiFetch.mockResolvedValue( {
			failed_count: 0,
			failed_overflow_count: 0,
		} );
		const { container } = render( <FailedOrdersNotice /> );
		await waitFor( () =>
			expect( mockedApiFetch ).toHaveBeenCalledWith( {
				path: '/wc-analytics/imports/status',
			} )
		);
		expect( container ).toBeEmptyDOMElement();
	} );
	it( 'renders nothing when the status request fails', async () => {
		mockedApiFetch.mockRejectedValue( new Error( 'request failed' ) );
		const { container } = render( <FailedOrdersNotice /> );
		await waitFor( () => expect( mockedApiFetch ).toHaveBeenCalled() );
		expect( container ).toBeEmptyDOMElement();
	} );
	it( 'shows the failed count and a retry button', async () => {
		mockedApiFetch.mockResolvedValue( {
			failed_count: 3,
			failed_overflow_count: 0,
		} );
		render( <FailedOrdersNotice /> );
		expect(
			await screen.findByText( /3 orders failed to import/ )
		).toBeInTheDocument();
		expect(
			screen.getByRole( 'button', {
				name: 'Retry failed imports',
			} )
		).toBeInTheDocument();
		expect(
			screen.getByRole( 'link', {
				name: 'View the order import log',
			} )
		).toHaveAttribute(
			'href',
			'https://example.com/wp-admin/admin.php?page=wc-status&tab=logs&source=wc-analytics-order-import'
		);
	} );
	it( 'shows overflow guidance when the stored list overflowed', async () => {
		mockedApiFetch.mockResolvedValue( {
			failed_count: 1000,
			failed_overflow_count: 5,
		} );
		render( <FailedOrdersNotice /> );
		expect(
			await screen.findByText( /More than 1000 orders failed to import/ )
		).toBeInTheDocument();
	} );
	it( 'schedules a retry and shows a success notice', async () => {
		mockedApiFetch.mockImplementation( ( options ) => {
			if (
				(
					options as {
						path?: string;
					}
				 ).path === '/wc-analytics/imports/retry-failed'
			) {
				return Promise.resolve( {
					success: true,
					message: 'Re-import scheduled for 3 orders.',
					retried_count: 3,
					pruned_count: 0,
					already_scheduled_count: 0,
					error_count: 0,
				} );
			}
			return Promise.resolve( {
				failed_count: 3,
				failed_overflow_count: 0,
			} );
		} );
		render( <FailedOrdersNotice /> );
		const _element = await screen.findByRole( 'button', {
			name: 'Retry failed imports',
		} );
		await act( async () => {
			await userEvent.click( _element );
		} );
		await waitFor( () =>
			expect( mockedApiFetch ).toHaveBeenCalledWith( {
				path: '/wc-analytics/imports/retry-failed',
				method: 'POST',
			} )
		);
		await waitFor( () =>
			expect( mockCreateNotice ).toHaveBeenCalledWith(
				'success',
				'Re-import scheduled for 3 orders.'
			)
		);
	} );
	it( 'shows the server message when the retry request rejects with a REST error object', async () => {
		mockedApiFetch.mockImplementation( ( options ) => {
			if (
				(
					options as {
						path?: string;
					}
				 ).path === '/wc-analytics/imports/retry-failed'
			) {
				// @wordpress/api-fetch rejects with the parsed REST error
				// object — a plain object, not an Error instance.
				return Promise.reject( {
					code: 'woocommerce_rest_analytics_retry_failed',
					message:
						'The failed orders could not be scheduled for re-import. Check the order import log for details.',
				} );
			}
			return Promise.resolve( {
				failed_count: 3,
				failed_overflow_count: 0,
			} );
		} );
		render( <FailedOrdersNotice /> );
		const _element2 = await screen.findByRole( 'button', {
			name: 'Retry failed imports',
		} );
		await act( async () => {
			await userEvent.click( _element2 );
		} );
		await waitFor( () =>
			expect( mockCreateNotice ).toHaveBeenCalledWith(
				'error',
				'The failed orders could not be scheduled for re-import. Check the order import log for details.'
			)
		);
	} );
	it( 'shows a fallback message when the retry rejection has no message', async () => {
		mockedApiFetch.mockImplementation( ( options ) => {
			if (
				(
					options as {
						path?: string;
					}
				 ).path === '/wc-analytics/imports/retry-failed'
			) {
				return Promise.reject( {
					code: 'fetch_error',
				} );
			}
			return Promise.resolve( {
				failed_count: 3,
				failed_overflow_count: 0,
			} );
		} );
		render( <FailedOrdersNotice /> );
		const _element3 = await screen.findByRole( 'button', {
			name: 'Retry failed imports',
		} );
		await act( async () => {
			await userEvent.click( _element3 );
		} );
		await waitFor( () =>
			expect( mockCreateNotice ).toHaveBeenCalledWith(
				'error',
				'Failed to retry order imports.'
			)
		);
	} );
	it( 'disables the retry button while the retry request is in flight', async () => {
		let resolveRetry: ( value: unknown ) => void = () => {};
		mockedApiFetch.mockImplementation( ( options ) => {
			if (
				(
					options as {
						path?: string;
					}
				 ).path === '/wc-analytics/imports/retry-failed'
			) {
				return new Promise( ( resolve ) => {
					resolveRetry = resolve;
				} );
			}
			return Promise.resolve( {
				failed_count: 3,
				failed_overflow_count: 0,
			} );
		} );
		render( <FailedOrdersNotice /> );
		const button = await screen.findByRole( 'button', {
			name: 'Retry failed imports',
		} );
		await act( async () => {
			await userEvent.click( button );
		} );
		expect( button ).toBeDisabled();
		resolveRetry( {
			success: true,
			message: 'Re-import scheduled for 3 orders.',
			retried_count: 3,
			pruned_count: 0,
			already_scheduled_count: 0,
			error_count: 0,
		} );
		await waitFor( () => expect( button ).not.toBeDisabled() );
	} );
} );
