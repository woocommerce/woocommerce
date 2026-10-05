import { beforeEach, describe, expect, it, vi } from 'vitest';

/**
 * External dependencies
 */
import { WebClient } from '@slack/web-api';

/**
 * Internal dependencies
 */
import { postToSlack } from '../index';
import { sendFile, sendMessage } from '../slack-service';
vi.mock( '@slack/web-api', () => {
	const mock = {
		WebClient: vi.fn(),
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
vi.mock( '../../core/logger', () => {
	const mock = {
		Logger: {
			error: vi.fn(),
			notice: vi.fn(),
			startTask: vi.fn(),
			endTask: vi.fn(),
		},
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
vi.mock( '../slack-service', () => {
	return ( ( mock ) => ( {
		default: mock,
		...mock,
	} ) )( {
		resolveChannels: vi.fn( () => [ 'C123' ] ),
		sendMessage: vi.fn(),
		sendFile: vi.fn(),
	} );
} );
describe( 'postToSlack', () => {
	beforeEach( () => {
		vi.clearAllMocks();
		process.env.SLACK_TOKEN = 'xoxb-test-token';
		process.env.SLACK_CHANNELS = 'C123';
	} );
	describe( 'when options.file is not set', () => {
		it( 'calls sendMessage', async () => {
			await postToSlack( 'Hello Slack :wave:', {} );
			expect( sendMessage ).toHaveBeenCalledTimes( 1 );
			expect( sendMessage ).toHaveBeenCalledWith(
				expect.any( WebClient ),
				'Hello Slack :wave:',
				[ 'C123' ],
				undefined
			);
		} );
		it( 'does not call sendFile', async () => {
			await postToSlack( 'Hello Slack :wave:', {} );
			expect( sendFile ).not.toHaveBeenCalled();
		} );
	} );
	describe( 'when options.file is set', () => {
		it( 'calls sendFile', async () => {
			await postToSlack( 'Hello file', {
				file: '/tmp/file.txt',
				replyTs: '123',
			} );
			expect( sendFile ).toHaveBeenCalledTimes( 1 );
			expect( sendFile ).toHaveBeenCalledWith(
				expect.any( WebClient ),
				'Hello file',
				'/tmp/file.txt',
				[ 'C123' ],
				'123'
			);
		} );
		it( 'does not call sendMessage', async () => {
			await postToSlack( 'Hello file', {
				file: '/tmp/file.txt',
				replyTs: '123',
			} );
			expect( sendMessage ).not.toHaveBeenCalled();
		} );
	} );
} );
