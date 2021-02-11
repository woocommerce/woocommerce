/**
 * External dependencies
 */
const path = require( 'path' );
/**
 * Internal dependencies
 */
const { getTestConfig } = require( './test-config' );
const getAppRoot = require( './app-root' );

const getAppName = () => {
	const testConfig = getTestConfig();
	if ( testConfig.appName ) {
		return testConfig.appName;
	}
	return getAppBase();
};

const getAppBase = () => {
	const appRoot = getAppRoot();
	return path.basename( appRoot );
}

module.exports = {
	getAppName,
	getAppBase,
};
