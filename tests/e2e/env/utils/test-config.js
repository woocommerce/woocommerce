const path = require( 'path' );
const fs = require( 'fs' );

const getTestConfig = () => {
	const testConfigFile = path.resolve( __dirname, '../config/default.json' );
	const rawTestConfig = fs.readFileSync( testConfigFile );

	let testConfig = JSON.parse(rawTestConfig);
	testConfig.baseUrl = testConfig.url.substr(0, testConfig.url.length - 1);
	let testPort = testConfig.url.match(/[0-9]+/);
	testConfig.port = testPort[0] ? testPort[0] : '8084';
	return testConfig;
};

module.exports = getTestConfig;
