/**
 * External dependencies
 */
import { renderHook, act } from '@testing-library/react-hooks';
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */
import { QRLoginTokenStates, useQRLoginToken } from '../useQRLoginToken';

jest.mock( '@wordpress/api-fetch' );

const mockApiFetch = apiFetch as unknown as jest.MockedFunction<
	( options: { path: string; method: string } ) => Promise< unknown >
>;

// A fixed "now" keeps the countdown math deterministic across tests.
const NOW_SECONDS = 1_700_000_000;
const TTL_SECONDS = 300;

const buildResponse = ( ttl: number = TTL_SECONDS ) => ( {
	qr_url: `woocommerce://qr-login?token=abc&siteUrl=https%3A%2F%2Fexample.test&ttl=${ ttl }`,
	expires_at: NOW_SECONDS + ttl,
	ttl,
} );

// Mock `wpcom_account_required` is intentionally *not* listed here — the
// backend no longer returns it after WOOMOB-2764 and the hook no longer
// branches on it. If it ever shows up again we want it to fall through to
// the generic message (verified by the `unknown error code` test).
const expectedErrorMessages: Array< {
	code: string;
	message: RegExp;
} > = [
	{
		code: 'woocommerce_rest_cannot_view',
		message: /do not have permission to generate a QR login code/i,
	},
	{
		code: 'ssl_required',
		message: /requires an HTTPS connection/i,
	},
	{
		code: 'application_passwords_unavailable',
		message: /Application passwords are disabled/i,
	},
	{
		code: 'rate_limit_exceeded',
		message: /Too many QR login requests/i,
	},
];

describe( 'useQRLoginToken', () => {
	beforeEach( () => {
		jest.clearAllMocks();
		jest.useFakeTimers();
		// Pin Date.now so the countdown math is deterministic.
		jest.setSystemTime( NOW_SECONDS * 1000 );
	} );

	afterEach( () => {
		// Clear rather than run pending timers — otherwise the interval
		// callback fires against (potentially unmounted) test hooks and
		// React emits an "update not wrapped in act()" warning.
		jest.clearAllTimers();
		jest.useRealTimers();
	} );

	it( 'starts IDLE with empty state', () => {
		mockApiFetch.mockResolvedValue( buildResponse() );

		const { result } = renderHook( () => useQRLoginToken() );

		expect( result.current.state ).toBe( QRLoginTokenStates.IDLE );
		expect( result.current.qrUrl ).toBeNull();
		expect( result.current.secondsRemaining ).toBe( 0 );
		expect( result.current.errorMessage ).toBeNull();
	} );

	it( 'transitions IDLE → LOADING → READY on successful fetch', async () => {
		const response = buildResponse();
		mockApiFetch.mockResolvedValue( response );

		const { result } = renderHook( () => useQRLoginToken() );

		expect( result.current.state ).toBe( QRLoginTokenStates.IDLE );

		let fetchPromise: Promise< void > | undefined;
		act( () => {
			fetchPromise = result.current.fetchToken();
		} );

		// Synchronously after kicking off the fetch we should be LOADING.
		expect( result.current.state ).toBe( QRLoginTokenStates.LOADING );

		await act( async () => {
			await fetchPromise;
		} );

		expect( mockApiFetch ).toHaveBeenCalledWith( {
			path: '/wc-admin/mobile-app/qr-login-token',
			method: 'POST',
		} );
		expect( result.current.state ).toBe( QRLoginTokenStates.READY );
		expect( result.current.qrUrl ).toBe( response.qr_url );
		expect( result.current.errorMessage ).toBeNull();
		expect( result.current.secondsRemaining ).toBe( TTL_SECONDS );
	} );

	it( 'decrements secondsRemaining each second and transitions to EXPIRED at 0', async () => {
		mockApiFetch.mockResolvedValue( buildResponse( 3 ) );

		const { result } = renderHook( () => useQRLoginToken() );

		await act( async () => {
			await result.current.fetchToken();
		} );

		expect( result.current.state ).toBe( QRLoginTokenStates.READY );
		expect( result.current.secondsRemaining ).toBe( 3 );

		// `advanceTimersByTime` bumps the mocked clock *and* runs any
		// interval callbacks whose due-time falls inside the advance
		// window, so we don't need to call `setSystemTime` separately.
		act( () => {
			jest.advanceTimersByTime( 1000 );
		} );
		expect( result.current.secondsRemaining ).toBe( 2 );
		expect( result.current.state ).toBe( QRLoginTokenStates.READY );

		act( () => {
			jest.advanceTimersByTime( 1000 );
		} );
		expect( result.current.secondsRemaining ).toBe( 1 );
		expect( result.current.state ).toBe( QRLoginTokenStates.READY );

		act( () => {
			jest.advanceTimersByTime( 1000 );
		} );
		expect( result.current.secondsRemaining ).toBe( 0 );
		expect( result.current.state ).toBe( QRLoginTokenStates.EXPIRED );
		expect( result.current.qrUrl ).toBeNull();
	} );

	it.each( expectedErrorMessages )(
		'maps backend error code "$code" to the right user-facing message',
		async ( { code, message } ) => {
			mockApiFetch.mockRejectedValue( {
				code,
				message: `Backend said ${ code }`,
			} );

			const { result } = renderHook( () => useQRLoginToken() );

			await act( async () => {
				await result.current.fetchToken();
			} );

			expect( result.current.state ).toBe( QRLoginTokenStates.ERROR );
			expect( result.current.qrUrl ).toBeNull();
			expect( result.current.errorMessage ).toMatch( message );
		}
	);

	it( 'falls back to the backend-provided message for unknown error codes', async () => {
		mockApiFetch.mockRejectedValue( {
			code: 'something_unexpected',
			message: 'Specific backend error text.',
		} );

		const { result } = renderHook( () => useQRLoginToken() );

		await act( async () => {
			await result.current.fetchToken();
		} );

		expect( result.current.state ).toBe( QRLoginTokenStates.ERROR );
		expect( result.current.errorMessage ).toBe(
			'Specific backend error text.'
		);
	} );

	it( 'falls back to a generic message when the error has neither a code nor a message', async () => {
		mockApiFetch.mockRejectedValue( {} );

		const { result } = renderHook( () => useQRLoginToken() );

		await act( async () => {
			await result.current.fetchToken();
		} );

		expect( result.current.state ).toBe( QRLoginTokenStates.ERROR );
		expect( result.current.errorMessage ).toMatch(
			/Failed to generate QR login code/i
		);
	} );

	it( 'clears the previous error message when starting a new fetch', async () => {
		mockApiFetch.mockRejectedValueOnce( {
			code: 'ssl_required',
			message: 'SSL required',
		} );

		const { result } = renderHook( () => useQRLoginToken() );

		await act( async () => {
			await result.current.fetchToken();
		} );
		expect( result.current.state ).toBe( QRLoginTokenStates.ERROR );
		expect( result.current.errorMessage ).not.toBeNull();

		// Refetch with a successful response.
		mockApiFetch.mockResolvedValueOnce( buildResponse() );

		let retryPromise: Promise< void > | undefined;
		act( () => {
			retryPromise = result.current.refreshToken();
		} );
		expect( result.current.state ).toBe( QRLoginTokenStates.LOADING );
		expect( result.current.errorMessage ).toBeNull();

		await act( async () => {
			await retryPromise;
		} );
		expect( result.current.state ).toBe( QRLoginTokenStates.READY );
	} );

	it( 'refetch after EXPIRED yields a fresh token (EXPIRED → LOADING → READY)', async () => {
		mockApiFetch.mockResolvedValueOnce( buildResponse( 1 ) );

		const { result } = renderHook( () => useQRLoginToken() );

		await act( async () => {
			await result.current.fetchToken();
		} );

		// Expire the current token.
		act( () => {
			jest.advanceTimersByTime( 1000 );
		} );
		expect( result.current.state ).toBe( QRLoginTokenStates.EXPIRED );

		// Second fetch returns a new token. Build the response relative to
		// the mocked "now" the hook will see at resolution time so the
		// countdown math is unambiguous.
		const freshNowSeconds = Math.floor( Date.now() / 1000 );
		const secondResponse = {
			qr_url: 'woocommerce://qr-login?token=second&siteUrl=x',
			expires_at: freshNowSeconds + TTL_SECONDS,
			ttl: TTL_SECONDS,
		};
		mockApiFetch.mockResolvedValueOnce( secondResponse );

		let refetchPromise: Promise< void > | undefined;
		act( () => {
			refetchPromise = result.current.refreshToken();
		} );
		expect( result.current.state ).toBe( QRLoginTokenStates.LOADING );

		await act( async () => {
			await refetchPromise;
		} );

		expect( result.current.state ).toBe( QRLoginTokenStates.READY );
		expect( result.current.qrUrl ).toBe( secondResponse.qr_url );
		expect( result.current.secondsRemaining ).toBe( TTL_SECONDS );
		expect( mockApiFetch ).toHaveBeenCalledTimes( 2 );
	} );

	it( 'failed refetch clears the previous token and keeps the error visible', async () => {
		const firstResponse = buildResponse( 5 );
		mockApiFetch.mockResolvedValueOnce( firstResponse );

		const { result } = renderHook( () => useQRLoginToken() );

		await act( async () => {
			await result.current.fetchToken();
		} );

		expect( result.current.state ).toBe( QRLoginTokenStates.READY );
		expect( result.current.qrUrl ).toBe( firstResponse.qr_url );
		expect( result.current.secondsRemaining ).toBe( 5 );

		mockApiFetch.mockRejectedValueOnce( {
			code: 'rate_limit_exceeded',
			message: 'Too many requests',
		} );

		let refetchPromise: Promise< void > | undefined;
		act( () => {
			refetchPromise = result.current.refreshToken();
		} );

		expect( result.current.state ).toBe( QRLoginTokenStates.LOADING );
		expect( result.current.qrUrl ).toBeNull();
		expect( result.current.secondsRemaining ).toBe( 0 );

		await act( async () => {
			await refetchPromise;
		} );

		expect( result.current.state ).toBe( QRLoginTokenStates.ERROR );
		expect( result.current.qrUrl ).toBeNull();
		expect( result.current.secondsRemaining ).toBe( 0 );
		expect( result.current.errorMessage ).toMatch(
			/Too many QR login requests/i
		);

		act( () => {
			jest.advanceTimersByTime( 5000 );
		} );

		expect( result.current.state ).toBe( QRLoginTokenStates.ERROR );
	} );

	it( 'does not start a countdown after unmount during LOADING', async () => {
		let resolveFetch: ( value: unknown ) => void = () => undefined;
		const pendingResponse = new Promise( ( resolve ) => {
			resolveFetch = resolve;
		} );
		mockApiFetch.mockReturnValueOnce( pendingResponse );
		const setIntervalSpy = jest.spyOn( global, 'setInterval' );

		const { result, unmount } = renderHook( () => useQRLoginToken() );

		act( () => {
			// Fire off the request but don't await it yet.
			void result.current.fetchToken();
		} );
		expect( result.current.state ).toBe( QRLoginTokenStates.LOADING );

		// Unmount while still LOADING.
		unmount();

		// Now let the request resolve; the hook should not attempt to
		// update state on the unmounted component.
		await act( async () => {
			resolveFetch( buildResponse() );
			await pendingResponse;
		} );

		expect( setIntervalSpy ).not.toHaveBeenCalled();

		setIntervalSpy.mockRestore();
	} );

	it( 'cleans up the countdown interval on unmount', async () => {
		mockApiFetch.mockResolvedValue( buildResponse( 5 ) );
		const clearIntervalSpy = jest.spyOn( global, 'clearInterval' );

		const { result, unmount } = renderHook( () => useQRLoginToken() );

		await act( async () => {
			await result.current.fetchToken();
		} );
		expect( result.current.state ).toBe( QRLoginTokenStates.READY );

		unmount();

		expect( clearIntervalSpy ).toHaveBeenCalled();
		clearIntervalSpy.mockRestore();
	} );
} );
