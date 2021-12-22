const { testAdminPaymentSetupTask } = require( '@woocommerce/admin-e2e-tests' );
const skipOnRetest = require( '../smoke-tests/skip-retest' );

skipOnRetest( testAdminPaymentSetupTask, 'Analytics pages' );
