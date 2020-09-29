/* eslint-disable jest/expect-expect */
/*
 * Internal dependencies
 */
const runActivationTest = require( './activate-and-setup/activate.test' );
const { runOnboardingFlowTest, runTaskListTest } = require( './activate-and-setup/onboarding-tasklist.test' );
const runInitialStoreSettingsTest = require( './activate-and-setup/setup.test' );
const runCartPageTest = require( './front-end/front-end-cart.test' );
const runCheckoutPageTest = require( './front-end/front-end-checkout.test' );
const runMyAccountPageTest = require( './front-end/front-end-my-account.test' );
const runSingleProductPageTest = require( './front-end/front-end-single-product.test' );

const runSetupOnboardingTests = () => {
	runActivationTest();
	runOnboardingFlowTest();
	runTaskListTest();
	runInitialStoreSettingsTest();
};

const runFrontEndTests = () => {
	runCartPageTest();
	runCheckoutPageTest();
	runMyAccountPageTest();
	runSingleProductPageTest();
};

module.exports = {
	runActivationTest,
	runOnboardingFlowTest,
	runTaskListTest,
	runInitialStoreSettingsTest,
	runSetupOnboardingTests,
	runCartPageTest,
	runCheckoutPageTest,
	runMyAccountPageTest,
	runSingleProductPageTest,
	runFrontEndTests,
};
