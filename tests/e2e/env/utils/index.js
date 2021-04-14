const getAppRoot = require( './app-root' );
const { getAppName, getAppBase } = require( './app-name' );
const { getTestConfig, getAdminConfig } = require( './test-config' );
const takeScreenshotFor = require( './take-screenshot' );

module.exports = {
	getAppBase,
	getAppRoot,
	getAppName,
	getTestConfig,
	getAdminConfig,
	takeScreenshotFor,
};
