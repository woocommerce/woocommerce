/**
 * WooCommerce Dependency Detection - Utility Functions
 *
 * Extracted from dependency-detection.js for testability.
 * These functions are used by the inline detection script.
 */

/**
 * Exact mapping of wc.* property names to their required handles.
 * Must match PHP DependencyDetection::WC_GLOBAL_EXPORTS exactly.
 *
 * This interface is used for development-time dependency warnings only.
 * It is not a public API and may change without notice. Extensions should
 * not rely on which properties are tracked or the detection behavior.
 *
 * @internal
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
 *
 * @internal
 */
export type WcGlobalKey = keyof WcGlobalExportsMap;

/**
 * WooCommerce script dependency handles.
 *
 * @internal
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
 * WooCommerce asset subdirectories that contain core scripts.
 */
const WC_ASSET_DIRS = [ 'client/', 'assets/', 'build/', 'vendor/' ];

/**
 * Fallback pattern for WooCommerce core scripts when plugin URL is not available.
 * Matches /plugins/woocommerce/(client|assets|build|vendor)/ but NOT /plugins/woocommerce-subscriptions/ etc.
 */
const WC_CORE_SCRIPT_FALLBACK_PATTERN =
	/\/plugins\/woocommerce\/(client|assets|build|vendor)\//;

/**
 * Check if a URL belongs to WooCommerce core scripts.
 *
 * Uses the plugin URL from the server to account for custom plugin directories
 * (WP_PLUGIN_DIR, WP_CONTENT_DIR configurations). Falls back to a hardcoded
 * pattern if the plugin URL is not available.
 *
 * @param url         - The script URL to check.
 * @param wcPluginUrl - The WooCommerce plugin URL from the server.
 * @return True if this is a WooCommerce core script.
 */
export function isWooCommerceScript(
	url: string | null,
	wcPluginUrl = ''
): boolean {
	if ( ! url ) {
		return false;
	}

	// If WC_PLUGIN_URL is not available, fall back to hardcoded pattern.
	// This handles cases where PHP injection failed.
	if ( ! wcPluginUrl ) {
		return WC_CORE_SCRIPT_FALLBACK_PATTERN.test( url );
	}

	// Check if the URL starts with the WooCommerce plugin URL.
	if ( ! url.startsWith( wcPluginUrl ) ) {
		return false;
	}

	// Get the path after the plugin URL.
	const relativePath = url.substring( wcPluginUrl.length );

	// Check if it's in one of the known WooCommerce asset directories.
	for ( let i = 0; i < WC_ASSET_DIRS.length; i++ ) {
		if ( relativePath.startsWith( WC_ASSET_DIRS[ i ] ) ) {
			return true;
		}
	}

	return false;
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
 * Check if a stack trace line should be skipped when searching for the caller.
 *
 * We skip internal lines because we want to find the actual third-party script
 * that accessed wc.*, not the detection code itself. Lines to skip include:
 * - Current page lines: These are from our inline detection script (the IIFE
 *   output by PHP). They appear as "cart/:123" or "checkout/:456" in the stack.
 * - Webpack source maps: Internal WooCommerce build artifacts that aren't the
 *   actual caller script.
 *
 * @param line        - A single line from the stack trace.
 * @param currentPage - The current page pathname (e.g., '/cart/', '/checkout/').
 * @return True if this line should be skipped.
 */
export function shouldSkipLine( line: string, currentPage: string ): boolean {
	// Skip lines from the current page (our inline detection script).
	if ( line.includes( currentPage + ':' ) ) {
		return true;
	}

	// Skip webpack source-mapped files (internal build artifacts).
	if ( line.includes( 'webpack://' ) ) {
		return true;
	}

	return false;
}

/**
 * Stack trace format types.
 * - 'v8': Chrome, Edge, Node.js - format: "at funcName (url:line:col)" with "Error" header
 * - 'spidermonkey': Firefox (SpiderMonkey), Safari (JavaScriptCore) - format: "funcName@url:line:col" without header
 */
export type StackFormatType = 'v8' | 'spidermonkey';

/**
 * Detect the stack trace format type from a stack string.
 *
 * V8 (Chrome/Edge/Node): Lines contain "at " followed by function name and URL in parentheses.
 * SpiderMonkey (Firefox/Safari): Lines contain "@" between function name and URL.
 *
 * @param stack - The stack trace string.
 * @return The detected format type, defaults to 'v8' if unknown.
 */
export function detectStackFormat( stack: string ): StackFormatType {
	if ( ! stack || typeof stack !== 'string' ) {
		return 'v8';
	}

	// SpiderMonkey format: lines have "@" before the URL (e.g., "funcName@https://...")
	// V8 format: lines have "at " prefix (e.g., "at funcName (https://...)")
	const lines = stack.split( '\n' );

	// Start from line 0 because SpiderMonkey stacks may not have an "Error" header.
	for ( let i = 0; i < lines.length; i++ ) {
		const line = lines[ i ];

		// SpiderMonkey: "@https://" or "@http://" pattern
		if ( /@https?:\/\//.test( line ) ) {
			return 'spidermonkey';
		}

		// V8: "at " prefix pattern
		if ( /^\s*at\s/.test( line ) ) {
			return 'v8';
		}
	}

	return 'v8';
}

/**
 * Extract a .js URL from a V8-format stack trace line (Chrome/Edge/Node).
 *
 * V8 format examples:
 * - "at funcName (https://example.com/script.js:10:5)" - full URL
 * - "at funcName (script.js:10:5)" - relative/bare filename
 *
 * @param line - A single line from the stack trace.
 * @return The extracted URL or null.
 */
export function extractJsUrlV8( line = '' ): string | null {
	if ( typeof line !== 'string' ) {
		return null;
	}
	// First try to match full URL with protocol
	const fullUrlMatch = line.match( /(https?:\/\/[^\s)]+?\.js)(?:[?:#]|$)/ );
	if ( fullUrlMatch ) {
		return fullUrlMatch[ 1 ];
	}

	// Fall back to bare filename (e.g., "script.js" without protocol).
	// Match inside parentheses: ( followed by path ending in .js
	const bareMatch = line.match( /\(([^()\s]+\.js)(?:[?:#]|$)/ );
	return bareMatch ? bareMatch[ 1 ] : null;
}

/**
 * Extract a .js URL from a SpiderMonkey-format stack trace line (Firefox/Safari).
 *
 * SpiderMonkey format: "funcName@https://example.com/script.js:10:7"
 * The URL comes after "@", followed by line:col.
 *
 * @param line - A single line from the stack trace.
 * @return The extracted URL or null.
 */
export function extractJsUrlSpiderMonkey( line = '' ): string | null {
	if ( typeof line !== 'string' ) {
		return null;
	}
	// Match URL after "@", ending with .js before query/hash/line number.
	// Use non-greedy match [^\s]+? to stop at first .js occurrence.
	const match = line.match( /@(https?:\/\/[^\s]+?\.js)(?:[?:#]|$)/ );
	return match ? match[ 1 ] : null;
}

/**
 * Extract a .js URL from a stack trace line using the specified format.
 *
 * @param line   - A single line from the stack trace.
 * @param format - The stack format type to use for extraction.
 * @return The extracted URL or null.
 */
export function extractJsUrl(
	line = '',
	format: StackFormatType = 'v8'
): string | null {
	if ( typeof line !== 'string' ) {
		return null;
	}

	if ( format === 'spidermonkey' ) {
		return extractJsUrlSpiderMonkey( line );
	}

	return extractJsUrlV8( line );
}

/**
 * Parse an error stack trace to find the calling script URL.
 *
 * Detects the stack format (V8 vs SpiderMonkey) once and uses
 * the appropriate extractor for all lines.
 *
 * @param stack       - The error stack trace.
 * @param currentPage - The current page pathname.
 * @return The caller URL or null if not found.
 */
export function parseStackForCallerUrl(
	stack: string | null,
	currentPage: string
): string | null {
	if ( ! stack || typeof stack !== 'string' ) {
		return null;
	}

	// Detect format once for the entire stack.
	const format = detectStackFormat( stack );
	const lines = stack.split( '\n' );

	// V8 stacks have "Error" as line 0, so start at 1.
	// SpiderMonkey stacks start directly with frames, so start at 0.
	const startLine = format === 'v8' ? 1 : 0;

	for ( let i = startLine; i < lines.length; i++ ) {
		const line = lines[ i ];

		// Skip internal lines (our script, webpack).
		if ( shouldSkipLine( line, currentPage ) ) continue;

		// Found an external URL - return it.
		const url = extractJsUrl( line, format );
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
	if (
		! scriptInfo ||
		! scriptInfo.handle ||
		! Array.isArray( scriptInfo.deps )
	) {
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
