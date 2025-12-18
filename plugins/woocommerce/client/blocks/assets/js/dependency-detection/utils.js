/**
 * WooCommerce Dependency Detection - Utility Functions
 *
 * Extracted from dependency-detection.js for testability.
 * These functions are used by the inline detection script.
 */

/**
 * Pattern to identify WooCommerce core scripts (which we should skip).
 * Matches /plugins/woocommerce/(client|assets|build)/ but NOT /plugins/woocommerce-subscriptions/ etc.
 */
const WC_CORE_SCRIPT_PATTERN =
	/\/plugins\/woocommerce\/(client|assets|build|vendor)\//;

/**
 * Check if a URL belongs to WooCommerce core scripts.
 *
 * @param {string} url - The script URL to check.
 * @return {boolean} True if this is a WooCommerce core script.
 */
export function isWooCommerceScript( url ) {
	if ( ! url ) return false;
	return WC_CORE_SCRIPT_PATTERN.test( url );
}

/**
 * Extract filename from a URL.
 *
 * @param {string} url - The URL to extract filename from.
 * @return {string} The filename or 'unknown'.
 */
export function getFilename( url ) {
	if ( ! url ) return 'unknown';

	const filename = url.split( '/' ).pop().split( '?' )[ 0 ].split( '#' )[ 0 ];

	return filename || 'unknown';
}

/**
 * Check if a stack line should be skipped (internal code).
 *
 * @param {string} line        - A single line from the stack trace.
 * @param {string} currentPage - The current page pathname.
 * @return {boolean} True if this line should be skipped.
 */
export function shouldSkipLine( line, currentPage ) {
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
export function extractJsUrl( line ) {
	const match = line.match( /(https?:\/\/[^\s)]+\.js)(?:[?:#]|$)/ );
	return match ? match[ 1 ] : null;
}

/**
 * Parse an error stack trace to find the calling script URL.
 *
 * @param {string} stack       - The error stack trace.
 * @param {string} currentPage - The current page pathname.
 * @return {string|null} The caller URL or null if not found.
 */
export function parseStackForCallerUrl( stack, currentPage ) {
	if ( ! stack ) return null;

	const lines = stack.split( '\n' );

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
 * Create the warning message for missing dependencies.
 *
 * @param {string|null} callerUrl                - The URL of the calling script.
 * @param {string}      wcGlobalKey              - The property being accessed.
 * @param {string}      requiredDependencyHandle - The required dependency handle.
 * @param {Object}      scriptRegistry           - Registry of scripts with their handles and deps.
 * @param {Function}    getFilenameFn            - Function to extract filename from URL.
 * @return {Object|null} Warning info { type, message } or null if no warning needed.
 */
export function getWarningInfo(
	callerUrl,
	wcGlobalKey,
	requiredDependencyHandle,
	scriptRegistry,
	getFilenameFn = getFilename
) {
	// Case 1: Inline or unknown script.
	if ( ! callerUrl ) {
		return {
			type: 'inline',
			message:
				'[WooCommerce] An inline or unknown script accessed wc.' +
				wcGlobalKey +
				' without proper dependency declaration. This script should declare "' +
				requiredDependencyHandle +
				'" as a dependency.',
		};
	}

	const scriptInfo = scriptRegistry[ callerUrl ];

	// Case 2: Unregistered script.
	if ( ! scriptInfo ) {
		return {
			type: 'unregistered',
			message:
				'[WooCommerce] Unregistered script "' +
				getFilenameFn( callerUrl ) +
				'" accessed wc.' +
				wcGlobalKey +
				'. This script should be registered with wp_enqueue_script() and declare "' +
				requiredDependencyHandle +
				'" as a dependency.',
		};
	}

	// Case 3: Missing dependency.
	if ( scriptInfo.deps.indexOf( requiredDependencyHandle ) === -1 ) {
		return {
			type: 'missing-dependency',
			message:
				'[WooCommerce] Script "' +
				scriptInfo.handle +
				'" accessed wc.' +
				wcGlobalKey +
				' without declaring "' +
				requiredDependencyHandle +
				'" as a dependency. Add "' +
				requiredDependencyHandle +
				'" to the script\'s dependencies array.',
		};
	}

	// No warning needed - dependency is properly declared.
	return null;
}

/**
 * Create a Proxy wrapper for the wc object.
 *
 * Intercepts property access on window.wc to check if the calling script
 * has declared the required dependency. Uses a guard flag (isChecking) to
 * prevent infinite recursion when accessing a property triggers nested
 * proxy calls (e.g., wc.blocksCheckout internally uses wc.wcSettings).
 *
 * @param {Object}   target             - The object to wrap.
 * @param {Object}   wcGlobalExports    - Map of wc.* properties to required handles.
 * @param {Function} getCallerScriptUrl - Function to get the caller script URL.
 * @param {Function} checkDependency    - Function to check and warn about dependencies.
 * @return {Proxy} The proxied object.
 */
export function createWcProxy(
	target,
	wcGlobalExports,
	getCallerScriptUrl,
	checkDependency
) {
	let isChecking = false;

	function __wcProxyGet( obj, prop ) {
		// Recursive call - skip checking and just return the value.
		if ( isChecking ) {
			return obj[ prop ];
		}

		if ( wcGlobalExports[ prop ] ) {
			// Set guard before any operations that might trigger nested proxy calls.
			isChecking = true;
			try {
				const callerUrl = getCallerScriptUrl();
				checkDependency( callerUrl, prop, wcGlobalExports[ prop ] );
				// Get the value (may trigger nested proxy calls, but isChecking blocks them).
				return obj[ prop ];
			} finally {
				// Reset guard only after we have the value, even if an error occurs.
				isChecking = false;
			}
		}

		return obj[ prop ];
	}

	return new Proxy( target, { get: __wcProxyGet } );
}
