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
} from './utils';

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
		return parseStackForCallerUrl( stack, window.location.pathname );
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
		set( newValue ) {
			// When WC scripts set window.wc, wrap the new value.
			originalWc = newValue;
			wcProxy = createWcProxy(
				newValue,
				WC_GLOBAL_EXPORTS,
				getCallerScriptUrl,
				checkDependency
			);
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

	// eslint-disable-next-line no-console
	console.info(
		'[WooCommerce] Dependency detection enabled. Warnings will be shown for scripts that access wc.* globals without proper dependencies.'
	);
} )();
