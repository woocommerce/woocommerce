const getAppRoot = require( './app-root' );
const { getAppName, getAppBase } = require( './app-name' );
const { getTestConfig, getAdminConfig } = require( './test-config' );

module.exports = {
	getAppBase,
	getAppRoot,
	getAppName,
	getTestConfig,
	getAdminConfig,
};
