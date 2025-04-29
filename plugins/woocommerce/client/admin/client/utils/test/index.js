/**
 * Internal dependencies
 */
import {
	getUrlParams,
	getTimeFrame,
	createDeprecatedPropertiesProxy,
} from '../index';

describe( 'getUrlParams', () => {
	let locationSearch = '?param1=text1&param2=text2';

	test( 'should return an object with sent params', () => {
		const { param1, param2 } = getUrlParams( locationSearch );
		expect( param1 ).toEqual( 'text1' );
		expect( param2 ).toEqual( 'text2' );
	} );

	test( 'should return an object with 2 keys/params', () => {
		const params = getUrlParams( locationSearch );
		expect( Object.keys( params ).length ).toEqual( 2 );
	} );

	test( 'should return an empty object', () => {
		locationSearch = '';
		const params = getUrlParams( locationSearch );
		expect( Object.keys( params ).length ).toEqual( 0 );
	} );

	test( 'should return an object with key "no_value" equal to "undefined"', () => {
		locationSearch = 'no_value';
		const { no_value: noValue } = getUrlParams( locationSearch );
		expect( noValue ).toBeUndefined();
	} );
} );

describe( 'getTimeFrame', () => {
	test.each( [
		{
			timeInMs: 1000,
			expected: '0-2s',
		},
		{
			timeInMs: 3000,
			expected: '2-5s',
		},
		{
			timeInMs: 100000,
			expected: '>60s',
		},
	] )(
		'should return time frames $expected when given $timeInMs',
		( { timeInMs, expected } ) => {
			expect( getTimeFrame( timeInMs ) ).toEqual( expected );
		}
	);
} );

describe( 'createDeprecatedPropertiesProxy', () => {
	let consoleWarnSpy;
	let wcSettings;
	let proxiedSettings;

	beforeEach( () => {
		consoleWarnSpy = jest
			.spyOn( console, 'warn' )
			.mockImplementation( () => {} );

		wcSettings = {
			admin: {
				onboarding: {
					profile: {
						name: 'hello',
						arr: [ 'one', 'two', 'three' ],
					},
				},
			},
		};

		proxiedSettings = createDeprecatedPropertiesProxy( wcSettings, {
			admin: {
				onboarding: {
					profile:
						'Deprecated: wcSettings.admin.onboarding.profile is deprecated. It is planned to be released in WooCommerce 10.0.0. Please use `getProfileItems` from the onboarding store. See https://github.com/woocommerce/woocommerce/tree/trunk/packages/js/data/src/onboarding for more information.',
				},
			},
		} );
	} );

	afterEach( () => {
		consoleWarnSpy.mockRestore();
	} );

	it( 'should return non-object values as is', () => {
		expect( createDeprecatedPropertiesProxy( null, {} ) ).toBeNull();
		expect(
			createDeprecatedPropertiesProxy( undefined, {} )
		).toBeUndefined();
		expect( createDeprecatedPropertiesProxy( 42, {} ) ).toBe( 42 );
		expect( createDeprecatedPropertiesProxy( 'string', {} ) ).toBe(
			'string'
		);
		expect( createDeprecatedPropertiesProxy( true, {} ) ).toBe( true );
	} );

	it( 'should handle wcSettings deprecation warnings', () => {
		expect( consoleWarnSpy ).not.toHaveBeenCalled();
		expect( proxiedSettings.admin.onboarding.profile.name ).toBe( 'hello' );
		expect( consoleWarnSpy ).toHaveBeenCalledWith(
			'Deprecated: wcSettings.admin.onboarding.profile is deprecated. It is planned to be released in WooCommerce 10.0.0. Please use `getProfileItems` from the onboarding store. See https://github.com/woocommerce/woocommerce/tree/trunk/packages/js/data/src/onboarding for more information.'
		);

		// Reset spy for next test
		consoleWarnSpy.mockClear();

		// Array methods should work on wcSettings
		expect( proxiedSettings.admin.onboarding.profile.arr.length ).toBe( 3 );
		expect( [ ...proxiedSettings.admin.onboarding.profile.arr ] ).toEqual( [
			'one',
			'two',
			'three',
		] );
		expect( consoleWarnSpy ).toHaveBeenCalledWith(
			'Deprecated: wcSettings.admin.onboarding.profile is deprecated. It is planned to be released in WooCommerce 10.0.0. Please use `getProfileItems` from the onboarding store. See https://github.com/woocommerce/woocommerce/tree/trunk/packages/js/data/src/onboarding for more information.'
		);
	} );

	it( 'should not log a warning when accessing non-deprecated wcSettings properties', () => {
		expect( proxiedSettings.admin ).toBeDefined();
		expect( consoleWarnSpy ).not.toHaveBeenCalled();
	} );

	it( 'should log a warning when accessing a deprecated property', () => {
		const obj = {
			foo: {
				bar: 'test',
			},
		};

		const proxiedObj = createDeprecatedPropertiesProxy( obj, {
			foo: {
				bar: 'foo.bar is deprecated',
			},
		} );

		expect( consoleWarnSpy ).not.toHaveBeenCalled();
		expect( proxiedObj.foo.bar ).toBe( 'test' );
		expect( consoleWarnSpy ).toHaveBeenCalledWith(
			'foo.bar is deprecated'
		);
	} );

	it( 'should handle array methods correctly', () => {
		const arr = [ 1, 2, 3 ];
		const proxiedArr = createDeprecatedPropertiesProxy( arr, {
			1: 'accessing index 1 is deprecated',
		} );

		// Reset spy
		consoleWarnSpy.mockClear();

		// Test array spreading
		expect( [ ...proxiedArr ] ).toEqual( [ 1, 2, 3 ] );
		expect( consoleWarnSpy ).toHaveBeenCalled(); // Symbol.iterator should trigger warning

		// Reset spy
		consoleWarnSpy.mockClear();

		// Test accessing deprecated index
		expect( proxiedArr[ 1 ] ).toBe( 2 );
		expect( consoleWarnSpy ).toHaveBeenCalledWith(
			'accessing index 1 is deprecated'
		);
	} );

	it( 'should handle numeric property names', () => {
		const obj = {
			1: 'one',
			2: 'two',
		};

		const proxiedObj = createDeprecatedPropertiesProxy( obj, {
			1: 'numeric property 1 is deprecated',
		} );

		expect( proxiedObj[ 1 ] ).toBe( 'one' );
		expect( consoleWarnSpy ).toHaveBeenCalledWith(
			'numeric property 1 is deprecated'
		);
	} );

	it( 'should handle boolean property names', () => {
		const obj = {
			[ true ]: 'true value',
			[ false ]: 'false value',
		};

		const proxiedObj = createDeprecatedPropertiesProxy( obj, {
			true: 'true prop is deprecated',
			false: 'false prop is deprecated',
		} );

		// eslint-disable-next-line dot-notation
		expect( proxiedObj[ true ] ).toBe( 'true value' );
		expect( consoleWarnSpy ).toHaveBeenCalledWith(
			'true prop is deprecated'
		);

		consoleWarnSpy.mockClear();

		// eslint-disable-next-line dot-notation
		expect( proxiedObj[ false ] ).toBe( 'false value' );
		expect( consoleWarnSpy ).toHaveBeenCalledWith(
			'false prop is deprecated'
		);
	} );

	it( 'should handle nested objects with arrays', () => {
		const obj = {
			items: [ { id: 1 }, { id: 2 } ],
		};

		const proxiedObj = createDeprecatedPropertiesProxy( obj, {
			items: {
				0: {
					id: 'first item id is deprecated',
				},
			},
		} );

		expect( proxiedObj.items[ 0 ].id ).toBe( 1 );
		expect( consoleWarnSpy ).toHaveBeenCalledWith(
			'first item id is deprecated'
		);

		// Array methods should work
		expect( proxiedObj.items.length ).toBe( 2 );
		expect( [ ...proxiedObj.items ] ).toEqual( [ { id: 1 }, { id: 2 } ] );
	} );

	it( 'should handle Symbol properties without triggering deprecation warnings', () => {
		const testSymbol = Symbol( 'test' );
		const iteratorSymbol = Symbol.iterator;

		const obj = {
			[ testSymbol ]: 'symbol value',
			*[ iteratorSymbol ]() {
				yield 1;
				yield 2;
			},
			regularProp: 'regular value',
		};

		const proxiedObj = createDeprecatedPropertiesProxy( obj, {
			regularProp: 'regular prop is deprecated',
			[ testSymbol.description ]: 'should not trigger for symbol',
			Symbol: 'should not trigger for symbol without description',
		} );

		// Custom Symbol access should not trigger warning
		expect( proxiedObj[ testSymbol ] ).toBe( 'symbol value' );
		expect( consoleWarnSpy ).not.toHaveBeenCalled();

		// Built-in Symbol access should not trigger warning
		expect( typeof proxiedObj[ iteratorSymbol ] ).toBe( 'function' );
		expect( consoleWarnSpy ).not.toHaveBeenCalled();

		// Regular property should still trigger warning
		expect( proxiedObj.regularProp ).toBe( 'regular value' );
		expect( consoleWarnSpy ).toHaveBeenCalledWith(
			'regular prop is deprecated'
		);
	} );

	it( 'should handle undefined and null properties correctly', () => {
		const obj = {
			undefinedProp: undefined,
			nullProp: null,
			nested: {
				undefinedChild: undefined,
				nullChild: null,
			},
		};

		const proxiedObj = createDeprecatedPropertiesProxy( obj, {
			undefinedProp: 'undefined prop is deprecated',
			nullProp: 'null prop is deprecated',
			nested: {
				undefinedChild: 'undefined child is deprecated',
				nullChild: 'null child is deprecated',
			},
		} );

		expect( proxiedObj.undefinedProp ).toBeUndefined();
		expect( consoleWarnSpy ).toHaveBeenCalledWith(
			'undefined prop is deprecated'
		);

		consoleWarnSpy.mockClear();
		expect( proxiedObj.nullProp ).toBeNull();
		expect( consoleWarnSpy ).toHaveBeenCalledWith(
			'null prop is deprecated'
		);

		consoleWarnSpy.mockClear();
		expect( proxiedObj.nested.undefinedChild ).toBeUndefined();
		expect( consoleWarnSpy ).toHaveBeenCalledWith(
			'undefined child is deprecated'
		);

		consoleWarnSpy.mockClear();
		expect( proxiedObj.nested.nullChild ).toBeNull();
		expect( consoleWarnSpy ).toHaveBeenCalledWith(
			'null child is deprecated'
		);
	} );
} );
