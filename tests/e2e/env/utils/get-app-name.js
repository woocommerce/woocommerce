/**
 * Provide the application name to bash scripts.
 */
const { getAppName } = require( './app-root' );
const appName = getAppName();

console.log( appName );
