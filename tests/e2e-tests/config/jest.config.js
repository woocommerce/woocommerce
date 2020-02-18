/**
 * External dependencies
 */
const { jestConfig } = require( 'puppeteer-utils' );
const modifiedConfig = jestConfig;
const afterEnvSetup = modifiedConfig.setupFilesAfterEnv;

afterEnvSetup.push( '<rootDir>/tests/e2e-tests/config/jest.setup.js');
modifiedConfig.setupFilesAfterEnv = afterEnvSetup;

// Sort test path alphabetically. This is needed so that `activate-and-setup` tests run first
modifiedConfig.testSequencer = '<rootDir>/tests/e2e-tests/config/jest-custom-sequencer.js';

module.exports = modifiedConfig;
