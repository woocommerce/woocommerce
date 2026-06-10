/**
 * Internal dependencies
 */
import { getAdminSetting } from '../../admin-settings';
import {
	applyRetiredFeatureFlagDeprecationProxy,
	isFeatureEnabled,
} from '../features';
import { getRetiredFeatureFlagDeprecationMessage } from '../retired-feature-flags';

jest.mock( '../../admin-settings', () => ( {
	getAdminSetting: jest.fn(),
} ) );

const mockedGetAdminSetting = getAdminSetting as jest.Mock;

describe( 'isFeatureEnabled', () => {
	let consoleWarnSpy: jest.SpyInstance;

	beforeEach( () => {
		consoleWarnSpy = jest
			.spyOn( console, 'warn' )
			.mockImplementation( () => {} );
		mockedGetAdminSetting.mockReturnValue( {} );
	} );

	afterEach( () => {
		consoleWarnSpy.mockRestore();
		mockedGetAdminSetting.mockReset();
		delete ( window as Partial< Window > ).wcAdminFeatures;
	} );

	it( 'returns true and warns when a retired feature flag is missing from settings', () => {
		expect( isFeatureEnabled( 'launch-your-store' ) ).toBe( true );
		expect( consoleWarnSpy ).toHaveBeenCalledWith(
			getRetiredFeatureFlagDeprecationMessage( 'launch-your-store' )
		);
	} );

	it( 'uses wcAdminFeatures compatibility values without warning or reading admin settings features', () => {
		window.wcAdminFeatures = {
			'launch-your-store': false,
		} as Window[ 'wcAdminFeatures' ];

		expect( isFeatureEnabled( 'launch-your-store' ) ).toBe( false );
		expect( consoleWarnSpy ).not.toHaveBeenCalled();
		expect( mockedGetAdminSetting ).not.toHaveBeenCalled();
	} );

	it( 'returns false without warning for unknown feature flags', () => {
		expect( isFeatureEnabled( 'unknown-feature-flag' ) ).toBe( false );
		expect( consoleWarnSpy ).not.toHaveBeenCalled();
	} );

	it( 'keeps active configurable feature flags tied to settings', () => {
		mockedGetAdminSetting.mockReturnValue( {
			'settings-ui': {
				is_enabled: true,
			},
		} );

		expect( isFeatureEnabled( 'settings-ui' ) ).toBe( true );
		expect( consoleWarnSpy ).not.toHaveBeenCalled();
	} );
} );

describe( 'applyRetiredFeatureFlagDeprecationProxy', () => {
	let consoleWarnSpy: jest.SpyInstance;
	let originalNodeEnv: string | undefined;

	beforeEach( () => {
		originalNodeEnv = process.env.NODE_ENV;
		process.env.NODE_ENV = 'development';
		consoleWarnSpy = jest
			.spyOn( console, 'warn' )
			.mockImplementation( () => {} );
		window.wcAdminFeatures = {
			'launch-your-store': true,
			'settings-ui': true,
		} as Window[ 'wcAdminFeatures' ];
	} );

	afterEach( () => {
		consoleWarnSpy.mockRestore();
		process.env.NODE_ENV = originalNodeEnv;
		delete ( window as Partial< Window > ).wcAdminFeatures;
	} );

	it( 'warns when retired window.wcAdminFeatures values are accessed in development', () => {
		applyRetiredFeatureFlagDeprecationProxy();

		expect( window.wcAdminFeatures[ 'launch-your-store' ] ).toBe( true );
		expect( consoleWarnSpy ).toHaveBeenCalledWith(
			getRetiredFeatureFlagDeprecationMessage( 'launch-your-store' )
		);
	} );

	it( 'does not warn when active window.wcAdminFeatures values are accessed in development', () => {
		applyRetiredFeatureFlagDeprecationProxy();

		expect( window.wcAdminFeatures[ 'settings-ui' ] ).toBe( true );
		expect( consoleWarnSpy ).not.toHaveBeenCalled();
	} );

	it( 'does not warn when isFeatureEnabled reads a proxied wcAdminFeatures value', () => {
		applyRetiredFeatureFlagDeprecationProxy();
		consoleWarnSpy.mockClear();

		expect( isFeatureEnabled( 'launch-your-store' ) ).toBe( true );
		expect( consoleWarnSpy ).not.toHaveBeenCalled();
	} );
} );
