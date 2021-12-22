const { testAdminAnalyticsPages } = require( '@woocommerce/admin-e2e-tests' );
const skipOnRetest = require( '../smoke-tests/skip-retest' );

skipOnRetest( testAdminAnalyticsPages, 'Analytics pages' );
