const {
	testAdminOnboardingWizard,
	testSelectiveBundleWCPay,
} = require( '@woocommerce/admin-e2e-tests' );

const {
	withRestApi,
	IS_RETEST_MODE,
} = require( '@woocommerce/e2e-utils' );
const skipOnRetest = require( '../smoke-tests/skip-retest' );

skipOnRetest( testAdminOnboardingWizard, 'Onboarding wizard' );
skipOnRetest( testSelectiveBundleWCPay, 'WC Pay' );
