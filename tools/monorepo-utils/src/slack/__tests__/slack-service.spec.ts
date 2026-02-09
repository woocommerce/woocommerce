/**
 * External dependencies
 */
import path from 'path';
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
	let client;
	beforeEach( () => {
		client = {
			chat: { postMessage: jest.fn() },
		};
		jest.clearAllMocks();
	} );

	it( 'should send a message to all channels and log notice on success', async () => {
		client.chat.postMessage.mockResolvedValue( { ok: true, ts: '123' } );
		const channels = [ 'C1', 'C2' ];
		await sendMessage( client, 'Hello', channels, undefined );
		expect( client.chat.postMessage ).toHaveBeenCalledTimes(
			channels.length
		);
		expect( client.chat.postMessage ).toHaveBeenCalledWith( {
			channel: 'C1',
			text: 'Hello',
			unfurl_links: false,
			unfurl_media: false,
		} );
		expect( client.chat.postMessage ).toHaveBeenCalledWith( {
			channel: 'C2',
			text: 'Hello',
			unfurl_links: false,
			unfurl_media: false,
		} );
	} );

	it( 'should send a message as a reply when replyTs is set', async () => {
		client.chat.postMessage.mockResolvedValue( { ok: true, ts: '123' } );
		const channels = [ 'C1' ];
		await sendMessage( client, 'Hello in thread', channels, 'thread123' );
		expect( client.chat.postMessage ).toHaveBeenCalledWith( {
			channel: 'C1',
			text: 'Hello in thread',
			unfurl_links: false,
			unfurl_media: false,
			thread_ts: 'thread123',
		} );
	} );

	it.each( [
		{
			desc: 'should error if Slack client returns an error',
			setup: ( testClient ) => {
				testClient.chat.postMessage.mockResolvedValue( {
					ok: false,
					error: 'some_error',
				} );
				return { text: 'Hello', channels: [ 'C1' ] };
			},
			expectedError:
				'Slack client returned an error: some_error, message failed to send.',
		},
		{
			desc: 'should error if text is missing',
			setup: () => {
				return { text: '', channels: [ 'C1' ] };
			},
			expectedError: 'The text argument is missing.',
		},
		{
			desc: 'should error if postMessage throws',
			setup: ( testClient ) => {
				testClient.chat.postMessage.mockRejectedValue(
					new Error( 'fail' )
				);
				return { text: 'Hello', channels: [ 'C1' ] };
			},
			expectedError: expect.any( Error ),
		},
	] )( '$desc', async ( { setup, expectedError } ) => {
		const { text, channels } = setup( client );
		await sendMessage( client, text, channels, undefined );
		expect( Logger.error ).toHaveBeenCalledWith( expectedError );
	} );
} );

describe( 'sendFile', () => {
	let client;
	const filePath = path.resolve( __dirname, 'file.txt' );

	beforeEach( () => {
		client = {
			files: { uploadV2: jest.fn() },
		};
		jest.clearAllMocks();
	} );

	it( 'should upload a file to all channels and log notice on success', async () => {
		client.files.uploadV2.mockResolvedValue( { ok: true } );
		const channels = [ 'C1', 'C2' ];
		await sendFile( client, 'A comment', filePath, channels, 'ts123' );

		expect( client.files.uploadV2 ).toHaveBeenCalledTimes(
			channels.length
		);
		for ( const channel of channels ) {
			expect( client.files.uploadV2 ).toHaveBeenCalledWith(
				expect.objectContaining( {
					file: filePath,
					channel_id: channel,
					initial_comment: 'A comment',
					thread_ts: 'ts123',
				} )
			);
		}
	} );

	it( 'should error if file does not exist', async () => {
		await sendFile(
			client,
			'A comment',
			'/not/a/real/file.txt',
			[ 'C1' ],
			null
		);
		expect( Logger.error ).toHaveBeenCalledWith(
			'Unable to open file with path: /not/a/real/file.txt'
		);
	} );

	it.each( [
		{
			desc: 'should error if uploadV2 throws',
			receivedError: new Error( 'fail' ),
			expectedError: 'fail',
		},
		{
			desc: 'should error with missing_scope message',
			receivedError: { code: undefined, message: 'missing_scope' },
			expectedError:
				'The provided token does not have the required scopes, please add files:write and chat:write to the token.',
		},
	] )( '$desc', async ( { receivedError, expectedError } ) => {
		client.files.uploadV2.mockRejectedValue( receivedError );
		await sendFile( client, 'A comment', filePath, [ 'C1' ], null );
		expect( Logger.error ).toHaveBeenCalledWith( expectedError );
	} );
} );
