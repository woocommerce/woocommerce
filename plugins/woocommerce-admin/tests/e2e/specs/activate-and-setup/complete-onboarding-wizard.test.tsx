const {
	testAdminOnboardingWizard,
	testSelectiveBundleWCPay,
	testDifferentStoreCurrenciesWCPay,
	testSubscriptionsInclusion,
	testBusinessDetailsForm,
} = require( '@woocommerce/admin-e2e-tests' );

testAdminOnboardingWizard();
testSelectiveBundleWCPay();
testDifferentStoreCurrenciesWCPay();
testSubscriptionsInclusion();
testBusinessDetailsForm();
