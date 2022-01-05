const {
	testAdminOnboardingWizard,
	testSelectiveBundleWCPay,
} = require( '@woocommerce/admin-e2e-tests' );
const {
	withRestApi,
	IS_RETEST_MODE,
} = require( '@woocommerce/e2e-utils' );

/**
 * Work around implementation for https://github.com/woocommerce/woocommerce-admin/issues/8060
 * For the smoke testing sites which are running PHP 8.0
 */
if ( IS_RETEST_MODE ) {
	describe.skip( 'Store owner can complete onboarding wizard', () => {
		// Dummy test to satisfy parsing.
		it( 'can start the profile wizard', async () => {
			const testValue = true;
			expect( testValue ).toBeTruthy();
		} );
	} );
} else {

// End of Work around

// Reset onboarding profile when re-running tests on a site
	if ( IS_RETEST_MODE ) {
		withRestApi.resetOnboarding();
	}

	testAdminOnboardingWizard();
	testSelectiveBundleWCPay();
}
