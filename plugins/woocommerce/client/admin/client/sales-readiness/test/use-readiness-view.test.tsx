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
		expect( viewCalls() ).toBe( POLL_FOR_MS / POLL_MS + 1 );
	} );
} );

describe( 'RunStatus', () => {
	const run = ( pending: boolean, stalled: boolean, message: string ) => ( {
		label: 'Run check',
		pending,
		stalled,
		message,
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
