/**
 * Provide the application name to bash scripts.
 */
const getAppName = require( './app-name' );
const appName = getAppName();

console.log( appName );
