const path = require( 'path' );
const { useE2EJestConfig } = require( '@woocommerce/e2e-environment' );

// eslint-disable-next-line
const jestConfig = useE2EJestConfig( {
	roots: [ path.resolve( __dirname, '../specs' ) ],
} );

module.exports = jestConfig;
