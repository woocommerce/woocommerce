/**
 * Internal dependencies
 */
import { Logger } from '../../core/logger';
import { resolveChannels, sendFile, sendMessage } from '../slack-service';

jest.mock( '../../core/logger', () => ( {
	Logger: {
		error: jest.fn(),
		notice: jest.fn(),
		startTask: jest.fn(),
		endTask: jest.fn(),
	},
} ) );

jest.mock( '@slack/web-api', () => ( {
	WebClient: jest.fn().mockImplementation( () => ( {
		chat: { postMessage: jest.fn() },
		files: { uploadV2: jest.fn() },
	} ) ),
	ErrorCode: {},
} ) );

describe( 'resolveChannels', () => {
	const originalEnv = process.env;

	beforeEach( () => {
		process.env = { ...originalEnv };
	} );

	afterEach( () => {
		process.env = originalEnv;
		jest.restoreAllMocks();
	} );

	it.each( [
		{
			env: '  C123 ,C456,,  C789  ',
			expected: [ 'C123', 'C456', 'C789' ],
			desc: 'returns trimmed, non-empty channel IDs when SLACK_CHANNELS is set',
		},
		{
			env: 'C123',
			expected: [ 'C123' ],
			desc: 'returns a single channel in an array when SLACK_CHANNELS is a single channel',
		},
	] )( '$desc', ( { env, expected } ) => {
		process.env.SLACK_CHANNELS = env;
		const result = resolveChannels();
		expect( result ).toEqual( expected );
	} );

	it.each( [
		{
			env: undefined,
			desc: 'errors when SLACK_CHANNELS is not set',
		},
		{
			env: '   , ,',
			desc: 'errors when SLACK_CHANNELS is only empty/whitespace',
		},
	] )( '$desc', ( { env } ) => {
		if ( env === undefined ) {
			delete process.env.SLACK_CHANNELS;
		} else {
			process.env.SLACK_CHANNELS = env;
		}
		const result = resolveChannels();
		expect( result ).toBeNull();
		expect( Logger.error ).toHaveBeenCalledWith(
			'SLACK_CHANNELS environment variable must be set with comma-separated channel IDs.'
		);
	} );
} );

describe( 'sendMessage', () => {
	it( 'should be defined and a function', () => {
		expect( sendMessage ).toBeDefined();
		expect( typeof sendMessage ).toBe( 'function' );
	} );
} );

describe( 'sendFile', () => {
	it( 'should be defined and a function', () => {
		expect( sendFile ).toBeDefined();
		expect( typeof sendFile ).toBe( 'function' );
	} );
} );
