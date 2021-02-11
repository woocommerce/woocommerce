#!/usr/bin/env node

const { spawnSync } = require( 'child_process' );
const program = require( 'commander' );
const path = require( 'path' );
const fs = require( 'fs' );
const { getAppRoot } = require( '../utils' );
const { JEST_PUPPETEER_CONFIG } = process.env;

program
	.usage( '<file ...> [options]' )
	.option( '--dev', 'Development mode' )
	.option( '--debug', 'Debug mode' )
	.parse( process.argv );

const appPath = getAppRoot();

const nodeConfigDirs = [
	path.resolve( __dirname, '../config' ),
];

if ( appPath ) {
	nodeConfigDirs.unshift(
		path.resolve( appPath, 'tests/e2e/config' )
	);
}

let testEnvVars = {
	NODE_ENV: 'test-e2e',
	NODE_CONFIG_DIR: nodeConfigDirs.join( ':' ),
	node_config_dev: program.dev ? 'yes' : 'no',
	jest_test_timeout: program.dev ? 120000 : 30000,
};

if ( ! JEST_PUPPETEER_CONFIG ) {
	// Use local Puppeteer config if there is one.
	// Load test configuration file into an object.
	const localJestConfigFile = path.resolve( appPath, 'tests/e2e/config/jest-puppeteer.config.js' );
	const jestConfigFile = path.resolve( __dirname, '../config/jest-puppeteer.config.js' );

	testEnvVars.JEST_PUPPETEER_CONFIG = fs.existsSync( localJestConfigFile ) ? localJestConfigFile : jestConfigFile;
}

// Check for running a specific script
if ( program.args.length == 1 ) {
	// Check for both absolute and relative paths
	const testSpecAbs = path.resolve( program.args[0] );
	const testSpecRel = path.resolve( appPath, program.args[0] );
	if ( fs.existsSync( testSpecAbs ) ) {
		process.env.jest_test_spec = testSpecAbs;
	} else if ( fs.existsSync( testSpecRel ) ) {
			process.env.jest_test_spec = testSpecRel;
	}
}

let jestCommand = 'jest';
const jestArgs = [
	'--maxWorkers=1',
	'--rootDir=./',
	'--verbose',
	...program.args,
];

if ( program.debug ) {
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
