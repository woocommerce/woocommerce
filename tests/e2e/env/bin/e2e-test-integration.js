#!/usr/bin/env node

const { spawnSync } = require( 'child_process' );
const program = require( 'commander' );
const path = require( 'path' );
const fs = require( 'fs' );
const getAppPath = require( '../utils/app-root' );

program
	.usage( '<file ...> [options]' )
	.option( '--dev', 'Development mode' )
	.parse( process.argv );

const appPath = getAppPath();

const nodeConfigDirs = [
	path.resolve( __dirname, '../config' ),
];

if ( appPath ) {
	nodeConfigDirs.unshift(
		path.resolve( appPath, 'tests/e2e/config' )
	);
}

const testEnvVars = {
	NODE_ENV: 'test-e2e',
	JEST_PUPPETEER_CONFIG: path.resolve(
		__dirname,
		'../config/jest-puppeteer.config.js'
	),
	NODE_CONFIG_DIR: nodeConfigDirs.join( ':' ),
	node_config_dev: program.dev ? 'yes' : 'no',
};

let jestCommand = 'jest';
const jestArgs = [
	'--maxWorkers=1',
	'--rootDir=./',
	'--verbose',
	...program.args,
];

if ( program.dev ) {
	jestCommand = 'npx';
	jestArgs.unshift( 'ndb', 'jest' );
}

const envVars = Object.assign( {}, process.env, testEnvVars );

let configPath = path.resolve( __dirname, '../config/jest.config.js' );

// Look for a Jest config in the dependent app's path.
if ( appPath ) {
	const appConfig = path.resolve( appPath, 'tests/e2e/config/jest.config.js' );

	if ( fs.existsSync( appConfig ) ) {
		configPath = appConfig;
	}
}

jestArgs.push( '--config=' + configPath );

const jestProcess = spawnSync(
	jestCommand,
	jestArgs,
	{
		stdio: 'inherit',
		env: envVars,
	}
);

console.log( 'Jest exit code: ' + jestProcess.status );

// Pass Jest exit code to npm
process.exit( jestProcess.status );
