/**
 * Provide the base test URL to bash scripts.
 */
const { getTestConfig } = require( './test-config' );
const testConfig = getTestConfig();

console.log( testConfig.baseUrl );
