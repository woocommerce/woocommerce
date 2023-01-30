/*
 * Internal dependencies
 */
const {
	runActivationTest,
	runInitialStoreSettingsTest,
	runSetupOnboardingTests,
} = require( '@woocommerce/e2e-core-tests' );

runActivationTest();
runInitialStoreSettingsTest();
runSetupOnboardingTests();
