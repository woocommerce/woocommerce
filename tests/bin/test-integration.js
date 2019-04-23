#!/usr/bin/env node

const { spawn } = require( 'child_process' );
const program = require( 'commander' );

program
	.usage( '<file ...> [options]' )
	.option( '-d, --dev', 'Development mode' )
	.option( '-h, --headless', 'Headless run' )
	.parse( process.argv );

const testEnvVars = {
	NODE_ENV: 'test',
	JEST_PUPPETEER_CONFIG: 'tests/e2e-tests/config/jest-puppeteer.config.js',
	NODE_CONFIG_DIR: 'tests/e2e-tests/config',
};

if ( program.dev ) {
	testEnvVars.JEST_PUPPETEER_CONFIG = 'tests/e2e-tests/config/jest-puppeteer.dev.config.js';
}

if ( program.headless ) {
	testEnvVars.PUPPETEER_HEADLESS = true;
}

const envVars = Object.assign( {}, process.env, testEnvVars );

spawn(
	'jest',
	[
		'--maxWorkers=4',
		'--config=tests/e2e-tests/config/jest.config.integration.js',
		'--rootDir=./',
		program.args,
	],
	{
		stdio: 'inherit',
		env: envVars,
	}
);
