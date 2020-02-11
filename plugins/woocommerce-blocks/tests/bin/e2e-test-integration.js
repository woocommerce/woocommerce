#!/usr/bin/env node

const { spawnSync } = require( 'child_process' );
const program = require( 'commander' );

program
	.usage( '<file ...> [options]' )
	.option( '--dev', 'Development mode' )
	.parse( process.argv );

const testEnvVars = {
	NODE_ENV: 'test:e2e',
	JEST_PUPPETEER_CONFIG: 'tests/e2e-tests/config/jest-puppeteer.config.js',
	NODE_CONFIG_DIR: 'tests/e2e-tests/config',
};

if ( program.dev ) {
	testEnvVars.JEST_PUPPETEER_CONFIG =
		'tests/e2e-tests/config/jest-puppeteer.dev.config.js';
}

const envVars = Object.assign( {}, process.env, testEnvVars );

let jestProcess = spawnSync(
	'jest',
	[
		'--maxWorkers=1',
		'--config=tests/e2e-tests/config/jest.config.js',
		'--rootDir=./',
		'--verbose',
		program.args,
	],
	{
		stdio: 'inherit',
		env: envVars,
	}
);

console.log( 'Jest exit code: ' + jestProcess.status );

// Pass Jest exit code to npm
process.exit( jestProcess.status );
