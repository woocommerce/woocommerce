const path = require( 'path' );
const { useE2EJestConfig, getAppRoot } = require( '@woocommerce/e2e-environment' );

const jestConfig = useE2EJestConfig( {
	// roots: [ path.resolve( __dirname, '../specs' ) ],
	// roots: [ path.resolve( getAppRoot(), 'plugins/woocommerce/tests/e2e/specs' ) ],
	roots: [ '/home/runner/work/woocommerce/woocommerce/package/woocommerce/tests/e2e-tests/specs' ],
} );

module.exports = jestConfig;
