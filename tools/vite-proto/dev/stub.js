// Dev stand-in for assets/client/admin/app/index.js: hands the entry to Vite.
( function () {
	var origin = 'http://localhost:5173';
	var script = document.createElement( 'script' );
	script.type = 'module';
	// The React Refresh preamble from @vitejs/plugin-react, then the entry.
	script.textContent =
		'import { injectIntoGlobalHook } from "' + origin + '/@react-refresh";' +
		'injectIntoGlobalHook(window);' +
		'window.$RefreshReg$ = () => {};' +
		'window.$RefreshSig$ = () => (type) => type;' +
		'await import("' + origin + '/@vite/client");' +
		'await import("' + origin + '/client/index.tsx");';
	document.head.appendChild( script );
} )();
