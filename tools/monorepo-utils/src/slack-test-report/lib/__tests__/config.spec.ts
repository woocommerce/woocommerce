/**
 * External dependencies
 */
import path from 'path';

/**
 * Internal dependencies
 */
import {
	loadConfig,
	parseConfig,
	getConfiguredChannels,
	resolveChannels,
} from '../config';

describe( 'loadConfigData', () => {
	it( 'should read config from file', () => {
		const loadedConfig = loadConfig(
			`${ __dirname }/notifications-config.json`
		);

		expect( loadedConfig ).toMatchObject( {
			routes: [
				{
					checkType: 'release-checks',
					channels: [ 'CHANNEL_ID_1' ],
				},
				{
					checkType: 'daily-checks',
					channels: [ 'CHANNEL_ID_2' ],
				},
				{
					refName: 'release/**',
					channels: [ 'CHANNEL_ID_1' ],
					excludeDefaultChannel: true,
				},
			],
		} );
	} );

	it( 'should throw error when file does not exist', () => {
		const nonExistentPath = path.join( __dirname, 'non-existent.json' );

		expect( () => loadConfig( nonExistentPath ) ).toThrow(
			'Failed to read config file: ENOENT: no such file or directory'
		);
	} );

	it( 'should throw error when file contains invalid JSON', () => {
		const invalidJsonPath = path.join( __dirname, 'invalid.json' );

		expect( () => loadConfig( invalidJsonPath ) ).toThrow(
			'Failed to parse config file'
		);
	} );

	it( 'should throw error when file is empty', () => {
		const emptyFilePath = path.join( __dirname, 'empty.json' );

		expect( () => loadConfig( emptyFilePath ) ).toThrow(
			'Failed to parse config file'
		);
	} );
} );

describe( 'parseConfig', () => {
	describe( 'input validation', () => {
		test.each( [
			{
				name: 'empty object',
				input: {},
				error: 'Failed to parse config file: defaultChannel must be a non-empty string',
			},
			{
				name: 'null',
				input: null,
				error: 'Failed to parse config file: config needs to be an Object',
			},
			{
				name: 'undefined',
				input: undefined,
				error: 'Failed to parse config file: config needs to be an Object',
			},
			{
				name: 'string',
				input: 'string' as any,
				error: 'Failed to parse config file: config needs to be an Object',
			},
		] )( 'should throw error for $name', ( { input, error } ) => {
			expect( () => parseConfig( input ) ).toThrow( error );
		} );
	} );

	describe( 'defaultChannel validation', () => {
		test.each( [
			{
				name: 'empty string',
				input: { defaultChannel: '' },
				error: 'Failed to parse config file: defaultChannel must be a non-empty string',
			},
			{
				name: 'number',
				input: { defaultChannel: 123 as any },
				error: 'Failed to parse config file: defaultChannel must be a non-empty string',
			},
			{
				name: 'null',
				input: { defaultChannel: null as any },
				error: 'Failed to parse config file: defaultChannel must be a non-empty string',
			},
		] )( 'should throw error for $name', ( { input, error } ) => {
			expect( () => parseConfig( input ) ).toThrow( error );
		} );
	} );

	describe( 'routes validation', () => {
		test.each( [
			{
				name: 'non-array routes',
				input: {
					defaultChannel: 'CHANNEL_ID_0',
					routes: 'not-an-array' as any,
				},
				error: 'Failed to parse config file: routes must be an array',
			},
			{
				name: 'non-object route',
				input: {
					defaultChannel: 'CHANNEL_ID_0',
					routes: [ 'not-an-object' as any ],
				},
				error: 'Failed to parse config file: route needs to be an Object',
			},
			{
				name: 'missing channels',
				input: {
					defaultChannel: 'CHANNEL_ID_0',
					routes: [ { checkType: 'test' } ],
				},
				error: 'Failed to parse config file: channels must be an array of strings',
			},
			{
				name: 'non-array channels',
				input: {
					defaultChannel: 'CHANNEL_ID_0',
					routes: [
						{
							checkType: 'test',
							channels: 'not-an-array' as any,
						},
					],
				},
				error: 'Failed to parse config file: channels must be an array of strings',
			},
			{
				name: 'non-string channel',
				input: {
					defaultChannel: 'CHANNEL_ID_0',
					routes: [
						{
							checkType: 'test',
							channels: [ 123 as any ],
						},
					],
				},
				error: 'Failed to parse config file: channels must be an array of strings',
			},
			{
				name: 'missing checkType and refName',
				input: {
					defaultChannel: 'CHANNEL_ID_0',
					routes: [ { channels: [ 'CHANNEL_1' ] } ],
				},
				error: 'Failed to parse config file: route must have at least one of checkType or refName as a non-empty string',
			},
			{
				name: 'empty checkType',
				input: {
					defaultChannel: 'CHANNEL_ID_0',
					routes: [ { channels: [ 'CHANNEL_1' ], checkType: '' } ],
				},
				error: 'Failed to parse config file: route must have at least one of checkType or refName as a non-empty string',
			},
			{
				name: 'empty refName',
				input: {
					defaultChannel: 'CHANNEL_ID_0',
					routes: [ { channels: [ 'CHANNEL_1' ], refName: '' } ],
				},
				error: 'Failed to parse config file: route must have at least one of checkType or refName as a non-empty string',
			},
			{
				name: 'null checkType and refName',
				input: {
					defaultChannel: 'CHANNEL_ID_0',
					routes: [
						{
							channels: [ 'CHANNEL_1' ],
							checkType: null as any,
							refName: null as any,
						},
					],
				},
				error: 'Failed to parse config file: route must have at least one of checkType or refName as a non-empty string',
			},
			{
				name: 'invalid excludeDefaultChannel',
				input: {
					defaultChannel: 'CHANNEL_ID_0',
					routes: [
						{
							channels: [ 'CHANNEL_1' ],
							checkType: 'test',
							excludeDefaultChannel: 'true' as any,
						},
					],
				},
				error: 'Failed to parse config file: excludeDefaultChannel must be a boolean when present',
			},
		] )( 'should throw error for $name', ( { input, error } ) => {
			expect( () => parseConfig( input ) ).toThrow( error );
		} );
	} );

	describe( 'valid configurations', () => {
		test.each( [
			{
				name: 'config with missing routes',
				input: { defaultChannel: 'CHANNEL_ID_0' },
				expected: {
					defaultChannel: 'CHANNEL_ID_0',
					routes: [],
				},
			},
			{
				name: 'config with checkType route',
				input: {
					defaultChannel: 'CHANNEL_ID_0',
					routes: [
						{
							checkType: 'release-checks',
							channels: [ 'CHANNEL_ID_1' ],
							excludeDefaultChannel: true,
						},
					],
				},
				expected: {
					defaultChannel: 'CHANNEL_ID_0',
					routes: [
						{
							checkType: 'release-checks',
							channels: [ 'CHANNEL_ID_1' ],
							excludeDefaultChannel: true,
						},
					],
				},
			},
			{
				name: 'config with refName route',
				input: {
					defaultChannel: 'CHANNEL_ID_0',
					routes: [
						{
							refName: 'release/**',
							channels: [ 'CHANNEL_ID_1' ],
							excludeDefaultChannel: true,
						},
					],
				},
				expected: {
					defaultChannel: 'CHANNEL_ID_0',
					routes: [
						{
							refName: 'release/**',
							channels: [ 'CHANNEL_ID_1' ],
							excludeDefaultChannel: true,
						},
					],
				},
			},
			{
				name: 'config with multiple routes',
				input: {
					defaultChannel: 'CHANNEL_ID_0',
					routes: [
						{
							checkType: 'test-1',
							channels: [ 'CHANNEL_1' ],
						},
						{
							refName: 'feature/**',
							channels: [ 'CHANNEL_2' ],
							excludeDefaultChannel: true,
						},
						{
							checkType: 'test-2',
							refName: 'release/**',
							channels: [ 'CHANNEL_3', 'CHANNEL_4' ],
						},
					],
				},
				expected: {
					defaultChannel: 'CHANNEL_ID_0',
					routes: [
						{
							checkType: 'test-1',
							channels: [ 'CHANNEL_1' ],
							excludeDefaultChannel: false,
						},
						{
							refName: 'feature/**',
							channels: [ 'CHANNEL_2' ],
							excludeDefaultChannel: true,
						},
						{
							checkType: 'test-2',
							refName: 'release/**',
							channels: [ 'CHANNEL_3', 'CHANNEL_4' ],
							excludeDefaultChannel: false,
						},
					],
				},
			},
			{
				name: 'config with both checkType and refName in single route',
				input: {
					defaultChannel: 'CHANNEL_ID_0',
					routes: [
						{
							checkType: 'e2e-tests',
							refName: 'trunk/**',
							channels: [ 'CHANNEL_ID_1' ],
						},
					],
				},
				expected: {
					defaultChannel: 'CHANNEL_ID_0',
					routes: [
						{
							checkType: 'e2e-tests',
							refName: 'trunk/**',
							channels: [ 'CHANNEL_ID_1' ],
							excludeDefaultChannel: false,
						},
					],
				},
			},
			{
				name: 'excludeDefaultChannel defaults to false when not specified',
				input: {
					defaultChannel: 'CHANNEL_ID_0',
					routes: [
						{
							checkType: 'daily-checks',
							channels: [ 'CHANNEL_ID_1' ],
						},
						{
							refName: 'feature/**',
							channels: [ 'CHANNEL_ID_2' ],
						},
						{
							checkType: 'e2e-tests',
							refName: 'trunk/**',
							channels: [ 'CHANNEL_ID_3' ],
						},
					],
				},
				expected: {
					defaultChannel: 'CHANNEL_ID_0',
					routes: [
						{
							checkType: 'daily-checks',
							channels: [ 'CHANNEL_ID_1' ],
							excludeDefaultChannel: false,
						},
						{
							refName: 'feature/**',
							channels: [ 'CHANNEL_ID_2' ],
							excludeDefaultChannel: false,
						},
						{
							checkType: 'e2e-tests',
							refName: 'trunk/**',
							channels: [ 'CHANNEL_ID_3' ],
							excludeDefaultChannel: false,
						},
					],
				},
			},
			{
				name: 'mixed excludeDefaultChannel specified and not specified',
				input: {
					defaultChannel: 'CHANNEL_ID_0',
					routes: [
						{
							checkType: 'daily-checks',
							channels: [ 'CHANNEL_ID_1' ],
						},
						{
							refName: 'feature/**',
							channels: [ 'CHANNEL_ID_2' ],
							excludeDefaultChannel: true,
						},
						{
							checkType: 'e2e-tests',
							channels: [ 'CHANNEL_ID_3' ],
							excludeDefaultChannel: false,
						},
					],
				},
				expected: {
					defaultChannel: 'CHANNEL_ID_0',
					routes: [
						{
							checkType: 'daily-checks',
							channels: [ 'CHANNEL_ID_1' ],
							excludeDefaultChannel: false,
						},
						{
							refName: 'feature/**',
							channels: [ 'CHANNEL_ID_2' ],
							excludeDefaultChannel: true,
						},
						{
							checkType: 'e2e-tests',
							channels: [ 'CHANNEL_ID_3' ],
							excludeDefaultChannel: false,
						},
					],
				},
			},
		] )( 'should correctly parse $name', ( { input, expected } ) => {
			expect( parseConfig( input ) ).toMatchObject( expected );
		} );
	} );
} );

describe( 'getConfiguredChannels', () => {
	describe( 'input validation', () => {
		it( 'should throw error when config is undefined', () => {
			expect( () =>
				getConfiguredChannels( undefined, 'main', 'test-check' )
			).toThrow( 'Config must be provided to get configured channels' );
		} );
	} );

	describe( 'channel selection', () => {
		test.each( [
			{
				name: 'matching checkType only',
				config: {
					defaultChannel: 'DEFAULT_CHANNEL',
					routes: [
						{
							checkType: 'e2e-tests',
							channels: [ 'CHANNEL_1' ],
						},
					],
				},
				refName: 'main',
				checkName: 'e2e-tests',
				expected: [ 'CHANNEL_1', 'DEFAULT_CHANNEL' ],
			},
			{
				name: 'matching refName only',
				config: {
					defaultChannel: 'DEFAULT_CHANNEL',
					routes: [
						{
							refName: 'feature/**',
							channels: [ 'CHANNEL_2' ],
						},
					],
				},
				refName: 'feature/test',
				checkName: 'unknown-check',
				expected: [ 'CHANNEL_2', 'DEFAULT_CHANNEL' ],
			},
			{
				name: 'matching both checkType and refName',
				config: {
					defaultChannel: 'DEFAULT_CHANNEL',
					routes: [
						{
							checkType: 'release-checks',
							refName: 'release/**',
							channels: [ 'CHANNEL_3' ],
						},
					],
				},
				refName: 'release/1.0',
				checkName: 'release-checks',
				expected: [ 'CHANNEL_3', 'DEFAULT_CHANNEL' ],
			},
			{
				name: 'matching route with excludeDefaultChannel',
				config: {
					defaultChannel: 'DEFAULT_CHANNEL',
					routes: [
						{
							checkType: 'daily-checks',
							channels: [ 'CHANNEL_4' ],
							excludeDefaultChannel: true,
						},
					],
				},
				refName: 'main',
				checkName: 'daily-checks',
				expected: [ 'CHANNEL_4' ],
			},
			{
				name: 'no matching routes',
				config: {
					defaultChannel: 'DEFAULT_CHANNEL',
					routes: [
						{
							checkType: 'e2e-tests',
							channels: [ 'CHANNEL_1' ],
						},
					],
				},
				refName: 'main',
				checkName: 'unknown-check',
				expected: [ 'DEFAULT_CHANNEL' ],
			},
			{
				name: 'multiple matching routes',
				config: {
					defaultChannel: 'DEFAULT_CHANNEL',
					routes: [
						{
							checkType: 'e2e-tests',
							channels: [ 'CHANNEL_1' ],
						},
						{
							checkType: 'e2e-tests',
							channels: [ 'CHANNEL_2' ],
						},
					],
				},
				refName: 'main',
				checkName: 'e2e-tests',
				expected: [ 'CHANNEL_1', 'CHANNEL_2', 'DEFAULT_CHANNEL' ],
			},
			{
				name: 'matching nested feature path',
				config: {
					defaultChannel: 'DEFAULT_CHANNEL',
					routes: [
						{
							refName: '**/feature/*',
							channels: [ 'CHANNEL_1' ],
						},
					],
				},
				refName: 'team-a/feature/login',
				checkName: 'unit-test',
				expected: [ 'CHANNEL_1', 'DEFAULT_CHANNEL' ],
			},
			{
				name: 'matching specific beta release pattern',
				config: {
					defaultChannel: 'DEFAULT_CHANNEL',
					routes: [
						{
							refName: 'release/*/beta',
							channels: [ 'CHANNEL_1' ],
						},
					],
				},
				refName: 'release/1.2.3/beta',
				checkName: 'unit-test',
				expected: [ 'CHANNEL_1', 'DEFAULT_CHANNEL' ],
			},
			{
				name: 'matching wildcard in checkType',
				config: {
					defaultChannel: 'DEFAULT_CHANNEL',
					routes: [
						{
							checkType: 'test-*-checks',
							channels: [ 'CHANNEL_1' ],
						},
					],
				},
				refName: 'main',
				checkName: 'test-integration-checks',
				expected: [ 'CHANNEL_1', 'DEFAULT_CHANNEL' ],
			},
			{
				name: 'matching complex nested path and checkType pattern',
				config: {
					defaultChannel: 'DEFAULT_CHANNEL',
					routes: [
						{
							refName: 'projects/*/tests/**',
							checkType: '*-e2e-*',
							channels: [ 'CHANNEL_1' ],
						},
					],
				},
				refName: 'projects/auth/tests/login',
				checkName: 'auth-e2e-tests',
				expected: [ 'CHANNEL_1', 'DEFAULT_CHANNEL' ],
			},
			{
				name: 'matching multiple glob patterns',
				config: {
					defaultChannel: 'DEFAULT_CHANNEL',
					routes: [
						{
							checkType: 'e2e-tests',
							channels: [ 'CHANNEL_1' ],
						},
						{
							refName: 'feature/**',
							channels: [ 'CHANNEL_2' ],
						},
					],
				},
				refName: 'feature/test',
				checkName: 'e2e-tests',
				expected: [ 'CHANNEL_1', 'CHANNEL_2', 'DEFAULT_CHANNEL' ],
			},
		] )(
			'should return correct channels for $name',
			( { config, refName, checkName, expected } ) => {
				const result = getConfiguredChannels(
					config,
					refName,
					checkName
				);
				expect( new Set( result ) ).toEqual( new Set( expected ) );
			}
		);
	} );
} );

describe( 'resolveChannels', () => {
	let originalEnv: NodeJS.ProcessEnv;

	beforeEach( () => {
		originalEnv = { ...process.env };
		process.env.SLACK_CHANNEL_1 = 'C1234567890';
		process.env.SLACK_CHANNEL_2 = 'C0987654321';
	} );

	afterEach( () => {
		process.env = originalEnv;
	} );

	test( 'should resolve existing environment variables', () => {
		const result = resolveChannels( [
			'SLACK_CHANNEL_1',
			'SLACK_CHANNEL_2',
		] );
		expect( result ).toEqual( [ 'C1234567890', 'C0987654321' ] );
	} );

	test( 'should throw error for non-existent environment variables', () => {
		expect( () =>
			resolveChannels( [
				'SLACK_CHANNEL_1',
				'NON_EXISTENT_CHANNEL',
				'SLACK_CHANNEL_2',
			] )
		).toThrow(
			'Missing required environment variables: NON_EXISTENT_CHANNEL'
		);
	} );

	test( 'should handle empty array', () => {
		const result = resolveChannels( [] );
		expect( result ).toEqual( [] );
	} );

	test( 'should throw error when all variables are undefined', () => {
		expect( () =>
			resolveChannels( [ 'NON_EXISTENT_1', 'NON_EXISTENT_2' ] )
		).toThrow(
			'Missing required environment variables: NON_EXISTENT_1, NON_EXISTENT_2'
		);
	} );

	test( 'should handle environment variables with empty values', () => {
		process.env.EMPTY_CHANNEL = '';
		const result = resolveChannels( [
			'EMPTY_CHANNEL',
			'SLACK_CHANNEL_1',
		] );
		expect( result ).toEqual( [ '', 'C1234567890' ] );
	} );
} );
