const {
	testAdminOnboardingWizard,
	testSelectiveBundleWCPay,
	testDifferentStoreCurrenciesWCPay,
	testSubscriptionsInclusion,
	testBusinessDetailsForm,
	testAdminHomescreen,
} = require( '@woocommerce/admin-e2e-tests' );
const { withRestApi, IS_RETEST_MODE } = require( '@woocommerce/e2e-utils' );

// Reset onboarding profile when re-running tests on a site
if ( IS_RETEST_MODE ) {
	withRestApi.resetOnboarding();
}

testAdminOnboardingWizard();
testSelectiveBundleWCPay();
testDifferentStoreCurrenciesWCPay();
testSubscriptionsInclusion();
testBusinessDetailsForm();
testAdminHomescreen();
