/**
 * WooCommerce Dependency Detection - Entry Point
 *
 * This file is the entry point for the webpack build that creates
 * the inline detection script. It imports utils and wraps them in
 * the IIFE that PHP outputs to the page.
 */

/**
 * Internal dependencies
 */
import {
	isWooCommerceScript,
	getFilename,
	parseStackForCallerUrl,
	getWarningInfo,
	createWcProxy,
	type ScriptRegistry,
	type WcGlobalExportsMap,
	type WcGlobalKey,
	type WcDependencyHandle,
} from './utils';

declare global {
	// eslint-disable-next-line no-var, @typescript-eslint/naming-convention
	var __WC_GLOBAL_EXPORTS_PLACEHOLDER__: WcGlobalExportsMap;
	// eslint-disable-next-line no-var, @typescript-eslint/naming-convention
	var __WC_PLUGIN_URL_PLACEHOLDER__: string;
}

/**
 * Pending check stored when registry isn't loaded yet.
 */
interface PendingCheck {
	callerUrl: string;
	wcGlobalKey: WcGlobalKey;
	requiredDependencyHandle: WcDependencyHandle;
}

( function () {
	// Set up a placeholder that will be replaced with the real proxy later.
	// This ensures we capture window.wc before any WC scripts set it.
	let originalWc: Record< string, unknown > = window.wc || {};
	let scriptRegistry: ScriptRegistry = {};
	let registryLoaded = false;
	const warnedScripts: Record< string, boolean > = {};
	let pendingChecks: PendingCheck[] = []; // Queue checks until registry is loaded

	// Maps window.wc.* property names to their required script handles.
	// Injected by PHP from DependencyDetection::WC_GLOBAL_EXPORTS (source of truth).
	// eslint-disable-next-line no-undef
	const WC_GLOBAL_EXPORTS: WcGlobalExportsMap =
		__WC_GLOBAL_EXPORTS_PLACEHOLDER__;

	// WooCommerce plugin URL, injected by PHP to account for custom plugin directories.
	// eslint-disable-next-line no-undef
	const WC_PLUGIN_URL: string = __WC_PLUGIN_URL_PLACEHOLDER__;
	/**
	 * Get the URL of the script that called this function.
	 *
	 * @return The caller script URL or null.
	 */
	function getCallerScriptUrl(): string | null {
		const src = ( document.currentScript as HTMLScriptElement | null )?.src;
		if ( src && typeof src === 'string' ) {
			return src.replace( /\?.*$/, '' );
		}

		// Fallback for scenarios when currentScript isn't available
		const stack = new Error().stack;
		return parseStackForCallerUrl(
			stack ?? null,
			window.location.pathname
		);
	}

	/**
	 * Perform the actual dependency check and warn if missing.
	 *
	 * @param callerUrl                - The URL of the calling script.
	 * @param wcGlobalKey              - The property being accessed.
	 * @param requiredDependencyHandle - The required dependency handle.
	 */
	function warnIfMissingDependency(
		callerUrl: string | null,
		wcGlobalKey: WcGlobalKey,
		requiredDependencyHandle: WcDependencyHandle
	): void {
		const warningKey = ( callerUrl || 'inline' ) + ':' + wcGlobalKey;

		// Don't warn twice for the same script + property combination.
		if ( warnedScripts[ warningKey ] ) {
			return;
		}

		const warning = getWarningInfo(
			callerUrl,
			wcGlobalKey,
			requiredDependencyHandle,
			scriptRegistry,
			getFilename
		);

		if ( warning ) {
			// eslint-disable-next-line no-console
			console.warn( warning.message );
			warnedScripts[ warningKey ] = true;
		}
	}

	/**
	 * Check if a script has declared the required dependency.
	 *
	 * @param callerUrl                - The URL of the calling script.
	 * @param wcGlobalKey              - The property being accessed (e.g., 'blocksCheckout').
	 * @param requiredDependencyHandle - The required dependency handle.
	 */
	function checkDependency(
		callerUrl: string | null,
		wcGlobalKey: WcGlobalKey,
		requiredDependencyHandle: WcDependencyHandle
	): void {
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
		if ( isWooCommerceScript( callerUrl, WC_PLUGIN_URL ) ) {
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

	// Create the proxy using the utility function.
	let wcProxy = createWcProxy(
		originalWc,
		WC_GLOBAL_EXPORTS,
		getCallerScriptUrl,
		checkDependency
	);

	// Define window.wc as a getter/setter to maintain the proxy.
	Object.defineProperty( window, 'wc', {
		get() {
			return wcProxy;
		},
		set( newValue: Record< string, unknown > ) {
			// When WC scripts set window.wc, wrap the new value.
			// Handle null/undefined to prevent Proxy TypeError.
			originalWc = newValue || {};
			wcProxy = createWcProxy(
				originalWc,
				WC_GLOBAL_EXPORTS,
				getCallerScriptUrl,
				checkDependency
			);
		},
		configurable: true,
		enumerable: true,
	} );

	/**
	 * Update the script registry. Called by WooCommerce PHP to provide
	 * registered script data for dependency checking.
	 *
	 * Not for external use. Calling this will overwrite the registry
	 * provided by WooCommerce.
	 *
	 * @internal
	 */
	( window.wc as Record< string, unknown > ).wcUpdateDependencyRegistry =
		function ( registry: ScriptRegistry ): void {
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

	// eslint-disable-next-line no-console
	console.info(
		'[WooCommerce] Dependency detection enabled. Warnings will be shown for scripts that access wc.* globals without proper dependencies.'
	);
} )();
