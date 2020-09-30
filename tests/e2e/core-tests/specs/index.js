/* eslint-disable jest/expect-expect */
/*
 * Internal dependencies
 */
const runActivationTest = require( './activate-and-setup/activate.test' );
const { runOnboardingFlowTest, runTaskListTest } = require( './activate-and-setup/onboarding-tasklist.test' );
const runInitialStoreSettingsTest = require( './activate-and-setup/setup.test' );
const runCartPageTest = require( './shopper/front-end-cart.test' );
const runCheckoutPageTest = require( './shopper/front-end-checkout.test' );
const runMyAccountPageTest = require( './shopper/front-end-my-account.test' );
const runSingleProductPageTest = require( './shopper/front-end-single-product.test' );
const runCreateCouponTest = require( './merchant/wp-admin-coupon-new.test' );
const runCreateOrderTest = require( './merchant/wp-admin-order-new.test' );
const { runAddSimpleProductTest, runAddVariableProductTest } = require( './merchant/wp-admin-product-new.test' );
const runUpdateGeneralSettingsTest = require( './merchant/wp-admin-settings-general.test' );
const runProductSettingsTest = require( './merchant/wp-admin-settings-product.test' );
const runTaxSettingsTest = require( './merchant/wp-admin-settings-tax.test' );

const runSetupOnboardingTests = () => {
	runActivationTest();
	runOnboardingFlowTest();
	runTaskListTest();
	runInitialStoreSettingsTest();
};

const runShopperTests = () => {
	runCartPageTest();
	runCheckoutPageTest();
	runMyAccountPageTest();
	runSingleProductPageTest();
};

const runMerchantTests = () => {
	runCreateCouponTest();
	runCreateOrderTest();
	runAddSimpleProductTest();
	runAddVariableProductTest();
	runUpdateGeneralSettingsTest();
	runProductSettingsTest();
	runTaxSettingsTest();
}

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
	runShopperTests,
	runCreateCouponTest,
	runCreateOrderTest,
	runAddSimpleProductTest,
	runAddVariableProductTest,
	runUpdateGeneralSettingsTest,
	runProductSettingsTest,
	runTaxSettingsTest,
	runMerchantTests,
};
