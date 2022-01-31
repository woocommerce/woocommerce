#!/usr/bin/env node

const { spawnSync } = require( 'child_process' );
const program = require( 'commander' );
const path = require( 'path' );
const fs = require( 'fs' );
const {
	getAppRoot,
	resolveLocalE2ePath,
	resolvePackagePath,
	resolveSingleE2EPath,
} = require( '../utils' );
const {
	WC_E2E_SCREENSHOTS,
	JEST_PUPPETEER_CONFIG,
	DEFAULT_TIMEOUT_OVERRIDE,
} = process.env;

program
	.usage( '<file ...> [options]' )
	.option( '--dev', 'Development mode' )
	.option( '--debug', 'Debug mode' )
	.parse( process.argv );

const appPath = getAppRoot();

// clear the screenshots folder before running tests.
if ( WC_E2E_SCREENSHOTS ) {
	const screenshotPath = resolveLocalE2ePath( 'screenshots' );
	if ( fs.existsSync( screenshotPath ) ) {
		fs.readdirSync( screenshotPath ).forEach( ( file, index ) => {
			const filename = path.join( screenshotPath, file );
			fs.unlinkSync( filename );
		} );
	}
}

const nodeConfigDirs = [ resolvePackagePath( 'config' ) ];

if ( appPath ) {
	nodeConfigDirs.unshift( resolveLocalE2ePath( 'config' ) );
}

const testEnvVars = {
	NODE_ENV: 'test-e2e',
	NODE_CONFIG_DIR: nodeConfigDirs.join( ':' ),
	node_config_dev: program.dev ? 'yes' : 'no',
	jest_test_timeout: program.dev ? 120000 : 30000,
};

if ( DEFAULT_TIMEOUT_OVERRIDE ) {
	testEnvVars.jest_test_timeout = DEFAULT_TIMEOUT_OVERRIDE;
}

if ( ! JEST_PUPPETEER_CONFIG ) {
	// Use local Puppeteer config if there is one.
	// Load test configuration file into an object.
	const localJestConfigFile = resolveLocalE2ePath( 'config/jest-puppeteer.config.js' );
	const jestConfigFile = resolvePackagePath( 'config/jest-puppeteer.config.js' );

	testEnvVars.JEST_PUPPETEER_CONFIG = fs.existsSync( localJestConfigFile )
		? localJestConfigFile
		: jestConfigFile;
}

// Check for running a specific script
if ( program.args.length == 1 ) {
	// Check for both absolute and relative paths
	const testSpecAbs = path.resolve( program.args[ 0 ] );
	const testSpecRel = resolveSingleE2EPath( program.args[ 0 ] );
	if ( fs.existsSync( testSpecAbs ) ) {
		process.env.jest_test_spec = testSpecAbs;
	} else if ( fs.existsSync( testSpecRel ) ) {
		process.env.jest_test_spec = testSpecRel;
	}
}

let jestCommand = 'jest';
let outputFile = 'test-results.json';
const jestArgs = [
	'--maxWorkers=1',
	'--rootDir=./',
	'--verbose',
	'--json',
	'--outputFile=' + outputFile,
	...program.args,
];

if ( program.debug ) {
	jestCommand = 'npx';
	jestArgs.unshift( 'ndb', 'jest' );
}

const envVars = Object.assign( {}, process.env, testEnvVars );

let configPath = resolvePackagePath( 'config/jest.config.js' );

// Look for a Jest config in the dependent app's path.
if ( appPath ) {
	const appConfig = resolveLocalE2ePath( 'config/jest.config.js' );

	if ( fs.existsSync( appConfig ) ) {
		configPath = appConfig;
	}
}

jestArgs.push( '--config=' + configPath );

const jestProcess = spawnSync( jestCommand, jestArgs, {
	stdio: 'inherit',
	env: envVars,
} );

let results = resolvePackagePath( outputFile );
if ( fs.existsSync( results ) ) {
	let localResults = resolveLocalE2ePath( outputFile );
	fs.copyFileSync( results, localResults );
}
console.log( 'Jest exit code: ' + jestProcess.status );

// Pass Jest exit code to npm
process.exit( jestProcess.status );
