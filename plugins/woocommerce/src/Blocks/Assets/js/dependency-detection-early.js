/**
 * WooCommerce Dependency Detection - Early Proxy Setup
 *
 * This script sets up a Proxy on window.wc to detect when extensions
 * access globals without properly declaring dependencies.
 *
 * IMPORTANT: This script must be loaded inline in wp_head before any other scripts.
 * It is read by PHP and output as an inline script to ensure correct timing.
 */
( function () {
	// Set up a placeholder that will be replaced with the real proxy later.
	// This ensures we capture window.wc before any WC scripts set it.
	let originalWc = window.wc || {};
	let proxyEnabled = false;
	let scriptRegistry = {};
	let registryLoaded = false;
	const warnedScripts = {};
	let pendingChecks = []; // Queue checks until registry is loaded

	const WC_GLOBAL_TO_HANDLE = {
		blocksCheckout: 'wc-blocks-checkout',
		wcBlocksData: 'wc-blocks-data-store',
	};

	// Patterns to identify WooCommerce's own scripts (which we should skip).
	const WC_SCRIPT_PATTERNS = [
		/\/woocommerce\//i,
		/\/wc-blocks/i,
		/wc-blocks-/,
		/blocks-checkout/,
		/blocks-components/,
		/blocks-registry/,
		/blocks-data/,
		/price-format/,
		/checkout-frontend/,
		/cart-frontend/,
		/wc-settings/,
		/wc-payment-method-/,
	];

	/**
	 * Check if a URL belongs to WooCommerce core scripts.
	 *
	 * @param {string} url - The script URL to check.
	 * @return {boolean} True if this is a WooCommerce core script.
	 */
	function isWooCommerceScript( url ) {
		if ( ! url ) return false;
		for ( let i = 0; i < WC_SCRIPT_PATTERNS.length; i++ ) {
			if ( WC_SCRIPT_PATTERNS[ i ].test( url ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Extract filename from a URL.
	 *
	 * @param {string} url - The URL to extract filename from.
	 * @return {string} The filename or 'unknown'.
	 */
	function getFilename( url ) {
		if ( ! url ) return 'unknown';
		const filename = url
			.split( '/' )
			.pop()
			.split( '?' )[ 0 ]
			.split( '#' )[ 0 ];
		return filename || 'unknown';
	}

	/**
	 * Parse an error stack trace to find the calling script URL.
	 *
	 * @param {string} stack - The error stack trace.
	 * @return {string|null} The caller URL or null if not found.
	 */
	function parseStackForCallerUrl( stack ) {
		if ( ! stack ) return null;
		const lines = stack.split( '\n' );
		for ( let i = 1; i < lines.length; i++ ) {
			const line = lines[ i ];
			// Skip our own detection script lines.
			if ( line.indexOf( 'wc-dependency-detection' ) !== -1 ) continue;
			if ( line.indexOf( 'Proxy.' ) !== -1 ) continue;
			if ( line.indexOf( 'Reflect.' ) !== -1 ) continue;
			if ( line.indexOf( 'Object.get' ) !== -1 ) continue;
			if ( line.indexOf( 'checkDependency' ) !== -1 ) continue;
			if ( line.indexOf( 'performCheck' ) !== -1 ) continue;
			if ( line.indexOf( 'getCallerScriptUrl' ) !== -1 ) continue;
			if ( line.indexOf( 'parseStackForCallerUrl' ) !== -1 ) continue;
			// Skip native functions (setTimeout, etc.)
			if ( line.indexOf( '[native code]' ) !== -1 ) continue;
			if ( /^\s*(at\s+)?setTimeout\s*$/.test( line.trim() ) ) continue;
			// Match URLs pointing to .js files
			const match = line.match( /(https?:\/\/[^\s)?\u0022]+\.js)/ );
			if ( match ) {
				return match[ 1 ];
			}
		}
		return null;
	}

	/**
	 * Get the URL of the script that called this function.
	 *
	 * @return {string|null} The caller script URL or null.
	 */
	function getCallerScriptUrl() {
		if ( document.currentScript && document.currentScript.src ) {
			return document.currentScript.src.replace( /\?.*$/, '' );
		}
		const stack = new Error().stack;
		return parseStackForCallerUrl( stack );
	}

	/**
	 * Check if a script has declared the required dependency.
	 *
	 * @param {string|null} callerUrl      - The URL of the calling script.
	 * @param {string}      prop           - The property being accessed (e.g., 'blocksCheckout').
	 * @param {string}      requiredHandle - The required dependency handle.
	 */
	function checkDependency( callerUrl, prop, requiredHandle ) {
		// Skip WooCommerce's own scripts - they manage their own dependencies.
		if ( isWooCommerceScript( callerUrl ) ) {
			return;
		}

		// If registry not loaded yet, queue the check for later.
		if ( ! registryLoaded ) {
			pendingChecks.push( {
				callerUrl,
				prop,
				requiredHandle,
			} );
			return;
		}

		performCheck( callerUrl, prop, requiredHandle );
	}

	/**
	 * Perform the actual dependency check and warn if missing.
	 *
	 * @param {string|null} callerUrl      - The URL of the calling script.
	 * @param {string}      prop           - The property being accessed.
	 * @param {string}      requiredHandle - The required dependency handle.
	 */
	function performCheck( callerUrl, prop, requiredHandle ) {
		const warningKey = ( callerUrl || 'inline' ) + ':' + prop;
		if ( warnedScripts[ warningKey ] ) return;

		if ( ! callerUrl ) {
			console.warn(
				'[WooCommerce] An inline or unknown script accessed wc.' +
					prop +
					' without proper dependency declaration. This script should declare "' +
					requiredHandle +
					'" as a dependency.'
			);
			warnedScripts[ warningKey ] = true;
			return;
		}

		const scriptInfo = scriptRegistry[ callerUrl ];
		if ( ! scriptInfo ) {
			console.warn(
				'[WooCommerce] Unregistered script "' +
					getFilename( callerUrl ) +
					'" accessed wc.' +
					prop +
					'. This script should be registered with wp_enqueue_script() and declare "' +
					requiredHandle +
					'" as a dependency.'
			);
			warnedScripts[ warningKey ] = true;
			return;
		}

		if ( scriptInfo.deps.indexOf( requiredHandle ) === -1 ) {
			console.warn(
				'[WooCommerce] Script "' +
					scriptInfo.handle +
					'" accessed wc.' +
					prop +
					' without declaring "' +
					requiredHandle +
					'" as a dependency. Add "' +
					requiredHandle +
					'" to the script\'s dependencies array.'
			);
			warnedScripts[ warningKey ] = true;
		}
	}

	/**
	 * Create a Proxy wrapper for the wc object.
	 *
	 * @param {Object} target - The object to wrap.
	 * @return {Proxy} The proxied object.
	 */
	function createWcProxy( target ) {
		return new Proxy( target, {
			get( obj, prop ) {
				if ( proxyEnabled && WC_GLOBAL_TO_HANDLE[ prop ] ) {
					const callerUrl = getCallerScriptUrl();
					checkDependency(
						callerUrl,
						prop,
						WC_GLOBAL_TO_HANDLE[ prop ]
					);
				}
				return Reflect.get( obj, prop );
			},
			set( obj, prop, value ) {
				return Reflect.set( obj, prop, value );
			},
		} );
	}

	// Create the proxy immediately.
	let wcProxy = createWcProxy( originalWc );

	// Define window.wc as a getter/setter to maintain the proxy.
	Object.defineProperty( window, 'wc', {
		get() {
			return wcProxy;
		},
		set( newValue ) {
			// When WC scripts set window.wc, wrap the new value.
			originalWc = newValue;
			wcProxy = createWcProxy( newValue );
		},
		configurable: true,
		enumerable: true,
	} );

	// Expose function to update registry (called later after all scripts registered).
	window.__wcUpdateDependencyRegistry = function ( registry ) {
		scriptRegistry = registry || {};
		registryLoaded = true;

		// Process any pending checks now that we have the registry.
		for ( let i = 0; i < pendingChecks.length; i++ ) {
			const check = pendingChecks[ i ];
			performCheck( check.callerUrl, check.prop, check.requiredHandle );
		}
		pendingChecks = [];
	};

	// Enable detection immediately.
	proxyEnabled = true;
	console.info(
		'[WooCommerce] Dependency detection enabled. Warnings will be shown for scripts that access wc.* globals without proper dependencies.'
	);
} )();
