/**
 * Internal dependencies
 */
import { deprecatedAdminProperties, getAdminSetting } from '../admin-settings';

describe( 'getAdminSetting', () => {
	let consoleWarnSpy;
	let originalNodeEnv;
	const fallback = {
		onboarding: {
			profile: {
				name: 'tester',
			},
		},
		test: {},
	};

	const filter = ( value ) => value;

	beforeEach( () => {
		originalNodeEnv = process.env.NODE_ENV; // Store original NODE_ENV
		process.env.NODE_ENV = 'development'; // Set to development mode
		consoleWarnSpy = jest
			.spyOn( console, 'warn' )
			.mockImplementation( () => {} );
	} );

	afterEach( () => {
		consoleWarnSpy.mockRestore();
		process.env.NODE_ENV = originalNodeEnv; // Restore original NODE_ENV
	} );

	it( 'should log a warning if the deprecated setting exists under "admin.onboarding.profile"', () => {
		const deprecatedWcSettings = {
			onboarding: {
				profile: 'This setting is deprecated',
			},
		};

		const onboarding = getAdminSetting(
			'onboarding',
			fallback,
			filter,
			deprecatedWcSettings
		);

		void onboarding.profile;

		expect( consoleWarnSpy ).toHaveBeenCalledWith(
			'This setting is deprecated'
		);
	} );

	it( 'should not log a warning if the setting does not exist', () => {
		const deprecatedWcSettings = {
			onboarding: {
				profile: 'This setting is deprecated',
			},
		};

		getAdminSetting( 'test', fallback, filter, deprecatedWcSettings );

		expect( consoleWarnSpy ).not.toHaveBeenCalled();
	} );

	it( 'should not log a warning if NODE_ENV is not "development"', () => {
		const _originalNodeEnv = process.env.NODE_ENV;
		process.env.NODE_ENV = 'production'; // Simulate non-development environment

		const deprecatedWcSettings = {
			onboarding: {
				profile: 'This setting is deprecated',
			},
		};

		getAdminSetting( 'onboarding', fallback, filter, deprecatedWcSettings );

		expect( consoleWarnSpy ).not.toHaveBeenCalled();

		process.env.NODE_ENV = _originalNodeEnv; // Restore ENV
	} );

	it( 'should not treat admin features as deprecated settings', () => {
		const features = getAdminSetting( 'features', {
			'launch-your-store': {
				is_enabled: true,
			},
		} );

		void features[ 'launch-your-store' ];

		expect( deprecatedAdminProperties.features ).toBeUndefined();
		expect( consoleWarnSpy ).not.toHaveBeenCalled();
	} );
} );
