// Set the node-config directory so we don't need to put it in the project root.
process.env.NODE_CONFIG_DIR = __dirname + '/tests/e2e/';

const { jestConfig } = require( 'puppeteer-utils' );

// Change the default configuration to suit our needs.
delete jestConfig.roots; // We want to be explicit with our search path.
jestConfig.rootDir = 'tests/e2e';
jestConfig.testSequencer = '<rootDir>/jest-obw-sequencer.js';
jestConfig.verbose = true;
jestConfig.maxWorkers = 1; // Tests must be run sequentially!

// Give enough time in development mode to inspect the request.
if ( 'development' === process.env.NODE_ENV ) {
	jestConfig.testTimeout = 120000;
} else {
	jestConfig.testTimeout = 60000;
}

module.exports = jestConfig;
