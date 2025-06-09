/**
 * Internal dependencies
 */
import {
	resolveChannels,
	sendMessage,
	sendFile,
	postToSlack,
} from '../slack-service';
import { Logger } from '../../core/logger';

jest.mock( '@slack/web-api', () => ( {
	WebClient: jest.fn().mockImplementation( () => ( {
		chat: { postMessage: jest.fn() },
		files: { uploadV2: jest.fn() },
	} ) ),
	ErrorCode: {},
} ) );

jest.mock( '../../core/logger', () => {
	return {
		Logger: {
			error: jest.fn(),
		},
	};
} );

describe( 'resolveChannels', () => {
	const originalEnv = process.env;

	beforeEach( () => {
		process.env = { ...originalEnv };
	} );

	afterEach( () => {
		process.env = originalEnv;
		jest.restoreAllMocks();
	} );

	it( 'returns trimmed, non-empty channel IDs when SLACK_CHANNELS is set', () => {
		process.env.SLACK_CHANNELS = '  C123 ,C456,,  C789  ';
		const result = resolveChannels();
		expect( result ).toEqual( [ 'C123', 'C456', 'C789' ] );
	} );

	it( 'errors when SLACK_CHANNELS is not set', () => {
		delete process.env.SLACK_CHANNELS;
		let threw = false;
		try {
			resolveChannels();
		} catch ( e ) {
			threw = true;
		}
		expect( threw ).toBe( true );
		expect( Logger.error ).toHaveBeenCalledWith(
			'SLACK_CHANNELS environment variable must be set with comma-separated channel IDs.'
		);
	} );

	it( 'returns an empty array if SLACK_CHANNELS is only empty/whitespace', () => {
		process.env.SLACK_CHANNELS = '   , ,';
		const result = resolveChannels();
		expect( result ).toEqual( [] );
	} );

	it( 'returns a single channel in an array when SLACK_CHANNELS is a single channel', () => {
		process.env.SLACK_CHANNELS = 'C123';
		const result = resolveChannels();
		expect( result ).toEqual( [ 'C123' ] );
	} );
} );

describe( 'postToSlack', () => {
	it( 'should be defined and a function', () => {
		expect( postToSlack ).toBeDefined();
		expect( typeof postToSlack ).toBe( 'function' );
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
