#!/usr/bin/env node

const { spawnSync } = require( 'child_process' );
const program = require( 'commander' );
const path = require( 'path' );
const fs = require( 'fs' );
const { getAdminConfig, getAppBase, getAppRoot, getAppName, getTestConfig } = require( '../utils' );

const dockerArgs = [];
let command = '';
let customInitFile = '';

program
    .command( 'up', 'Start and build the Docker container' )
    .command( 'down', 'Stop the Docker container and remove volumes' )
    .action( ( cmd, options ) => {
        args = options.args ? options.args : options;
        if ( 'up' === args[0] ) {
            command = 'up';
            dockerArgs.push( 'up', '--build', '-d' );
            customInitFile = args[1] ? args[1] : '';
        }

        if ( 'down' === args[0] ) {
            command = 'down';
            dockerArgs.push( 'down', '-v' );
        }
    } )
    .parse( process.argv );

const appPath = getAppRoot();
const envVars = getAdminConfig();

if ( appPath ) {
    if ( 'up' === command ) {
        // Look for an initialization script in the dependent app.
        if ( customInitFile ) {
            const possibleInitFile = customInitFile;
            customInitFile = path.resolve( possibleInitFile );
            if ( ! fs.existsSync( customInitFile ) ) {
                customInitFile = path.resolve( appPath, possibleInitFile );
            }
            if ( ! fs.existsSync( customInitFile ) ) {
                customInitFile = '';
            }
        }
        const appInitFile = customInitFile ? customInitFile : path.resolve( appPath, 'tests/e2e/docker/initialize.sh' );
        // If found, copy it into the wp-cli Docker context so
        // it gets picked up by the entrypoint script.
        if ( fs.existsSync( appInitFile ) ) {
            fs.copyFileSync(
                appInitFile,
                path.resolve( __dirname, '../docker/wp-cli/initialize.sh' )
            );
            console.log('Initializing ' + appInitFile );
        }
    }

    // Provide an "app name" to use in Docker container names.
    envVars.APP_NAME = getAppName();
}

// Load test configuration file into an object.
const testConfig = getTestConfig();

// Set some environment variables
if ( ! process.env.WC_E2E_FOLDER_MAPPING ) {
	envVars.WC_E2E_FOLDER_MAPPING = '/var/www/html/wp-content/plugins/' + getAppBase();
}
if ( ! process.env.WORDPRESS_PORT ) {
	process.env.WORDPRESS_PORT = testConfig.port;
}
if ( ! process.env.WORDPRESS_URL ) {
	process.env.WORDPRESS_URL = testConfig.url;
}

// Ensure that the first Docker compose file loaded is from our local env.
dockerArgs.unshift( '-f', path.resolve( __dirname, '../docker-compose.yaml' ) );

const dockerProcess = spawnSync(
	'docker-compose',
	dockerArgs,
	{
		stdio: 'inherit',
		env: Object.assign( {}, process.env, envVars ),
	}
);

console.log( 'Docker exit code: ' + dockerProcess.status );

// Pass Docker exit code to npm
process.exit( dockerProcess.status );
