const { testAdminBasicSetup } = require( '@woocommerce/admin-e2e-tests' );
const skipOnRetest = require( '../smoke-tests/skip-retest' );

skipOnRetest( testAdminBasicSetup, 'Basic setup' );
