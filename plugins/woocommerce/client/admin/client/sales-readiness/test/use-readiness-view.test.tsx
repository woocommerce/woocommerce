/**
 * External dependencies
 */
import { act, render, renderHook, screen } from '@testing-library/react';
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */
import {
	POLL_FOR_MS,
	POLL_MS,
	SCAN_PATH,
	useReadinessView,
} from '../use-readiness-view';
import { RunStatus } from '../components/run-status';
import { ErrorNotice } from '../components/error-notice';

jest.mock( '@wordpress/api-fetch', () => jest.fn() );
jest.mock( '@woocommerce/tracks', () => ( { recordEvent: jest.fn() } ) );
const mockFetch = apiFetch as unknown as jest.Mock;

const view = ( pending: boolean, label = 'Run check' ) => ( {
	sections: [],
	fix_first: null,
	run: { label, pending, stalled: false, message: '', link: null },
} );

const viewCalls = () =>
	mockFetch.mock.calls.filter( ( [ args ] ) => args.path !== SCAN_PATH )
		.length;

describe( 'useReadinessView', () => {
	beforeEach( () => {
		jest.useFakeTimers();
		mockFetch.mockReset();
		// Tracks calls from one test must not reach the next one's assertions.
		jest.requireMock( '@woocommerce/tracks' ).recordEvent.mockClear();
	} );
	afterEach( () => jest.useRealTimers() );

	it( 'reads the view again while a check is pending, and shows the results once it finishes', async () => {
		mockFetch
			.mockResolvedValueOnce( view( false ) )
			.mockResolvedValueOnce( {} )
			.mockResolvedValueOnce( view( true ) )
			.mockResolvedValueOnce( view( true ) )
			.mockResolvedValueOnce( view( false, 'Check again' ) );
		const { result } = renderHook( () => useReadinessView( 'page' ) );
		await act( async () => jest.advanceTimersByTime( 0 ) );
		await act( async () => result.current.runCheck() );
		expect( result.current.view?.run.pending ).toBe( true );

		await act( async () => jest.advanceTimersByTime( POLL_MS ) );
		expect( result.current.view?.run.pending ).toBe( true );
		await act( async () => jest.advanceTimersByTime( POLL_MS ) );
		expect( result.current.view?.run.label ).toBe( 'Check again' );

		await act( async () => jest.advanceTimersByTime( POLL_MS * 3 ) );
		expect( viewCalls() ).toBe( 4 );
	} );

	it( 'uses a view that came with the page without loading it again', async () => {
		const initial = view( false, 'Check again' ) as never;
		const { result } = renderHook( () =>
			useReadinessView( 'overview', initial )
		);
		await act( async () => jest.advanceTimersByTime( POLL_MS ) );
		expect( result.current.view ).toBe( initial );
		expect( viewCalls() ).toBe( 0 );
	} );

	it( 'polls a pending view that came with the page until the check finishes', async () => {
		mockFetch.mockImplementation( async () =>
			view( false, 'Check again' )
		);
		const { result } = renderHook( () =>
			useReadinessView( 'page', view( true ) as never )
		);
		await act( async () => jest.advanceTimersByTime( POLL_MS ) );
		expect( result.current.view?.run.label ).toBe( 'Check again' );
		expect( viewCalls() ).toBe( 1 );
	} );

	it( 'does not poll when nothing is pending', async () => {
		mockFetch.mockImplementation( async () => view( false ) );
		renderHook( () => useReadinessView( 'page' ) );
		await act( async () => jest.advanceTimersByTime( POLL_MS * 3 ) );
		expect( viewCalls() ).toBe( 1 );
	} );

	it( 'stops polling a check that stays pending', async () => {
		mockFetch.mockImplementation( async () => view( true ) );
		renderHook( () => useReadinessView( 'page' ) );
		await act( async () => jest.advanceTimersByTime( 0 ) );
		for ( let i = 0; i < POLL_FOR_MS / POLL_MS + 5; i++ ) {
			await act( async () => jest.advanceTimersByTime( POLL_MS ) );
		}
		// The first load, then one poll every POLL_MS until POLL_FOR_MS has passed.
		expect( viewCalls() ).toBe( POLL_FOR_MS / POLL_MS );
	} );

	it( 'offers Reload page when the results did not load', async () => {
		mockFetch.mockRejectedValueOnce( new Error( 'offline' ) );
		const { result } = renderHook( () => useReadinessView( 'page' ) );
		await act( async () => {} );
		expect( result.current.error ).toBe(
			'Your check results didn’t load. Reload the page to try again.'
		);
		expect( result.current.reload ).toBe( true );
	} );

	it( 'stops offering Reload page once the results load', async () => {
		mockFetch.mockRejectedValueOnce( new Error( 'offline' ) );
		const { result } = renderHook( () => useReadinessView( 'page' ) );
		await act( async () => {} );
		expect( result.current.reload ).toBe( true );
		mockFetch.mockResolvedValueOnce( {} );
		mockFetch.mockResolvedValueOnce( view( false ) );
		await act( async () => {
			await result.current.runCheck();
		} );
		expect( result.current.reload ).toBe( false );
		expect( result.current.error ).toBeNull();
	} );

	it( 'does not offer Reload page when Check again fails, since the results and the button are still there', async () => {
		mockFetch.mockResolvedValueOnce( view( false ) );
		const { result } = renderHook( () => useReadinessView( 'page' ) );
		await act( async () => {} );
		mockFetch.mockRejectedValueOnce( new Error( 'refused' ) );
		await act( async () => {
			await result.current.runCheck();
		} );
		expect( result.current.error ).toBe(
			'The check didn’t start. Try again in a few minutes.'
		);
		expect( result.current.reload ).toBe( false );
	} );

	it( 'shows Reload page in the notice only when asked', () => {
		const { rerender } = render(
			<ErrorNotice message="Your check results didn’t load." reload />
		);
		expect(
			screen.getByRole( 'button', { name: 'Reload page' } )
		).toBeInTheDocument();
		rerender(
			<ErrorNotice message="The check didn’t start." reload={ false } />
		);
		expect(
			screen.queryByRole( 'button', { name: 'Reload page' } )
		).not.toBeInTheDocument();
	} );

	it( 'does not record a load error or clear the page when a poll fails', async () => {
		const { recordEvent } = jest.requireMock( '@woocommerce/tracks' );
		mockFetch
			.mockResolvedValueOnce( view( true ) )
			.mockRejectedValueOnce( new Error( 'network' ) );
		const { result } = renderHook( () => useReadinessView( 'page' ) );
		await act( async () => jest.advanceTimersByTime( 0 ) );
		expect( result.current.view?.run.pending ).toBe( true );

		await act( async () => jest.advanceTimersByTime( POLL_MS ) );
		expect( result.current.view?.run.pending ).toBe( true );
		expect( result.current.error ).toBeNull();
		expect( recordEvent ).not.toHaveBeenCalledWith(
			'sales_readiness_error',
			expect.objectContaining( { action: 'load' } )
		);
	} );

	it( 'does not record viewed after unmount', async () => {
		const { recordEvent } = jest.requireMock( '@woocommerce/tracks' );
		let finish: ( value: unknown ) => void = () => {};
		mockFetch.mockImplementationOnce(
			() =>
				new Promise( ( resolve ) => {
					finish = resolve;
				} )
		);
		const { unmount } = renderHook( () => useReadinessView( 'overview' ) );
		unmount();
		await act( async () => {
			finish( view( false ) );
		} );
		expect( recordEvent ).not.toHaveBeenCalledWith(
			'sales_readiness_viewed',
			expect.anything()
		);
	} );
} );

describe( 'RunStatus', () => {
	const run = ( pending: boolean, stalled: boolean, message: string ) => ( {
		label: 'Run check',
		pending,
		stalled,
		message,
		link: null,
	} );

	it( 'shows no spinner while a check runs, only its message for screen readers', () => {
		const { container } = render(
			<RunStatus run={ run( true, false, 'Checking your store.' ) } />
		);
		expect( container.querySelector( '.components-spinner' ) ).toBeNull();
		expect( screen.getByRole( 'status' ) ).toHaveTextContent(
			'Checking your store.'
		);
		expect(
			container.querySelector( '.wc-sales-readiness__muted' )
		).toBeNull();
	} );

	it( 'shows the message once the check is stuck', () => {
		const { container } = render(
			<RunStatus run={ run( true, true, 'Not started.' ) } />
		);
		expect( container.querySelector( '.components-spinner' ) ).toBeNull();
		expect( screen.getByText( 'Not started.' ) ).toBeVisible();
	} );
} );
