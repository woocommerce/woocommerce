#!/usr/bin/env node

const { spawnSync } = require( 'child_process' );
const program = require( 'commander' );
const path = require( 'path' );
const fs = require( 'fs' );
const {
	getAdminConfig,
	getAppBase,
	getAppRoot,
	getAppName,
	getTestConfig,
	resolveLocalE2ePath,
	resolvePackagePath,
} = require( '../utils' );

const dockerArgs = [];
let command = '';
let customInitFile = '';

program
	.command( 'up', 'Start and build the Docker container' )
	.command( 'down', 'Stop the Docker container and remove volumes' )
	.action( ( cmd, options ) => {
		args = options.args ? options.args : options;
		if ( args[ 0 ] === 'up' ) {
			command = 'up';
			dockerArgs.push( 'up', '--build', '-d' );
			customInitFile = args[ 1 ] ? args[ 1 ] : '';
		}

		if ( args[ 0 ] === 'down' ) {
			command = 'down';
			dockerArgs.push( 'down', '-v' );
		}
	} )
	.parse( process.argv );

const appPath = getAppRoot();
const envVars = getAdminConfig();

if ( appPath ) {
	if ( command === 'up' ) {
		// Look for an initialization script in the dependent app.
		if ( customInitFile && typeof customInitFile === 'string' ) {
			const possibleInitFile = customInitFile;
			customInitFile = path.resolve( possibleInitFile );

			if ( ! fs.existsSync( customInitFile ) ) {
				customInitFile = path.resolve( appPath, possibleInitFile );
			}
			if ( ! fs.existsSync( customInitFile ) ) {
				customInitFile = '';
			}
		} else {
			customInitFile = '';
		}

		const appInitFile = customInitFile
			? customInitFile
			: resolveLocalE2ePath( 'docker/initialize.sh' );
		// If found, copy it into the wp-cli Docker context so
		// it gets picked up by the entrypoint script.
		if ( fs.existsSync( appInitFile ) ) {
			fs.copyFileSync(
				appInitFile,
				resolvePackagePath( 'docker/wp-cli/initialize.sh' )
			);
			console.log( 'Initializing ' + appInitFile );
		}
	}

	// Provide an "app name" to use in Docker container names.
	envVars.APP_NAME = getAppName();
}

// Load test configuration file into an object.
const testConfig = getTestConfig();

// Set some environment variables
if ( ! process.env.WC_E2E_FOLDER_MAPPING ) {
	envVars.WC_E2E_FOLDER_MAPPING =
		'/var/www/html/wp-content/plugins/' + getAppBase();
}

if ( ! process.env.WORDPRESS_PORT ) {
	process.env.WORDPRESS_PORT = testConfig.port;
}
if ( ! process.env.WORDPRESS_URL ) {
	process.env.WORDPRESS_URL = testConfig.url;
}

// Allow overriding default services and settings configurations.
if ( fs.existsSync( '../../../docker-compose.override.yml' ) ) {
	console.log( 'Using `docker-compose.override.yml`.' );
	dockerArgs.unshift( '-f', path.resolve('../../../docker-compose.override.yml' ) );
}

// Ensure that the first Docker compose file loaded is from our local env.
dockerArgs.unshift( '-f', resolvePackagePath( 'docker-compose.yaml' ) );

const dockerProcess = spawnSync( 'docker-compose', dockerArgs, {
	stdio: 'inherit',
	env: Object.assign( {}, process.env, envVars ),
} );

console.log( 'Docker exit code: ' + dockerProcess.status );

// Pass Docker exit code to npm
process.exit( dockerProcess.status );
