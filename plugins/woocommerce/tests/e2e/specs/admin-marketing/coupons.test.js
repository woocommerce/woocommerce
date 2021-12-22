const { testAdminCouponsPage } = require( '@woocommerce/admin-e2e-tests' );
const skipOnRetest = require( '../smoke-tests/skip-retest' );

skipOnRetest( testAdminCouponsPage, 'Analytics pages' );

