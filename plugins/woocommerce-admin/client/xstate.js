/**
 * This script configures the XState development tools in the
 * development environment. It should not be included in production.
 *
 * To enable the XState inspector, open the browser console and type:
 * localStorage.setItem("xstate_inspect", "true")
 */

async function enableXStateInspect() {
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
		'Devtools: XState inspector enabled for WooCommerce Admin. To disable, type localStorage.setItem("xstate_inspect", "false")'
	);
}

if (
	process.env.NODE_ENV === 'development' &&
	window.localStorage.getItem( 'xstate_inspect' ) === 'true'
) {
	enableXStateInspect();
}
