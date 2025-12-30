/**
 * Internal dependencies
 */
import {
	isWooCommerceScript,
	getFilename,
	shouldSkipLine,
	extractJsUrl,
	parseStackForCallerUrl,
	getWarningInfo,
	createWcProxy,
	type ScriptRegistry,
	type WcGlobalExportsMap,
} from '../utils';

describe( 'Dependency Detection Utils', () => {
	describe( 'isWooCommerceScript', () => {
		it( 'returns true for WooCommerce core scripts', () => {
			expect(
				isWooCommerceScript(
					'https://example.com/wp-content/plugins/woocommerce/client/blocks/index.js'
				)
			).toBe( true );
			expect(
				isWooCommerceScript(
					'https://example.com/wp-content/plugins/woocommerce/assets/js/frontend.js'
				)
			).toBe( true );
			expect(
				isWooCommerceScript(
					'https://example.com/wp-content/plugins/woocommerce/build/bundle.js'
				)
			).toBe( true );
		} );

		it( 'returns false for WooCommerce extensions', () => {
			expect(
				isWooCommerceScript(
					'https://example.com/wp-content/plugins/woocommerce-subscriptions/assets/js/index.js'
				)
			).toBe( false );
			expect(
				isWooCommerceScript(
					'https://example.com/wp-content/plugins/woocommerce-payments/build/index.js'
				)
			).toBe( false );
		} );

		it( 'returns false for empty or null URLs', () => {
			expect( isWooCommerceScript( '' ) ).toBe( false );
			expect( isWooCommerceScript( null ) ).toBe( false );
		} );
	} );

	describe( 'getFilename', () => {
		it( 'extracts filename from URL', () => {
			expect(
				getFilename( 'https://example.com/path/to/script.js' )
			).toBe( 'script.js' );
		} );

		it( 'removes query strings', () => {
			expect(
				getFilename( 'https://example.com/path/to/script.js?ver=1.0.0' )
			).toBe( 'script.js' );
		} );

		it( 'removes hash fragments', () => {
			expect(
				getFilename( 'https://example.com/path/to/script.js#section' )
			).toBe( 'script.js' );
		} );

		it( 'returns unknown for empty or null URLs', () => {
			expect( getFilename( '' ) ).toBe( 'unknown' );
			expect( getFilename( null ) ).toBe( 'unknown' );
		} );

		it( 'returns unknown for URL with trailing slash and no filename', () => {
			expect( getFilename( 'https://example.com/' ) ).toBe( 'unknown' );
			expect( getFilename( '/' ) ).toBe( 'unknown' );
		} );
	} );

	describe( 'shouldSkipLine', () => {
		it( 'skips lines from current page', () => {
			// Stack trace format: path appears after opening paren, followed by colon and line number
			expect(
				shouldSkipLine( '    at someFunc (/cart/:123:45)', '/cart/' )
			).toBe( true );
			expect(
				shouldSkipLine(
					'    at someFunc (/checkout/:123:45)',
					'/checkout/'
				)
			).toBe( true );
		} );

		it( 'skips webpack source-mapped files', () => {
			expect(
				shouldSkipLine(
					'    at someFunc (webpack://woocommerce/src/index.js:10:5)',
					'/cart/'
				)
			).toBe( true );
		} );

		it( 'does not skip external script URLs', () => {
			expect(
				shouldSkipLine(
					'    at someFunc (https://example.com/script.js:10:5)',
					'/cart/'
				)
			).toBe( false );
		} );
	} );

	describe( 'extractJsUrl', () => {
		it( 'extracts URL from stack line with line numbers', () => {
			expect(
				extractJsUrl(
					'    at someFunc (https://example.com/script.js:10:5)'
				)
			).toBe( 'https://example.com/script.js' );
		} );

		it( 'extracts URL with query string', () => {
			expect(
				extractJsUrl(
					'    at someFunc (https://example.com/script.js?ver=1.0:10:5)'
				)
			).toBe( 'https://example.com/script.js' );
		} );

		it( 'returns null for lines without .js URLs', () => {
			expect( extractJsUrl( '    at someFunc (cart/:123:45)' ) ).toBe(
				null
			);
			expect( extractJsUrl( 'Error: test error' ) ).toBe( null );
		} );

		it( 'handles http URLs', () => {
			expect(
				extractJsUrl(
					'    at someFunc (http://localhost/script.js:10:5)'
				)
			).toBe( 'http://localhost/script.js' );
		} );
	} );

	describe( 'parseStackForCallerUrl', () => {
		it( 'returns null for empty stack', () => {
			expect( parseStackForCallerUrl( null, '/cart/' ) ).toBe( null );
			expect( parseStackForCallerUrl( '', '/cart/' ) ).toBe( null );
		} );

		it( 'finds external script URL in stack trace', () => {
			const stack = `Error
    at getCallerScriptUrl (cart/:141:17)
    at Object.__wcProxyGet [as get] (cart/:286:23)
    at getBlocksConfiguration (https://example.com/wp-content/plugins/my-plugin/utils.js:10:31)
    at canMakePayment (https://example.com/wp-content/plugins/my-plugin/index.js:99:32)`;

			expect( parseStackForCallerUrl( stack, '/cart/' ) ).toBe(
				'https://example.com/wp-content/plugins/my-plugin/utils.js'
			);
		} );

		it( 'returns null when no external URL found', () => {
			const stack = `Error
    at getCallerScriptUrl (cart/:141:17)
    at Object.__wcProxyGet [as get] (cart/:286:23)`;

			expect( parseStackForCallerUrl( stack, '/cart/' ) ).toBe( null );
		} );

		it( 'handles real-world stack trace', () => {
			const stack = `Error
    at getCallerScriptUrl (cart/:141:17)
    at Object.__wcProxyGet [as get] (cart/:286:23)
    at getBlocksConfiguration (utils.js:10:31)
    at canMakePayment (index.js:99:32)
    at ExpressPaymentMethodConfig.<anonymous> (payment-method-config-helper.ts:30:41)
    at checkPaymentMethodsCanPay (check-payment-methods.ts:237:21)
    at async actions.ts:189:29
    at async updatePaymentMethods (update-payment-methods.ts:24:2)
    at async index.ts:126:28`;

			// No https:// URLs in this stack, so should return null
			expect( parseStackForCallerUrl( stack, '/cart/' ) ).toBe( null );
		} );

		it( 'handles stack with versioned script URLs', () => {
			const stack = `Error
    at getCallerScriptUrl (cart/:141:17)
    at Object.__wcProxyGet [as get] (cart/:286:23)
    at C (https://example.com/wp-content/plugins/extension/index.js?ver=7d1eee3294e4247830b6:19:2191)`;

			expect( parseStackForCallerUrl( stack, '/cart/' ) ).toBe(
				'https://example.com/wp-content/plugins/extension/index.js'
			);
		} );
	} );

	describe( 'getWarningInfo', () => {
		const mockRegistry: ScriptRegistry = {
			'https://example.com/registered-with-dep.js': {
				handle: 'my-script-with-dep',
				deps: [ 'wc-blocks-checkout' ],
			},
			'https://example.com/registered-without-dep.js': {
				handle: 'my-script-without-dep',
				deps: [],
			},
		};

		it( 'returns inline warning for null callerUrl', () => {
			const result = getWarningInfo(
				null,
				'blocksCheckout',
				'wc-blocks-checkout',
				mockRegistry
			);

			expect( result?.type ).toBe( 'inline' );
			expect( result?.message ).toBe(
				'[WooCommerce] An inline or unknown script accessed wc.blocksCheckout without proper dependency declaration. This script should declare "wc-blocks-checkout" as a dependency.'
			);
		} );

		it( 'returns unregistered warning for unknown script URL', () => {
			const result = getWarningInfo(
				'https://example.com/unregistered.js',
				'blocksCheckout',
				'wc-blocks-checkout',
				mockRegistry
			);

			expect( result?.type ).toBe( 'unregistered' );
			expect( result?.message ).toBe(
				'[WooCommerce] Unregistered script "unregistered.js" accessed wc.blocksCheckout. This script should be registered with wp_enqueue_script() and declare "wc-blocks-checkout" as a dependency.'
			);
		} );

		it( 'returns missing-dependency warning for registered script without dependency', () => {
			const result = getWarningInfo(
				'https://example.com/registered-without-dep.js',
				'blocksCheckout',
				'wc-blocks-checkout',
				mockRegistry
			);

			expect( result?.type ).toBe( 'missing-dependency' );
			expect( result?.message ).toBe(
				'[WooCommerce] Script "my-script-without-dep" accessed wc.blocksCheckout without declaring "wc-blocks-checkout" as a dependency. Add "wc-blocks-checkout" to the script\'s dependencies array.'
			);
		} );

		it( 'returns null for registered script with correct dependency', () => {
			const result = getWarningInfo(
				'https://example.com/registered-with-dep.js',
				'blocksCheckout',
				'wc-blocks-checkout',
				mockRegistry
			);

			expect( result ).toBe( null );
		} );

		it( 'returns unregistered warning for malformed registry entry with missing deps', () => {
			const malformedRegistry = {
				'https://example.com/malformed.js': {
					handle: 'malformed-script',
				},
			} as unknown as ScriptRegistry;

			const result = getWarningInfo(
				'https://example.com/malformed.js',
				'blocksCheckout',
				'wc-blocks-checkout',
				malformedRegistry
			);

			expect( result?.type ).toBe( 'unregistered' );
		} );

		it( 'returns unregistered warning for malformed registry entry with non-array deps', () => {
			const malformedRegistry = {
				'https://example.com/malformed.js': {
					handle: 'malformed-script',
					deps: 'not-an-array',
				},
			} as unknown as ScriptRegistry;

			const result = getWarningInfo(
				'https://example.com/malformed.js',
				'blocksCheckout',
				'wc-blocks-checkout',
				malformedRegistry
			);

			expect( result?.type ).toBe( 'unregistered' );
		} );

		it( 'returns unregistered warning when registry is not an object', () => {
			const result = getWarningInfo(
				'https://example.com/script.js',
				'blocksCheckout',
				'wc-blocks-checkout',
				null as unknown as ScriptRegistry
			);

			expect( result?.type ).toBe( 'unregistered' );
		} );
	} );

	describe( 'createWcProxy', () => {
		it( 'returns value for non-tracked properties', () => {
			const target: Record< string, unknown > = { someProperty: 'value' };
			const proxy = createWcProxy(
				target,
				{} as WcGlobalExportsMap, // No tracked exports
				jest.fn(),
				jest.fn()
			);

			expect( proxy.someProperty ).toBe( 'value' );
		} );

		it( 'calls checkDependency for tracked properties', () => {
			const target: Record< string, unknown > = {
				blocksCheckout: { Component: () => {} },
			};
			const wcGlobalExports = {
				blocksCheckout: 'wc-blocks-checkout',
			} as WcGlobalExportsMap;
			const getCallerScriptUrl = jest
				.fn()
				.mockReturnValue( 'https://example.com/script.js' );
			const checkDependency = jest.fn();

			const proxy = createWcProxy(
				target,
				wcGlobalExports,
				getCallerScriptUrl,
				checkDependency
			);

			const result = proxy.blocksCheckout;

			expect( getCallerScriptUrl ).toHaveBeenCalled();
			expect( checkDependency ).toHaveBeenCalledWith(
				'https://example.com/script.js',
				'blocksCheckout',
				'wc-blocks-checkout'
			);
			expect( result ).toBe( target.blocksCheckout );
		} );

		it( 'prevents infinite recursion with guard flag', () => {
			let accessCount = 0;
			const target: Record< string, unknown > = {
				get blocksCheckout(): unknown {
					accessCount++;
					// Simulate nested access (like blocksCheckout using wcSettings)
					if ( accessCount === 1 ) {
						// First access triggers nested access
						return this.wcSettings;
					}
					return { Component: () => {} };
				},
				wcSettings: { currency: 'USD' },
			};

			const wcGlobalExports = {
				blocksCheckout: 'wc-blocks-checkout',
				wcSettings: 'wc-settings',
			} as WcGlobalExportsMap;
			const getCallerScriptUrl = jest
				.fn()
				.mockReturnValue( 'https://example.com/script.js' );
			const checkDependency = jest.fn();

			const proxy = createWcProxy(
				target,
				wcGlobalExports,
				getCallerScriptUrl,
				checkDependency
			);

			// Access blocksCheckout which internally accesses wcSettings
			// eslint-disable-next-line no-unused-expressions
			proxy.blocksCheckout;

			// checkDependency should only be called once (for blocksCheckout),
			// not twice (the nested wcSettings access should be blocked)
			expect( checkDependency ).toHaveBeenCalledTimes( 1 );
			expect( checkDependency ).toHaveBeenCalledWith(
				'https://example.com/script.js',
				'blocksCheckout',
				'wc-blocks-checkout'
			);
		} );

		it( 'resets guard flag after access completes', () => {
			const target: Record< string, unknown > = {
				blocksCheckout: {},
				wcSettings: {},
			};
			const wcGlobalExports = {
				blocksCheckout: 'wc-blocks-checkout',
				wcSettings: 'wc-settings',
			} as WcGlobalExportsMap;
			const checkDependency = jest.fn();

			const proxy = createWcProxy(
				target,
				wcGlobalExports,
				jest.fn().mockReturnValue( 'https://example.com/script.js' ),
				checkDependency
			);

			// First access
			// eslint-disable-next-line no-unused-expressions
			proxy.blocksCheckout;
			// Second independent access should also trigger check
			// eslint-disable-next-line no-unused-expressions
			proxy.wcSettings;

			expect( checkDependency ).toHaveBeenCalledTimes( 2 );
		} );
	} );
} );
