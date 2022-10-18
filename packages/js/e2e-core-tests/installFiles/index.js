/**
 * External dependencies
 */
const path = require( 'path' );

const buildPath = ( filename ) => `installFiles${path.sep}${filename}`;

module.exports = {
	testSpecs: buildPath( 'scaffold-tests.json' ),
	defaultJson: buildPath( 'default-test-config.json' ),
	initializeSh: buildPath( 'initialize.sh.default' ),
};
