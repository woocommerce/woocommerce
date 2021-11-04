const {
	testAdminOnboardingWizard,
	testSelectiveBundleWCPay,
	testDifferentStoreCurrenciesWCPay,
	testSubscriptionsInclusion,
} = require( '@woocommerce/admin-e2e-tests' );

testAdminOnboardingWizard();
testSelectiveBundleWCPay();
testDifferentStoreCurrenciesWCPay();
testSubscriptionsInclusion();
