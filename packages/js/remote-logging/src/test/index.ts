/**
 * External dependencies
 */
import '@wordpress/jest-console';
import { addFilter, removeFilter } from '@wordpress/hooks';
import { getSetting } from '@woocommerce/settings';

/**
 * Internal dependencies
 */
import { init, log, captureException } from '../';
import {
	RemoteLogger,
	REMOTE_LOGGING_SHOULD_SEND_ERROR_FILTER,
	REMOTE_LOGGING_ERROR_DATA_FILTER,
	REMOTE_LOGGING_LOG_ENDPOINT_FILTER,
	REMOTE_LOGGING_JS_ERROR_ENDPOINT_FILTER,
} from '../remote-logger';
import { fetchMock } from './__mocks__/fetch';

jest.mock( '@woocommerce/settings', () => ( {
	getSetting: jest.fn().mockReturnValue( {
		isRemoteLoggingEnabled: true,
	} ),
} ) );

jest.mock( 'tracekit', () => ( {
	computeStackTrace: jest.fn().mockReturnValue( {
		name: 'Error',
		message: 'Test error',
		stack: [
			{
				url: 'http://example.com/woocommerce/assets/js/admin/app.min.js',
				func: 'testFunction',
				args: [],
				line: 1,
				column: 1,
			},
		],
	} ),
} ) );

describe( 'RemoteLogger', () => {
	const originalConsoleWarn = console.warn;
	let logger: RemoteLogger;

	beforeEach( () => {
		jest.clearAllMocks();
		localStorage.clear();
		logger = new RemoteLogger( { errorRateLimitMs: 60000 } ); // 1 minute
	} );

	afterEach( () => {
		removeFilter( REMOTE_LOGGING_SHOULD_SEND_ERROR_FILTER, 'test' );
	} );

	beforeAll( () => {
		console.warn = jest.fn();
	} );

	afterAll( () => {
		console.warn = originalConsoleWarn;
	} );

	describe( 'log', () => {
		it( 'should send a log message to the API', async () => {
			await logger.log( 'info', 'Test message' );
			expect( fetchMock ).toHaveBeenCalledWith(
				'https://public-api.wordpress.com/rest/v1.1/logstash',
				expect.objectContaining( {
					method: 'POST',
					body: expect.any( FormData ),
				} )
			);
		} );

		it( 'should not send an empty message', async () => {
			await logger.log( 'info', '' );
			expect( fetchMock ).not.toHaveBeenCalled();
		} );

		it( 'should use the filtered Log endpoint', async () => {
			const customEndpoint = 'https://custom-logstash.example.com';
			addFilter(
				REMOTE_LOGGING_LOG_ENDPOINT_FILTER,
				'test',
				() => customEndpoint
			);

			await logger.log( 'info', 'Test message' );

			expect( fetchMock ).toHaveBeenCalledWith(
				customEndpoint,
				expect.objectContaining( {
					method: 'POST',
					body: expect.any( FormData ),
				} )
			);

			removeFilter( REMOTE_LOGGING_LOG_ENDPOINT_FILTER, 'test' );
		} );
	} );

	describe( 'error', () => {
        it( 'should send an error to the API with default data', async () => {
            const error = new Error( 'Test error' );
            await logger.error( error );

            expect( fetchMock ).toHaveBeenCalledWith(
                'https://public-api.wordpress.com/rest/v1.1/js-error',
                expect.objectContaining( {
                    method: 'POST',
                    body: expect.any( FormData ),
                } )
            );

            const formData = fetchMock.mock.calls[0][1].body;
			const payload = JSON.parse(formData.get('error'));
            expect( payload['message'] ).toBe( 'Test error' );
            expect( payload['severity'] ).toBe( 'error' );
            expect( payload['trace'] ).toContain( '#1 at testFunction (http://example.com/woocommerce/assets/js/admin/app.min.js:1:1)' );
        } );

        it( 'should send an error to the API with extra data', async () => {
            const error = new Error( 'Test error' );
            const extraData = {
                severity: 'warning' as const,
                tags: ['custom-tag'],
            };
            await logger.error( error, extraData );

            expect( fetchMock ).toHaveBeenCalledWith(
                'https://public-api.wordpress.com/rest/v1.1/js-error',
                expect.objectContaining( {
                    method: 'POST',
                    body: expect.any( FormData ),
                } )
            );

            const formData = fetchMock.mock.calls[0][1].body;
			const payload = JSON.parse(formData.get('error'));
            expect( payload['message'] ).toBe( 'Test error' );
            expect( payload['severity'] ).toBe( 'warning' );
            expect( payload['tags'] ).toEqual( ["woocommerce", "js", "custom-tag"]);
            expect( payload['trace'] ).toContain( '#1 at testFunction (http://example.com/woocommerce/assets/js/admin/app.min.js:1:1)' );
        } );
    } );

	describe( 'handleError', () => {
		it( 'should send an error to the API', async () => {
			const error = new Error( 'Test error' );
			await ( logger as any ).handleError( error );
			expect( fetchMock ).toHaveBeenCalledWith(
				'https://public-api.wordpress.com/rest/v1.1/js-error',
				expect.objectContaining( {
					method: 'POST',
					body: expect.any( FormData ),
				} )
			);
		} );

		it( 'should respect rate limiting', async () => {
			addFilter(
				REMOTE_LOGGING_SHOULD_SEND_ERROR_FILTER,
				'test',
				() => true
			);

			const error = new Error( 'Test error - rate limit' );
			await ( logger as any ).handleError( error );
			await ( logger as any ).handleError( error );
			expect( fetchMock ).toHaveBeenCalledTimes( 1 );
		} );

		it( 'should filter error data', async () => {
			const filteredErrorData = {
				message: 'Filtered test error',
				severity: 'warning',
				tags: [ 'filtered-tag' ],
				trace: 'Filtered stack trace',
			};

			addFilter( REMOTE_LOGGING_ERROR_DATA_FILTER, 'test', ( data ) => {
				return filteredErrorData;
			} );
			// mock sendError to return true
			const sendErrorSpy = jest
				.spyOn( logger as any, 'sendError' )
				.mockImplementation( () => {} );

			const error = new Error( 'Test error' );
			await ( logger as any ).handleError( error );

			expect( sendErrorSpy ).toHaveBeenCalledWith( filteredErrorData );
		} );

		it( 'should use the filtered JS error endpoint', async () => {
			const customEndpoint = 'https://custom-js-error.example.com';
			addFilter(
				REMOTE_LOGGING_JS_ERROR_ENDPOINT_FILTER,
				'test',
				() => customEndpoint
			);

			const error = new Error( 'Test error' );
			await ( logger as any ).handleError( error );

			expect( fetchMock ).toHaveBeenCalledWith(
				customEndpoint,
				expect.objectContaining( {
					method: 'POST',
					body: expect.any( FormData ),
				} )
			);

			removeFilter( REMOTE_LOGGING_JS_ERROR_ENDPOINT_FILTER, 'test' );
		} );
	} );

	describe( 'shouldHandleError', () => {
		it( 'should return true for WooCommerce errors', () => {
			const error = new Error( 'Test error' );
			const stackFrames = [
				{
					url: 'http://example.com/wp-content/plugins/woocommerce/assets/js/admin/app.min.js',
					func: 'testFunction',
					args: [],
					line: 1,
					column: 1,
				},
			];
			const result = ( logger as any ).shouldHandleError(
				error,
				stackFrames
			);
			expect( result ).toBe( true );
		} );

		it( 'should return false for non-WooCommerce errors', () => {
			const error = new Error( 'Test error' );
			const stackFrames = [
				{
					url: 'http://example.com/other/script.js',
					func: 'testFunction',
					args: [],
					line: 1,
					column: 1,
				},
			];
			const result = ( logger as any ).shouldHandleError(
				error,
				stackFrames
			);
			expect( result ).toBe( false );
		} );

		it( 'should return false for WooCommerce errors with no stack frames', () => {
			const error = new Error( 'Test error' );
			const result = ( logger as any ).shouldHandleError( error, [] );
			expect( result ).toBe( false );
		} );

		it( 'should return true if filter returns true', () => {
			addFilter(
				REMOTE_LOGGING_SHOULD_SEND_ERROR_FILTER,
				'test',
				() => true
			);
			const error = new Error( 'Test error' );
			const result = ( logger as any ).shouldHandleError( error, [] );
			expect( result ).toBe( true );
		} );
	} );

	describe( 'getFormattedStackFrame', () => {
		it( 'should format stack frames correctly', () => {
			const stackTrace = {
				name: 'Error',
				message: 'Test error',
				stack: [
					{
						url: 'http://example.com/woocommerce/assets/js/admin/wc-admin.min.js',
						func: 'testFunction',
						args: [],
						line: 1,
						column: 1,
						context: [
							'const x = 1;',
							'throw new Error("Test error");',
							'const y = 2;',
						],
					},
				],
			};
			const result = ( logger as any ).getFormattedStackFrame(
				stackTrace
			);
			expect( result ).toContain(
				'#1 at testFunction (http://example.com/woocommerce/assets/js/admin/wc-admin.min.js:1:1)'
			);
			expect( result ).toContain( 'const x = 1;' );
			expect( result ).toContain( 'throw new Error("Test error");' );
			expect( result ).toContain( 'const y = 2;' );
		} );
	} );
} );

describe( 'init', () => {
	beforeEach( () => {
		jest.clearAllMocks();

		( getSetting as jest.Mock ).mockImplementation(
			( key, defaultValue ) => {
				if ( key === 'isRemoteLoggingEnabled' ) {
					return true;
				}
				return defaultValue;
			}
		);
	} );

	it( 'should not initialize or log when remote logging is disabled', () => {
		// Mock the getSetting function to return false for isRemoteLoggingEnabled
		( getSetting as jest.Mock ).mockImplementation(
			( key, defaultValue ) => {
				if ( key === 'isRemoteLoggingEnabled' ) {
					return false;
				}
				return defaultValue;
			}
		);

		init( { errorRateLimitMs: 1000 } );
		log( 'info', 'Test message' );
		expect( fetchMock ).not.toHaveBeenCalled();
	} );

	it( 'should initialize and log without throwing when remote logging is enabled', () => {
		init( { errorRateLimitMs: 1000 } );
		expect( () => log( 'info', 'Test message' ) ).not.toThrow();
		expect( fetchMock ).toHaveBeenCalled();
	} );

	it( 'should not initialize the logger twice', () => {
		init( { errorRateLimitMs: 1000 } );
		init( { errorRateLimitMs: 2000 } );

		expect( console ).toHaveWarnedWith(
			'RemoteLogger: RemoteLogger is already initialized.'
		);
	} );
} );

describe( 'log', () => {
	it( 'should not log if remote logging is disabled', () => {
		( getSetting as jest.Mock ).mockImplementation(
			( key, defaultValue ) => {
				if ( key === 'isRemoteLoggingEnabled' ) {
					return false;
				}
				return defaultValue;
			}
		);

		log( 'info', 'Test message' );
		expect( fetchMock ).not.toHaveBeenCalled();
	} );
} );

describe( 'captureException', () => {
	it( 'should not log error if remote logging is disabled', () => {
		( getSetting as jest.Mock ).mockImplementation(
			( key, defaultValue ) => {
				if ( key === 'isRemoteLoggingEnabled' ) {
					return false;
				}
				return defaultValue;
			}
		);

		captureException( new Error( 'Test error' ) );
		expect( fetchMock ).not.toHaveBeenCalled();
	} );
} );
