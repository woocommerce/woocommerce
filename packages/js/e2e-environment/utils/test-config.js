const path = require( 'path' );
const fs = require( 'fs' );
const getAppRoot = require( './app-root' );

const appPath = getAppRoot();

/**
 * Resolve a local E2E file.
 *
 * @param {string} filename Filename to append to the path.
 * @return {string}
 */
const resolveLocalE2ePath = ( filename = '' ) => {
	const { WC_E2E_FOLDER } = process.env;
	const localPath = `${ WC_E2E_FOLDER }/tests/e2e/${ filename }`;
	const resolvedPath = path.resolve(
		appPath,
		localPath.indexOf( '/' ) == 0 ? localPath.slice( 1 ) : localPath
	);

	return resolvedPath;
};

/**
 * Resolve a package name installable by npm install.
 *
 * @param {string} packageName Name of the installed package.
 * @param {boolean} allowRecurse Allow a recursive call. Default true.
 * @return {Object}
 */
const resolvePackage = ( packageName, allowRecurse = true ) => {
	const resolvedPackage = {};

	try {
		const resolvedPath = path.dirname( require.resolve( packageName ) );
		const buildPaths = [ 'dist', 'build', 'build-modules' ];

		// Remove build paths from the resolved path.
		let resolvedParts = resolvedPath.split( path.sep );
		for ( let rp = resolvedParts.length - 1; rp >= 0; rp-- ) {
			if ( buildPaths.includes( resolvedParts[ rp ] ) ) {
				resolvedParts = resolvedParts.slice( 0, -1 );
			} else {
				break;
			}
		}
		resolvedPackage.path = resolvedParts.join( path.sep );
		resolvedPackage.name = packageName;
	} catch ( e ) {
		// Package name installed is not the package name.
		resolvedPackage.path = '';
		resolvedPackage.name = '';
	}

	// Attempt to find the package through the project package lock file.
	if ( ! resolvedPackage.path.length && allowRecurse ) {
		const packageLockPath = path.resolve( appPath, 'package-lock.json' );
		const packageLockContent = fs.readFileSync( packageLockPath );
		const { dependencies } = JSON.parse( packageLockContent );

		for ( const [ key, value ] of Object.entries( dependencies ) ) {
			if ( value.version.indexOf( packageName ) == 0 ) {
				resolvedPackage = resolvePackage( key, false );
				break;
			}
		}
	}

	return resolvedPackage;
};

/**
 * Resolve a file in a package.
 *
 * @param {string} filename Filename to append to the path.
 * @param {string} packageName Name of the installed package. Default @woocommerce/e2e-environment.
 * @return {string}
 */
const resolvePackagePath = ( filename, packageName = '' ) => {
	let packagePath;
	if ( ! packageName.length ) {
		packagePath = path.resolve( __dirname, '../' );
	} else {
		const pkg = resolvePackage( packageName );
		packagePath = pkg.path;
	}

	const resolvedPath = path.resolve(
		packagePath,
		filename.indexOf( '/' ) == 0 ? filename.slice( 1 ) : filename
	);

	return resolvedPath;
};

/**
 * Resolves the path a single E2E test
 *
 * @param {string} filePath Path to a specific test file
 * @param {Array} exclude An array of directories that won't be removed in the event that duplicates exist.
 * @return {string}
 */
const resolveSingleE2EPath = ( filePath ) => {
	const { SMOKE_TEST_URL, GITHUB_ACTIONS } = process.env;
	const localPath = resolveLocalE2ePath( filePath );

	if ( fs.existsSync( localPath ) ) {
		return localPath;
	} else {
		const prunedPath = filePath.replace( 'tests/e2e', '' );
		return resolveLocalE2ePath( prunedPath );
	}
};

// Copy local test configuration file if it exists.
const localTestConfigFile = resolveLocalE2ePath( 'config/default.json' );
const defaultConfigFile = resolvePackagePath( 'config/default/default.json' );
const testConfigFile = resolvePackagePath( 'config/default.json' );

if ( fs.existsSync( localTestConfigFile ) ) {
	fs.copyFileSync( localTestConfigFile, testConfigFile );
} else {
	fs.copyFileSync( defaultConfigFile, testConfigFile );
}

/**
 * Get test container configuration.
 *
 * @return {any}
 */
const getTestConfig = () => {
	const rawTestConfig = fs.readFileSync( testConfigFile );
	const config = require( 'config' );
	const url = config.get( 'url' );
	const users = config.get( 'users' );

	// Support for environment variable overrides.
	const testConfig = JSON.parse( rawTestConfig );
	if ( url ) {
		testConfig.url = url;
	}
	if ( users ) {
		if ( users.admin ) {
			testConfig.users.admin = users.admin;
		}
		if ( users.customer ) {
			testConfig.users.customer = users.customer;
		}
	}

	const testPort = testConfig.url.match( /[0-9]+/ );
	testConfig.baseUrl = testConfig.url.substr( 0, testConfig.url.length - 1 );
	if ( Array.isArray( testPort ) ) {
		testConfig.port = testPort[ 0 ] ? testPort[ 0 ] : '8084';
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
		WORDPRESS_LOGIN: testConfig.users.admin.username
			? testConfig.users.admin.username
			: 'admin',
		WORDPRESS_PASSWORD: testConfig.users.admin.password
			? testConfig.users.admin.password
			: 'password',
		WORDPRESS_EMAIL: testConfig.users.admin.email
			? testConfig.users.admin.email
			: 'admin@woocommercecoree2etestsuite.com',
	};

	return adminConfig;
};

module.exports = {
	getTestConfig,
	getAdminConfig,
	resolveLocalE2ePath,
	resolvePackage,
	resolvePackagePath,
	resolveSingleE2EPath,
};
