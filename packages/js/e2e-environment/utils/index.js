const getAppRoot = require( './app-root' );
const { getAppName, getAppBase } = require( './app-name' );
const { getTestConfig, getAdminConfig, resolveLocalE2ePath } = require( './test-config' );
const { getRemotePluginZip, getLatestReleaseZipUrl, deleteDownloadedPluginFiles } = require('./get-plugin-zip');
const takeScreenshotFor = require( './take-screenshot' );
const updateReadyPageStatus = require('./update-ready-page');
const consoleUtils = require( './filter-console' );

module.exports = {
	getAppBase,
	getAppRoot,
	getAppName,
	getTestConfig,
	getAdminConfig,
	resolveLocalE2ePath,
	getRemotePluginZip,
	getLatestReleaseZipUrl,
	deleteDownloadedPluginFiles,
	takeScreenshotFor,
	updateReadyPageStatus,
	...consoleUtils,
};
