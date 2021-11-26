/*
 * Internal dependencies
 */
const allSpecs = require( './specs' );
const fs = require( 'fs' );

/**
 * Test set up configuration for the test scaffolding tool.
 */
const packageInstallFiles = {
	testSpecs: 'data/install-specs.json',
	defaultJson: 'data/default-test-config.json',
	initializeSh: 'data/initialize.sh.default',
};

module.exports = {
	allSpecs,
	packageInstallFiles,
};
