/* eslint-disable jest/expect-expect */
/*
 * Internal dependencies
 */

// Setup and onboarding tests
const runActivationTest = require( './activate-and-setup/activate.test' );
const { runOnboardingFlowTest, runTaskListTest } = require( './activate-and-setup/onboarding-tasklist.test' );
const runInitialStoreSettingsTest = require( './activate-and-setup/setup.test' );

// Shopper tests
const runProductBrowseSearchSortTest = require( './shopper/front-end-product-browse-search-sort.test' );
const runCartApplyCouponsTest = require( './shopper/front-end-cart-coupons.test');
const runCartPageTest = require( './shopper/front-end-cart.test' );
const runCheckoutApplyCouponsTest = require( './shopper/front-end-checkout-coupons.test');
const runCheckoutPageTest = require( './shopper/front-end-checkout.test' );
const runMyAccountPageTest = require( './shopper/front-end-my-account.test' );
const runMyAccountPayOrderTest = require( './shopper/front-end-my-account-pay-order.test' );
const runMyAccountCreateAccountTest = require( './shopper/front-end-my-account-create-account.test' );
const runSingleProductPageTest = require( './shopper/front-end-single-product.test' );
const runVariableProductUpdateTest = require( './shopper/front-end-variable-product-updates.test' );
const runCheckoutCreateAccountTest = require( './shopper/front-end-checkout-create-account.test' );
const runCheckoutLoginAccountTest = require( './shopper/front-end-checkout-login-account.test' );
const runCartCalculateShippingTest = require( './shopper/front-end-cart-calculate-shipping.test' );
const runCartRedirectionTest = require( './shopper/front-end-cart-redirection.test' );
const runOrderEmailReceivingTest = require( './shopper/front-end-order-email-receiving.test' );

// Merchant tests
const runAddNewShippingZoneTest = require ( './merchant/wp-admin-settings-shipping-zones.test' );
const runAddShippingClassesTest = require('./merchant/wp-admin-settings-shipping-classes.test')
const runCreateCouponTest = require( './merchant/wp-admin-coupon-new.test' );
const runCreateOrderTest = require( './merchant/wp-admin-order-new.test' );
const runEditOrderTest = require( './merchant/wp-admin-order-edit.test' );
const { runAddSimpleProductTest, runAddVariableProductTest } = require( './merchant/wp-admin-product-new.test' );
const runUpdateGeneralSettingsTest = require( './merchant/wp-admin-settings-general.test' );
const runProductSettingsTest = require( './merchant/wp-admin-settings-product.test' );
const runTaxSettingsTest = require( './merchant/wp-admin-settings-tax.test' );
const runOrderStatusFiltersTest = require( './merchant/wp-admin-order-status-filters.test' );
const runOrderRefundTest = require( './merchant/wp-admin-order-refund.test' );
const runOrderApplyCouponTest = require( './merchant/wp-admin-order-apply-coupon.test' );
const runProductEditDetailsTest = require( './merchant/wp-admin-product-edit-details.test' );
const runProductSearchTest = require( './merchant/wp-admin-product-search.test' );
const runMerchantOrdersCustomerPaymentPage = require( './merchant/wp-admin-order-customer-payment-page.test' );
const runMerchantOrderEmailsTest = require( './merchant/wp-admin-order-emails.test' );
const runOrderSearchingTest = require( './merchant/wp-admin-order-searching.test' );
const runAnalyticsPageLoadsTest = require( './merchant/wp-admin-analytics-page-loads.test' );
const runImportProductsTest = require( './merchant/wp-admin-product-import-csv.test' );
const runInitiateWccomConnectionTest = require( './merchant/wp-admin-extensions-connect-wccom.test' );

// REST API tests
const runExternalProductAPITest = require( './api/external-product.test' );
const runCouponApiTest = require( './api/coupon.test' );
const runGroupedProductAPITest = require( './api/grouped-product.test' );
const runVariableProductAPITest = require( './api/variable-product.test' );
const runOrderApiTest = require( './api/order.test' );

const runSetupOnboardingTests = () => {
	runActivationTest();
	runOnboardingFlowTest();
	runTaskListTest();
	runInitialStoreSettingsTest();
};

const runShopperTests = () => {
	runProductBrowseSearchSortTest();
	runCartApplyCouponsTest();
	runCartPageTest();
	runCheckoutApplyCouponsTest();
	runCheckoutPageTest();
	runMyAccountPageTest();
	runMyAccountPayOrderTest();
	runMyAccountCreateAccountTest();
	runSingleProductPageTest();
	runVariableProductUpdateTest();
	runCheckoutCreateAccountTest();
	runCheckoutLoginAccountTest();
	runCartCalculateShippingTest();
	runCartRedirectionTest();
	runOrderEmailReceivingTest();
};

const runMerchantTests = () => {
	runAddShippingClassesTest();
	runImportProductsTest();
	runOrderSearchingTest();
	runAddNewShippingZoneTest();
	runCreateCouponTest();
	runCreateOrderTest();
	runEditOrderTest();
	runAddSimpleProductTest();
	runAddVariableProductTest();
	runUpdateGeneralSettingsTest();
	runProductSettingsTest();
	runTaxSettingsTest();
	runOrderStatusFiltersTest();
	runOrderRefundTest();
	runOrderApplyCouponTest();
	runProductEditDetailsTest();
	runProductSearchTest();
	runMerchantOrdersCustomerPaymentPage();
	runAnalyticsPageLoadsTest();
	runInitiateWccomConnectionTest();
}

const runApiTests = () => {
	runExternalProductAPITest();
	runGroupedProductAPITest();
	runVariableProductAPITest();
	runCouponApiTest();
	runOrderApiTest();
}

module.exports = {
	runActivationTest,
	runOnboardingFlowTest,
	runTaskListTest,
	runInitialStoreSettingsTest,
	runSetupOnboardingTests,
	runExternalProductAPITest,
	runGroupedProductAPITest,
	runVariableProductAPITest,
	runCouponApiTest,
	runCartApplyCouponsTest,
	runCartPageTest,
	runCheckoutApplyCouponsTest,
	runCheckoutPageTest,
	runMyAccountPageTest,
	runMyAccountPayOrderTest,
	runSingleProductPageTest,
	runVariableProductUpdateTest,
	runShopperTests,
	runCreateCouponTest,
	runCreateOrderTest,
	runEditOrderTest,
	runAddSimpleProductTest,
	runAddVariableProductTest,
	runUpdateGeneralSettingsTest,
	runProductSettingsTest,
	runTaxSettingsTest,
	runOrderApiTest,
	runOrderStatusFiltersTest,
	runOrderRefundTest,
	runOrderApplyCouponTest,
	runProductEditDetailsTest,
	runProductSearchTest,
	runMerchantOrdersCustomerPaymentPage,
	runMerchantOrderEmailsTest,
	runMerchantTests,
	runOrderSearchingTest,
	runAddNewShippingZoneTest,
	runProductBrowseSearchSortTest,
	runApiTests,
	runAddShippingClassesTest,
	runAnalyticsPageLoadsTest,
	runCheckoutCreateAccountTest,
	runImportProductsTest,
	runCheckoutLoginAccountTest,
	runCartCalculateShippingTest,
	runCartRedirectionTest,
	runMyAccountCreateAccountTest,
	runOrderEmailReceivingTest,
	runInitiateWccomConnectionTest,
};
