/**
 * External dependencies
 */
import { renderHook, act } from '@testing-library/react-hooks/dom';
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

const buildResponse = ( ttl: number = TTL_SECONDS, token = 'abc' ) => ( {
	qr_url: `woocommerce://qr-login?token=${ token }&siteUrl=https%3A%2F%2Fexample.test&ttl=${ ttl }`,
	expires_at: NOW_SECONDS + ttl,
	ttl,
} );

// Mock `wpcom_account_required` is intentionally *not* listed here — the
// backend no longer returns it after WOOMOB-2764 and the hook no longer
// branches on it. If it ever shows up again we want it to fall through to
// the generic message (verified by the `unknown error code` test).
//
// `application_passwords_unavailable` is intentionally *not* listed either —
// its message is a `ReactNode` (it embeds an inline link to the WordPress
// docs) rather than a plain string, so it has its own dedicated test below
// that asserts on `errorCode` + structural properties of the message.
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
		code: 'rate_limit_exceeded',
		message: /requested QR login codes too quickly/i,
	},
];

describe( 'useQRLoginToken', () => {
	beforeEach( () => {
		jest.clearAllMocks();
		jest.useFakeTimers();
		// Pin Date.now so the countdown math is deterministic.
		jest.setSystemTime( NOW_SECONDS * 1000 );
		jest.spyOn( console, 'warn' ).mockImplementation( () => undefined );
	} );

	afterEach( () => {
		// Clear rather than run pending timers — otherwise the interval
		// callback fires against (potentially unmounted) test hooks and
		// React emits an "update not wrapped in act()" warning.
		jest.clearAllTimers();
		jest.useRealTimers();
		jest.restoreAllMocks();
	} );

	it( 'starts IDLE with empty state', () => {
		mockApiFetch.mockResolvedValue( buildResponse() );

		const { result } = renderHook( () => useQRLoginToken() );

		expect( result.current.state ).toBe( QRLoginTokenStates.IDLE );
		expect( result.current.qrUrl ).toBeNull();
		expect( result.current.secondsRemaining ).toBe( 0 );
		expect( result.current.errorMessage ).toBeNull();
		expect( result.current.errorCode ).toBeNull();
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
		expect( mockApiFetch ).toHaveBeenCalledWith( {
			path: '/wc-admin/mobile-app/qr-login-status',
			method: 'POST',
			data: { token: 'abc' },
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
			expect( result.current.errorCode ).toBe( code );
			expect( result.current.errorMessage ).toMatch( message );
		}
	);

	it( 'surfaces the application_passwords_unavailable case with a ReactNode message + docs link', async () => {
		mockApiFetch.mockRejectedValue( {
			code: 'application_passwords_unavailable',
			message: 'Backend said application_passwords_unavailable',
		} );

		const { result } = renderHook( () => useQRLoginToken() );

		await act( async () => {
			await result.current.fetchToken();
		} );

		expect( result.current.state ).toBe( QRLoginTokenStates.ERROR );
		expect( result.current.errorCode ).toBe(
			'application_passwords_unavailable'
		);
		// The message is a ReactNode (interpolated with an inline link), so
		// we can't `toMatch` against a string. Just confirm it's set and not
		// null — the rendering surface is `<QRDirectLoginCode />` and the
		// link visibility is covered there.
		expect( result.current.errorMessage ).not.toBeNull();
		expect( typeof result.current.errorMessage ).not.toBe( 'string' );
	} );

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
		expect( result.current.errorCode ).toBeNull();

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
		// Count only the token-generation POSTs. The hook now also polls the
		// status endpoint after each successful generate, so total apiFetch
		// calls > 2 — but exactly two were token POSTs.
		const tokenGenerateCalls = mockApiFetch.mock.calls.filter(
			( [ args ] ) =>
				typeof args === 'object' &&
				args !== null &&
				'path' in args &&
				typeof ( args as { path: string } ).path === 'string' &&
				( args as { path: string } ).path.includes(
					'qr-login-token'
				) &&
				( args as { method?: string } ).method === 'POST'
		);
		expect( tokenGenerateCalls ).toHaveLength( 2 );
	} );

	// Cloudflare/VIP/etc. edge rate-limiters return an HTML 429 page;
	// apiFetch surfaces that as `invalid_json` because the body isn't
	// parseable. We collapse it onto the same merchant-facing message as
	// our own rate-limit code so the user gets a clear next step instead
	// of "The response is not a valid JSON response."
	it( 'maps an upstream HTML 429 (apiFetch invalid_json) to the rate-limited message', async () => {
		mockApiFetch.mockRejectedValueOnce( {
			code: 'invalid_json',
			message: 'The response is not a valid JSON response.',
		} );

		const { result } = renderHook( () => useQRLoginToken() );

		await act( async () => {
			await result.current.fetchToken();
		} );

		expect( result.current.state ).toBe( QRLoginTokenStates.ERROR );
		expect( result.current.errorMessage ).toMatch(
			/requested QR login codes too quickly/i
		);
		// The raw "not a valid JSON response" message should never be
		// surfaced to the merchant.
		expect( result.current.errorMessage ).not.toMatch( /JSON/i );
	} );

	it( 'maps an explicit HTTP 429 status to the rate-limited message', async () => {
		mockApiFetch.mockRejectedValueOnce( {
			code: 'unexpected_code',
			message: 'whatever',
			data: { status: 429 },
		} );

		const { result } = renderHook( () => useQRLoginToken() );

		await act( async () => {
			await result.current.fetchToken();
		} );

		expect( result.current.state ).toBe( QRLoginTokenStates.ERROR );
		expect( result.current.errorMessage ).toMatch(
			/requested QR login codes too quickly/i
		);
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
			/requested QR login codes too quickly/i
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

	it( 'ignores stale in-flight status polls after refreshing to a new token', async () => {
		let resolveFirstStatusPoll: ( value: unknown ) => void = () =>
			undefined;
		const firstStatusPoll = new Promise( ( resolve ) => {
			resolveFirstStatusPoll = resolve;
		} );
		let tokenRequestCount = 0;

		mockApiFetch.mockImplementation( ( options ) => {
			const request = options as {
				path: string;
				data?: { token?: string };
			};

			if ( request.path.includes( 'qr-login-token' ) ) {
				++tokenRequestCount;
				return Promise.resolve(
					tokenRequestCount === 1
						? buildResponse( TTL_SECONDS, 'first' )
						: buildResponse( TTL_SECONDS, 'second' )
				);
			}

			if ( request.data?.token === 'first' ) {
				return firstStatusPoll;
			}

			return Promise.resolve( {
				status: 'pending',
				expires_at: NOW_SECONDS + TTL_SECONDS,
			} );
		} );

		const { result } = renderHook( () => useQRLoginToken() );

		await act( async () => {
			await result.current.fetchToken();
		} );

		expect( result.current.state ).toBe( QRLoginTokenStates.READY );
		expect( result.current.qrUrl ).toContain( 'token=first' );

		await act( async () => {
			await result.current.refreshToken();
		} );

		expect( result.current.state ).toBe( QRLoginTokenStates.READY );
		expect( result.current.qrUrl ).toContain( 'token=second' );

		await act( async () => {
			resolveFirstStatusPoll( {
				status: 'scanned',
				numbers: [ '317', '042', '589' ],
				device: { model: 'Stale Device' },
				expires_at: NOW_SECONDS + 90,
			} );
			await firstStatusPoll;
		} );

		expect( result.current.state ).toBe( QRLoginTokenStates.READY );
		expect( result.current.qrUrl ).toContain( 'token=second' );
		expect( result.current.candidateNumbers ).toBeNull();
	} );

	// -----------------------------------------------------------------
	// Task 7 — number-matching state transitions.
	// -----------------------------------------------------------------

	/**
	 * The status endpoint returns the new `scanned` shape once the mobile
	 * app has called /qr-login-scan. The hook should switch to the SCANNED
	 * state, surface the shuffled candidate triple + device info, and
	 * record the challenge expiry so the number-match step can render its
	 * own 90-s countdown.
	 */
	it( 'transitions READY → SCANNED on a scanned status payload and surfaces the candidate triple', async () => {
		// First call: token mint. Second call onward: status polls. The hook
		// fires its first poll synchronously after the token mint resolves
		// (no setInterval delay) so by the time the act() block flushes the
		// fetchToken promise, the poll's microtask has already flipped state
		// to SCANNED — that's the intentional UX (instant feedback).
		mockApiFetch
			.mockResolvedValueOnce( buildResponse() )
			.mockResolvedValue( {
				status: 'scanned',
				numbers: [ '317', '042', '589' ],
				device: {
					os: 'Android',
					os_version: '16',
					model: 'Pixel 10',
					app_version: '24.7.0',
				},
				expires_at: NOW_SECONDS + 90,
			} );

		const { result } = renderHook( () => useQRLoginToken() );

		await act( async () => {
			await result.current.fetchToken();
		} );

		expect( result.current.state ).toBe( QRLoginTokenStates.SCANNED );
		expect( result.current.candidateNumbers ).toEqual( [
			'317',
			'042',
			'589',
		] );
		expect( result.current.deviceInfo ).toMatchObject( {
			model: 'Pixel 10',
			os: 'Android',
		} );
		expect( result.current.challengeExpiresAt ).toBe( NOW_SECONDS + 90 );
		// Once the scan is in we no longer render the QR — clear the
		// plaintext-bearing qrUrl from React state so an XSS / malicious
		// extension can't scrape it from the heap for the rest of the flow.
		expect( result.current.qrUrl ).toBeNull();
	} );

	/**
	 * `chooseNumber` posts to /qr-login-approve. On `approved` the hook
	 * flips to APPROVED locally so the UI advances without waiting on the
	 * next status-poll tick. Tiles should be cleared so a re-render can't
	 * accidentally show stale candidates.
	 */
	it( 'chooseNumber → approved flips state to APPROVED and clears candidates', async () => {
		mockApiFetch
			.mockResolvedValueOnce( buildResponse() )
			.mockResolvedValueOnce( {
				status: 'scanned',
				numbers: [ '317', '042', '589' ],
				device: { model: 'Pixel 10' },
				expires_at: NOW_SECONDS + 90,
			} );

		const { result } = renderHook( () => useQRLoginToken() );

		await act( async () => {
			await result.current.fetchToken();
		} );

		await act( async () => {
			jest.advanceTimersByTime( 2600 );
			await Promise.resolve();
		} );
		expect( result.current.state ).toBe( QRLoginTokenStates.SCANNED );

		// Now stub the approve response.
		mockApiFetch.mockResolvedValueOnce( { state: 'approved' } );

		await act( async () => {
			await result.current.chooseNumber( '042' );
		} );

		expect( mockApiFetch ).toHaveBeenLastCalledWith( {
			path: '/wc-admin/mobile-app/qr-login-approve',
			method: 'POST',
			data: { token: 'abc', choice: '042' },
		} );
		expect( result.current.state ).toBe( QRLoginTokenStates.APPROVED );
		expect( result.current.candidateNumbers ).toBeNull();
	} );

	it( 'ignores an older same-token scanned poll after chooseNumber approves', async () => {
		let resolveStalePoll: ( value: unknown ) => void = () => undefined;
		const stalePoll = new Promise( ( resolve ) => {
			resolveStalePoll = resolve;
		} );
		let statusPollCount = 0;
		const scannedResponse = {
			status: 'scanned',
			numbers: [ '317', '042', '589' ],
			device: { model: 'Pixel 10' },
			expires_at: NOW_SECONDS + 90,
		};

		mockApiFetch.mockImplementation( ( options ) => {
			const request = options as { path: string };

			if ( request.path.includes( 'qr-login-token' ) ) {
				return Promise.resolve( buildResponse() );
			}

			if ( request.path.includes( 'qr-login-status' ) ) {
				++statusPollCount;
				return statusPollCount === 1
					? Promise.resolve( scannedResponse )
					: stalePoll;
			}

			return Promise.resolve( { state: 'approved' } );
		} );

		const { result } = renderHook( () => useQRLoginToken() );

		await act( async () => {
			await result.current.fetchToken();
		} );
		expect( result.current.state ).toBe( QRLoginTokenStates.SCANNED );

		await act( async () => {
			jest.advanceTimersByTime( 2600 );
			await Promise.resolve();
		} );

		await act( async () => {
			await result.current.chooseNumber( '042' );
		} );
		expect( result.current.state ).toBe( QRLoginTokenStates.APPROVED );

		await act( async () => {
			resolveStalePoll( scannedResponse );
			await stalePoll;
		} );

		expect( result.current.state ).toBe( QRLoginTokenStates.APPROVED );
		expect( result.current.candidateNumbers ).toBeNull();
	} );

	it( 'ignores an older same-token scanned poll after a status poll approves', async () => {
		let resolveStalePoll: ( value: unknown ) => void = () => undefined;
		const stalePoll = new Promise( ( resolve ) => {
			resolveStalePoll = resolve;
		} );
		const scannedResponse = {
			status: 'scanned',
			numbers: [ '317', '042', '589' ],
			device: { model: 'Pixel 10' },
			expires_at: NOW_SECONDS + 90,
		};

		mockApiFetch
			.mockResolvedValueOnce( buildResponse() )
			.mockResolvedValueOnce( scannedResponse )
			.mockImplementationOnce( () => stalePoll )
			.mockResolvedValueOnce( { status: 'approved' } );

		const { result } = renderHook( () => useQRLoginToken() );

		await act( async () => {
			await result.current.fetchToken();
		} );
		expect( result.current.state ).toBe( QRLoginTokenStates.SCANNED );

		await act( async () => {
			jest.advanceTimersByTime( 2600 );
			await Promise.resolve();
		} );

		await act( async () => {
			jest.advanceTimersByTime( 2600 );
			await Promise.resolve();
		} );

		expect( result.current.state ).toBe( QRLoginTokenStates.APPROVED );
		expect( result.current.candidateNumbers ).toBeNull();

		await act( async () => {
			resolveStalePoll( scannedResponse );
			await stalePoll;
		} );

		expect( result.current.state ).toBe( QRLoginTokenStates.APPROVED );
		expect( result.current.candidateNumbers ).toBeNull();
		expect( result.current.challengeExpiresAt ).toBe( 0 );
	} );

	it( 'moves APPROVED → EXPIRED when a later status poll reports expiry', async () => {
		mockApiFetch
			.mockResolvedValueOnce( buildResponse() )
			.mockResolvedValueOnce( { status: 'approved' } )
			.mockResolvedValueOnce( { status: 'expired' } );

		const { result } = renderHook( () => useQRLoginToken() );

		await act( async () => {
			await result.current.fetchToken();
		} );

		expect( result.current.state ).toBe( QRLoginTokenStates.APPROVED );

		await act( async () => {
			jest.advanceTimersByTime( 2600 );
			await Promise.resolve();
		} );

		expect( result.current.state ).toBe( QRLoginTokenStates.EXPIRED );
		expect( result.current.qrUrl ).toBeNull();
	} );

	/**
	 * Wrong pick → server returns `rejected`. The hook must terminate the
	 * session (clear the token ref so subsequent operations no-op) and
	 * surface the REJECTED state so the UI can render the terminal screen.
	 */
	it( 'chooseNumber → rejected flips state to REJECTED and clears the token', async () => {
		mockApiFetch
			.mockResolvedValueOnce( buildResponse() )
			.mockResolvedValueOnce( {
				status: 'scanned',
				numbers: [ '317', '042', '589' ],
				device: {},
				expires_at: NOW_SECONDS + 90,
			} );

		const { result } = renderHook( () => useQRLoginToken() );

		await act( async () => {
			await result.current.fetchToken();
		} );
		await act( async () => {
			jest.advanceTimersByTime( 2600 );
			await Promise.resolve();
		} );

		mockApiFetch.mockResolvedValueOnce( { state: 'rejected' } );

		await act( async () => {
			await result.current.chooseNumber( '317' );
		} );

		expect( result.current.state ).toBe( QRLoginTokenStates.REJECTED );
		expect( result.current.candidateNumbers ).toBeNull();
	} );

	/**
	 * Approve responding 410 (challenge expired between scan and tap) is a
	 * race we want to surface as REJECTED so the user sees the same
	 * terminal "start over" screen rather than a misleading network error.
	 */
	it( 'chooseNumber → 410 expired surfaces REJECTED', async () => {
		mockApiFetch
			.mockResolvedValueOnce( buildResponse() )
			.mockResolvedValueOnce( {
				status: 'scanned',
				numbers: [ '317', '042', '589' ],
				device: {},
				expires_at: NOW_SECONDS + 90,
			} );

		const { result } = renderHook( () => useQRLoginToken() );

		await act( async () => {
			await result.current.fetchToken();
		} );
		await act( async () => {
			jest.advanceTimersByTime( 2600 );
			await Promise.resolve();
		} );

		mockApiFetch.mockRejectedValueOnce( {
			code: 'qr_login_expired',
			data: { status: 410 },
			message: 'expired',
		} );

		await act( async () => {
			await result.current.chooseNumber( '042' );
		} );

		expect( result.current.state ).toBe( QRLoginTokenStates.REJECTED );
	} );

	it( 'chooseNumber keeps the number-match step visible and surfaces non-terminal approval errors', async () => {
		mockApiFetch
			.mockResolvedValueOnce( buildResponse() )
			.mockResolvedValueOnce( {
				status: 'scanned',
				numbers: [ '317', '042', '589' ],
				device: {},
				expires_at: NOW_SECONDS + 90,
			} );

		const { result } = renderHook( () => useQRLoginToken() );

		await act( async () => {
			await result.current.fetchToken();
		} );
		await act( async () => {
			jest.advanceTimersByTime( 2600 );
			await Promise.resolve();
		} );

		mockApiFetch.mockRejectedValueOnce( {
			code: 'qr_login_approval_in_progress',
			message: 'Approval is already in progress.',
			data: { status: 409 },
		} );

		await act( async () => {
			await result.current.chooseNumber( '042' );
		} );

		expect( result.current.state ).toBe( QRLoginTokenStates.SCANNED );
		expect( result.current.errorCode ).toBe(
			'qr_login_approval_in_progress'
		);
		expect( result.current.errorMessage ).toBe(
			'Approval is already in progress.'
		);
	} );
} );
