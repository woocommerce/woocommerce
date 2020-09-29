/* eslint-disable jest/expect-expect */
/*
 * Internal dependencies
 */
const runActivationTest = require( './activate-and-setup/activate.test.js' );
const { runOnboardingFlowTest, runTaskListTest } = require( './activate-and-setup/onboarding-tasklist.test.js' );
const runInitialStoreSettingsTest = require( './activate-and-setup/setup.test.js' );

const runSetupOnboardingTests = () => {
	runActivationTest();
	runOnboardingFlowTest();
	runTaskListTest();
	runInitialStoreSettingsTest();
};

module.exports = {
	runActivationTest,
	runOnboardingFlowTest,
	runTaskListTest,
	runInitialStoreSettingsTest,
	runSetupOnboardingTests,
};
