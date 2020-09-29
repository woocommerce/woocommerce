/* eslint-disable jest/expect-expect */
/*
 * Internal dependencies
 */
const {
	runActivationTest,
	runOnboardingFlowTest,
	runTaskListTest,
	runInitialStoreSettingsTest,
} = require( './activate-and-setup/setup-wizard.test.js' );

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
