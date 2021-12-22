const { testAdminAnalyticsOverview } = require( '@woocommerce/admin-e2e-tests' );
const skipOnRetest = require( '../smoke-tests/skip-retest' );

skipOnRetest( testAdminAnalyticsOverview, 'Analytics pages' );
