/**
 * Internal dependencies
 */
import {
	createMessage,
	getRunUrl,
	getBlocksChunksBySize,
	getBlocksChunksByType,
} from '../message';

describe( 'createMessage', () => {
	const defaultOptions = {
		reportName: 'Test Report',
		username: 'test-user',
		isFailure: true,
		eventName: 'pull_request',
		sha: '1234567890abcdef',
		commitMessage: 'Test commit message',
		prTitle: 'Test PR Title',
		prNumber: '123',
		actor: 'test-actor',
		triggeringActor: 'trigger-actor',
		runId: '456',
		runAttempt: '1',
		serverUrl: 'https://github.com',
		repository: 'test/repo',
		refType: 'branch',
		refName: 'main',
	};

	it( 'should create a message for pull request event', async () => {
		const result = await createMessage( defaultOptions );

		expect( result ).toMatchObject( {
			text: ':x:	_*Test Report*_ failed for pull request *#123*',
			mainMsgBlocks: [
				{
					type: 'section',
					text: {
						type: 'mrkdwn',
						text: ':x:	_*Test Report*_ failed for pull request *#123*',
					},
				},
				{
					type: 'context',
					elements: [
						{
							type: 'plain_text',
							text: 'Title: Test PR Title',
							emoji: false,
						},
						{
							type: 'plain_text',
							text: 'Actor: test-actor',
							emoji: false,
						},
						{
							type: 'plain_text',
							text: 'Run: 456/1, triggered by trigger-actor',
							emoji: false,
						},
					],
				},
				{
					type: 'actions',
					elements: [
						{
							type: 'button',
							text: {
								type: 'plain_text',
								text: 'View Run',
							},
							url: 'https://github.com/test/repo/actions/runs/456',
						},
						{
							type: 'button',
							text: {
								type: 'plain_text',
								text: 'PR #123',
							},
							url: 'https://github.com/test/repo/pull/123',
						},
					],
				},
			],
		} );
	} );

	it( 'should create a message for push event', async () => {
		const pushOptions = {
			...defaultOptions,
			eventName: 'push',
		};

		const result = await createMessage( pushOptions );

		expect( result ).toMatchObject( {
			text: ':x:	_*Test Report*_ failed on branch _*main*_ (push)',
			mainMsgBlocks: expect.arrayContaining( [
				{
					type: 'section',
					text: {
						type: 'mrkdwn',
						text: ':x:	_*Test Report*_ failed on branch _*main*_ (push)',
					},
				},
				{
					type: 'context',
					elements: expect.arrayContaining( [
						{
							type: 'plain_text',
							text: 'Commit: 12345678 Test commit message',
							emoji: false,
						},
					] ),
				},
			] ),
		} );
	} );

	it( 'should truncate long commit messages', async () => {
		const longMessageOptions = {
			...defaultOptions,
			eventName: 'push',
			commitMessage:
				'This is a very long commit message that should be truncated at some point because it is too long',
		};

		const result = await createMessage( longMessageOptions );
		const contextBlock = result.mainMsgBlocks.find(
			( block ) => block.type === 'context'
		);
		const commitElement = contextBlock.elements.find( ( element ) =>
			element.text.startsWith( 'Commit:' )
		);

		expect( commitElement.text ).toMatch(
			/^Commit: [a-f0-9]{8} This is a very long commit message.*\.\.\.$/
		);
	} );

	it( 'should create a message for repository_dispatch event', async () => {
		const dispatchOptions = {
			...defaultOptions,
			eventName: 'repository_dispatch',
		};

		const result = await createMessage( dispatchOptions );

		expect( result.text ).toBe(
			':x:	_*Test Report*_ failed for event _*repository_dispatch*_'
		);
	} );

	it( 'should handle missing reportName', async () => {
		const noReportOptions = {
			...defaultOptions,
			reportName: '',
		};

		const result = await createMessage( noReportOptions );

		expect( result.text ).toMatch( /^:x:	Failure for/ );
	} );

	it( 'should include run attempt in URL when requested', async () => {
		const result = await createMessage( defaultOptions );
		const actionsBlock = result.mainMsgBlocks.find(
			( block ) => block.type === 'actions'
		);
		const runButton = actionsBlock.elements.find(
			( element ) => element.text.text === 'View Run'
		);

		// Test URL without attempts
		expect( runButton.url ).toBe(
			'https://github.com/test/repo/actions/runs/456'
		);

		// Test URL with attempts by calling getRunUrl directly
		const withAttemptUrl = getRunUrl( defaultOptions, true );
		expect( withAttemptUrl ).toBe(
			'https://github.com/test/repo/actions/runs/456/attempts/1'
		);
	} );

	it( 'should create a message for schedule event', async () => {
		const scheduleOptions = {
			...defaultOptions,
			eventName: 'schedule',
			refName: 'trunk',
			refType: 'branch',
		};

		const result = await createMessage( scheduleOptions );

		expect( result ).toMatchObject( {
			text: ':x:	_*Test Report*_ failed on branch _*trunk*_ (schedule)',
			mainMsgBlocks: [
				{
					type: 'section',
					text: {
						type: 'mrkdwn',
						text: ':x:	_*Test Report*_ failed on branch _*trunk*_ (schedule)',
					},
				},
				{
					type: 'context',
					elements: [
						{
							type: 'plain_text',
							text: 'Commit: 12345678 Test commit message',
							emoji: false,
						},
						{
							type: 'plain_text',
							text: 'Run: 456/1',
							emoji: false,
						},
					],
				},
				{
					type: 'actions',
					elements: [
						{
							type: 'button',
							text: {
								type: 'plain_text',
								text: 'View Run',
							},
							url: 'https://github.com/test/repo/actions/runs/456',
						},
						{
							type: 'button',
							text: {
								type: 'plain_text',
								text: 'Commit 12345678',
							},
							url: 'https://github.com/test/repo/commit/1234567890abcdef',
						},
					],
				},
			],
		} );
	} );

	it( 'should create a message for workflow_run event on release branch', async () => {
		const releaseOptions = {
			...defaultOptions,
			eventName: 'workflow_run',
			refName: 'release/1.0.0',
			refType: 'branch',
			commitMessage: 'Prepare release 1.0.0',
		};

		const result = await createMessage( releaseOptions );

		expect( result ).toMatchObject( {
			text: ':x:	_*Test Report*_ failed on branch _*release/1.0.0*_ (workflow_run)',
			mainMsgBlocks: [
				{
					type: 'section',
					text: {
						type: 'mrkdwn',
						text: ':x:	_*Test Report*_ failed on branch _*release/1.0.0*_ (workflow_run)',
					},
				},
				{
					type: 'context',
					elements: [
						{
							type: 'plain_text',
							text: 'Commit: 12345678 Prepare release 1.0.0',
							emoji: false,
						},
						{
							type: 'plain_text',
							text: 'Actor: test-actor',
							emoji: false,
						},
						{
							type: 'plain_text',
							text: 'Run: 456/1, triggered by trigger-actor',
							emoji: false,
						},
					],
				},
				{
					type: 'actions',
					elements: [
						{
							type: 'button',
							text: {
								type: 'plain_text',
								text: 'View Run',
							},
							url: 'https://github.com/test/repo/actions/runs/456',
						},
						{
							type: 'button',
							text: {
								type: 'plain_text',
								text: 'Commit 12345678',
							},
							url: 'https://github.com/test/repo/commit/1234567890abcdef',
						},
					],
				},
			],
		} );
	} );

	it( 'should include jobs list when provided', async () => {
		const withJobsOptions = {
			...defaultOptions,
			jobsList: 'Job 1,Job 2,Job 3',
		};

		const result = await createMessage( withJobsOptions );

		// Jobs context should be before actions block
		const jobsBlock = result.mainMsgBlocks[ 2 ];
		const actionsBlock = result.mainMsgBlocks[ 3 ];

		expect( jobsBlock ).toMatchObject( {
			type: 'context',
			elements: [
				{
					type: 'mrkdwn',
					text: '• Job 1\n• Job 2\n• Job 3',
				},
			],
		} );
		expect( actionsBlock.type ).toBe( 'actions' );
	} );

	it( 'should not include jobs list when empty', async () => {
		const withoutJobsOptions = {
			...defaultOptions,
			jobsList: '',
		};

		const result = await createMessage( withoutJobsOptions );

		// Should have 3 blocks (section, context, actions)
		expect( result.mainMsgBlocks ).toHaveLength( 3 );
		expect( result.mainMsgBlocks[ 2 ].type ).toBe( 'actions' );
	} );

	it( 'should not include jobs list when only whitespace', async () => {
		const withWhitespaceJobsOptions = {
			...defaultOptions,
			jobsList: '   ',
		};

		const result = await createMessage( withWhitespaceJobsOptions );

		expect( result.mainMsgBlocks ).toHaveLength( 3 );
		expect( result.mainMsgBlocks[ 2 ].type ).toBe( 'actions' );
	} );

	it( 'should filter out empty job names from list', async () => {
		const withEmptyJobsOptions = {
			...defaultOptions,
			jobsList: 'Job 1,,Job 2,  ,Job 3',
		};

		const result = await createMessage( withEmptyJobsOptions );

		const jobsBlock = result.mainMsgBlocks[ 2 ];
		expect( jobsBlock ).toMatchObject( {
			type: 'context',
			elements: [
				{
					type: 'mrkdwn',
					text: '• Job 1\n• Job 2\n• Job 3',
				},
			],
		} );
	} );

	it( 'should trim job names', async () => {
		const withSpacedJobsOptions = {
			...defaultOptions,
			jobsList: '  Job 1  ,  Job 2  ,  Job 3  ',
		};

		const result = await createMessage( withSpacedJobsOptions );

		const jobsBlock = result.mainMsgBlocks[ 2 ];
		expect( jobsBlock ).toMatchObject( {
			type: 'context',
			elements: [
				{
					type: 'mrkdwn',
					text: '• Job 1\n• Job 2\n• Job 3',
				},
			],
		} );
	} );

	it( 'should parse custom header with ### separator', async () => {
		const withCustomHeaderOptions = {
			...defaultOptions,
			jobsList: 'Failed:###Job 1,Job 2,Job 3',
		};

		const result = await createMessage( withCustomHeaderOptions );

		const jobsBlock = result.mainMsgBlocks[ 2 ];
		expect( jobsBlock ).toMatchObject( {
			type: 'context',
			elements: [
				{
					type: 'mrkdwn',
					text: '*Failed:*\n• Job 1\n• Job 2\n• Job 3',
				},
			],
		} );
	} );

	it( 'should handle custom header with spaces', async () => {
		const withCustomHeaderOptions = {
			...defaultOptions,
			jobsList: '  Failed Jobs:  ###  Job 1  ,  Job 2  ',
		};

		const result = await createMessage( withCustomHeaderOptions );

		const jobsBlock = result.mainMsgBlocks[ 2 ];
		expect( jobsBlock ).toMatchObject( {
			type: 'context',
			elements: [
				{
					type: 'mrkdwn',
					text: '*Failed Jobs:*\n• Job 1\n• Job 2',
				},
			],
		} );
	} );

	it( 'should show only bullets when no custom header provided', async () => {
		const withoutCustomHeaderOptions = {
			...defaultOptions,
			jobsList: 'Job 1,Job 2',
		};

		const result = await createMessage( withoutCustomHeaderOptions );

		const jobsBlock = result.mainMsgBlocks[ 2 ];
		expect( jobsBlock ).toMatchObject( {
			type: 'context',
			elements: [
				{
					type: 'mrkdwn',
					text: '• Job 1\n• Job 2',
				},
			],
		} );
	} );

	it( 'should limit jobs list to 5 and show remaining count', async () => {
		const withManyJobsOptions = {
			...defaultOptions,
			jobsList: 'Job 1,Job 2,Job 3,Job 4,Job 5,Job 6,Job 7,Job 8',
		};

		const result = await createMessage( withManyJobsOptions );

		const jobsBlock = result.mainMsgBlocks[ 2 ];
		expect( jobsBlock ).toMatchObject( {
			type: 'context',
			elements: [
				{
					type: 'mrkdwn',
					text: '• Job 1\n• Job 2\n• Job 3\n• Job 4\n• Job 5\n• _3 more_',
				},
			],
		} );
	} );

	it( 'should limit jobs list to 5 with custom header', async () => {
		const withManyJobsAndHeaderOptions = {
			...defaultOptions,
			jobsList: 'Failed:###Job 1,Job 2,Job 3,Job 4,Job 5,Job 6,Job 7',
		};

		const result = await createMessage( withManyJobsAndHeaderOptions );

		const jobsBlock = result.mainMsgBlocks[ 2 ];
		expect( jobsBlock ).toMatchObject( {
			type: 'context',
			elements: [
				{
					type: 'mrkdwn',
					text: '*Failed:*\n• Job 1\n• Job 2\n• Job 3\n• Job 4\n• Job 5\n• _2 more_',
				},
			],
		} );
	} );

	it( 'should not show "more" when exactly 5 jobs', async () => {
		const withExactly5JobsOptions = {
			...defaultOptions,
			jobsList: 'Job 1,Job 2,Job 3,Job 4,Job 5',
		};

		const result = await createMessage( withExactly5JobsOptions );

		const jobsBlock = result.mainMsgBlocks[ 2 ];
		expect( jobsBlock ).toMatchObject( {
			type: 'context',
			elements: [
				{
					type: 'mrkdwn',
					text: '• Job 1\n• Job 2\n• Job 3\n• Job 4\n• Job 5',
				},
			],
		} );
	} );
} );

describe( 'getBlocksChunksBySize', () => {
	it( 'should split array into chunks of specified size', () => {
		const blocks = [
			{ id: 1 },
			{ id: 2 },
			{ id: 3 },
			{ id: 4 },
			{ id: 5 },
		];
		const result = getBlocksChunksBySize( blocks, 2 );

		expect( result ).toEqual( [
			[ { id: 1 }, { id: 2 } ],
			[ { id: 3 }, { id: 4 } ],
			[ { id: 5 } ],
		] );
	} );

	it( 'should handle empty array', () => {
		const result = getBlocksChunksBySize( [], 3 );
		expect( result ).toEqual( [] );
	} );

	it( 'should handle chunk size larger than array length', () => {
		const blocks = [ { id: 1 }, { id: 2 } ];
		const result = getBlocksChunksBySize( blocks, 5 );
		expect( result ).toEqual( [ [ { id: 1 }, { id: 2 } ] ] );
	} );

	it( 'should handle chunk size of 1', () => {
		const blocks = [ { id: 1 }, { id: 2 } ];
		const result = getBlocksChunksBySize( blocks, 1 );
		expect( result ).toEqual( [ [ { id: 1 } ], [ { id: 2 } ] ] );
	} );
} );

describe( 'getBlocksChunksByType', () => {
	it( 'should split array into chunks based on delimiter type', () => {
		const blocks = [
			{ type: 'context' },
			{ type: 'context' },
			{ type: 'file' },
			{ type: 'context' },
			{ type: 'file' },
			{ type: 'section' },
		];
		const result = getBlocksChunksByType( blocks, 'file' );

		expect( result ).toEqual( [
			[ { type: 'context' }, { type: 'context' } ],
			[ { type: 'file' } ],
			[ { type: 'context' } ],
			[ { type: 'file' } ],
			[ { type: 'section' } ],
		] );
	} );

	it( 'should handle empty array', () => {
		const result = getBlocksChunksByType( [], 'file' );
		expect( result ).toEqual( [] );
	} );

	it( 'should handle array with no delimiter type', () => {
		const blocks = [
			{ type: 'context' },
			{ type: 'section' },
			{ type: 'context' },
		];
		const result = getBlocksChunksByType( blocks, 'file' );
		expect( result ).toEqual( [
			[ { type: 'context' }, { type: 'section' }, { type: 'context' } ],
		] );
	} );

	it( 'should handle array starting with delimiter type', () => {
		const blocks = [
			{ type: 'file' },
			{ type: 'context' },
			{ type: 'section' },
		];
		const result = getBlocksChunksByType( blocks, 'file' );
		expect( result ).toEqual( [
			[ { type: 'file' } ],
			[ { type: 'context' }, { type: 'section' } ],
		] );
	} );

	it( 'should handle array ending with delimiter type', () => {
		const blocks = [
			{ type: 'context' },
			{ type: 'section' },
			{ type: 'file' },
		];
		const result = getBlocksChunksByType( blocks, 'file' );
		expect( result ).toEqual( [
			[ { type: 'context' }, { type: 'section' } ],
			[ { type: 'file' } ],
		] );
	} );

	it( 'should handle consecutive delimiter types', () => {
		const blocks = [
			{ type: 'context' },
			{ type: 'file' },
			{ type: 'file' },
			{ type: 'section' },
		];
		const result = getBlocksChunksByType( blocks, 'file' );
		expect( result ).toEqual( [
			[ { type: 'context' } ],
			[ { type: 'file' } ],
			[ { type: 'file' } ],
			[ { type: 'section' } ],
		] );
	} );
} );
