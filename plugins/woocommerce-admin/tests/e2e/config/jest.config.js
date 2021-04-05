const path = require( 'path' );
const { jestConfig: baseE2Econfig } = require( '@woocommerce/e2e-environment' );

module.exports = {
	...baseE2Econfig,
	moduleFileExtensions: [ 'js', 'ts' ],
	roots: [ path.resolve( __dirname, '../specs' ) ],
	testMatch: [ '**/*.(test|spec).(j|t)s', '*.(test|spec).(j|t)s' ],
	testTimeout: 30000,
	transform: {
		'\\.[jt]sx?$': [
			'babel-jest',
			{
				configFile: path.join(
					__dirname,
					'../../../',
					'babel.config.js'
				),
			},
		],
	},
};
