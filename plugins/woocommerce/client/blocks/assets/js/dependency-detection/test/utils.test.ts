/**
 * Internal dependencies
 */
import {
	isWooCommerceScript,
	getFilename,
	shouldSkipLine,
	detectStackFormat,
	extractJsUrl,
	extractJsUrlV8,
	extractJsUrlSpiderMonkey,
	parseStackForCallerUrl,
	getWarningInfo,
	createWcProxy,
	type ScriptRegistry,
	type WcGlobalExportsMap,
} from '../utils';

describe( 'Dependency Detection Utils', () => {
	describe( 'isWooCommerceScript', () => {
		const wcPluginUrl =
			'https://example.com/wp-content/plugins/woocommerce/';

		it( 'returns true for WooCommerce core scripts', () => {
			expect(
				isWooCommerceScript(
					'https://example.com/wp-content/plugins/woocommerce/client/blocks/index.js',
					wcPluginUrl
				)
			).toBe( true );
			expect(
				isWooCommerceScript(
					'https://example.com/wp-content/plugins/woocommerce/assets/js/frontend.js',
					wcPluginUrl
				)
			).toBe( true );
			expect(
				isWooCommerceScript(
					'https://example.com/wp-content/plugins/woocommerce/build/bundle.js',
					wcPluginUrl
				)
			).toBe( true );
			expect(
				isWooCommerceScript(
					'https://example.com/wp-content/plugins/woocommerce/vendor/some-lib.js',
					wcPluginUrl
				)
			).toBe( true );
		} );

		it( 'returns false for WooCommerce extensions', () => {
			expect(
				isWooCommerceScript(
					'https://example.com/wp-content/plugins/woocommerce-subscriptions/assets/js/index.js',
					wcPluginUrl
				)
			).toBe( false );
			expect(
				isWooCommerceScript(
					'https://example.com/wp-content/plugins/woocommerce-payments/build/index.js',
					wcPluginUrl
				)
			).toBe( false );
		} );

		it( 'returns false for empty or null URLs', () => {
			expect( isWooCommerceScript( '', wcPluginUrl ) ).toBe( false );
			expect( isWooCommerceScript( null, wcPluginUrl ) ).toBe( false );
		} );

		it( 'falls back to hardcoded pattern when wcPluginUrl is empty', () => {
			// Standard path should match fallback pattern
			expect(
				isWooCommerceScript(
					'https://example.com/wp-content/plugins/woocommerce/client/blocks/index.js',
					''
				)
			).toBe( true );
			expect(
				isWooCommerceScript(
					'https://example.com/wp-content/plugins/woocommerce/assets/js/frontend.js',
					''
				)
			).toBe( true );
			// Extensions should not match
			expect(
				isWooCommerceScript(
					'https://example.com/wp-content/plugins/woocommerce-subscriptions/assets/js/index.js',
					''
				)
			).toBe( false );
			// Custom paths won't work with fallback (expected limitation)
			expect(
				isWooCommerceScript(
					'https://example.com/app/extensions/woocommerce/client/blocks/index.js',
					''
				)
			).toBe( false );
		} );

		it( 'works with custom plugin directories', () => {
			const customPluginUrl =
				'https://example.com/app/extensions/woocommerce/';

			expect(
				isWooCommerceScript(
					'https://example.com/app/extensions/woocommerce/client/blocks/index.js',
					customPluginUrl
				)
			).toBe( true );
			expect(
				isWooCommerceScript(
					'https://example.com/app/extensions/woocommerce/assets/js/frontend.js',
					customPluginUrl
				)
			).toBe( true );
			// Other plugins in custom directory should not match
			expect(
				isWooCommerceScript(
					'https://example.com/app/extensions/woocommerce-subscriptions/assets/js/index.js',
					customPluginUrl
				)
			).toBe( false );
		} );

		it( 'returns false for scripts in non-asset directories', () => {
			expect(
				isWooCommerceScript(
					'https://example.com/wp-content/plugins/woocommerce/includes/some-file.js',
					wcPluginUrl
				)
			).toBe( false );
			expect(
				isWooCommerceScript(
					'https://example.com/wp-content/plugins/woocommerce/readme.js',
					wcPluginUrl
				)
			).toBe( false );
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

	describe( 'detectStackFormat', () => {
		it( 'detects V8 format (Chrome/Edge/Node)', () => {
			const v8Stack = `Error
    at getCallerScriptUrl (checkout/:7:3437)
    at Object.c [as get] (checkout/:7:2171)
    at bad-extension.js?ver=1.0.0:31:30`;

			expect( detectStackFormat( v8Stack ) ).toBe( 'v8' );
		} );

		it( 'detects SpiderMonkey format (Firefox/Safari)', () => {
			const spiderMonkeyStack = `s@https://store.local/checkout/:7:3437
c@https://store.local/checkout/:7:2171
@https://store.local/wp-content/plugins/wc-dependency-test/bad-extension.js?ver=1.0.0:31:7`;

			expect( detectStackFormat( spiderMonkeyStack ) ).toBe(
				'spidermonkey'
			);
		} );

		it( 'returns v8 as default for empty or invalid input', () => {
			expect( detectStackFormat( '' ) ).toBe( 'v8' );
			expect( detectStackFormat( null as unknown as string ) ).toBe(
				'v8'
			);
		} );
	} );

	describe( 'extractJsUrlV8', () => {
		it( 'extracts full URL with protocol', () => {
			expect(
				extractJsUrlV8(
					'    at someFunc (https://example.com/script.js:10:5)'
				)
			).toBe( 'https://example.com/script.js' );
		} );

		it( 'extracts bare filename without protocol', () => {
			expect( extractJsUrlV8( '    at bad-extension.js:31:30' ) ).toBe(
				null
			);
			// Bare filename needs to be in parentheses for V8 format
			expect(
				extractJsUrlV8( '    at someFunc (bad-extension.js:31:30)' )
			).toBe( 'bad-extension.js' );
		} );

		it( 'extracts URL with query string', () => {
			expect(
				extractJsUrlV8(
					'    at (https://example.com/script.js?ver=1.0.0:10:5)'
				)
			).toBe( 'https://example.com/script.js' );
		} );

		it( 'returns null for non-.js files', () => {
			expect( extractJsUrlV8( '    at someFunc (cart/:123:45)' ) ).toBe(
				null
			);
		} );
	} );

	describe( 'extractJsUrlSpiderMonkey', () => {
		it( 'extracts URL after @ symbol', () => {
			expect(
				extractJsUrlSpiderMonkey(
					'@https://store.local/wp-content/plugins/test/bad-extension.js?ver=1.0.0:31:7'
				)
			).toBe(
				'https://store.local/wp-content/plugins/test/bad-extension.js'
			);
		} );

		it( 'extracts URL with function name prefix', () => {
			expect(
				extractJsUrlSpiderMonkey(
					'someFunc@https://example.com/script.js:10:5'
				)
			).toBe( 'https://example.com/script.js' );
		} );

		it( 'returns null for V8 format lines', () => {
			expect(
				extractJsUrlSpiderMonkey(
					'    at someFunc (https://example.com/script.js:10:5)'
				)
			).toBe( null );
		} );

		it( 'returns null for non-.js files', () => {
			expect(
				extractJsUrlSpiderMonkey(
					's@https://store.local/checkout/:7:3437'
				)
			).toBe( null );
		} );
	} );

	describe( 'extractJsUrl', () => {
		it( 'extracts V8 format URL with explicit format', () => {
			expect(
				extractJsUrl(
					'    at someFunc (https://example.com/script.js:10:5)',
					'v8'
				)
			).toBe( 'https://example.com/script.js' );
		} );

		it( 'extracts SpiderMonkey format URL with explicit format', () => {
			expect(
				extractJsUrl(
					'someFunc@https://example.com/script.js:10:5',
					'spidermonkey'
				)
			).toBe( 'https://example.com/script.js' );
		} );

		it( 'defaults to V8 format when no format specified', () => {
			expect(
				extractJsUrl(
					'    at someFunc (https://example.com/script.js:10:5)'
				)
			).toBe( 'https://example.com/script.js' );
		} );

		it( 'extracts URL with query string', () => {
			expect(
				extractJsUrl(
					'    at someFunc (https://example.com/script.js?ver=1.0:10:5)',
					'v8'
				)
			).toBe( 'https://example.com/script.js' );
			expect(
				extractJsUrl(
					'@https://example.com/script.js?ver=1.0:10:5',
					'spidermonkey'
				)
			).toBe( 'https://example.com/script.js' );
		} );

		it( 'returns null for lines without .js URLs', () => {
			expect(
				extractJsUrl( '    at someFunc (cart/:123:45)', 'v8' )
			).toBe( null );
			expect( extractJsUrl( 'Error: test error', 'v8' ) ).toBe( null );
			expect(
				extractJsUrl(
					's@https://store.local/checkout/:7:3437',
					'spidermonkey'
				)
			).toBe( null );
		} );

		it( 'handles http URLs', () => {
			expect(
				extractJsUrl(
					'    at someFunc (http://localhost/script.js:10:5)',
					'v8'
				)
			).toBe( 'http://localhost/script.js' );
			expect(
				extractJsUrl(
					'someFunc@http://localhost/script.js:10:5',
					'spidermonkey'
				)
			).toBe( 'http://localhost/script.js' );
		} );

		it( 'returns null for non-string input', () => {
			expect( extractJsUrl( 123 as unknown as string, 'v8' ) ).toBe(
				null
			);
			expect( extractJsUrl( null as unknown as string, 'v8' ) ).toBe(
				null
			);
			expect( extractJsUrl( {} as unknown as string, 'v8' ) ).toBe(
				null
			);
		} );
	} );

	describe( 'parseStackForCallerUrl', () => {
		it( 'returns null for empty stack', () => {
			expect( parseStackForCallerUrl( null, '/cart/' ) ).toBe( null );
			expect( parseStackForCallerUrl( '', '/cart/' ) ).toBe( null );
		} );

		it( 'returns null for non-string stack', () => {
			expect(
				parseStackForCallerUrl( 123 as unknown as string, '/cart/' )
			).toBe( null );
			expect(
				parseStackForCallerUrl( {} as unknown as string, '/cart/' )
			).toBe( null );
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

		it( 'handles real-world V8 stack trace with bare filenames', () => {
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

			// V8 format can include bare filenames without protocol.
			// First .js file after skipping cart/ lines should be found.
			expect( parseStackForCallerUrl( stack, '/cart/' ) ).toBe(
				'utils.js'
			);
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

		it( 'handles SpiderMonkey format stack trace (Firefox/Safari)', () => {
			const stack = `s@https://store.local/checkout/:7:3437
c@https://store.local/checkout/:7:2171
@https://store.local/wp-content/plugins/wc-dependency-test/bad-extension.js?ver=1.0.0:31:7
setTimeout handler*@https://store.local/wp-content/plugins/wc-dependency-test/bad-extension.js?ver=1.0.0:29:11`;

			expect( parseStackForCallerUrl( stack, '/checkout/' ) ).toBe(
				'https://store.local/wp-content/plugins/wc-dependency-test/bad-extension.js'
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

		it( 'returns unregistered warning for malformed registry entry with missing handle', () => {
			const malformedRegistry = {
				'https://example.com/malformed.js': {
					deps: [ 'wc-blocks-checkout' ],
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
