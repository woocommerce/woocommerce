const path = require( 'path' );
const fs = require( 'fs' );
const getAppRoot = require( './app-root' );

// Copy local test configuration file if it exists.
const appPath = getAppRoot();
const localTestConfigFile = path.resolve( appPath, 'tests/e2e/config/default.json' );
const testConfigFile = path.resolve( __dirname, '../config/default.json' );
if ( fs.existsSync( localTestConfigFile ) ) {
	fs.copyFileSync(
		localTestConfigFile,
		testConfigFile
	);
}

const getTestConfig = () => {
	const rawTestConfig = fs.readFileSync( testConfigFile );

	let testConfig = JSON.parse(rawTestConfig);
	let testPort = testConfig.url.match(/[0-9]+/);
	if ( testPort ) {
		testConfig.baseUrl = testConfig.url.substr(0, testConfig.url.length - 1);
		testConfig.port = testPort[0] ? testPort[0] : '8084';
	} else {
		testConfig.baseUrl = testConfig.url;
		testConfig.port = '';
	}
	return testConfig;
};

module.exports = getTestConfig;
