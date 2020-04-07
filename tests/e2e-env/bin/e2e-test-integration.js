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

const testEnvVars = {
	NODE_ENV: 'test:e2e',
	JEST_PUPPETEER_CONFIG: path.resolve(
		__dirname,
		'../config/jest-puppeteer.config.js'
	),
	NODE_CONFIG_DIR: path.resolve(
		__dirname,
		'../config'
	),
};

let jestCommand = 'jest';
const jestArgs = [
	'--maxWorkers=1',
	'--rootDir=./',
	'--verbose',
	...program.args,
];

if ( program.dev ) {
	testEnvVars.JEST_PUPPETEER_CONFIG = path.resolve(
		__dirname,
		'../config/jest-puppeteer.dev.config.js'
	);
	jestCommand = 'npx';
	jestArgs.unshift( 'ndb', 'jest' );
}

const envVars = Object.assign( {}, process.env, testEnvVars );

const appPath = getAppPath();
let configPath = path.resolve( __dirname, '../config/jest.config.js' );

// Look for a Jest config in the dependent app's path.
if ( appPath ) {
	const appConfig = path.resolve( appPath, 'tests/e2e-tests/config/jest.config.js' );

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
