/**
 * WooCommerce Dependency Detection - Utility Functions
 *
 * Extracted from dependency-detection.js for testability.
 * These functions are used by the inline detection script.
 */

/**
 * Exact mapping of wc.* property names to their required handles.
 * Must match PHP DependencyDetection::WC_GLOBAL_EXPORTS exactly.
 */
export interface WcGlobalExportsMap {
	wcBlocksRegistry: 'wc-blocks-registry';
	wcSettings: 'wc-settings';
	wcBlocksData: 'wc-blocks-data-store';
	data: 'wc-store-data';
	wcBlocksSharedContext: 'wc-blocks-shared-context';
	wcBlocksSharedHocs: 'wc-blocks-shared-hocs';
	priceFormat: 'wc-price-format';
	blocksCheckout: 'wc-blocks-checkout';
	blocksCheckoutEvents: 'wc-blocks-checkout-events';
	blocksComponents: 'wc-blocks-components';
	wcTypes: 'wc-types';
	sanitize: 'wc-sanitize';
}

/**
 * Allowed window.wc.* property names that are tracked.
 */
export type WcGlobalKey = keyof WcGlobalExportsMap;

/**
 * WooCommerce script dependency handles.
 */
export type WcDependencyHandle = WcGlobalExportsMap[ WcGlobalKey ];

/**
 * Script information stored in the registry.
 */
export interface ScriptInfo {
	handle: string;
	deps: WcDependencyHandle[];
}

/**
 * Registry mapping script URLs to their info.
 */
export type ScriptRegistry = Record< string, ScriptInfo >;

/**
 * Warning information returned by getWarningInfo.
 */
export interface WarningInfo {
	type: 'inline' | 'unregistered' | 'missing-dependency';
	message: string;
}

/**
 * Pattern to identify WooCommerce core scripts (which we should skip).
 * Matches /plugins/woocommerce/(client|assets|build)/ but NOT /plugins/woocommerce-subscriptions/ etc.
 */
const WC_CORE_SCRIPT_PATTERN =
	/\/plugins\/woocommerce\/(client|assets|build|vendor)\//;

/**
 * Check if a URL belongs to WooCommerce core scripts.
 *
 * @param url - The script URL to check.
 * @return True if this is a WooCommerce core script.
 */
export function isWooCommerceScript( url: string | null ): boolean {
	if ( ! url ) return false;
	return WC_CORE_SCRIPT_PATTERN.test( url );
}

/**
 * Extract filename from a URL.
 *
 * @param url - The URL to extract filename from.
 * @return The filename or 'unknown'.
 */
export function getFilename( url: string | null ): string {
	if ( ! url ) {
		return 'unknown';
	}

	const lastSegment = url.split( '/' ).pop();
	if ( ! lastSegment ) {
		return 'unknown';
	}

	const filename = lastSegment.split( '?' )[ 0 ].split( '#' )[ 0 ];

	return filename || 'unknown';
}

/**
 * Check if a stack line should be skipped (internal code).
 *
 * @param line        - A single line from the stack trace.
 * @param currentPage - The current page pathname.
 * @return True if this line should be skipped.
 */
export function shouldSkipLine( line: string, currentPage: string ): boolean {
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
 * @param line - A single line from the stack trace.
 * @return The extracted URL or null.
 */
export function extractJsUrl( line: string ): string | null {
	const match = line.match( /(https?:\/\/[^\s)]+\.js)(?:[?:#]|$)/ );
	return match ? match[ 1 ] : null;
}

/**
 * Parse an error stack trace to find the calling script URL.
 *
 * @param stack       - The error stack trace.
 * @param currentPage - The current page pathname.
 * @return The caller URL or null if not found.
 */
export function parseStackForCallerUrl(
	stack: string | null,
	currentPage: string
): string | null {
	if ( ! stack ) {
		return null;
	}

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
 * @param callerUrl                - The URL of the calling script.
 * @param wcGlobalKey              - The property being accessed.
 * @param requiredDependencyHandle - The required dependency handle.
 * @param scriptRegistry           - Registry of scripts with their handles and deps.
 * @param getFilenameFn            - Function to extract filename from URL.
 * @return Warning info { type, message } or null if no warning needed.
 */
export function getWarningInfo(
	callerUrl: string | null,
	wcGlobalKey: WcGlobalKey,
	requiredDependencyHandle: WcDependencyHandle,
	scriptRegistry: ScriptRegistry,
	getFilenameFn: ( url: string | null ) => string = getFilename
): WarningInfo | null {
	// Case 1: Inline or unknown script.
	if ( ! callerUrl ) {
		return {
			type: 'inline',
			message: `[WooCommerce] An inline or unknown script accessed wc.${ wcGlobalKey } without proper dependency declaration. This script should declare "${ requiredDependencyHandle }" as a dependency.`,
		};
	}

	const scriptInfo =
		scriptRegistry && typeof scriptRegistry === 'object'
			? scriptRegistry[ callerUrl ]
			: undefined;

	// Case 2: Unregistered script or malformed registry entry.
	if ( ! scriptInfo || ! Array.isArray( scriptInfo.deps ) ) {
		return {
			type: 'unregistered',
			message: `[WooCommerce] Unregistered script "${ getFilenameFn(
				callerUrl
			) }" accessed wc.${ wcGlobalKey }. This script should be registered with wp_enqueue_script() and declare "${ requiredDependencyHandle }" as a dependency.`,
		};
	}

	// Case 3: Missing dependency.
	if ( scriptInfo.deps.indexOf( requiredDependencyHandle ) === -1 ) {
		return {
			type: 'missing-dependency',
			message: `[WooCommerce] Script "${ scriptInfo.handle }" accessed wc.${ wcGlobalKey } without declaring "${ requiredDependencyHandle }" as a dependency. Add "${ requiredDependencyHandle }" to the script's dependencies array.`,
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
 * @param target             - The object to wrap.
 * @param wcGlobalExports    - Map of wc.* properties to required handles.
 * @param getCallerScriptUrl - Function to get the caller script URL.
 * @param checkDependency    - Function to check and warn about dependencies.
 * @return The proxied object.
 */
export function createWcProxy< T extends Record< string, unknown > >(
	target: T,
	wcGlobalExports: WcGlobalExportsMap,
	getCallerScriptUrl: () => string | null,
	checkDependency: (
		callerUrl: string | null,
		wcGlobalKey: WcGlobalKey,
		requiredDependencyHandle: WcDependencyHandle
	) => void
): T {
	let isChecking = false;

	function __wcProxyGet( obj: T, prop: string ): unknown {
		// Recursive call - skip checking and just return the value.
		if ( isChecking ) {
			return obj[ prop as keyof T ];
		}

		// Check if this property is a tracked wc global export.
		// Type guard needed for TypeScript to narrow the type.
		const isTrackedKey = ( key: string ): key is WcGlobalKey =>
			key in wcGlobalExports;

		if ( isTrackedKey( prop ) ) {
			// Set guard before any operations that might trigger nested proxy calls.
			isChecking = true;
			try {
				const callerUrl = getCallerScriptUrl();
				checkDependency( callerUrl, prop, wcGlobalExports[ prop ] );
				// Get the value (may trigger nested proxy calls, but isChecking blocks them).
				return obj[ prop as keyof T ];
			} finally {
				// Reset guard only after we have the value, even if an error occurs.
				isChecking = false;
			}
		}

		return obj[ prop as keyof T ];
	}

	return new Proxy( target, { get: __wcProxyGet } );
}
