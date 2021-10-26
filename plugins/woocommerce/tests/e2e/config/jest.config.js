const path = require( 'path' );
const { useE2EJestConfig, getAppRoot } = require( '@woocommerce/e2e-environment' );

const jestConfig = useE2EJestConfig( {
	// roots: [ path.resolve( __dirname, '../specs' ) ],
	roots: [ path.resolve( getAppRoot(), 'plugins/woocommerce/tests/e2e/specs' ) ],
} );

module.exports = jestConfig;
