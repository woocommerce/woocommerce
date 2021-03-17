const path = require( 'path' );
const { jestConfig: baseE2Econfig } = require( '@woocommerce/e2e-environment' );

module.exports = {
	...baseE2Econfig,
	moduleFileExtensions: [ 'js', 'ts' ],
	roots: [ path.resolve( __dirname, '../specs' ) ],
	testMatch: [ '**/*.(test|spec).(j|t)s', '*.(test|spec).(j|t)s' ],
	testTimeout: 30000,
	// transform: {
	// 	'^.+\\.(ts|tsx|js|jsx)$': 'babel-jest',
	// },
};
