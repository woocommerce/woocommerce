const path = require( 'path' );
const { useE2EJestConfig } = require( '@woocommerce/e2e-environment' );

const config = useE2EJestConfig( {
	moduleFileExtensions: [ 'js', 'ts', 'tsx' ],
	roots: [ path.resolve( __dirname, '../specs' ) ],
	testMatch: [ '**/*.(test|spec).(js|ts|tsx)', '*.(test|spec).(js|ts|tsx)' ],
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
} );

module.exports = config;
