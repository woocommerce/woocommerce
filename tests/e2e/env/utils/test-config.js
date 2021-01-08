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

	let testConfig = JSON.parse( rawTestConfig );
	let testPort = testConfig.url.match( /[0-9]+/ );
	testConfig.baseUrl = testConfig.url.substr( 0, testConfig.url.length - 1 );
	if ( Array.isArray( testPort ) ) {
		testConfig.port = testPort[0] ? testPort[0] : '8084';
	} else {
		testConfig.port = '';
	}
	return testConfig;
};

/**
 * Get user account settings for Docker configuration.
 */
const getAdminConfig = () => {
	const testConfig = getTestConfig();
	const adminConfig = {
		'WORDPRESS_LOGIN': testConfig.users.admin.username ? testConfig.users.admin.username : 'admin',
		'WORDPRESS_PASSWORD': testConfig.users.admin.password ? testConfig.users.admin.password : 'password',
		'WORDPRESS_EMAIL': testConfig.users.admin.email ? testConfig.users.admin.email : 'admin@woocommercecoree2etestsuite.com',
	};

	return adminConfig;
};

module.exports = {
	getTestConfig,
	getAdminConfig,
};
