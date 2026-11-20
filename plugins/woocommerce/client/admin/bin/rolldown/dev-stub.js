// Dev stand-in for a built admin script: hands the entry to Vite.
// dev/stub.mjs writes it with the entry's source path.
( function () {
	var origin = 'http://localhost:5173';
	// The React Refresh preamble from @vitejs/plugin-react and the Vite
	// client run once, before any entry, even with several stubs on a page.
	window.__wcViteReady =
		window.__wcViteReady ||
		import( origin + '/@react-refresh' ).then( function ( refresh ) {
			refresh.injectIntoGlobalHook( window );
			window.$RefreshReg$ = function () {};
			window.$RefreshSig$ = function () {
				return function ( type ) {
					return type;
				};
			};
			return import( origin + '/@vite/client' );
		} );
	window.__wcViteReady.then( function () {
		return import( origin + '/client/index.tsx' );
	} );
} )();
