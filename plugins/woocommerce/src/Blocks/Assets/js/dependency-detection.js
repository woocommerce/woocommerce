/**
 * WooCommerce Dependency Detection - Early Proxy Setup
 *
 * This script sets up a Proxy on window.wc to detect when extensions
 * access blocks exposed globals without properly declaring dependencies.
 *
 * IMPORTANT: This script must be loaded inline in wp_head before any other scripts.
 * It is read by PHP and output as an inline script to ensure correct timing.
 */
( function () {
	// Set up a placeholder that will be replaced with the real proxy later.
	// This ensures we capture window.wc before any WC scripts set it.
	let originalWc = window.wc || {};
	let scriptRegistry = {};
	let registryLoaded = false;
	const warnedScripts = {};
	let pendingChecks = []; // Queue checks until registry is loaded

	// Maps window.wc.* property names to their required script handles.
	// Injected by PHP from DependencyDetection::WC_GLOBAL_EXPORTS (source of truth).
	// eslint-disable-next-line no-undef
	const WC_GLOBAL_EXPORTS = __WC_GLOBAL_EXPORTS_PLACEHOLDER__;

	// Pattern to identify WooCommerce core scripts (which we should skip).
	// Matches /plugins/woocommerce/(client|assets|build)/ but NOT /plugins/woocommerce-subscriptions/ etc.
	const WC_CORE_SCRIPT_PATTERN =
		/\/plugins\/woocommerce\/(client|assets|build)\//;

	/**
	 * Check if a URL belongs to WooCommerce core scripts.
	 *
	 * @param {string} url - The script URL to check.
	 * @return {boolean} True if this is a WooCommerce core script.
	 */
	function isWooCommerceScript( url ) {
		if ( ! url ) return false;

		return WC_CORE_SCRIPT_PATTERN.test( url );
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
	 * Check if a stack line should be skipped (internal code).
	 *
	 * @param {string} line        - A single line from the stack trace.
	 * @param {string} currentPage - The current page pathname.
	 * @return {boolean} True if this line should be skipped.
	 */
	function shouldSkipLine( line, currentPage ) {
		// Skip lines from the current page (our inline detection script).
		if ( line.indexOf( currentPage + ':' ) !== -1 ) {
			return true;
		}

		// Skip webpack source-mapped files (internal build artifacts).
		if ( line.indexOf( 'webpack://' ) !== -1 ) return true;

		return false;
	}

	/**
	 * Extract a .js URL from a stack trace line.
	 *
	 * @param {string} line - A single line from the stack trace.
	 * @return {string|null} The extracted URL or null.
	 */
	function extractJsUrl( line ) {
		const match = line.match( /(https?:\/\/[^\s)]+\.js)(?:[?:#]|$)/ );
		return match ? match[ 1 ] : null;
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
		const currentPage = window.location.pathname;

		for ( let i = 1; i < lines.length; i++ ) {
			const line = lines[ i ];

			// Skip internal lines (our script, webpack).
			if ( shouldSkipLine( line, currentPage ) ) continue;

			// Found an external URL - return it.
			const url = extractJsUrl( line );
			if ( url ) {
				return url;
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

		// Fallback for scenarios when currentScript isn't available
		const stack = new Error().stack;
		return parseStackForCallerUrl( stack );
	}

	/**
	 * Perform the actual dependency check and warn if missing.
	 *
	 * @param {string|null} callerUrl                - The URL of the calling script.
	 * @param {string}      wcGlobalKey              - The property being accessed.
	 * @param {string}      requiredDependencyHandle - The required dependency handle.
	 */
	function warnIfMissingDependency(
		callerUrl,
		wcGlobalKey,
		requiredDependencyHandle
	) {
		const warningKey = ( callerUrl || 'inline' ) + ':' + wcGlobalKey;

		// Don't warn twice for the same script + property combination.
		if ( warnedScripts[ warningKey ] ) return;

		// Case 1: Inline or unknown script.
		// We couldn't identify the calling script from the stack trace.
		// This happens with:
		// - Inline <script> tags in the HTML
		// - Scripts loaded via eval() or dynamic injection
		// - Code running in contexts where stack traces don't include URLs (e.g., some React renders)
		if ( ! callerUrl ) {
			console.warn(
				'[WooCommerce] An inline or unknown script accessed wc.' +
					wcGlobalKey +
					' without proper dependency declaration. This script should declare "' +
					requiredDependencyHandle +
					'" as a dependency.'
			);
			warnedScripts[ warningKey ] = true;
			return;
		}

		const scriptInfo = scriptRegistry[ callerUrl ];

		// Case 2: Unregistered script.
		// The script URL was found in the stack trace, but it's not in our registry.
		// This means the script was loaded without using wp_enqueue_script().
		// Common causes:
		// - Script loaded via a direct <script src="..."> tag
		// - Script loaded by a third-party library
		if ( ! scriptInfo ) {
			console.warn(
				'[WooCommerce] Unregistered script "' +
					getFilename( callerUrl ) +
					'" accessed wc.' +
					wcGlobalKey +
					'. This script should be registered with wp_enqueue_script() and declare "' +
					requiredDependencyHandle +
					'" as a dependency.'
			);
			warnedScripts[ warningKey ] = true;
			return;
		}

		// Case 3: Missing dependency.
		// The script is properly registered via wp_enqueue_script(), but it doesn't
		// declare the required WooCommerce handle as a dependency.
		if ( scriptInfo.deps.indexOf( requiredDependencyHandle ) === -1 ) {
			console.warn(
				'[WooCommerce] Script "' +
					scriptInfo.handle +
					'" accessed wc.' +
					wcGlobalKey +
					' without declaring "' +
					requiredDependencyHandle +
					'" as a dependency. Add "' +
					requiredDependencyHandle +
					'" to the script\'s dependencies array.'
			);
			warnedScripts[ warningKey ] = true;
		}
	}

	/**
	 * Check if a script has declared the required dependency.
	 *
	 * @param {string|null} callerUrl                - The URL of the calling script.
	 * @param {string}      wcGlobalKey              - The property being accessed (e.g., 'blocksCheckout').
	 * @param {string}      requiredDependencyHandle - The required dependency handle.
	 */
	function checkDependency(
		callerUrl,
		wcGlobalKey,
		requiredDependencyHandle
	) {
		// For null/unknown callerUrl, warn immediately - no registry needed.
		// We already know it's an inline or unknown script.
		if ( ! callerUrl ) {
			warnIfMissingDependency(
				callerUrl,
				wcGlobalKey,
				requiredDependencyHandle
			);
			return;
		}

		// Skip WooCommerce's own scripts - they manage their own dependencies.
		if ( isWooCommerceScript( callerUrl ) ) {
			return;
		}

		// If registry not loaded yet, queue the check for later.
		if ( ! registryLoaded ) {
			pendingChecks.push( {
				callerUrl,
				wcGlobalKey,
				requiredDependencyHandle,
			} );
			return;
		}

		warnIfMissingDependency(
			callerUrl,
			wcGlobalKey,
			requiredDependencyHandle
		);
	}

	let isChecking = false;

	/**
	 * Create a Proxy wrapper for the wc object.
	 *
	 * Intercepts property access on window.wc to check if the calling script
	 * has declared the required dependency. Uses a guard flag (isChecking) to
	 * prevent infinite recursion when accessing a property triggers nested
	 * proxy calls (e.g., wc.blocksCheckout internally uses wc.wcSettings).
	 *
	 * @param {Object} target - The object to wrap.
	 * @return {Proxy} The proxied object.
	 */
	function createWcProxy( target ) {
		function __wcProxyGet( obj, prop ) {
			// Recursive call - skip checking and just return the value.
			if ( isChecking ) {
				return obj[ prop ];
			}

			if ( WC_GLOBAL_EXPORTS[ prop ] ) {
				// Set guard before any operations that might trigger nested proxy calls.
				// The guard flag isChecking stays true from when we start checking until after we get the value. Any nested proxy calls during that time hit the guard
				// and return immediately without checking.
				isChecking = true;
				const callerUrl = getCallerScriptUrl();
				checkDependency( callerUrl, prop, WC_GLOBAL_EXPORTS[ prop ] );
			}

			// Get the value (This may trigger nested proxy calls, e.g., blocksCheckout uses wcSettings, but isChecking blocks them).
			const value = obj[ prop ];
			// Reset guard only after we have the value.
			isChecking = false;
			return value;
		}

		return new Proxy( target, { get: __wcProxyGet } );
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
			warnIfMissingDependency(
				check.callerUrl,
				check.wcGlobalKey,
				check.requiredDependencyHandle
			);
		}
		pendingChecks = [];
	};

	console.info(
		'[WooCommerce] Dependency detection enabled. Warnings will be shown for scripts that access wc.* globals without proper dependencies.'
	);
} )();
