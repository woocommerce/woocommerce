/**
 * This script configures the XState V4 development tools in the
 * development environment. It should not be included in production.
 *
 * To enable the XState inspector, open the browser console and type:
 * localStorage.setItem("xstate_inspect", "true")
 */

async function enableXStateV4Inspect() {
	const { inspect } = await import( '@xstate/inspect' );
	const { Interpreter } = await import( 'xstate' );
	// configure the XState inspector to open in a new tab
	inspect( {
		url: 'https://stately.ai/viz?inspect',
		iframe: false,
	} );
	// configure all XServices to use the inspector
	Interpreter.defaultOptions.devTools = true;
	// eslint-disable-next-line no-console
	console.log(
		'Devtools: XState V4 inspector enabled for WooCommerce Admin. To disable, type localStorage.setItem("xstate_inspect", "false")'
	);
}

/**
 * The below is specific to XState V5
 */
let XStateV5Inspect;
async function enableXStateV5Inspect() {
	const { createBrowserInspector } = await import( '@statelyai/inspect' );
	XStateV5Inspect = createBrowserInspector;
	// eslint-disable-next-line no-console
	console.log(
		'Devtools: XState V5 inspector enabled for WooCommerce Admin. To disable, type localStorage.setItem("xstateV5_inspect", "false")'
	);
}

const isXStateInspectEnabled =
	window.localStorage.getItem( 'xstate_inspect' ) === 'true';
const isXStateV5InspectEnabled =
	window.localStorage.getItem( 'xstateV5_inspect' ) === 'true';
const isDevelopmentEnvironment = process.env.NODE_ENV === 'development';

let versionEnabled; // not enabled
if ( isDevelopmentEnvironment && isXStateInspectEnabled ) {
	// XState V5 inspector malfunctions when V4 is also enabled
	if ( isXStateV5InspectEnabled ) {
		versionEnabled = 'V5';
		enableXStateV5Inspect();
	} else {
		versionEnabled = 'V4';
		enableXStateV4Inspect();
	}
}

export const useXStateInspect = ( machineVersion ) => {
	let xstateV5Inspector;
	if ( isXStateV5InspectEnabled && machineVersion === 'V5' ) {
		xstateV5Inspector = XStateV5Inspect().inspect;
	}

	return {
		versionEnabled,
		xstateV5Inspector,
	};
};
