#!/usr/bin/env node

const { spawnSync } = require( 'child_process' );
const program = require( 'commander' );
const path = require( 'path' );
const fs = require( 'fs' );
const getAppPath = require( '../utils/app-root' );

const dockerArgs = [];
let command = '';

program
    .command( 'up', 'Start and build the Docker container' )
    .command( 'down', 'Stop the Docker container and remove volumes' )
    .action( ( cmd, options ) => {
        arg = options.args ? options.args[ 0 ] : options[ 0 ];
        if ( 'up' === arg ) {
            command = 'up';
            dockerArgs.push( 'up', '--build', '-d' );
        }

        if ( 'down' === arg ) {
            command = 'down';
            dockerArgs.push( 'down', '-v' );
        }
    } )
    .parse( process.argv );

const appPath = getAppPath();
const envVars = {};

if ( appPath ) {
    // Look for a Docker compose file in the dependent app's path.
	const appDockerComposefile = path.resolve( appPath, 'docker-compose.yaml' );

    // Specify the app's Docker compose file in our command.
	if ( fs.existsSync( appDockerComposefile ) ) {
		dockerArgs.unshift( '-f', appDockerComposefile );
    }

    if ( 'up' === command ) {
        // Look for an initialization script in the dependent app.
        const appInitFile = path.resolve( appPath, 'tests/e2e/docker/initialize.sh' );

        // If found, copy it into the wp-cli Docker context so
        // it gets picked up by the entrypoint script.
        if ( fs.existsSync( appInitFile ) ) {
            fs.copyFileSync(
                appInitFile,
                path.resolve( __dirname, '../docker/wp-cli/initialize.sh' )
            );
        }
    }

    // Provide an "app name" to use in Docker container names.
    envVars.APP_NAME = path.basename( appPath );
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
