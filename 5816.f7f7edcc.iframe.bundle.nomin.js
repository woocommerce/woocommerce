"use strict";
(self["webpackChunk_woocommerce_storybook"] = self["webpackChunk_woocommerce_storybook"] || []).push([[5816],{

/***/ "../../packages/js/data/src/index.ts":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {


// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  Jd: () => (/* reexport */ useUser),
  HW: () => (/* reexport */ useUserPreferences)
});

// UNUSED EXPORTS: COUNTRIES_STORE_NAME, DAY, EXPERIMENTAL_PRODUCT_ATTRIBUTES_STORE_NAME, EXPERIMENTAL_PRODUCT_ATTRIBUTE_TERMS_STORE_NAME, EXPERIMENTAL_PRODUCT_CATEGORIES_STORE_NAME, EXPERIMENTAL_PRODUCT_FORM_STORE_NAME, EXPERIMENTAL_PRODUCT_SHIPPING_CLASSES_STORE_NAME, EXPERIMENTAL_PRODUCT_TAGS_STORE_NAME, EXPERIMENTAL_PRODUCT_VARIATIONS_STORE_NAME, EXPERIMENTAL_SHIPPING_ZONES_STORE_NAME, EXPERIMENTAL_TAX_CLASSES_STORE_NAME, EXPORT_STORE_NAME, HOUR, IMPORT_STORE_NAME, ITEMS_STORE_NAME, MAX_PER_PAGE, MINUTE, MONTH, NAMESPACE, NAVIGATION_STORE_NAME, NOTES_STORE_NAME, ONBOARDING_STORE_NAME, OPTIONS_STORE_NAME, ORDERS_STORE_NAME, PAYMENT_GATEWAYS_STORE_NAME, PLUGINS_STORE_NAME, PRODUCTS_STORE_NAME, PaymentGateway, PluginActions, PluginSelectors, PluginsStoreActions, ProductAttribute, ProductAttributeSelectors, ProductAttributeTerm, ProductAttributeTermsActions, ProductAttributeTermsSelectors, ProductAttributesActions, ProductCategory, ProductCategoryActions, ProductCategoryImage, ProductCategorySelectors, ProductForm, ProductFormField, ProductFormSection, ProductShippingClassesActions, ProductTag, ProductTagsActions, ProductVariationsActions, ProductsStoreActions, QUERY_DEFAULTS, Query, QueryProductAttribute, REPORTS_STORE_NAME, REVIEWS_STORE_NAME, SECOND, SETTINGS_STORE_NAME, SHIPPING_METHODS_STORE_NAME, ShippingMethod, ShippingZonesActions, TaxClass, TaxClassActions, USER_STORE_NAME, UserPreferences, WCS_NAMESPACE, WCUser, WC_ADMIN_NAMESPACE, WEEK, getFilterQuery, getLeaderboard, getReportChartData, getReportTableData, getReportTableQuery, getSummaryNumbers, getTooltipValueFormat, getVisibleTasks, isRestApiError, pluginNames, productReadOnlyProperties, searchItemsByString, useOptionsHydration, useSettings, withCurrentUserHydration, withNavigationHydration, withOnboardingHydration, withOptionsHydration, withPluginsHydration, withSettingsHydration

// NAMESPACE OBJECT: ../../packages/js/data/src/settings/selectors.ts
var selectors_namespaceObject = {};
__webpack_require__.r(selectors_namespaceObject);
__webpack_require__.d(selectors_namespaceObject, {
  getDirtyKeys: () => (getDirtyKeys),
  getIsDirty: () => (getIsDirty),
  getLastSettingsErrorForGroup: () => (getLastSettingsErrorForGroup),
  getSetting: () => (getSetting),
  getSettings: () => (getSettings),
  getSettingsError: () => (getSettingsError),
  getSettingsForGroup: () => (getSettingsForGroup),
  getSettingsGroupNames: () => (getSettingsGroupNames),
  isUpdateSettingsRequesting: () => (isUpdateSettingsRequesting)
});

// NAMESPACE OBJECT: ../../packages/js/data/src/settings/actions.ts
var actions_namespaceObject = {};
__webpack_require__.r(actions_namespaceObject);
__webpack_require__.d(actions_namespaceObject, {
  clearIsDirty: () => (clearIsDirty),
  clearSettings: () => (clearSettings),
  persistSettingsForGroup: () => (persistSettingsForGroup),
  setIsRequesting: () => (setIsRequesting),
  updateAndPersistSettingsForGroup: () => (updateAndPersistSettingsForGroup),
  updateErrorForGroup: () => (updateErrorForGroup),
  updateSettingsForGroup: () => (updateSettingsForGroup)
});

// NAMESPACE OBJECT: ../../packages/js/data/src/settings/resolvers.ts
var resolvers_namespaceObject = {};
__webpack_require__.r(resolvers_namespaceObject);
__webpack_require__.d(resolvers_namespaceObject, {
  getSettings: () => (resolvers_getSettings),
  getSettingsForGroup: () => (resolvers_getSettingsForGroup)
});

// NAMESPACE OBJECT: ../../packages/js/data/src/plugins/selectors.ts
var plugins_selectors_namespaceObject = {};
__webpack_require__.r(plugins_selectors_namespaceObject);
__webpack_require__.d(plugins_selectors_namespaceObject, {
  getActivePlugins: () => (getActivePlugins),
  getInstalledPlugins: () => (getInstalledPlugins),
  getJetpackConnectUrl: () => (getJetpackConnectUrl),
  getJetpackConnectionData: () => (getJetpackConnectionData),
  getPaypalOnboardingStatus: () => (getPaypalOnboardingStatus),
  getPluginInstallState: () => (getPluginInstallState),
  getPluginsError: () => (getPluginsError),
  getRecommendedPlugins: () => (getRecommendedPlugins),
  isJetpackConnected: () => (isJetpackConnected),
  isPluginsRequesting: () => (isPluginsRequesting)
});

// NAMESPACE OBJECT: ../../packages/js/data/src/plugins/actions.ts
var plugins_actions_namespaceObject = {};
__webpack_require__.r(plugins_actions_namespaceObject);
__webpack_require__.d(plugins_actions_namespaceObject, {
  activatePlugins: () => (activatePlugins),
  connectToJetpack: () => (connectToJetpack),
  connectToJetpackWithFailureRedirect: () => (connectToJetpackWithFailureRedirect),
  createErrorNotice: () => (createErrorNotice),
  dismissRecommendedPlugins: () => (dismissRecommendedPlugins),
  installAndActivatePlugins: () => (installAndActivatePlugins),
  installJetpackAndConnect: () => (installJetpackAndConnect),
  installPlugins: () => (installPlugins),
  setError: () => (setError),
  setIsRequesting: () => (actions_setIsRequesting),
  setPaypalOnboardingStatus: () => (setPaypalOnboardingStatus),
  setRecommendedPlugins: () => (setRecommendedPlugins),
  updateActivePlugins: () => (updateActivePlugins),
  updateInstalledPlugins: () => (updateInstalledPlugins),
  updateIsJetpackConnected: () => (updateIsJetpackConnected),
  updateJetpackConnectUrl: () => (updateJetpackConnectUrl),
  updateJetpackConnectionData: () => (updateJetpackConnectionData)
});

// NAMESPACE OBJECT: ../../packages/js/data/src/options/selectors.ts
var options_selectors_namespaceObject = {};
__webpack_require__.r(options_selectors_namespaceObject);
__webpack_require__.d(options_selectors_namespaceObject, {
  getOption: () => (getOption),
  getOptionsRequestingError: () => (getOptionsRequestingError),
  getOptionsUpdatingError: () => (getOptionsUpdatingError),
  isOptionsUpdating: () => (isOptionsUpdating)
});

// NAMESPACE OBJECT: ../../packages/js/data/src/options/actions.ts
var options_actions_namespaceObject = {};
__webpack_require__.r(options_actions_namespaceObject);
__webpack_require__.d(options_actions_namespaceObject, {
  receiveOptions: () => (receiveOptions),
  setIsUpdating: () => (setIsUpdating),
  setRequestingError: () => (setRequestingError),
  setUpdatingError: () => (setUpdatingError),
  updateOptions: () => (updateOptions)
});

// NAMESPACE OBJECT: ../../packages/js/data/src/options/resolvers.ts
var options_resolvers_namespaceObject = {};
__webpack_require__.r(options_resolvers_namespaceObject);
__webpack_require__.d(options_resolvers_namespaceObject, {
  getOption: () => (resolvers_getOption)
});

// NAMESPACE OBJECT: ../../packages/js/data/src/plugins/resolvers.ts
var plugins_resolvers_namespaceObject = {};
__webpack_require__.r(plugins_resolvers_namespaceObject);
__webpack_require__.d(plugins_resolvers_namespaceObject, {
  getActivePlugins: () => (resolvers_getActivePlugins),
  getInstalledPlugins: () => (resolvers_getInstalledPlugins),
  getJetpackConnectUrl: () => (resolvers_getJetpackConnectUrl),
  getJetpackConnectionData: () => (resolvers_getJetpackConnectionData),
  getPaypalOnboardingStatus: () => (resolvers_getPaypalOnboardingStatus),
  getRecommendedPlugins: () => (resolvers_getRecommendedPlugins),
  isJetpackConnected: () => (resolvers_isJetpackConnected)
});

// NAMESPACE OBJECT: ../../packages/js/data/src/onboarding/selectors.ts
var onboarding_selectors_namespaceObject = {};
__webpack_require__.r(onboarding_selectors_namespaceObject);
__webpack_require__.d(onboarding_selectors_namespaceObject, {
  getEmailPrefill: () => (getEmailPrefill),
  getFreeExtensions: () => (getFreeExtensions),
  getJetpackAuthUrl: () => (getJetpackAuthUrl),
  getOnboardingError: () => (getOnboardingError),
  getPaymentGatewaySuggestions: () => (getPaymentGatewaySuggestions),
  getProductTypes: () => (getProductTypes),
  getProfileItems: () => (getProfileItems),
  getTask: () => (getTask),
  getTaskList: () => (getTaskList),
  getTaskLists: () => (getTaskLists),
  getTaskListsByIds: () => (getTaskListsByIds),
  isOnboardingRequesting: () => (isOnboardingRequesting)
});

// NAMESPACE OBJECT: ../../packages/js/data/src/onboarding/actions.ts
var onboarding_actions_namespaceObject = {};
__webpack_require__.r(onboarding_actions_namespaceObject);
__webpack_require__.d(onboarding_actions_namespaceObject, {
  actionTask: () => (actionTask),
  actionTaskError: () => (actionTaskError),
  actionTaskRequest: () => (actionTaskRequest),
  actionTaskSuccess: () => (actionTaskSuccess),
  coreProfilerCompleted: () => (coreProfilerCompleted),
  coreProfilerCompletedError: () => (coreProfilerCompletedError),
  coreProfilerCompletedRequest: () => (coreProfilerCompletedRequest),
  coreProfilerCompletedSuccess: () => (coreProfilerCompletedSuccess),
  dismissTask: () => (dismissTask),
  dismissTaskError: () => (dismissTaskError),
  dismissTaskRequest: () => (dismissTaskRequest),
  dismissTaskSuccess: () => (dismissTaskSuccess),
  getFreeExtensionsError: () => (getFreeExtensionsError),
  getFreeExtensionsSuccess: () => (getFreeExtensionsSuccess),
  getProductTypesError: () => (getProductTypesError),
  getProductTypesSuccess: () => (getProductTypesSuccess),
  getTaskListsError: () => (getTaskListsError),
  getTaskListsSuccess: () => (getTaskListsSuccess),
  hideTaskList: () => (hideTaskList),
  hideTaskListError: () => (hideTaskListError),
  hideTaskListRequest: () => (hideTaskListRequest),
  hideTaskListSuccess: () => (hideTaskListSuccess),
  installAndActivatePluginsAsync: () => (installAndActivatePluginsAsync),
  keepCompletedTaskList: () => (keepCompletedTaskList),
  keepCompletedTaskListSuccess: () => (keepCompletedTaskListSuccess),
  optimisticallyCompleteTask: () => (optimisticallyCompleteTask),
  optimisticallyCompleteTaskRequest: () => (optimisticallyCompleteTaskRequest),
  setEmailPrefill: () => (setEmailPrefill),
  setError: () => (actions_setError),
  setIsRequesting: () => (onboarding_actions_setIsRequesting),
  setJetpackAuthUrl: () => (setJetpackAuthUrl),
  setPaymentMethods: () => (setPaymentMethods),
  setProfileItems: () => (setProfileItems),
  snoozeTask: () => (snoozeTask),
  snoozeTaskError: () => (snoozeTaskError),
  snoozeTaskRequest: () => (snoozeTaskRequest),
  snoozeTaskSuccess: () => (snoozeTaskSuccess),
  undoDismissTask: () => (undoDismissTask),
  undoDismissTaskError: () => (undoDismissTaskError),
  undoDismissTaskRequest: () => (undoDismissTaskRequest),
  undoDismissTaskSuccess: () => (undoDismissTaskSuccess),
  undoSnoozeTask: () => (undoSnoozeTask),
  undoSnoozeTaskError: () => (undoSnoozeTaskError),
  undoSnoozeTaskRequest: () => (undoSnoozeTaskRequest),
  undoSnoozeTaskSuccess: () => (undoSnoozeTaskSuccess),
  unhideTaskList: () => (unhideTaskList),
  unhideTaskListError: () => (unhideTaskListError),
  unhideTaskListRequest: () => (unhideTaskListRequest),
  unhideTaskListSuccess: () => (unhideTaskListSuccess),
  updateProfileItems: () => (updateProfileItems),
  visitedTask: () => (visitedTask)
});

// NAMESPACE OBJECT: ../../packages/js/data/src/onboarding/resolvers.ts
var onboarding_resolvers_namespaceObject = {};
__webpack_require__.r(onboarding_resolvers_namespaceObject);
__webpack_require__.d(onboarding_resolvers_namespaceObject, {
  getEmailPrefill: () => (resolvers_getEmailPrefill),
  getFreeExtensions: () => (resolvers_getFreeExtensions),
  getJetpackAuthUrl: () => (resolvers_getJetpackAuthUrl),
  getPaymentGatewaySuggestions: () => (resolvers_getPaymentGatewaySuggestions),
  getProductTypes: () => (resolvers_getProductTypes),
  getProfileItems: () => (resolvers_getProfileItems),
  getTask: () => (resolvers_getTask),
  getTaskList: () => (resolvers_getTaskList),
  getTaskLists: () => (resolvers_getTaskLists),
  getTaskListsByIds: () => (resolvers_getTaskListsByIds)
});

// NAMESPACE OBJECT: ../../packages/js/data/src/reviews/selectors.ts
var reviews_selectors_namespaceObject = {};
__webpack_require__.r(reviews_selectors_namespaceObject);
__webpack_require__.d(reviews_selectors_namespaceObject, {
  getReviews: () => (getReviews),
  getReviewsError: () => (getReviewsError),
  getReviewsTotalCount: () => (getReviewsTotalCount)
});

// NAMESPACE OBJECT: ../../packages/js/data/src/reviews/actions.ts
var reviews_actions_namespaceObject = {};
__webpack_require__.r(reviews_actions_namespaceObject);
__webpack_require__.d(reviews_actions_namespaceObject, {
  deleteReview: () => (deleteReview),
  setError: () => (reviews_actions_setError),
  setReview: () => (setReview),
  setReviewIsUpdating: () => (setReviewIsUpdating),
  updateReview: () => (updateReview),
  updateReviews: () => (updateReviews)
});

// NAMESPACE OBJECT: ../../packages/js/data/src/reviews/resolvers.ts
var reviews_resolvers_namespaceObject = {};
__webpack_require__.r(reviews_resolvers_namespaceObject);
__webpack_require__.d(reviews_resolvers_namespaceObject, {
  getReviews: () => (resolvers_getReviews),
  getReviewsTotalCount: () => (resolvers_getReviewsTotalCount)
});

// NAMESPACE OBJECT: ../../packages/js/data/src/notes/selectors.ts
var notes_selectors_namespaceObject = {};
__webpack_require__.r(notes_selectors_namespaceObject);
__webpack_require__.d(notes_selectors_namespaceObject, {
  getNotes: () => (getNotes),
  getNotesError: () => (getNotesError),
  isNotesRequesting: () => (isNotesRequesting)
});

// NAMESPACE OBJECT: ../../packages/js/data/src/notes/actions.ts
var notes_actions_namespaceObject = {};
__webpack_require__.r(notes_actions_namespaceObject);
__webpack_require__.d(notes_actions_namespaceObject, {
  batchUpdateNotes: () => (batchUpdateNotes),
  removeAllNotes: () => (removeAllNotes),
  removeNote: () => (removeNote),
  setError: () => (notes_actions_setError),
  setIsRequesting: () => (notes_actions_setIsRequesting),
  setNote: () => (setNote),
  setNoteIsUpdating: () => (setNoteIsUpdating),
  setNotes: () => (setNotes),
  setNotesQuery: () => (setNotesQuery),
  triggerNoteAction: () => (triggerNoteAction),
  updateNote: () => (updateNote)
});

// NAMESPACE OBJECT: ../../packages/js/data/src/notes/resolvers.ts
var notes_resolvers_namespaceObject = {};
__webpack_require__.r(notes_resolvers_namespaceObject);
__webpack_require__.d(notes_resolvers_namespaceObject, {
  getNotes: () => (resolvers_getNotes)
});

// NAMESPACE OBJECT: ../../packages/js/data/src/reports/selectors.ts
var reports_selectors_namespaceObject = {};
__webpack_require__.r(reports_selectors_namespaceObject);
__webpack_require__.d(reports_selectors_namespaceObject, {
  getReportItems: () => (getReportItems),
  getReportItemsError: () => (getReportItemsError),
  getReportStats: () => (getReportStats),
  getReportStatsError: () => (getReportStatsError)
});

// NAMESPACE OBJECT: ../../packages/js/data/src/reports/actions.ts
var reports_actions_namespaceObject = {};
__webpack_require__.r(reports_actions_namespaceObject);
__webpack_require__.d(reports_actions_namespaceObject, {
  setReportItems: () => (setReportItems),
  setReportItemsError: () => (setReportItemsError),
  setReportStats: () => (setReportStats),
  setReportStatsError: () => (setReportStatsError)
});

// NAMESPACE OBJECT: ../../packages/js/data/src/reports/resolvers.ts
var reports_resolvers_namespaceObject = {};
__webpack_require__.r(reports_resolvers_namespaceObject);
__webpack_require__.d(reports_resolvers_namespaceObject, {
  getReportItems: () => (resolvers_getReportItems),
  getReportStats: () => (resolvers_getReportStats)
});

// NAMESPACE OBJECT: ../../packages/js/data/src/countries/selectors.ts
var countries_selectors_namespaceObject = {};
__webpack_require__.r(countries_selectors_namespaceObject);
__webpack_require__.d(countries_selectors_namespaceObject, {
  geolocate: () => (geolocate),
  getCountries: () => (getCountries),
  getCountry: () => (getCountry),
  getLocale: () => (getLocale),
  getLocales: () => (getLocales)
});

// NAMESPACE OBJECT: ../../packages/js/data/src/countries/actions.ts
var countries_actions_namespaceObject = {};
__webpack_require__.r(countries_actions_namespaceObject);
__webpack_require__.d(countries_actions_namespaceObject, {
  geolocationError: () => (geolocationError),
  geolocationSuccess: () => (geolocationSuccess),
  getCountriesError: () => (getCountriesError),
  getCountriesSuccess: () => (getCountriesSuccess),
  getLocalesError: () => (getLocalesError),
  getLocalesSuccess: () => (getLocalesSuccess)
});

// NAMESPACE OBJECT: ../../packages/js/data/src/countries/resolvers.ts
var countries_resolvers_namespaceObject = {};
__webpack_require__.r(countries_resolvers_namespaceObject);
__webpack_require__.d(countries_resolvers_namespaceObject, {
  geolocate: () => (resolvers_geolocate),
  getCountries: () => (resolvers_getCountries),
  getCountry: () => (resolvers_getCountry),
  getLocale: () => (resolvers_getLocale),
  getLocales: () => (resolvers_getLocales)
});

// NAMESPACE OBJECT: ../../packages/js/data/src/navigation/selectors.ts
var navigation_selectors_namespaceObject = {};
__webpack_require__.r(navigation_selectors_namespaceObject);
__webpack_require__.d(navigation_selectors_namespaceObject, {
  getFavorites: () => (getFavorites),
  getMenuItems: () => (getMenuItems),
  getPersistedQuery: () => (getPersistedQuery),
  isNavigationRequesting: () => (isNavigationRequesting)
});

// NAMESPACE OBJECT: ../../packages/js/data/src/navigation/actions.ts
var navigation_actions_namespaceObject = {};
__webpack_require__.r(navigation_actions_namespaceObject);
__webpack_require__.d(navigation_actions_namespaceObject, {
  addFavorite: () => (addFavorite),
  addFavoriteFailure: () => (addFavoriteFailure),
  addFavoriteRequest: () => (addFavoriteRequest),
  addFavoriteSuccess: () => (addFavoriteSuccess),
  addMenuItems: () => (addMenuItems),
  getFavoritesFailure: () => (getFavoritesFailure),
  getFavoritesRequest: () => (getFavoritesRequest),
  getFavoritesSuccess: () => (getFavoritesSuccess),
  onHistoryChange: () => (onHistoryChange),
  onLoad: () => (onLoad),
  removeFavorite: () => (removeFavorite),
  removeFavoriteFailure: () => (removeFavoriteFailure),
  removeFavoriteRequest: () => (removeFavoriteRequest),
  removeFavoriteSuccess: () => (removeFavoriteSuccess),
  setMenuItems: () => (setMenuItems)
});

// NAMESPACE OBJECT: ../../packages/js/data/src/navigation/resolvers.ts
var navigation_resolvers_namespaceObject = {};
__webpack_require__.r(navigation_resolvers_namespaceObject);
__webpack_require__.d(navigation_resolvers_namespaceObject, {
  getFavorites: () => (resolvers_getFavorites)
});

// NAMESPACE OBJECT: ../../packages/js/data/src/items/selectors.ts
var items_selectors_namespaceObject = {};
__webpack_require__.r(items_selectors_namespaceObject);
__webpack_require__.d(items_selectors_namespaceObject, {
  getItems: () => (getItems),
  getItemsError: () => (getItemsError),
  getItemsTotalCount: () => (getItemsTotalCount)
});

// NAMESPACE OBJECT: ../../packages/js/data/src/items/actions.ts
var items_actions_namespaceObject = {};
__webpack_require__.r(items_actions_namespaceObject);
__webpack_require__.d(items_actions_namespaceObject, {
  createProductFromTemplate: () => (createProductFromTemplate),
  setError: () => (items_actions_setError),
  setItem: () => (setItem),
  setItems: () => (setItems),
  setItemsTotalCount: () => (setItemsTotalCount),
  updateProductStock: () => (updateProductStock)
});

// NAMESPACE OBJECT: ../../packages/js/data/src/items/resolvers.ts
var items_resolvers_namespaceObject = {};
__webpack_require__.r(items_resolvers_namespaceObject);
__webpack_require__.d(items_resolvers_namespaceObject, {
  getItems: () => (resolvers_getItems),
  getItemsTotalCount: () => (resolvers_getItemsTotalCount),
  getReviewsTotalCount: () => (items_resolvers_getReviewsTotalCount)
});

// NAMESPACE OBJECT: ../../packages/js/data/src/payment-gateways/actions.ts
var payment_gateways_actions_namespaceObject = {};
__webpack_require__.r(payment_gateways_actions_namespaceObject);
__webpack_require__.d(payment_gateways_actions_namespaceObject, {
  getPaymentGatewayError: () => (getPaymentGatewayError),
  getPaymentGatewayRequest: () => (getPaymentGatewayRequest),
  getPaymentGatewaySuccess: () => (getPaymentGatewaySuccess),
  getPaymentGatewaysError: () => (getPaymentGatewaysError),
  getPaymentGatewaysRequest: () => (getPaymentGatewaysRequest),
  getPaymentGatewaysSuccess: () => (getPaymentGatewaysSuccess),
  updatePaymentGateway: () => (updatePaymentGateway),
  updatePaymentGatewayError: () => (updatePaymentGatewayError),
  updatePaymentGatewayRequest: () => (updatePaymentGatewayRequest),
  updatePaymentGatewaySuccess: () => (updatePaymentGatewaySuccess)
});

// NAMESPACE OBJECT: ../../packages/js/data/src/payment-gateways/resolvers.ts
var payment_gateways_resolvers_namespaceObject = {};
__webpack_require__.r(payment_gateways_resolvers_namespaceObject);
__webpack_require__.d(payment_gateways_resolvers_namespaceObject, {
  getPaymentGateway: () => (getPaymentGateway),
  getPaymentGateways: () => (getPaymentGateways)
});

// NAMESPACE OBJECT: ../../packages/js/data/src/payment-gateways/selectors.ts
var payment_gateways_selectors_namespaceObject = {};
__webpack_require__.r(payment_gateways_selectors_namespaceObject);
__webpack_require__.d(payment_gateways_selectors_namespaceObject, {
  getPaymentGateway: () => (selectors_getPaymentGateway),
  getPaymentGatewayError: () => (selectors_getPaymentGatewayError),
  getPaymentGateways: () => (selectors_getPaymentGateways),
  isPaymentGatewayUpdating: () => (isPaymentGatewayUpdating)
});

// NAMESPACE OBJECT: ../../packages/js/data/src/shipping-methods/actions.ts
var shipping_methods_actions_namespaceObject = {};
__webpack_require__.r(shipping_methods_actions_namespaceObject);
__webpack_require__.d(shipping_methods_actions_namespaceObject, {
  getShippingMethodsError: () => (getShippingMethodsError),
  getShippingMethodsRequest: () => (getShippingMethodsRequest),
  getShippingMethodsSuccess: () => (getShippingMethodsSuccess)
});

// NAMESPACE OBJECT: ../../packages/js/data/src/shipping-methods/resolvers.ts
var shipping_methods_resolvers_namespaceObject = {};
__webpack_require__.r(shipping_methods_resolvers_namespaceObject);
__webpack_require__.d(shipping_methods_resolvers_namespaceObject, {
  getShippingMethods: () => (getShippingMethods)
});

// NAMESPACE OBJECT: ../../packages/js/data/src/shipping-methods/selectors.ts
var shipping_methods_selectors_namespaceObject = {};
__webpack_require__.r(shipping_methods_selectors_namespaceObject);
__webpack_require__.d(shipping_methods_selectors_namespaceObject, {
  getShippingMethods: () => (selectors_getShippingMethods),
  isShippingMethodsUpdating: () => (isShippingMethodsUpdating)
});

// NAMESPACE OBJECT: ../../packages/js/data/src/products/selectors.ts
var products_selectors_namespaceObject = {};
__webpack_require__.r(products_selectors_namespaceObject);
__webpack_require__.d(products_selectors_namespaceObject, {
  getCreateProductError: () => (getCreateProductError),
  getDeleteProductError: () => (getDeleteProductError),
  getPermalinkParts: () => (getPermalinkParts),
  getProduct: () => (getProduct),
  getProducts: () => (getProducts),
  getProductsError: () => (getProductsError),
  getProductsTotalCount: () => (getProductsTotalCount),
  getRelatedProducts: () => (getRelatedProducts),
  getSuggestedProducts: () => (getSuggestedProducts),
  getUpdateProductError: () => (getUpdateProductError),
  isPending: () => (isPending)
});

// NAMESPACE OBJECT: ../../packages/js/data/src/products/actions.ts
var products_actions_namespaceObject = {};
__webpack_require__.r(products_actions_namespaceObject);
__webpack_require__.d(products_actions_namespaceObject, {
  createProduct: () => (createProduct),
  createProductError: () => (createProductError),
  deleteProduct: () => (deleteProduct),
  deleteProductError: () => (deleteProductError),
  deleteProductStart: () => (deleteProductStart),
  deleteProductSuccess: () => (deleteProductSuccess),
  duplicateProduct: () => (duplicateProduct),
  duplicateProductError: () => (duplicateProductError),
  getProductError: () => (getProductError),
  getProductSuccess: () => (getProductSuccess),
  getProductsError: () => (actions_getProductsError),
  getProductsSuccess: () => (getProductsSuccess),
  getProductsTotalCountError: () => (getProductsTotalCountError),
  getProductsTotalCountSuccess: () => (getProductsTotalCountSuccess),
  setSuggestedProductAction: () => (setSuggestedProductAction),
  updateProduct: () => (updateProduct),
  updateProductError: () => (updateProductError)
});

// NAMESPACE OBJECT: ../../packages/js/data/src/products/resolvers.ts
var products_resolvers_namespaceObject = {};
__webpack_require__.r(products_resolvers_namespaceObject);
__webpack_require__.d(products_resolvers_namespaceObject, {
  getPermalinkParts: () => (resolvers_getPermalinkParts),
  getProduct: () => (resolvers_getProduct),
  getProducts: () => (resolvers_getProducts),
  getProductsTotalCount: () => (resolvers_getProductsTotalCount),
  getRelatedProducts: () => (resolvers_getRelatedProducts),
  getSuggestedProducts: () => (resolvers_getSuggestedProducts)
});

// NAMESPACE OBJECT: ../../packages/js/data/src/orders/selectors.ts
var orders_selectors_namespaceObject = {};
__webpack_require__.r(orders_selectors_namespaceObject);
__webpack_require__.d(orders_selectors_namespaceObject, {
  getOrders: () => (getOrders),
  getOrdersError: () => (getOrdersError),
  getOrdersTotalCount: () => (getOrdersTotalCount)
});

// NAMESPACE OBJECT: ../../packages/js/data/src/orders/actions.ts
var orders_actions_namespaceObject = {};
__webpack_require__.r(orders_actions_namespaceObject);
__webpack_require__.d(orders_actions_namespaceObject, {
  getOrderError: () => (getOrderError),
  getOrderSuccess: () => (getOrderSuccess),
  getOrdersError: () => (actions_getOrdersError),
  getOrdersSuccess: () => (getOrdersSuccess),
  getOrdersTotalCountError: () => (getOrdersTotalCountError),
  getOrdersTotalCountSuccess: () => (getOrdersTotalCountSuccess)
});

// NAMESPACE OBJECT: ../../packages/js/data/src/orders/resolvers.ts
var orders_resolvers_namespaceObject = {};
__webpack_require__.r(orders_resolvers_namespaceObject);
__webpack_require__.d(orders_resolvers_namespaceObject, {
  getOrders: () => (resolvers_getOrders),
  getOrdersTotalCount: () => (resolvers_getOrdersTotalCount)
});

// NAMESPACE OBJECT: ../../packages/js/data/src/product-variations/actions.ts
var product_variations_actions_namespaceObject = {};
__webpack_require__.r(product_variations_actions_namespaceObject);
__webpack_require__.d(product_variations_actions_namespaceObject, {
  batchUpdateProductVariations: () => (batchUpdateProductVariations),
  batchUpdateProductVariationsError: () => (batchUpdateProductVariationsError),
  generateProductVariations: () => (generateProductVariations),
  generateProductVariationsError: () => (generateProductVariationsError),
  generateProductVariationsRequest: () => (generateProductVariationsRequest),
  generateProductVariationsSuccess: () => (generateProductVariationsSuccess)
});

// NAMESPACE OBJECT: ../../packages/js/data/src/product-variations/selectors.ts
var product_variations_selectors_namespaceObject = {};
__webpack_require__.r(product_variations_selectors_namespaceObject);
__webpack_require__.d(product_variations_selectors_namespaceObject, {
  generateProductVariationsError: () => (selectors_generateProductVariationsError),
  isGeneratingVariations: () => (isGeneratingVariations)
});

// NAMESPACE OBJECT: ../../packages/js/data/src/product-form/selectors.ts
var product_form_selectors_namespaceObject = {};
__webpack_require__.r(product_form_selectors_namespaceObject);
__webpack_require__.d(product_form_selectors_namespaceObject, {
  getField: () => (getField),
  getFields: () => (getFields),
  getProductForm: () => (getProductForm)
});

// NAMESPACE OBJECT: ../../packages/js/data/src/product-form/actions.ts
var product_form_actions_namespaceObject = {};
__webpack_require__.r(product_form_actions_namespaceObject);
__webpack_require__.d(product_form_actions_namespaceObject, {
  getFieldsError: () => (getFieldsError),
  getFieldsSuccess: () => (getFieldsSuccess),
  getProductFormError: () => (getProductFormError),
  getProductFormSuccess: () => (getProductFormSuccess)
});

// NAMESPACE OBJECT: ../../packages/js/data/src/product-form/resolvers.ts
var product_form_resolvers_namespaceObject = {};
__webpack_require__.r(product_form_resolvers_namespaceObject);
__webpack_require__.d(product_form_resolvers_namespaceObject, {
  getFields: () => (resolvers_getFields),
  getProductForm: () => (resolvers_getProductForm)
});

// NAMESPACE OBJECT: ../../packages/js/data/src/tax-classes/resolvers.ts
var tax_classes_resolvers_namespaceObject = {};
__webpack_require__.r(tax_classes_resolvers_namespaceObject);
__webpack_require__.d(tax_classes_resolvers_namespaceObject, {
  getTaxClasses: () => (getTaxClasses)
});

// NAMESPACE OBJECT: ../../packages/js/data/src/export/selectors.ts
var export_selectors_namespaceObject = {};
__webpack_require__.r(export_selectors_namespaceObject);
__webpack_require__.d(export_selectors_namespaceObject, {
  getError: () => (getError),
  getExportId: () => (getExportId),
  isExportRequesting: () => (isExportRequesting)
});

// NAMESPACE OBJECT: ../../packages/js/data/src/export/actions.ts
var export_actions_namespaceObject = {};
__webpack_require__.r(export_actions_namespaceObject);
__webpack_require__.d(export_actions_namespaceObject, {
  setError: () => (export_actions_setError),
  setExportId: () => (setExportId),
  setIsRequesting: () => (export_actions_setIsRequesting),
  startExport: () => (startExport)
});

// NAMESPACE OBJECT: ../../packages/js/data/src/import/selectors.ts
var import_selectors_namespaceObject = {};
__webpack_require__.r(import_selectors_namespaceObject);
__webpack_require__.d(import_selectors_namespaceObject, {
  getFormSettings: () => (getFormSettings),
  getImportError: () => (getImportError),
  getImportStarted: () => (getImportStarted),
  getImportStatus: () => (getImportStatus),
  getImportTotals: () => (getImportTotals)
});

// NAMESPACE OBJECT: ../../packages/js/data/src/import/actions.ts
var import_actions_namespaceObject = {};
__webpack_require__.r(import_actions_namespaceObject);
__webpack_require__.d(import_actions_namespaceObject, {
  setImportError: () => (setImportError),
  setImportPeriod: () => (setImportPeriod),
  setImportStarted: () => (setImportStarted),
  setImportStatus: () => (setImportStatus),
  setImportTotals: () => (setImportTotals),
  setSkipPrevious: () => (setSkipPrevious),
  updateImportation: () => (updateImportation)
});

// NAMESPACE OBJECT: ../../packages/js/data/src/import/resolvers.ts
var import_resolvers_namespaceObject = {};
__webpack_require__.r(import_resolvers_namespaceObject);
__webpack_require__.d(import_resolvers_namespaceObject, {
  getImportStatus: () => (resolvers_getImportStatus),
  getImportTotals: () => (resolvers_getImportTotals)
});

// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+core-data@4.4.5_react@17.0.2/node_modules/@wordpress/core-data/build-module/index.js + 25 modules
var build_module = __webpack_require__("../../node_modules/.pnpm/@wordpress+core-data@4.4.5_react@17.0.2/node_modules/@wordpress/core-data/build-module/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+data@6.6.1_react@17.0.2/node_modules/@wordpress/data/build-module/index.js
var data_build_module = __webpack_require__("../../node_modules/.pnpm/@wordpress+data@6.6.1_react@17.0.2/node_modules/@wordpress/data/build-module/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+data-controls@2.6.1_react@17.0.2/node_modules/@wordpress/data-controls/build-module/index.js
var data_controls_build_module = __webpack_require__("../../node_modules/.pnpm/@wordpress+data-controls@2.6.1_react@17.0.2/node_modules/@wordpress/data-controls/build-module/index.js");
;// CONCATENATED MODULE: ../../packages/js/data/src/settings/constants.ts
var constants_STORE_NAME = 'wc/admin/settings';
// EXTERNAL MODULE: ../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/toConsumableArray.js + 2 modules
var toConsumableArray = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/toConsumableArray.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.iterator.js
var es_array_iterator = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.iterator.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.to-string.js
var es_object_to_string = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.to-string.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.set.js
var es_set = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.set.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.string.iterator.js
var es_string_iterator = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.string.iterator.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/web.dom-collections.iterator.js
var web_dom_collections_iterator = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/web.dom-collections.iterator.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.map.js
var es_array_map = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.map.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.keys.js
var es_object_keys = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.keys.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.is-array.js
var es_array_is_array = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.is-array.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.for-each.js
var es_array_for_each = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.for-each.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/web.dom-collections.for-each.js
var web_dom_collections_for_each = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/web.dom-collections.for-each.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.some.js
var es_array_some = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.some.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.includes.js
var es_array_includes = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.includes.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.string.includes.js
var es_string_includes = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.string.includes.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.reduce.js
var es_array_reduce = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.reduce.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.symbol.js
var es_symbol = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.symbol.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.filter.js
var es_array_filter = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.filter.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.get-own-property-descriptor.js
var es_object_get_own_property_descriptor = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.get-own-property-descriptor.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.get-own-property-descriptors.js
var es_object_get_own_property_descriptors = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.get-own-property-descriptors.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.define-properties.js
var es_object_define_properties = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.define-properties.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.define-property.js
var es_object_define_property = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.define-property.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/objectWithoutProperties.js + 1 modules
var objectWithoutProperties = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/objectWithoutProperties.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/defineProperty.js
var defineProperty = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/defineProperty.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/slicedToArray.js + 1 modules
var slicedToArray = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/slicedToArray.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/typeof.js
var esm_typeof = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/typeof.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/regenerator/index.js
var regenerator = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/regenerator/index.js");
var regenerator_default = /*#__PURE__*/__webpack_require__.n(regenerator);
// EXTERNAL MODULE: ../../node_modules/.pnpm/regenerator-runtime@0.13.11/node_modules/regenerator-runtime/runtime.js
var runtime = __webpack_require__("../../node_modules/.pnpm/regenerator-runtime@0.13.11/node_modules/regenerator-runtime/runtime.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.sort.js
var es_array_sort = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.sort.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.entries.js
var es_object_entries = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.entries.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.regexp.exec.js
var es_regexp_exec = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.regexp.exec.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.string.replace.js
var es_string_replace = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.string.replace.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.concat.js
var es_array_concat = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.concat.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.index-of.js
var es_array_index_of = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.index-of.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.parse-int.js
var es_parse_int = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.parse-int.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+url@3.7.1/node_modules/@wordpress/url/build-module/add-query-args.js + 3 modules
var add_query_args = __webpack_require__("../../node_modules/.pnpm/@wordpress+url@3.7.1/node_modules/@wordpress/url/build-module/add-query-args.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.promise.js
var es_promise = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.promise.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+api-fetch@6.3.1/node_modules/@wordpress/api-fetch/build-module/index.js + 12 modules
var api_fetch_build_module = __webpack_require__("../../node_modules/.pnpm/@wordpress+api-fetch@6.3.1/node_modules/@wordpress/api-fetch/build-module/index.js");
;// CONCATENATED MODULE: ../../packages/js/data/src/controls.ts
















function ownKeys(e, r) {
  var t = Object.keys(e);
  if (Object.getOwnPropertySymbols) {
    var o = Object.getOwnPropertySymbols(e);
    r && (o = o.filter(function (r) {
      return Object.getOwnPropertyDescriptor(e, r).enumerable;
    })), t.push.apply(t, o);
  }
  return t;
}
function _objectSpread(e) {
  for (var r = 1; r < arguments.length; r++) {
    var t = null != arguments[r] ? arguments[r] : {};
    r % 2 ? ownKeys(Object(t), !0).forEach(function (r) {
      (0,defineProperty/* default */.A)(e, r, t[r]);
    }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(e, Object.getOwnPropertyDescriptors(t)) : ownKeys(Object(t)).forEach(function (r) {
      Object.defineProperty(e, r, Object.getOwnPropertyDescriptor(t, r));
    });
  }
  return e;
}
/**
 * External dependencies
 */


var fetchWithHeaders = function fetchWithHeaders(options) {
  return {
    type: 'FETCH_WITH_HEADERS',
    options: options
  };
};
var controls = _objectSpread(_objectSpread({}, data_controls_build_module/* controls */.ne), {}, {
  FETCH_WITH_HEADERS: function FETCH_WITH_HEADERS(action) {
    return (0,api_fetch_build_module/* default */.A)(_objectSpread(_objectSpread({}, action.options), {}, {
      parse: false
    })).then(function (response) {
      return Promise.all([response.headers, response.status, response.json()]);
    }).then(function (_ref) {
      var _ref2 = (0,slicedToArray/* default */.A)(_ref, 3),
        headers = _ref2[0],
        status = _ref2[1],
        data = _ref2[2];
      return {
        headers: headers,
        status: status,
        data: data
      };
    })["catch"](function (response) {
      return response.json().then(function (data) {
        throw data;
      });
    });
  }
});
/* harmony default export */ const src_controls = (controls);
;// CONCATENATED MODULE: ../../packages/js/data/src/user/constants.ts
var user_constants_STORE_NAME = 'core';
;// CONCATENATED MODULE: ../../packages/js/data/src/user/index.ts
/**
 * Internal dependencies
 */

var USER_STORE_NAME = user_constants_STORE_NAME;
;// CONCATENATED MODULE: ../../packages/js/data/src/utils.ts













var _excluded = ["_fields", "page", "per_page", "order", "orderby"];


var _marked = /*#__PURE__*/regenerator_default().mark(request),
  _marked2 = /*#__PURE__*/regenerator_default().mark(checkUserCapability);
function utils_ownKeys(e, r) {
  var t = Object.keys(e);
  if (Object.getOwnPropertySymbols) {
    var o = Object.getOwnPropertySymbols(e);
    r && (o = o.filter(function (r) {
      return Object.getOwnPropertyDescriptor(e, r).enumerable;
    })), t.push.apply(t, o);
  }
  return t;
}
function utils_objectSpread(e) {
  for (var r = 1; r < arguments.length; r++) {
    var t = null != arguments[r] ? arguments[r] : {};
    r % 2 ? utils_ownKeys(Object(t), !0).forEach(function (r) {
      (0,defineProperty/* default */.A)(e, r, t[r]);
    }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(e, Object.getOwnPropertyDescriptors(t)) : utils_ownKeys(Object(t)).forEach(function (r) {
      Object.defineProperty(e, r, Object.getOwnPropertyDescriptor(t, r));
    });
  }
  return e;
}











/**
 * External dependencies
 */



/**
 * Internal dependencies
 */



function replacer(_, value) {
  if (value) {
    if (Array.isArray(value)) {
      return (0,toConsumableArray/* default */.A)(value).sort();
    }
    if ((0,esm_typeof/* default */.A)(value) === 'object') {
      return Object.entries(value).sort().reduce(function (current, _ref) {
        var _ref2 = (0,slicedToArray/* default */.A)(_ref, 2),
          propKey = _ref2[0],
          propVal = _ref2[1];
        return utils_objectSpread(utils_objectSpread({}, current), {}, (0,defineProperty/* default */.A)({}, propKey, propVal));
      }, {});
    }
  }
  return value;
}
function utils_getResourceName(prefix) {
  for (var _len = arguments.length, identifier = new Array(_len > 1 ? _len - 1 : 0), _key = 1; _key < _len; _key++) {
    identifier[_key - 1] = arguments[_key];
  }
  var identifierString = JSON.stringify(identifier, replacer).replace(/\\"/g, '"');
  return "".concat(prefix, ":").concat(identifierString);
}

/**
 * Generate a resource name for order totals count.
 *
 * It omits query parameters from the identifier that don't affect
 * totals values like pagination and response field filtering.
 *
 * @param {string} prefix Resource name prefix.
 * @param {Object} query  Query for order totals count.
 * @return {string} Resource name for order totals.
 */
function getTotalCountResourceName(prefix, query) {
  var _fields = query._fields,
    page = query.page,
    per_page = query.per_page,
    order = query.order,
    orderby = query.orderby,
    totalsQuery = (0,objectWithoutProperties/* default */.A)(query, _excluded);
  return utils_getResourceName(prefix, totalsQuery);
}
function getResourcePrefix(resourceName) {
  var hasPrefixIndex = resourceName.indexOf(':');
  return hasPrefixIndex < 0 ? resourceName : resourceName.substring(0, hasPrefixIndex);
}
function isResourcePrefix(resourceName, prefix) {
  var resourcePrefix = getResourcePrefix(resourceName);
  return resourcePrefix === prefix;
}
function getResourceIdentifier(resourceName) {
  var identifierString = resourceName.substring(resourceName.indexOf(':') + 1);
  return JSON.parse(identifierString);
}
function request(namespace, query) {
  var url, isUnboundedRequest, fetch, response, totalCount;
  return regenerator_default().wrap(function request$(_context) {
    while (1) switch (_context.prev = _context.next) {
      case 0:
        url = (0,add_query_args/* addQueryArgs */.F)(namespace, query);
        isUnboundedRequest = query.per_page === -1;
        fetch = isUnboundedRequest ? data_controls_build_module/* apiFetch */.nr : fetchWithHeaders;
        _context.next = 5;
        return fetch({
          path: url,
          method: 'GET'
        });
      case 5:
        response = _context.sent;
        if (!(isUnboundedRequest && !('data' in response))) {
          _context.next = 8;
          break;
        }
        return _context.abrupt("return", {
          items: response,
          totalCount: response.length
        });
      case 8:
        if (!(!isUnboundedRequest && 'data' in response)) {
          _context.next = 11;
          break;
        }
        totalCount = parseInt(response.headers.get('x-wp-total') || '', 10);
        return _context.abrupt("return", {
          items: response.data,
          totalCount: totalCount
        });
      case 11:
      case "end":
        return _context.stop();
    }
  }, _marked);
}

/**
 * Utility function to check if the current user has a specific capability.
 *
 * @param {string} capability - The capability to check (e.g. 'manage_woocommerce').
 * @throws {Error} If the user does not have the required capability.
 */
function checkUserCapability(capability) {
  var currentUser;
  return regenerator_default().wrap(function checkUserCapability$(_context2) {
    while (1) switch (_context2.prev = _context2.next) {
      case 0:
        _context2.next = 2;
        return (0,data_controls_build_module/* select */.Lt)(USER_STORE_NAME, 'getCurrentUser');
      case 2:
        currentUser = _context2.sent;
        if (currentUser.capabilities[capability]) {
          _context2.next = 5;
          break;
        }
        throw new Error("User does not have ".concat(capability, " capability."));
      case 5:
      case "end":
        return _context2.stop();
    }
  }, _marked2);
}
;// CONCATENATED MODULE: ../../packages/js/data/src/settings/selectors.ts















/**
 * Internal dependencies
 */

var getSettingsGroupNames = function getSettingsGroupNames(state) {
  var groupNames = new Set(Object.keys(state).map(function (resourceName) {
    return getResourcePrefix(resourceName);
  }));
  return (0,toConsumableArray/* default */.A)(groupNames);
};
var getSettings = function getSettings(state, group) {
  var settings = {};
  var settingIds = state[group] && state[group].data || [];
  if (!Array.isArray(settingIds) || settingIds.length === 0) {
    return settings;
  }
  settingIds.forEach(function (id) {
    settings[id] = state[utils_getResourceName(group, id)].data;
  });
  return settings;
};
var getDirtyKeys = function getDirtyKeys(state, group) {
  return state[group].dirty || [];
};
var getIsDirty = function getIsDirty(state, group) {
  var keys = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : [];
  var dirtyMap = getDirtyKeys(state, group);
  // if empty array bail
  if (dirtyMap.length === 0) {
    return false;
  }
  // if at least one of the keys is in the dirty map then the state is dirty
  // meaning it hasn't been persisted.
  return keys.some(function (key) {
    return dirtyMap.includes(key);
  });
};
var getSettingsForGroup = function getSettingsForGroup(state, group, keys) {
  var allSettings = getSettings(state, group);
  return keys.reduce(function (accumulator, key) {
    accumulator[key] = allSettings[key] || {};
    return accumulator;
  }, {});
};
var isUpdateSettingsRequesting = function isUpdateSettingsRequesting(state, group) {
  return state[group] && Boolean(state[group].isRequesting);
};

/**
 * Retrieves a setting value from the setting store.
 *
 * @param {Object}   state                   State param added by wp.data.
 * @param {string}   group                   The settings group.
 * @param {string}   name                    The identifier for the setting.
 * @param {*}        [fallback=false]        The value to use as a fallback
 *                                           if the setting is not in the
 *                                           state.
 * @param {Function} [filter=( val ) => val] A callback for filtering the
 *                                           value before it's returned.
 *                                           Receives both the found value
 *                                           (if it exists for the key) and
 *                                           the provided fallback arg.
 *
 * @return {*}  The value present in the settings state for the given
 *                   name.
 */
function getSetting(state, group, name) {
  var fallback = arguments.length > 3 && arguments[3] !== undefined ? arguments[3] : false;
  var filter = arguments.length > 4 && arguments[4] !== undefined ? arguments[4] : function (val, _fallback) {
    return val;
  };
  var resourceName = utils_getResourceName(group, name);
  var value = state[resourceName] && state[resourceName].data || fallback;
  return filter(value, fallback);
}
var getLastSettingsErrorForGroup = function getLastSettingsErrorForGroup(state, group) {
  var settingsIds = state[group].data;
  if (!Array.isArray(settingsIds) || settingsIds.length === 0) {
    return state[group].error;
  }
  return (0,toConsumableArray/* default */.A)(settingsIds).pop().error;
};
var getSettingsError = function getSettingsError(state, group, id) {
  if (!id) {
    return state[group] && state[group].error || false;
  }
  return state[utils_getResourceName(group, id)].error || false;
};
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.date.to-string.js
var es_date_to_string = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.date.to-string.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+i18n@4.6.1/node_modules/@wordpress/i18n/build-module/index.js + 3 modules
var i18n_build_module = __webpack_require__("../../node_modules/.pnpm/@wordpress+i18n@4.6.1/node_modules/@wordpress/i18n/build-module/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+data@6.6.1_react@17.0.2/node_modules/@wordpress/data/build-module/controls.js
var build_module_controls = __webpack_require__("../../node_modules/.pnpm/@wordpress+data@6.6.1_react@17.0.2/node_modules/@wordpress/data/build-module/controls.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/lodash@4.17.21/node_modules/lodash/lodash.js
var lodash = __webpack_require__("../../node_modules/.pnpm/lodash@4.17.21/node_modules/lodash/lodash.js");
;// CONCATENATED MODULE: ../../packages/js/data/src/constants.ts
var JETPACK_NAMESPACE = '/jetpack/v4';
var NAMESPACE = '/wc-analytics';
var WC_ADMIN_NAMESPACE = '/wc-admin';
var WCS_NAMESPACE = '/wc/v1'; // WCS endpoints like Stripe are not available on later /wc versions

// WordPress & WooCommerce both set a hard limit of 100 for the per_page parameter
var constants_MAX_PER_PAGE = 100;
var SECOND = 1000;
var MINUTE = 60 * SECOND;
var HOUR = 60 * MINUTE;
var DAY = 24 * HOUR;
var WEEK = 7 * DAY;
var MONTH = 365 * DAY / 12;
var DEFAULT_REQUIREMENT = {
  timeout: 1 * MINUTE,
  freshness: 30 * MINUTE
};
var DEFAULT_ACTIONABLE_STATUSES = (/* unused pure expression or super */ null && (['processing', 'on-hold']));
var constants_QUERY_DEFAULTS = {
  pageSize: 25,
  period: 'month',
  compare: 'previous_year',
  noteTypes: ['info', 'marketing', 'survey', 'warning']
};
;// CONCATENATED MODULE: ../../packages/js/data/src/settings/action-types.ts
var TYPES = {
  UPDATE_SETTINGS_FOR_GROUP: 'UPDATE_SETTINGS_FOR_GROUP',
  UPDATE_ERROR_FOR_GROUP: 'UPDATE_ERROR_FOR_GROUP',
  CLEAR_SETTINGS: 'CLEAR_SETTINGS',
  SET_IS_REQUESTING: 'SET_IS_REQUESTING',
  CLEAR_IS_DIRTY: 'CLEAR_IS_DIRTY'
};
/* harmony default export */ const action_types = (TYPES);
;// CONCATENATED MODULE: ../../packages/js/data/src/settings/actions.ts


var actions_marked = /*#__PURE__*/regenerator_default().mark(persistSettingsForGroup),
  actions_marked2 = /*#__PURE__*/regenerator_default().mark(updateAndPersistSettingsForGroup);









/**
 * External dependencies
 */





/**
 * Internal dependencies
 */



// Can be removed in WP 5.9, wp.data is supported in >5.7.
var resolveSelect = build_module_controls/* controls */.n && build_module_controls/* controls */.n.resolveSelect ? build_module_controls/* controls */.n.resolveSelect : data_controls_build_module/* select */.Lt;
function updateSettingsForGroup(group, data) {
  var time = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : new Date();
  return {
    type: action_types.UPDATE_SETTINGS_FOR_GROUP,
    group: group,
    data: data,
    time: time
  };
}
function updateErrorForGroup(group, data, error) {
  var time = arguments.length > 3 && arguments[3] !== undefined ? arguments[3] : new Date();
  return {
    type: action_types.UPDATE_ERROR_FOR_GROUP,
    group: group,
    data: data,
    error: error,
    time: time
  };
}
function setIsRequesting(group, isRequesting) {
  return {
    type: action_types.SET_IS_REQUESTING,
    group: group,
    isRequesting: isRequesting
  };
}
function clearIsDirty(group) {
  return {
    type: action_types.CLEAR_IS_DIRTY,
    group: group
  };
}

// this would replace setSettingsForGroup
function persistSettingsForGroup(group) {
  var dirtyKeys, dirtyData, url, update, results;
  return regenerator_default().wrap(function persistSettingsForGroup$(_context) {
    while (1) switch (_context.prev = _context.next) {
      case 0:
        _context.next = 2;
        return setIsRequesting(group, true);
      case 2:
        _context.next = 4;
        return resolveSelect(constants_STORE_NAME, 'getDirtyKeys', group);
      case 4:
        dirtyKeys = _context.sent;
        if (!(dirtyKeys.length === 0)) {
          _context.next = 9;
          break;
        }
        _context.next = 8;
        return setIsRequesting(group, false);
      case 8:
        return _context.abrupt("return");
      case 9:
        _context.next = 11;
        return resolveSelect(constants_STORE_NAME, 'getSettingsForGroup', group, dirtyKeys);
      case 11:
        dirtyData = _context.sent;
        url = "".concat(NAMESPACE, "/settings/").concat(group, "/batch");
        update = dirtyKeys.reduce(function (updates, key) {
          var u = Object.keys(dirtyData[key]).map(function (k) {
            return {
              id: k,
              value: dirtyData[key][k]
            };
          });
          return (0,lodash.concat)(updates, u);
        }, []);
        _context.prev = 14;
        _context.next = 17;
        return (0,data_controls_build_module/* apiFetch */.nr)({
          path: url,
          method: 'POST',
          data: {
            update: update
          }
        });
      case 17:
        results = _context.sent;
        _context.next = 20;
        return setIsRequesting(group, false);
      case 20:
        if (results) {
          _context.next = 22;
          break;
        }
        throw new Error((0,i18n_build_module.__)('There was a problem updating your settings.', 'woocommerce'));
      case 22:
        _context.next = 24;
        return clearIsDirty(group);
      case 24:
        _context.next = 33;
        break;
      case 26:
        _context.prev = 26;
        _context.t0 = _context["catch"](14);
        _context.next = 30;
        return updateErrorForGroup(group, null, _context.t0);
      case 30:
        _context.next = 32;
        return setIsRequesting(group, false);
      case 32:
        throw _context.t0;
      case 33:
      case "end":
        return _context.stop();
    }
  }, actions_marked, null, [[14, 26]]);
}

// allows updating and persisting immediately in one action.
function updateAndPersistSettingsForGroup(group, data) {
  return regenerator_default().wrap(function updateAndPersistSettingsForGroup$(_context2) {
    while (1) switch (_context2.prev = _context2.next) {
      case 0:
        _context2.next = 2;
        return setIsRequesting(group, true);
      case 2:
        _context2.next = 4;
        return updateSettingsForGroup(group, data);
      case 4:
        return _context2.delegateYield(persistSettingsForGroup(group), "t0", 5);
      case 5:
      case "end":
        return _context2.stop();
    }
  }, actions_marked2);
}
function clearSettings() {
  return {
    type: action_types.CLEAR_SETTINGS
  };
}
;// CONCATENATED MODULE: ../../packages/js/data/src/types/api.ts
var isRestApiError = function isRestApiError(error) {
  return error.code !== undefined && error.message !== undefined;
};
;// CONCATENATED MODULE: ../../packages/js/data/src/settings/resolvers.ts



var resolvers_marked = /*#__PURE__*/regenerator_default().mark(resolvers_getSettings),
  resolvers_marked2 = /*#__PURE__*/regenerator_default().mark(resolvers_getSettingsForGroup);


/**
 * External dependencies
 */



/**
 * Internal dependencies
 */





// Can be removed in WP 5.9.
var dispatch = build_module_controls/* controls */.n && build_module_controls/* controls */.n.dispatch ? build_module_controls/* controls */.n.dispatch : data_controls_build_module/* dispatch */.JD;

// [class-wc-rest-setting-options-controller.php](https://github.com/woocommerce/woocommerce/blob/28926968bdcd2b504e16761a483388f85ee0c151/plugins/woocommerce/includes/rest-api/Controllers/Version3/class-wc-rest-setting-options-controller.php#L158-L248)

function settingsToSettingsResource(settings) {
  return settings.reduce(function (resource, setting) {
    resource[setting.id] = setting.value;
    return resource;
  }, {});
}
function resolvers_getSettings(group) {
  var url, results, resource;
  return regenerator_default().wrap(function getSettings$(_context) {
    while (1) switch (_context.prev = _context.next) {
      case 0:
        _context.next = 2;
        return dispatch(constants_STORE_NAME, 'setIsRequesting', group, true);
      case 2:
        _context.prev = 2;
        url = NAMESPACE + '/settings/' + group;
        _context.next = 6;
        return (0,data_controls_build_module/* apiFetch */.nr)({
          path: url,
          method: 'GET'
        });
      case 6:
        results = _context.sent;
        resource = settingsToSettingsResource(results);
        return _context.abrupt("return", updateSettingsForGroup(group, (0,defineProperty/* default */.A)({}, group, resource)));
      case 11:
        _context.prev = 11;
        _context.t0 = _context["catch"](2);
        if (!(_context.t0 instanceof Error || isRestApiError(_context.t0))) {
          _context.next = 15;
          break;
        }
        return _context.abrupt("return", updateErrorForGroup(group, null, _context.t0.message));
      case 15:
        throw "Unexpected error ".concat(_context.t0);
      case 16:
      case "end":
        return _context.stop();
    }
  }, resolvers_marked, null, [[2, 11]]);
}
function resolvers_getSettingsForGroup(group) {
  return regenerator_default().wrap(function getSettingsForGroup$(_context2) {
    while (1) switch (_context2.prev = _context2.next) {
      case 0:
        return _context2.abrupt("return", resolvers_getSettings(group));
      case 1:
      case "end":
        return _context2.stop();
    }
  }, resolvers_marked2);
}
;// CONCATENATED MODULE: ../../packages/js/data/src/settings/reducer.ts








function reducer_ownKeys(e, r) {
  var t = Object.keys(e);
  if (Object.getOwnPropertySymbols) {
    var o = Object.getOwnPropertySymbols(e);
    r && (o = o.filter(function (r) {
      return Object.getOwnPropertyDescriptor(e, r).enumerable;
    })), t.push.apply(t, o);
  }
  return t;
}
function reducer_objectSpread(e) {
  for (var r = 1; r < arguments.length; r++) {
    var t = null != arguments[r] ? arguments[r] : {};
    r % 2 ? reducer_ownKeys(Object(t), !0).forEach(function (r) {
      (0,defineProperty/* default */.A)(e, r, t[r]);
    }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(e, Object.getOwnPropertyDescriptors(t)) : reducer_ownKeys(Object(t)).forEach(function (r) {
      Object.defineProperty(e, r, Object.getOwnPropertyDescriptor(t, r));
    });
  }
  return e;
}






/**
 * External dependencies
 */

/**
 * Internal dependencies
 */


var updateGroupDataInNewState = function updateGroupDataInNewState(newState, _ref) {
  var group = _ref.group,
    groupIds = _ref.groupIds,
    data = _ref.data,
    time = _ref.time,
    error = _ref.error;
  groupIds.forEach(function (id) {
    newState[utils_getResourceName(group, id)] = {
      data: data[id],
      lastReceived: time,
      error: error
    };
  });
  return newState;
};
var reducer = function reducer() {
  var state = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};
  var action = arguments.length > 1 ? arguments[1] : undefined;
  var newState = {};
  switch (action.type) {
    case action_types.SET_IS_REQUESTING:
      state = reducer_objectSpread(reducer_objectSpread({}, state), {}, (0,defineProperty/* default */.A)({}, action.group, reducer_objectSpread(reducer_objectSpread({}, state[action.group]), {}, {
        isRequesting: action.isRequesting
      })));
      break;
    case action_types.CLEAR_IS_DIRTY:
      state = reducer_objectSpread(reducer_objectSpread({}, state), {}, (0,defineProperty/* default */.A)({}, action.group, reducer_objectSpread(reducer_objectSpread({}, state[action.group]), {}, {
        dirty: []
      })));
      break;
    case action_types.UPDATE_SETTINGS_FOR_GROUP:
    case action_types.UPDATE_ERROR_FOR_GROUP:
      var data = action.data,
        group = action.group,
        time = action.time;
      var groupIds = data ? Object.keys(data) : [];
      var error = action.type === action_types.UPDATE_ERROR_FOR_GROUP ? action.error : null;
      if (data === null) {
        state = reducer_objectSpread(reducer_objectSpread({}, state), {}, (0,defineProperty/* default */.A)({}, group, {
          data: state[group] ? state[group].data : [],
          error: error,
          lastReceived: time
        }));
      } else {
        var _state$group;
        var stateGroup = state[group];
        state = reducer_objectSpread(reducer_objectSpread({}, state), {}, (0,defineProperty/* default */.A)({}, group, {
          data: stateGroup && stateGroup.data && Array.isArray(stateGroup.data) ? [].concat((0,toConsumableArray/* default */.A)(stateGroup.data), (0,toConsumableArray/* default */.A)(groupIds)) : groupIds,
          error: error,
          lastReceived: time,
          isRequesting: ((_state$group = state[group]) === null || _state$group === void 0 ? void 0 : _state$group.isRequesting) || false,
          dirty: state[group] && state[group].dirty ? (0,lodash.union)(state[group].dirty, groupIds) : groupIds
        }), updateGroupDataInNewState(newState, {
          group: group,
          groupIds: groupIds,
          data: data,
          time: time,
          error: error
        }));
      }
      break;
    case action_types.CLEAR_SETTINGS:
      state = {};
  }
  return state;
};
/* harmony default export */ const settings_reducer = (reducer);
;// CONCATENATED MODULE: ../../packages/js/data/src/settings/index.ts
/**
 * External dependencies
 */




/**
 * Internal dependencies
 */







(0,data_build_module/* registerStore */.ti)(constants_STORE_NAME, {
  reducer: settings_reducer,
  actions: actions_namespaceObject,
  controls: data_controls_build_module/* controls */.ne,
  selectors: selectors_namespaceObject,
  resolvers: resolvers_namespaceObject
});
var SETTINGS_STORE_NAME = (/* unused pure expression or super */ null && (STORE_NAME));
;// CONCATENATED MODULE: ../../packages/js/data/src/plugins/constants.ts
/**
 * External dependencies
 */

var plugins_constants_STORE_NAME = 'wc/admin/plugins';
var PAYPAL_NAMESPACE = '/wc-paypal/v1';

/**
 * Plugin slugs and names as key/value pairs.
 */
var pluginNames = {
  'facebook-for-woocommerce': (0,i18n_build_module.__)('Facebook for WooCommerce', 'woocommerce'),
  jetpack: (0,i18n_build_module.__)('Jetpack', 'woocommerce'),
  'klarna-checkout-for-woocommerce': (0,i18n_build_module.__)('Klarna Checkout for WooCommerce', 'woocommerce'),
  'klarna-payments-for-woocommerce': (0,i18n_build_module.__)('Klarna Payments for WooCommerce', 'woocommerce'),
  'mailchimp-for-woocommerce': (0,i18n_build_module.__)('Mailchimp for WooCommerce', 'woocommerce'),
  'creative-mail-by-constant-contact': (0,i18n_build_module.__)('Creative Mail for WooCommerce', 'woocommerce'),
  'woocommerce-gateway-paypal-express-checkout': (0,i18n_build_module.__)('WooCommerce PayPal', 'woocommerce'),
  'woocommerce-gateway-stripe': (0,i18n_build_module.__)('WooCommerce Stripe', 'woocommerce'),
  'woocommerce-payfast-gateway': (0,i18n_build_module.__)('WooCommerce Payfast', 'woocommerce'),
  'woocommerce-payments': (0,i18n_build_module.__)('WooPayments', 'woocommerce'),
  'woocommerce-services': (0,i18n_build_module.__)('WooCommerce Shipping & Tax', 'woocommerce'),
  'woocommerce-services:shipping': (0,i18n_build_module.__)('WooCommerce Shipping & Tax', 'woocommerce'),
  'woocommerce-services:tax': (0,i18n_build_module.__)('WooCommerce Shipping & Tax', 'woocommerce'),
  'woocommerce-shipstation-integration': (0,i18n_build_module.__)('WooCommerce ShipStation Gateway', 'woocommerce'),
  'woocommerce-mercadopago': (0,i18n_build_module.__)('Mercado Pago payments for WooCommerce', 'woocommerce'),
  'google-listings-and-ads': (0,i18n_build_module.__)('Google for WooCommerce', 'woocommerce'),
  'woo-razorpay': (0,i18n_build_module.__)('Razorpay', 'woocommerce'),
  mailpoet: (0,i18n_build_module.__)('MailPoet', 'woocommerce'),
  'pinterest-for-woocommerce': (0,i18n_build_module.__)('Pinterest for WooCommerce', 'woocommerce'),
  'tiktok-for-business:alt': (0,i18n_build_module.__)('TikTok for WooCommerce', 'woocommerce'),
  codistoconnect: (0,i18n_build_module.__)('Omnichannel for WooCommerce', 'woocommerce')
};
;// CONCATENATED MODULE: ../../packages/js/data/src/plugins/selectors.ts


/**
 * Internal dependencies
 */

var getActivePlugins = function getActivePlugins(state) {
  return state.active || [];
};
var getInstalledPlugins = function getInstalledPlugins(state) {
  return state.installed || [];
};
var isPluginsRequesting = function isPluginsRequesting(state, selector) {
  return state.requesting[selector] || false;
};
var getPluginsError = function getPluginsError(state, selector) {
  return state.errors[selector] || false;
};
var isJetpackConnected = function isJetpackConnected(state) {
  return state.jetpackConnection;
};
var getJetpackConnectionData = function getJetpackConnectionData(state) {
  return state.jetpackConnectionData;
};
var getJetpackConnectUrl = function getJetpackConnectUrl(state, query) {
  return state.jetpackConnectUrls[query.redirect_url];
};
var getPluginInstallState = function getPluginInstallState(state, plugin) {
  if (state.active.includes(plugin)) {
    return 'activated';
  } else if (state.installed.includes(plugin)) {
    return 'installed';
  }
  return 'unavailable';
};
var getPaypalOnboardingStatus = function getPaypalOnboardingStatus(state) {
  return state.paypalOnboardingStatus;
};
var getRecommendedPlugins = function getRecommendedPlugins(state, type) {
  return state.recommended[type];
};

// Types
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.reflect.construct.js
var es_reflect_construct = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.reflect.construct.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/createClass.js
var createClass = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/createClass.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/classCallCheck.js
var classCallCheck = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/classCallCheck.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/inherits.js
var inherits = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/inherits.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/possibleConstructorReturn.js
var possibleConstructorReturn = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/possibleConstructorReturn.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/getPrototypeOf.js
var getPrototypeOf = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/getPrototypeOf.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/wrapNativeSuper.js + 3 modules
var wrapNativeSuper = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/wrapNativeSuper.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.join.js
var es_array_join = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.join.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.values.js
var es_object_values = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.values.js");
// EXTERNAL MODULE: ../../packages/js/tracks/src/index.ts + 2 modules
var src = __webpack_require__("../../packages/js/tracks/src/index.ts");
;// CONCATENATED MODULE: ../../packages/js/data/src/plugins/action-types.ts
var ACTION_TYPES = /*#__PURE__*/function (ACTION_TYPES) {
  ACTION_TYPES["UPDATE_ACTIVE_PLUGINS"] = "UPDATE_ACTIVE_PLUGINS";
  ACTION_TYPES["UPDATE_INSTALLED_PLUGINS"] = "UPDATE_INSTALLED_PLUGINS";
  ACTION_TYPES["SET_IS_REQUESTING"] = "SET_IS_REQUESTING";
  ACTION_TYPES["SET_ERROR"] = "SET_ERROR";
  ACTION_TYPES["UPDATE_JETPACK_CONNECTION"] = "UPDATE_JETPACK_CONNECTION";
  ACTION_TYPES["UPDATE_JETPACK_CONNECT_URL"] = "UPDATE_JETPACK_CONNECT_URL";
  ACTION_TYPES["UPDATE_JETPACK_CONNECTION_DATA"] = "UPDATE_JETPACK_CONNECTION_DATA";
  ACTION_TYPES["SET_PAYPAL_ONBOARDING_STATUS"] = "SET_PAYPAL_ONBOARDING_STATUS";
  ACTION_TYPES["SET_RECOMMENDED_PLUGINS"] = "SET_RECOMMENDED_PLUGINS";
  return ACTION_TYPES;
}({});
;// CONCATENATED MODULE: ../../packages/js/data/src/plugins/actions.ts


















function actions_ownKeys(e, r) {
  var t = Object.keys(e);
  if (Object.getOwnPropertySymbols) {
    var o = Object.getOwnPropertySymbols(e);
    r && (o = o.filter(function (r) {
      return Object.getOwnPropertyDescriptor(e, r).enumerable;
    })), t.push.apply(t, o);
  }
  return t;
}
function actions_objectSpread(e) {
  for (var r = 1; r < arguments.length; r++) {
    var t = null != arguments[r] ? arguments[r] : {};
    r % 2 ? actions_ownKeys(Object(t), !0).forEach(function (r) {
      (0,defineProperty/* default */.A)(e, r, t[r]);
    }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(e, Object.getOwnPropertyDescriptors(t)) : actions_ownKeys(Object(t)).forEach(function (r) {
      Object.defineProperty(e, r, Object.getOwnPropertyDescriptor(t, r));
    });
  }
  return e;
}


var plugins_actions_marked = /*#__PURE__*/regenerator_default().mark(handlePluginAPIError),
  plugins_actions_marked2 = /*#__PURE__*/regenerator_default().mark(activatePlugins),
  _marked3 = /*#__PURE__*/regenerator_default().mark(installAndActivatePlugins),
  _marked4 = /*#__PURE__*/regenerator_default().mark(connectToJetpack),
  _marked5 = /*#__PURE__*/regenerator_default().mark(installJetpackAndConnect),
  _marked6 = /*#__PURE__*/regenerator_default().mark(connectToJetpackWithFailureRedirect),
  _marked7 = /*#__PURE__*/regenerator_default().mark(dismissRecommendedPlugins);




function _createSuper(Derived) {
  var hasNativeReflectConstruct = _isNativeReflectConstruct();
  return function _createSuperInternal() {
    var Super = (0,getPrototypeOf/* default */.A)(Derived),
      result;
    if (hasNativeReflectConstruct) {
      var NewTarget = (0,getPrototypeOf/* default */.A)(this).constructor;
      result = Reflect.construct(Super, arguments, NewTarget);
    } else {
      result = Super.apply(this, arguments);
    }
    return (0,possibleConstructorReturn/* default */.A)(this, result);
  };
}
function _isNativeReflectConstruct() {
  if (typeof Reflect === "undefined" || !Reflect.construct) return false;
  if (Reflect.construct.sham) return false;
  if (typeof Proxy === "function") return true;
  try {
    Boolean.prototype.valueOf.call(Reflect.construct(Boolean, [], function () {}));
    return true;
  } catch (e) {
    return false;
  }
}
/**
 * External dependencies
 */





/**
 * Internal dependencies
 */




// Can be removed in WP 5.9, wp.data is supported in >5.7.
var actions_dispatch = build_module_controls/* controls */.n && build_module_controls/* controls */.n.dispatch ? build_module_controls/* controls */.n.dispatch : data_controls_build_module/* dispatch */.JD;
var actions_resolveSelect = build_module_controls/* controls */.n && build_module_controls/* controls */.n.resolveSelect ? build_module_controls/* controls */.n.resolveSelect : data_controls_build_module/* select */.Lt;
var PluginError = /*#__PURE__*/function (_Error) {
  (0,inherits/* default */.A)(PluginError, _Error);
  var _super = _createSuper(PluginError);
  function PluginError(message, data) {
    var _this;
    (0,classCallCheck/* default */.A)(this, PluginError);
    _this = _super.call(this, message);
    _this.data = data;
    return _this;
  }
  return (0,createClass/* default */.A)(PluginError);
}( /*#__PURE__*/(0,wrapNativeSuper/* default */.A)(Error));
var isPluginResponseError = function isPluginResponseError(plugins, error) {
  return (0,esm_typeof/* default */.A)(error) === 'object' && error !== null && plugins[0] in error;
};
var formatErrorMessage = function formatErrorMessage() {
  var actionType = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : 'install';
  var plugins = arguments.length > 1 ? arguments[1] : undefined;
  var rawErrorMessage = arguments.length > 2 ? arguments[2] : undefined;
  return (0,i18n_build_module/* sprintf */.nv)( /* translators: %(actionType): install or activate (the plugin). %(pluginName): a plugin slug (e.g. woocommerce-services). %(error): a single error message or in plural a comma separated error message list.*/
  (0,i18n_build_module._n)('Could not %(actionType)s %(pluginName)s plugin, %(error)s', 'Could not %(actionType)s the following plugins: %(pluginName)s with these Errors: %(error)s', Object.keys(plugins).length || 1, 'woocommerce'), {
    actionType: actionType,
    pluginName: plugins.join(', '),
    error: rawErrorMessage
  });
};
function updateActivePlugins(active) {
  var replace = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : false;
  return {
    type: ACTION_TYPES.UPDATE_ACTIVE_PLUGINS,
    active: active,
    replace: replace
  };
}
function updateInstalledPlugins(installed) {
  var replace = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : false;
  return {
    type: ACTION_TYPES.UPDATE_INSTALLED_PLUGINS,
    installed: installed,
    replace: replace
  };
}
function actions_setIsRequesting(selector, isRequesting) {
  return {
    type: ACTION_TYPES.SET_IS_REQUESTING,
    selector: selector,
    isRequesting: isRequesting
  };
}
function setError(selector, error) {
  return {
    type: ACTION_TYPES.SET_ERROR,
    selector: selector,
    error: error
  };
}
function updateIsJetpackConnected(jetpackConnection) {
  return {
    type: ACTION_TYPES.UPDATE_JETPACK_CONNECTION,
    jetpackConnection: jetpackConnection
  };
}
function updateJetpackConnectionData(results) {
  return {
    type: ACTION_TYPES.UPDATE_JETPACK_CONNECTION_DATA,
    results: results
  };
}
function updateJetpackConnectUrl(redirectUrl, jetpackConnectUrl) {
  return {
    type: ACTION_TYPES.UPDATE_JETPACK_CONNECT_URL,
    jetpackConnectUrl: jetpackConnectUrl,
    redirectUrl: redirectUrl
  };
}
var createErrorNotice = function createErrorNotice(errorMessage) {
  return actions_dispatch('core/notices', 'createNotice', 'error', errorMessage);
};
function setPaypalOnboardingStatus(status) {
  return {
    type: ACTION_TYPES.SET_PAYPAL_ONBOARDING_STATUS,
    paypalOnboardingStatus: status
  };
}
function setRecommendedPlugins(type, plugins) {
  return {
    type: ACTION_TYPES.SET_RECOMMENDED_PLUGINS,
    recommendedType: type,
    plugins: plugins
  };
}
function handlePluginAPIError(actionType, plugins, error) {
  var rawErrorMessage;
  return regenerator_default().wrap(function handlePluginAPIError$(_context) {
    while (1) switch (_context.prev = _context.next) {
      case 0:
        if (isPluginResponseError(plugins, error)) {
          // Backend error messages are in the form of { plugin-slug: [ error messages ] }.
          rawErrorMessage = Object.values(error).join(', \n');
        } else {
          // Other error such as API connection errors.
          rawErrorMessage = isRestApiError(error) || error instanceof Error ? error.message : JSON.stringify(error);
        }

        // Track the error.
        _context.t0 = actionType;
        _context.next = _context.t0 === 'install' ? 4 : _context.t0 === 'activate' ? 6 : 7;
        break;
      case 4:
        (0,src/* recordEvent */.yM)('install_plugins_error', {
          plugins: plugins.join(', '),
          message: rawErrorMessage
        });
        return _context.abrupt("break", 7);
      case 6:
        (0,src/* recordEvent */.yM)('activate_plugins_error', {
          plugins: plugins.join(', '),
          message: rawErrorMessage
        });
      case 7:
        throw new PluginError(formatErrorMessage(actionType, plugins, rawErrorMessage), error);
      case 8:
      case "end":
        return _context.stop();
    }
  }, plugins_actions_marked);
}

// Action Creator Generators
function installPlugins(plugins) {
  var async = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : false;
  return /*#__PURE__*/regenerator_default().mark(function _callee() {
    var _results$data$install, _results$errors, results;
    return regenerator_default().wrap(function _callee$(_context2) {
      while (1) switch (_context2.prev = _context2.next) {
        case 0:
          _context2.next = 2;
          return actions_setIsRequesting('installPlugins', true);
        case 2:
          _context2.prev = 2;
          _context2.next = 5;
          return (0,data_controls_build_module/* apiFetch */.nr)({
            path: "".concat(WC_ADMIN_NAMESPACE, "/plugins/install"),
            method: 'POST',
            data: {
              plugins: plugins.join(','),
              async: async
            }
          });
        case 5:
          results = _context2.sent;
          if (!((_results$data$install = results.data.installed) !== null && _results$data$install !== void 0 && _results$data$install.length)) {
            _context2.next = 9;
            break;
          }
          _context2.next = 9;
          return updateInstalledPlugins(results.data.installed);
        case 9:
          if (!((_results$errors = results.errors) !== null && _results$errors !== void 0 && _results$errors.errors && Object.keys(results.errors.errors).length)) {
            _context2.next = 11;
            break;
          }
          throw results.errors.errors;
        case 11:
          return _context2.abrupt("return", results);
        case 14:
          _context2.prev = 14;
          _context2.t0 = _context2["catch"](2);
          _context2.next = 18;
          return setError('installPlugins', _context2.t0);
        case 18:
          _context2.next = 20;
          return handlePluginAPIError('install', plugins, _context2.t0);
        case 20:
          _context2.prev = 20;
          _context2.next = 23;
          return actions_setIsRequesting('installPlugins', false);
        case 23:
          return _context2.finish(20);
        case 24:
        case "end":
          return _context2.stop();
      }
    }, _callee, null, [[2, 14, 20, 24]]);
  })();
}
function activatePlugins(plugins) {
  var results;
  return regenerator_default().wrap(function activatePlugins$(_context3) {
    while (1) switch (_context3.prev = _context3.next) {
      case 0:
        _context3.next = 2;
        return actions_setIsRequesting('activatePlugins', true);
      case 2:
        _context3.prev = 2;
        _context3.next = 5;
        return (0,data_controls_build_module/* apiFetch */.nr)({
          path: "".concat(WC_ADMIN_NAMESPACE, "/plugins/activate"),
          method: 'POST',
          data: {
            plugins: plugins.join(',')
          }
        });
      case 5:
        results = _context3.sent;
        if (!results.data.activated.length) {
          _context3.next = 9;
          break;
        }
        _context3.next = 9;
        return updateActivePlugins(results.data.activated);
      case 9:
        if (!Object.keys(results.errors.errors).length) {
          _context3.next = 11;
          break;
        }
        throw results.errors.errors;
      case 11:
        return _context3.abrupt("return", results);
      case 14:
        _context3.prev = 14;
        _context3.t0 = _context3["catch"](2);
        _context3.next = 18;
        return setError('activatePlugins', _context3.t0);
      case 18:
        _context3.next = 20;
        return handlePluginAPIError('activate', plugins, _context3.t0);
      case 20:
        _context3.prev = 20;
        _context3.next = 23;
        return actions_setIsRequesting('activatePlugins', false);
      case 23:
        return _context3.finish(20);
      case 24:
      case "end":
        return _context3.stop();
    }
  }, plugins_actions_marked2, null, [[2, 14, 20, 24]]);
}
function installAndActivatePlugins(plugins) {
  var installations, activations, response;
  return regenerator_default().wrap(function installAndActivatePlugins$(_context4) {
    while (1) switch (_context4.prev = _context4.next) {
      case 0:
        _context4.prev = 0;
        _context4.next = 3;
        return actions_dispatch(plugins_constants_STORE_NAME, 'installPlugins', plugins);
      case 3:
        installations = _context4.sent;
        _context4.next = 6;
        return actions_dispatch(plugins_constants_STORE_NAME, 'activatePlugins', plugins);
      case 6:
        activations = _context4.sent;
        response = actions_objectSpread(actions_objectSpread({}, activations), {}, {
          data: actions_objectSpread(actions_objectSpread({}, activations.data), installations.data)
        }); // If everything was a success and we both installed and activated, make the success message more informative.
        if (installations.success && Object.keys(installations.data.results).length && activations.success && activations.data.activated.length) {
          response.message = (0,i18n_build_module.__)('Plugins were successfully installed and activated.', 'woocommerce');
        }
        return _context4.abrupt("return", response);
      case 12:
        _context4.prev = 12;
        _context4.t0 = _context4["catch"](0);
        throw _context4.t0;
      case 15:
      case "end":
        return _context4.stop();
    }
  }, _marked3, null, [[0, 12]]);
}
function connectToJetpack(getAdminLink) {
  var url, error;
  return regenerator_default().wrap(function connectToJetpack$(_context5) {
    while (1) switch (_context5.prev = _context5.next) {
      case 0:
        _context5.next = 2;
        return actions_resolveSelect(plugins_constants_STORE_NAME, 'getJetpackConnectUrl', {
          redirect_url: getAdminLink('admin.php?page=wc-admin')
        });
      case 2:
        url = _context5.sent;
        _context5.next = 5;
        return actions_resolveSelect(plugins_constants_STORE_NAME, 'getPluginsError', 'getJetpackConnectUrl');
      case 5:
        error = _context5.sent;
        if (!error) {
          _context5.next = 10;
          break;
        }
        throw new Error(error);
      case 10:
        return _context5.abrupt("return", url);
      case 11:
      case "end":
        return _context5.stop();
    }
  }, _marked4);
}
function installJetpackAndConnect(errorAction, getAdminLink) {
  var url;
  return regenerator_default().wrap(function installJetpackAndConnect$(_context6) {
    while (1) switch (_context6.prev = _context6.next) {
      case 0:
        _context6.prev = 0;
        _context6.next = 3;
        return actions_dispatch(plugins_constants_STORE_NAME, 'installPlugins', ['jetpack']);
      case 3:
        _context6.next = 5;
        return actions_dispatch(plugins_constants_STORE_NAME, 'activatePlugins', ['jetpack']);
      case 5:
        _context6.next = 7;
        return actions_dispatch(plugins_constants_STORE_NAME, 'connectToJetpack', getAdminLink);
      case 7:
        url = _context6.sent;
        window.location.href = url;
        _context6.next = 19;
        break;
      case 11:
        _context6.prev = 11;
        _context6.t0 = _context6["catch"](0);
        if (!(_context6.t0 instanceof Error)) {
          _context6.next = 18;
          break;
        }
        _context6.next = 16;
        return errorAction(_context6.t0.message);
      case 16:
        _context6.next = 19;
        break;
      case 18:
        throw _context6.t0;
      case 19:
      case "end":
        return _context6.stop();
    }
  }, _marked5, null, [[0, 11]]);
}
function connectToJetpackWithFailureRedirect(failureRedirect, errorAction, getAdminLink) {
  var url;
  return regenerator_default().wrap(function connectToJetpackWithFailureRedirect$(_context7) {
    while (1) switch (_context7.prev = _context7.next) {
      case 0:
        _context7.prev = 0;
        _context7.next = 3;
        return actions_dispatch(plugins_constants_STORE_NAME, 'connectToJetpack', getAdminLink);
      case 3:
        url = _context7.sent;
        window.location.href = url;
        _context7.next = 16;
        break;
      case 7:
        _context7.prev = 7;
        _context7.t0 = _context7["catch"](0);
        if (!(_context7.t0 instanceof Error)) {
          _context7.next = 14;
          break;
        }
        _context7.next = 12;
        return errorAction(_context7.t0.message);
      case 12:
        _context7.next = 15;
        break;
      case 14:
        throw _context7.t0;
      case 15:
        window.location.href = failureRedirect;
      case 16:
      case "end":
        return _context7.stop();
    }
  }, _marked6, null, [[0, 7]]);
}
var SUPPORTED_TYPES = ['payments'];
function dismissRecommendedPlugins(type) {
  var plugins, success, url;
  return regenerator_default().wrap(function dismissRecommendedPlugins$(_context8) {
    while (1) switch (_context8.prev = _context8.next) {
      case 0:
        if (SUPPORTED_TYPES.includes(type)) {
          _context8.next = 2;
          break;
        }
        return _context8.abrupt("return", []);
      case 2:
        _context8.next = 4;
        return actions_resolveSelect(plugins_constants_STORE_NAME, 'getRecommendedPlugins', type);
      case 4:
        plugins = _context8.sent;
        _context8.next = 7;
        return setRecommendedPlugins(type, []);
      case 7:
        _context8.prev = 7;
        url = WC_ADMIN_NAMESPACE + '/payment-gateway-suggestions/dismiss';
        _context8.next = 11;
        return (0,data_controls_build_module/* apiFetch */.nr)({
          path: url,
          method: 'POST'
        });
      case 11:
        success = _context8.sent;
        _context8.next = 17;
        break;
      case 14:
        _context8.prev = 14;
        _context8.t0 = _context8["catch"](7);
        success = false;
      case 17:
        if (success) {
          _context8.next = 20;
          break;
        }
        _context8.next = 20;
        return setRecommendedPlugins(type, plugins);
      case 20:
        return _context8.abrupt("return", success);
      case 21:
      case "end":
        return _context8.stop();
    }
  }, _marked7, null, [[7, 14]]);
}

// Types
;// CONCATENATED MODULE: ../../packages/js/data/src/options/constants.ts
var options_constants_STORE_NAME = 'wc/admin/options';
;// CONCATENATED MODULE: ../../packages/js/data/src/options/selectors.ts
/**
 * Internal dependencies
 */

/**
 * Get option from state tree.
 *
 * @param {Object} state - Reducer state
 * @param {Array}  name  - Option name
 */
var getOption = function getOption(state, name) {
  return state[name];
};

/**
 * Determine if an options request resulted in an error.
 *
 * @param {Object} state - Reducer state
 * @param {string} name  - Option name
 */
var getOptionsRequestingError = function getOptionsRequestingError(state, name) {
  return state.requestingErrors[name] || false;
};

/**
 * Determine if options are being updated.
 *
 * @param {Object} state - Reducer state
 */
var isOptionsUpdating = function isOptionsUpdating(state) {
  return state.isUpdating || false;
};

/**
 * Determine if an options update resulted in an error.
 *
 * @param {Object} state - Reducer state
 */
var getOptionsUpdatingError = function getOptionsUpdatingError(state) {
  return state.updatingError || false;
};
;// CONCATENATED MODULE: ../../packages/js/data/src/options/action-types.ts
var action_types_TYPES = {
  RECEIVE_OPTIONS: 'RECEIVE_OPTIONS',
  SET_IS_REQUESTING: 'SET_IS_REQUESTING',
  SET_IS_UPDATING: 'SET_IS_UPDATING',
  SET_REQUESTING_ERROR: 'SET_REQUESTING_ERROR',
  SET_UPDATING_ERROR: 'SET_UPDATING_ERROR'
};
/* harmony default export */ const options_action_types = (action_types_TYPES);
;// CONCATENATED MODULE: ../../packages/js/data/src/options/actions.ts














var options_actions_marked = /*#__PURE__*/regenerator_default().mark(updateOptions);
function options_actions_ownKeys(e, r) {
  var t = Object.keys(e);
  if (Object.getOwnPropertySymbols) {
    var o = Object.getOwnPropertySymbols(e);
    r && (o = o.filter(function (r) {
      return Object.getOwnPropertyDescriptor(e, r).enumerable;
    })), t.push.apply(t, o);
  }
  return t;
}
function options_actions_objectSpread(e) {
  for (var r = 1; r < arguments.length; r++) {
    var t = null != arguments[r] ? arguments[r] : {};
    r % 2 ? options_actions_ownKeys(Object(t), !0).forEach(function (r) {
      (0,defineProperty/* default */.A)(e, r, t[r]);
    }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(e, Object.getOwnPropertyDescriptors(t)) : options_actions_ownKeys(Object(t)).forEach(function (r) {
      Object.defineProperty(e, r, Object.getOwnPropertyDescriptor(t, r));
    });
  }
  return e;
}
/**
 * External dependencies
 */


/**
 * Internal dependencies
 */


function receiveOptions(options) {
  return {
    type: options_action_types.RECEIVE_OPTIONS,
    options: options
  };
}
function setRequestingError(error, name) {
  return {
    type: options_action_types.SET_REQUESTING_ERROR,
    error: error,
    name: name
  };
}
function setUpdatingError(error) {
  return {
    type: options_action_types.SET_UPDATING_ERROR,
    error: error
  };
}
function setIsUpdating(isUpdating) {
  return {
    type: options_action_types.SET_IS_UPDATING,
    isUpdating: isUpdating
  };
}
function updateOptions(data) {
  var results;
  return regenerator_default().wrap(function updateOptions$(_context) {
    while (1) switch (_context.prev = _context.next) {
      case 0:
        _context.prev = 0;
        _context.next = 3;
        return setIsUpdating(true);
      case 3:
        _context.next = 5;
        return (0,data_controls_build_module/* apiFetch */.nr)({
          path: WC_ADMIN_NAMESPACE + '/options',
          method: 'POST',
          data: data
        });
      case 5:
        results = _context.sent;
        _context.next = 8;
        return setIsUpdating(false);
      case 8:
        if (!((0,esm_typeof/* default */.A)(results) !== 'object')) {
          _context.next = 10;
          break;
        }
        throw new Error("Invalid update options response from server: ".concat(results));
      case 10:
        _context.next = 12;
        return receiveOptions(data);
      case 12:
        return _context.abrupt("return", options_actions_objectSpread({
          success: true
        }, results));
      case 15:
        _context.prev = 15;
        _context.t0 = _context["catch"](0);
        _context.next = 19;
        return setUpdatingError(_context.t0);
      case 19:
        if (!((0,esm_typeof/* default */.A)(_context.t0) !== 'object')) {
          _context.next = 21;
          break;
        }
        throw new Error("Unexpected error: ".concat(_context.t0));
      case 21:
        return _context.abrupt("return", options_actions_objectSpread({
          success: false
        }, _context.t0));
      case 22:
      case "end":
        return _context.stop();
    }
  }, options_actions_marked, null, [[0, 15]]);
}
// EXTERNAL MODULE: ../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/asyncToGenerator.js
var asyncToGenerator = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/asyncToGenerator.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/web.timers.js
var web_timers = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/web.timers.js");
;// CONCATENATED MODULE: ../../packages/js/data/src/options/utils.ts


// TODO: Propose @woocommerce/base-utils package to be shared between packages and use debounce from there.

// eslint-disable-next-line @typescript-eslint/no-explicit-any

// eslint-disable-next-line @typescript-eslint/no-explicit-any
var debounce = function debounce(func, wait, immediate) {
  var timeout;
  var latestArgs = null;
  var debounced = function debounced() {
    for (var _len = arguments.length, args = new Array(_len), _key = 0; _key < _len; _key++) {
      args[_key] = arguments[_key];
    }
    latestArgs = args;
    if (timeout) clearTimeout(timeout);
    timeout = setTimeout(function () {
      timeout = null;
      if (!immediate && latestArgs) func.apply(void 0, (0,toConsumableArray/* default */.A)(latestArgs));
    }, wait);
    if (immediate && !timeout) func.apply(void 0, args);
  };
  debounced.flush = function () {
    if (timeout && latestArgs) {
      func.apply(void 0, (0,toConsumableArray/* default */.A)(latestArgs));
      clearTimeout(timeout);
      timeout = null;
    }
  };
  return debounced;
};
;// CONCATENATED MODULE: ../../packages/js/data/src/options/controls.ts



function controls_ownKeys(e, r) {
  var t = Object.keys(e);
  if (Object.getOwnPropertySymbols) {
    var o = Object.getOwnPropertySymbols(e);
    r && (o = o.filter(function (r) {
      return Object.getOwnPropertyDescriptor(e, r).enumerable;
    })), t.push.apply(t, o);
  }
  return t;
}
function controls_objectSpread(e) {
  for (var r = 1; r < arguments.length; r++) {
    var t = null != arguments[r] ? arguments[r] : {};
    r % 2 ? controls_ownKeys(Object(t), !0).forEach(function (r) {
      (0,defineProperty/* default */.A)(e, r, t[r]);
    }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(e, Object.getOwnPropertyDescriptors(t)) : controls_ownKeys(Object(t)).forEach(function (r) {
      Object.defineProperty(e, r, Object.getOwnPropertyDescriptor(t, r));
    });
  }
  return e;
}



















/**
 * External dependencies
 */


/**
 * Internal dependencies
 */


var batchFetch = function batchFetch(optionName) {
  return {
    type: 'BATCH_FETCH',
    optionName: optionName
  };
};
var optionNames = [];
var fetches = {};
var debouncedFetch = /*#__PURE__*/function () {
  var _ref = (0,asyncToGenerator/* default */.A)( /*#__PURE__*/regenerator_default().mark(function _callee3(optionName) {
    return regenerator_default().wrap(function _callee3$(_context3) {
      while (1) switch (_context3.prev = _context3.next) {
        case 0:
          return _context3.abrupt("return", new Promise( /*#__PURE__*/function () {
            var _ref2 = (0,asyncToGenerator/* default */.A)( /*#__PURE__*/regenerator_default().mark(function _callee2(resolve, reject) {
              return regenerator_default().wrap(function _callee2$(_context2) {
                while (1) switch (_context2.prev = _context2.next) {
                  case 0:
                    return _context2.abrupt("return", debounce(function () {
                      // If the option name is already being fetched, return the promise
                      if (fetches.hasOwnProperty(optionName)) {
                        return fetches[optionName].then(resolve)["catch"](reject);
                      }
                      if (optionNames.length === 0) {
                        // Previous batch fetch might fail
                        optionNames.push(optionName);
                      }

                      // Get unique option names
                      var uniqueOptionNames = (0,toConsumableArray/* default */.A)(new Set(optionNames));
                      var names = uniqueOptionNames.join(',');

                      // Send request for a group of options
                      var fetch = (0,api_fetch_build_module/* default */.A)({
                        path: "".concat(WC_ADMIN_NAMESPACE, "/options?options=").concat(names)
                      });
                      uniqueOptionNames.forEach( /*#__PURE__*/function () {
                        var _ref3 = (0,asyncToGenerator/* default */.A)( /*#__PURE__*/regenerator_default().mark(function _callee(option) {
                          return regenerator_default().wrap(function _callee$(_context) {
                            while (1) switch (_context.prev = _context.next) {
                              case 0:
                                fetches[option] = fetch;
                                _context.prev = 1;
                                _context.next = 4;
                                return fetch;
                              case 4:
                                _context.next = 8;
                                break;
                              case 6:
                                _context.prev = 6;
                                _context.t0 = _context["catch"](1);
                              case 8:
                                _context.prev = 8;
                                // Delete the fetch after completion to allow wp data to handle cache invalidation
                                delete fetches[option];
                                return _context.finish(8);
                              case 11:
                              case "end":
                                return _context.stop();
                            }
                          }, _callee, null, [[1, 6, 8, 11]]);
                        }));
                        return function (_x4) {
                          return _ref3.apply(this, arguments);
                        };
                      }());

                      // Clear option names after we've sent the request for a group of options
                      optionNames = [];
                      fetch.then(resolve)["catch"](reject);
                    }, 100)());
                  case 1:
                  case "end":
                    return _context2.stop();
                }
              }, _callee2);
            }));
            return function (_x2, _x3) {
              return _ref2.apply(this, arguments);
            };
          }() // 100ms debounce time for batch fetches (to avoid multiple fetches for the same options while not affecting user experience too much. Typically, values between 50ms and 200ms should provide a good balance for most applications.
          ));
        case 1:
        case "end":
          return _context3.stop();
      }
    }, _callee3);
  }));
  return function debouncedFetch(_x) {
    return _ref.apply(this, arguments);
  };
}();
var controls_controls = controls_objectSpread(controls_objectSpread({}, data_controls_build_module/* controls */.ne), {}, {
  BATCH_FETCH: function BATCH_FETCH(_ref4) {
    return (0,asyncToGenerator/* default */.A)( /*#__PURE__*/regenerator_default().mark(function _callee4() {
      var optionName;
      return regenerator_default().wrap(function _callee4$(_context4) {
        while (1) switch (_context4.prev = _context4.next) {
          case 0:
            optionName = _ref4.optionName;
            optionNames.push(optionName);

            // Consolidate multiple fetches into a single fetch
            _context4.next = 4;
            return debouncedFetch(optionName);
          case 4:
            return _context4.abrupt("return", _context4.sent);
          case 5:
          case "end":
            return _context4.stop();
        }
      }, _callee4);
    }))();
  }
});
;// CONCATENATED MODULE: ../../packages/js/data/src/options/resolvers.ts


var options_resolvers_marked = /*#__PURE__*/regenerator_default().mark(resolvers_getOption);
/**
 * Internal dependencies
 */


/**
 * Request an option value.
 *
 * @param {string} name - Option name
 */
function resolvers_getOption(name) {
  var result;
  return regenerator_default().wrap(function getOption$(_context) {
    while (1) switch (_context.prev = _context.next) {
      case 0:
        _context.prev = 0;
        _context.next = 3;
        return batchFetch(name);
      case 3:
        result = _context.sent;
        _context.next = 6;
        return receiveOptions(result);
      case 6:
        _context.next = 12;
        break;
      case 8:
        _context.prev = 8;
        _context.t0 = _context["catch"](0);
        _context.next = 12;
        return setRequestingError(_context.t0, name);
      case 12:
      case "end":
        return _context.stop();
    }
  }, options_resolvers_marked, null, [[0, 8]]);
}
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.function.name.js
var es_function_name = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.function.name.js");
;// CONCATENATED MODULE: ../../packages/js/data/src/options/reducer.ts












function options_reducer_ownKeys(e, r) {
  var t = Object.keys(e);
  if (Object.getOwnPropertySymbols) {
    var o = Object.getOwnPropertySymbols(e);
    r && (o = o.filter(function (r) {
      return Object.getOwnPropertyDescriptor(e, r).enumerable;
    })), t.push.apply(t, o);
  }
  return t;
}
function options_reducer_objectSpread(e) {
  for (var r = 1; r < arguments.length; r++) {
    var t = null != arguments[r] ? arguments[r] : {};
    r % 2 ? options_reducer_ownKeys(Object(t), !0).forEach(function (r) {
      (0,defineProperty/* default */.A)(e, r, t[r]);
    }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(e, Object.getOwnPropertyDescriptors(t)) : options_reducer_ownKeys(Object(t)).forEach(function (r) {
      Object.defineProperty(e, r, Object.getOwnPropertyDescriptor(t, r));
    });
  }
  return e;
}
/**
 * External dependencies
 */

/**
 * Internal dependencies
 */

var optionsReducer = function optionsReducer() {
  var state = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {
    isUpdating: false,
    requestingErrors: {}
  };
  var action = arguments.length > 1 ? arguments[1] : undefined;
  switch (action.type) {
    case options_action_types.RECEIVE_OPTIONS:
      state = options_reducer_objectSpread(options_reducer_objectSpread({}, state), action.options);
      break;
    case options_action_types.SET_IS_UPDATING:
      state = options_reducer_objectSpread(options_reducer_objectSpread({}, state), {}, {
        isUpdating: action.isUpdating
      });
      break;
    case options_action_types.SET_REQUESTING_ERROR:
      state = options_reducer_objectSpread(options_reducer_objectSpread({}, state), {}, {
        requestingErrors: (0,defineProperty/* default */.A)({}, action.name, action.error)
      });
      break;
    case options_action_types.SET_UPDATING_ERROR:
      state = options_reducer_objectSpread(options_reducer_objectSpread({}, state), {}, {
        error: action.error,
        updatingError: action.error,
        isUpdating: false
      });
      break;
  }
  return state;
};
/* harmony default export */ const options_reducer = (optionsReducer);
;// CONCATENATED MODULE: ../../packages/js/data/src/options/index.ts
/**
 * External dependencies
 */

/**
 * Internal dependencies
 */







(0,data_build_module/* registerStore */.ti)(options_constants_STORE_NAME, {
  reducer: options_reducer,
  actions: options_actions_namespaceObject,
  controls: controls_controls,
  selectors: options_selectors_namespaceObject,
  resolvers: options_resolvers_namespaceObject
});
var OPTIONS_STORE_NAME = options_constants_STORE_NAME;
;// CONCATENATED MODULE: ../../packages/js/data/src/plugins/resolvers.ts



var plugins_resolvers_marked = /*#__PURE__*/regenerator_default().mark(resolvers_getActivePlugins),
  plugins_resolvers_marked2 = /*#__PURE__*/regenerator_default().mark(resolvers_getInstalledPlugins),
  resolvers_marked3 = /*#__PURE__*/regenerator_default().mark(resolvers_isJetpackConnected),
  resolvers_marked4 = /*#__PURE__*/regenerator_default().mark(resolvers_getJetpackConnectionData),
  resolvers_marked5 = /*#__PURE__*/regenerator_default().mark(resolvers_getJetpackConnectUrl),
  resolvers_marked6 = /*#__PURE__*/regenerator_default().mark(setOnboardingStatusWithOptions),
  resolvers_marked7 = /*#__PURE__*/regenerator_default().mark(resolvers_getPaypalOnboardingStatus),
  _marked8 = /*#__PURE__*/regenerator_default().mark(resolvers_getRecommendedPlugins);
/**
 * External dependencies
 */




/**
 * Internal dependencies
 */






// Can be removed in WP 5.9, wp.data is supported in >5.7.
var resolvers_resolveSelect = build_module_controls/* controls */.n && build_module_controls/* controls */.n.resolveSelect ? build_module_controls/* controls */.n.resolveSelect : data_controls_build_module/* select */.Lt;
function resolvers_getActivePlugins() {
  var url, results;
  return regenerator_default().wrap(function getActivePlugins$(_context) {
    while (1) switch (_context.prev = _context.next) {
      case 0:
        _context.next = 2;
        return actions_setIsRequesting('getActivePlugins', true);
      case 2:
        _context.prev = 2;
        _context.next = 5;
        return checkUserCapability('manage_woocommerce');
      case 5:
        url = WC_ADMIN_NAMESPACE + '/plugins/active';
        _context.next = 8;
        return (0,data_controls_build_module/* apiFetch */.nr)({
          path: url,
          method: 'GET'
        });
      case 8:
        results = _context.sent;
        _context.next = 11;
        return updateActivePlugins(results.plugins, true);
      case 11:
        _context.next = 17;
        break;
      case 13:
        _context.prev = 13;
        _context.t0 = _context["catch"](2);
        _context.next = 17;
        return setError('getActivePlugins', _context.t0);
      case 17:
      case "end":
        return _context.stop();
    }
  }, plugins_resolvers_marked, null, [[2, 13]]);
}
function resolvers_getInstalledPlugins() {
  var url, results;
  return regenerator_default().wrap(function getInstalledPlugins$(_context2) {
    while (1) switch (_context2.prev = _context2.next) {
      case 0:
        _context2.next = 2;
        return actions_setIsRequesting('getInstalledPlugins', true);
      case 2:
        _context2.prev = 2;
        _context2.next = 5;
        return checkUserCapability('manage_woocommerce');
      case 5:
        url = WC_ADMIN_NAMESPACE + '/plugins/installed';
        _context2.next = 8;
        return (0,data_controls_build_module/* apiFetch */.nr)({
          path: url,
          method: 'GET'
        });
      case 8:
        results = _context2.sent;
        _context2.next = 11;
        return updateInstalledPlugins(results.plugins, true);
      case 11:
        _context2.next = 17;
        break;
      case 13:
        _context2.prev = 13;
        _context2.t0 = _context2["catch"](2);
        _context2.next = 17;
        return setError('getInstalledPlugins', _context2.t0);
      case 17:
      case "end":
        return _context2.stop();
    }
  }, plugins_resolvers_marked2, null, [[2, 13]]);
}
function resolvers_isJetpackConnected() {
  var url, results;
  return regenerator_default().wrap(function isJetpackConnected$(_context3) {
    while (1) switch (_context3.prev = _context3.next) {
      case 0:
        _context3.next = 2;
        return actions_setIsRequesting('isJetpackConnected', true);
      case 2:
        _context3.prev = 2;
        url = JETPACK_NAMESPACE + '/connection';
        _context3.next = 6;
        return (0,data_controls_build_module/* apiFetch */.nr)({
          path: url,
          method: 'GET'
        });
      case 6:
        results = _context3.sent;
        _context3.next = 9;
        return updateIsJetpackConnected(results.hasConnectedOwner);
      case 9:
        _context3.next = 15;
        break;
      case 11:
        _context3.prev = 11;
        _context3.t0 = _context3["catch"](2);
        _context3.next = 15;
        return setError('isJetpackConnected', _context3.t0);
      case 15:
        _context3.next = 17;
        return actions_setIsRequesting('isJetpackConnected', false);
      case 17:
      case "end":
        return _context3.stop();
    }
  }, resolvers_marked3, null, [[2, 11]]);
}
function resolvers_getJetpackConnectionData() {
  var url, results;
  return regenerator_default().wrap(function getJetpackConnectionData$(_context4) {
    while (1) switch (_context4.prev = _context4.next) {
      case 0:
        _context4.next = 2;
        return actions_setIsRequesting('getJetpackConnectionData', true);
      case 2:
        _context4.prev = 2;
        _context4.next = 5;
        return checkUserCapability('manage_woocommerce');
      case 5:
        url = JETPACK_NAMESPACE + '/connection/data';
        _context4.next = 8;
        return (0,data_controls_build_module/* apiFetch */.nr)({
          path: url,
          method: 'GET'
        });
      case 8:
        results = _context4.sent;
        _context4.next = 11;
        return updateJetpackConnectionData(results);
      case 11:
        _context4.next = 17;
        break;
      case 13:
        _context4.prev = 13;
        _context4.t0 = _context4["catch"](2);
        _context4.next = 17;
        return setError('getJetpackConnectionData', _context4.t0);
      case 17:
        _context4.next = 19;
        return actions_setIsRequesting('getJetpackConnectionData', false);
      case 19:
      case "end":
        return _context4.stop();
    }
  }, resolvers_marked4, null, [[2, 13]]);
}
function resolvers_getJetpackConnectUrl(query) {
  var url, results;
  return regenerator_default().wrap(function getJetpackConnectUrl$(_context5) {
    while (1) switch (_context5.prev = _context5.next) {
      case 0:
        _context5.next = 2;
        return actions_setIsRequesting('getJetpackConnectUrl', true);
      case 2:
        _context5.prev = 2;
        url = (0,add_query_args/* addQueryArgs */.F)(WC_ADMIN_NAMESPACE + '/plugins/connect-jetpack', query);
        _context5.next = 6;
        return (0,data_controls_build_module/* apiFetch */.nr)({
          path: url,
          method: 'GET'
        });
      case 6:
        results = _context5.sent;
        _context5.next = 9;
        return updateJetpackConnectUrl(query.redirect_url, results.connectAction);
      case 9:
        _context5.next = 15;
        break;
      case 11:
        _context5.prev = 11;
        _context5.t0 = _context5["catch"](2);
        _context5.next = 15;
        return setError('getJetpackConnectUrl', _context5.t0);
      case 15:
        _context5.next = 17;
        return actions_setIsRequesting('getJetpackConnectUrl', false);
      case 17:
      case "end":
        return _context5.stop();
    }
  }, resolvers_marked5, null, [[2, 11]]);
}
function setOnboardingStatusWithOptions() {
  var options, onboarded;
  return regenerator_default().wrap(function setOnboardingStatusWithOptions$(_context6) {
    while (1) switch (_context6.prev = _context6.next) {
      case 0:
        _context6.next = 2;
        return resolvers_resolveSelect(OPTIONS_STORE_NAME, 'getOption', 'woocommerce-ppcp-settings');
      case 2:
        options = _context6.sent;
        onboarded = options.merchant_email_production && options.merchant_id_production && options.client_id_production && options.client_secret_production;
        _context6.next = 6;
        return setPaypalOnboardingStatus({
          production: {
            state: onboarded ? 'onboarded' : 'unknown',
            onboarded: onboarded ? true : false
          }
        });
      case 6:
      case "end":
        return _context6.stop();
    }
  }, resolvers_marked6);
}
function resolvers_getPaypalOnboardingStatus() {
  var errorData, url, results;
  return regenerator_default().wrap(function getPaypalOnboardingStatus$(_context7) {
    while (1) switch (_context7.prev = _context7.next) {
      case 0:
        _context7.next = 2;
        return actions_setIsRequesting('getPaypalOnboardingStatus', true);
      case 2:
        _context7.next = 4;
        return resolvers_resolveSelect(plugins_constants_STORE_NAME, 'getPluginsError', 'getPaypalOnboardingStatus');
      case 4:
        errorData = _context7.sent;
        if (!(errorData && errorData.data && errorData.data.status === 404)) {
          _context7.next = 10;
          break;
        }
        _context7.next = 8;
        return setOnboardingStatusWithOptions();
      case 8:
        _context7.next = 25;
        break;
      case 10:
        _context7.prev = 10;
        url = PAYPAL_NAMESPACE + '/onboarding/get-status';
        _context7.next = 14;
        return (0,data_controls_build_module/* apiFetch */.nr)({
          path: url,
          method: 'GET'
        });
      case 14:
        results = _context7.sent;
        _context7.next = 17;
        return setPaypalOnboardingStatus(results);
      case 17:
        _context7.next = 25;
        break;
      case 19:
        _context7.prev = 19;
        _context7.t0 = _context7["catch"](10);
        _context7.next = 23;
        return setOnboardingStatusWithOptions();
      case 23:
        _context7.next = 25;
        return setError('getPaypalOnboardingStatus', _context7.t0);
      case 25:
        _context7.next = 27;
        return actions_setIsRequesting('getPaypalOnboardingStatus', false);
      case 27:
      case "end":
        return _context7.stop();
    }
  }, resolvers_marked7, null, [[10, 19]]);
}
var resolvers_SUPPORTED_TYPES = ['payments'];
function resolvers_getRecommendedPlugins(type) {
  var url, results;
  return regenerator_default().wrap(function getRecommendedPlugins$(_context8) {
    while (1) switch (_context8.prev = _context8.next) {
      case 0:
        if (resolvers_SUPPORTED_TYPES.includes(type)) {
          _context8.next = 2;
          break;
        }
        return _context8.abrupt("return", []);
      case 2:
        _context8.next = 4;
        return actions_setIsRequesting('getRecommendedPlugins', true);
      case 4:
        _context8.prev = 4;
        url = WC_ADMIN_NAMESPACE + '/payment-gateway-suggestions';
        _context8.next = 8;
        return (0,data_controls_build_module/* apiFetch */.nr)({
          path: url,
          method: 'GET'
        });
      case 8:
        results = _context8.sent;
        _context8.next = 11;
        return setRecommendedPlugins(type, results);
      case 11:
        _context8.next = 17;
        break;
      case 13:
        _context8.prev = 13;
        _context8.t0 = _context8["catch"](4);
        _context8.next = 17;
        return setError('getRecommendedPlugins', _context8.t0);
      case 17:
        _context8.next = 19;
        return actions_setIsRequesting('getRecommendedPlugins', false);
      case 19:
      case "end":
        return _context8.stop();
    }
  }, _marked8, null, [[4, 13]]);
}
;// CONCATENATED MODULE: ../../packages/js/data/src/plugins/reducer.ts













function plugins_reducer_ownKeys(e, r) {
  var t = Object.keys(e);
  if (Object.getOwnPropertySymbols) {
    var o = Object.getOwnPropertySymbols(e);
    r && (o = o.filter(function (r) {
      return Object.getOwnPropertyDescriptor(e, r).enumerable;
    })), t.push.apply(t, o);
  }
  return t;
}
function plugins_reducer_objectSpread(e) {
  for (var r = 1; r < arguments.length; r++) {
    var t = null != arguments[r] ? arguments[r] : {};
    r % 2 ? plugins_reducer_ownKeys(Object(t), !0).forEach(function (r) {
      (0,defineProperty/* default */.A)(e, r, t[r]);
    }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(e, Object.getOwnPropertyDescriptors(t)) : plugins_reducer_ownKeys(Object(t)).forEach(function (r) {
      Object.defineProperty(e, r, Object.getOwnPropertyDescriptor(t, r));
    });
  }
  return e;
}
/**
 * External dependencies
 */

/**
 * Internal dependencies
 */

var reducer_reducer = function reducer() {
  var state = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {
    active: [],
    installed: [],
    requesting: {},
    errors: {},
    jetpackConnectUrls: {},
    recommended: {}
  };
  var payload = arguments.length > 1 ? arguments[1] : undefined;
  if (payload && 'type' in payload) {
    switch (payload.type) {
      case ACTION_TYPES.UPDATE_ACTIVE_PLUGINS:
        state = plugins_reducer_objectSpread(plugins_reducer_objectSpread({}, state), {}, {
          active: payload.replace ? payload.active : (0,lodash.concat)(state.active, payload.active),
          requesting: plugins_reducer_objectSpread(plugins_reducer_objectSpread({}, state.requesting), {}, {
            getActivePlugins: false,
            activatePlugins: false
          }),
          errors: plugins_reducer_objectSpread(plugins_reducer_objectSpread({}, state.errors), {}, {
            getActivePlugins: false,
            activatePlugins: false
          })
        });
        break;
      case ACTION_TYPES.UPDATE_INSTALLED_PLUGINS:
        state = plugins_reducer_objectSpread(plugins_reducer_objectSpread({}, state), {}, {
          installed: payload.replace ? payload.installed : (0,lodash.concat)(state.installed, payload.installed),
          requesting: plugins_reducer_objectSpread(plugins_reducer_objectSpread({}, state.requesting), {}, {
            getInstalledPlugins: false,
            installPlugins: false
          }),
          errors: plugins_reducer_objectSpread(plugins_reducer_objectSpread({}, state.errors), {}, {
            getInstalledPlugins: false,
            installPlugin: false
          })
        });
        break;
      case ACTION_TYPES.SET_IS_REQUESTING:
        state = plugins_reducer_objectSpread(plugins_reducer_objectSpread({}, state), {}, {
          requesting: plugins_reducer_objectSpread(plugins_reducer_objectSpread({}, state.requesting), {}, (0,defineProperty/* default */.A)({}, payload.selector, payload.isRequesting))
        });
        break;
      case ACTION_TYPES.SET_ERROR:
        state = plugins_reducer_objectSpread(plugins_reducer_objectSpread({}, state), {}, {
          requesting: plugins_reducer_objectSpread(plugins_reducer_objectSpread({}, state.requesting), {}, (0,defineProperty/* default */.A)({}, payload.selector, false)),
          errors: plugins_reducer_objectSpread(plugins_reducer_objectSpread({}, state.errors), {}, (0,defineProperty/* default */.A)({}, payload.selector, payload.error))
        });
        break;
      case ACTION_TYPES.UPDATE_JETPACK_CONNECTION:
        state = plugins_reducer_objectSpread(plugins_reducer_objectSpread({}, state), {}, {
          jetpackConnection: payload.jetpackConnection
        });
        break;
      case ACTION_TYPES.UPDATE_JETPACK_CONNECTION_DATA:
        state = plugins_reducer_objectSpread(plugins_reducer_objectSpread({}, state), {}, {
          jetpackConnectionData: payload.results
        });
        break;
      case ACTION_TYPES.UPDATE_JETPACK_CONNECT_URL:
        state = plugins_reducer_objectSpread(plugins_reducer_objectSpread({}, state), {}, {
          jetpackConnectUrls: plugins_reducer_objectSpread(plugins_reducer_objectSpread({}, state.jetpackConnectUrls), {}, (0,defineProperty/* default */.A)({}, payload.redirectUrl, payload.jetpackConnectUrl))
        });
        break;
      case ACTION_TYPES.SET_PAYPAL_ONBOARDING_STATUS:
        state = plugins_reducer_objectSpread(plugins_reducer_objectSpread({}, state), {}, {
          paypalOnboardingStatus: payload.paypalOnboardingStatus
        });
        break;
      case ACTION_TYPES.SET_RECOMMENDED_PLUGINS:
        state = plugins_reducer_objectSpread(plugins_reducer_objectSpread({}, state), {}, {
          recommended: plugins_reducer_objectSpread(plugins_reducer_objectSpread({}, state.recommended), {}, (0,defineProperty/* default */.A)({}, payload.recommendedType, payload.plugins))
        });
        break;
    }
  }
  return state;
};
/* harmony default export */ const plugins_reducer = (reducer_reducer);
;// CONCATENATED MODULE: ../../packages/js/data/src/plugins/index.ts
/**
 * External dependencies
 */


/**
 * Internal dependencies
 */






(0,data_build_module/* registerStore */.ti)(plugins_constants_STORE_NAME, {
  reducer: plugins_reducer,
  actions: plugins_actions_namespaceObject,
  controls: data_controls_build_module/* controls */.ne,
  selectors: plugins_selectors_namespaceObject,
  resolvers: plugins_resolvers_namespaceObject
});
var PLUGINS_STORE_NAME = (/* unused pure expression or super */ null && (STORE_NAME));
;// CONCATENATED MODULE: ../../packages/js/data/src/onboarding/constants.ts
/**
 * Internal dependencies
 */
var onboarding_constants_STORE_NAME = 'wc/admin/onboarding';
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.find.js
var es_array_find = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.find.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/rememo@4.0.2/node_modules/rememo/rememo.js
var rememo = __webpack_require__("../../node_modules/.pnpm/rememo@4.0.2/node_modules/rememo/rememo.js");
;// CONCATENATED MODULE: ../../packages/js/data/src/onboarding/selectors.ts






/**
 * External dependencies
 */


/**
 * Internal dependencies
 */

var getFreeExtensions = function getFreeExtensions(state) {
  return state.freeExtensions || [];
};
var getProfileItems = function getProfileItems(state) {
  return state.profileItems || {};
};
var getTaskLists = (0,rememo/* default */.A)(function (state) {
  return Object.values(state.taskLists);
}, function (state) {
  return [state.taskLists];
});
var getTaskListsByIds = (0,rememo/* default */.A)(function (state, ids) {
  return ids.map(function (id) {
    return state.taskLists[id];
  });
}, function (state, ids) {
  return ids.map(function (id) {
    return state.taskLists[id];
  });
});
var getTaskList = function getTaskList(state, selector) {
  return state.taskLists[selector];
};
var getTask = function getTask(state, selector) {
  return Object.keys(state.taskLists).reduce(function (value, listId) {
    return value || state.taskLists[listId].tasks.find(function (task) {
      return task.id === selector;
    });
  }, undefined);
};
var getPaymentGatewaySuggestions = function getPaymentGatewaySuggestions(state) {
  return state.paymentMethods || [];
};
var getOnboardingError = function getOnboardingError(state, selector) {
  return state.errors[selector] || false;
};
var isOnboardingRequesting = function isOnboardingRequesting(state, selector) {
  return state.requesting[selector] || false;
};
var getEmailPrefill = function getEmailPrefill(state) {
  return state.emailPrefill || '';
};
var getProductTypes = function getProductTypes(state) {
  return state.productTypes || {};
};
var getJetpackAuthUrl = function getJetpackAuthUrl(state, query) {
  return state.jetpackAuthUrls[query.redirectUrl] || '';
};
;// CONCATENATED MODULE: ../../packages/js/data/src/onboarding/action-types.ts
var onboarding_action_types_TYPES = {
  SET_ERROR: 'SET_ERROR',
  SET_IS_REQUESTING: 'SET_IS_REQUESTING',
  SET_PROFILE_ITEMS: 'SET_PROFILE_ITEMS',
  SET_EMAIL_PREFILL: 'SET_EMAIL_PREFILL',
  GET_PAYMENT_METHODS_SUCCESS: 'GET_PAYMENT_METHODS_SUCCESS',
  GET_PRODUCT_TYPES_SUCCESS: 'GET_PRODUCT_TYPES_SUCCESS',
  GET_PRODUCT_TYPES_ERROR: 'GET_PRODUCT_TYPES_ERROR',
  GET_FREE_EXTENSIONS_ERROR: 'GET_FREE_EXTENSIONS_ERROR',
  GET_FREE_EXTENSIONS_SUCCESS: 'GET_FREE_EXTENSIONS_SUCCESS',
  GET_TASK_LISTS_ERROR: 'GET_TASK_LISTS_ERROR',
  GET_TASK_LISTS_SUCCESS: 'GET_TASK_LISTS_SUCCESS',
  DISMISS_TASK_ERROR: 'DISMISS_TASK_ERROR',
  DISMISS_TASK_REQUEST: 'DISMISS_TASK_REQUEST',
  DISMISS_TASK_SUCCESS: 'DISMISS_TASK_SUCCESS',
  UNDO_DISMISS_TASK_ERROR: 'UNDO_DISMISS_TASK_ERROR',
  UNDO_DISMISS_TASK_REQUEST: 'UNDO_DISMISS_TASK_REQUEST',
  UNDO_DISMISS_TASK_SUCCESS: 'UNDO_DISMISS_TASK_SUCCESS',
  SNOOZE_TASK_ERROR: 'SNOOZE_TASK_ERROR',
  SNOOZE_TASK_REQUEST: 'SNOOZE_TASK_REQUEST',
  SNOOZE_TASK_SUCCESS: 'SNOOZE_TASK_SUCCESS',
  UNDO_SNOOZE_TASK_ERROR: 'UNDO_SNOOZE_TASK_ERROR',
  UNDO_SNOOZE_TASK_REQUEST: 'UNDO_SNOOZE_TASK_REQUEST',
  UNDO_SNOOZE_TASK_SUCCESS: 'UNDO_SNOOZE_TASK_SUCCESS',
  HIDE_TASK_LIST_ERROR: 'HIDE_TASK_LIST_ERROR',
  HIDE_TASK_LIST_REQUEST: 'HIDE_TASK_LIST_REQUEST',
  HIDE_TASK_LIST_SUCCESS: 'HIDE_TASK_LIST_SUCCESS',
  UNHIDE_TASK_LIST_ERROR: 'UNHIDE_TASK_LIST_ERROR',
  UNHIDE_TASK_LIST_REQUEST: 'UNHIDE_TASK_LIST_REQUEST',
  UNHIDE_TASK_LIST_SUCCESS: 'UNHIDE_TASK_LIST_SUCCESS',
  OPTIMISTICALLY_COMPLETE_TASK_REQUEST: 'OPTIMISTICALLY_COMPLETE_TASK_REQUEST',
  ACTION_TASK_ERROR: 'ACTION_TASK_ERROR',
  ACTION_TASK_REQUEST: 'ACTION_TASK_REQUEST',
  ACTION_TASK_SUCCESS: 'ACTION_TASK_SUCCESS',
  VISITED_TASK: 'VISITED_TASK',
  KEEP_COMPLETED_TASKS_REQUEST: 'KEEP_COMPLETED_TASKS_REQUEST',
  KEEP_COMPLETED_TASKS_SUCCESS: 'KEEP_COMPLETED_TASKS_SUCCESS',
  SET_JETPACK_AUTH_URL: 'SET_JETPACK_AUTH_URL',
  CORE_PROFILER_COMPLETED_REQUEST: 'CORE_PROFILER_COMPLETED_REQUEST',
  CORE_PROFILER_COMPLETED_SUCCESS: 'CORE_PROFILER_COMPLETED_SUCCESS',
  CORE_PROFILER_COMPLETED_ERROR: 'CORE_PROFILER_COMPLETED_ERROR'
};
/* harmony default export */ const onboarding_action_types = (onboarding_action_types_TYPES);
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.slice.js
var es_array_slice = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.slice.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.regexp.to-string.js
var es_regexp_to_string = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.regexp.to-string.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.from.js
var es_array_from = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.from.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.symbol.description.js
var es_symbol_description = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.symbol.description.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.symbol.iterator.js
var es_symbol_iterator = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.symbol.iterator.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.string.search.js
var es_string_search = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.string.search.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+hooks@3.6.1/node_modules/@wordpress/hooks/build-module/index.js + 10 modules
var hooks_build_module = __webpack_require__("../../node_modules/.pnpm/@wordpress+hooks@3.6.1/node_modules/@wordpress/hooks/build-module/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/qs@6.11.2/node_modules/qs/lib/index.js
var lib = __webpack_require__("../../node_modules/.pnpm/qs@6.11.2/node_modules/qs/lib/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+deprecated@3.6.1/node_modules/@wordpress/deprecated/build-module/index.js
var deprecated_build_module = __webpack_require__("../../node_modules/.pnpm/@wordpress+deprecated@3.6.1/node_modules/@wordpress/deprecated/build-module/index.js");
;// CONCATENATED MODULE: ../../packages/js/data/src/onboarding/deprecated-tasks.ts























function _createForOfIteratorHelper(o, allowArrayLike) {
  var it = typeof Symbol !== "undefined" && o[Symbol.iterator] || o["@@iterator"];
  if (!it) {
    if (Array.isArray(o) || (it = _unsupportedIterableToArray(o)) || allowArrayLike && o && typeof o.length === "number") {
      if (it) o = it;
      var i = 0;
      var F = function F() {};
      return {
        s: F,
        n: function n() {
          if (i >= o.length) return {
            done: true
          };
          return {
            done: false,
            value: o[i++]
          };
        },
        e: function e(_e) {
          throw _e;
        },
        f: F
      };
    }
    throw new TypeError("Invalid attempt to iterate non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method.");
  }
  var normalCompletion = true,
    didErr = false,
    err;
  return {
    s: function s() {
      it = it.call(o);
    },
    n: function n() {
      var step = it.next();
      normalCompletion = step.done;
      return step;
    },
    e: function e(_e2) {
      didErr = true;
      err = _e2;
    },
    f: function f() {
      try {
        if (!normalCompletion && it["return"] != null) it["return"]();
      } finally {
        if (didErr) throw err;
      }
    }
  };
}
function _unsupportedIterableToArray(o, minLen) {
  if (!o) return;
  if (typeof o === "string") return _arrayLikeToArray(o, minLen);
  var n = Object.prototype.toString.call(o).slice(8, -1);
  if (n === "Object" && o.constructor) n = o.constructor.name;
  if (n === "Map" || n === "Set") return Array.from(o);
  if (n === "Arguments" || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)) return _arrayLikeToArray(o, minLen);
}
function _arrayLikeToArray(arr, len) {
  if (len == null || len > arr.length) len = arr.length;
  for (var i = 0, arr2 = new Array(len); i < len; i++) arr2[i] = arr[i];
  return arr2;
}
function deprecated_tasks_ownKeys(e, r) {
  var t = Object.keys(e);
  if (Object.getOwnPropertySymbols) {
    var o = Object.getOwnPropertySymbols(e);
    r && (o = o.filter(function (r) {
      return Object.getOwnPropertyDescriptor(e, r).enumerable;
    })), t.push.apply(t, o);
  }
  return t;
}
function deprecated_tasks_objectSpread(e) {
  for (var r = 1; r < arguments.length; r++) {
    var t = null != arguments[r] ? arguments[r] : {};
    r % 2 ? deprecated_tasks_ownKeys(Object(t), !0).forEach(function (r) {
      (0,defineProperty/* default */.A)(e, r, t[r]);
    }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(e, Object.getOwnPropertyDescriptors(t)) : deprecated_tasks_ownKeys(Object(t)).forEach(function (r) {
      Object.defineProperty(e, r, Object.getOwnPropertyDescriptor(t, r));
    });
  }
  return e;
}






/**
 * External dependencies
 */




/**
 * Internal dependencies
 */

function getQuery() {
  var searchString = window.location && window.location.search;
  if (!searchString) {
    return {};
  }
  var search = searchString.substring(1);
  return (0,lib.parse)(search);
}

/**
 * A simple class to handle deprecated tasks using the woocommerce_admin_onboarding_task_list filter.
 */
var DeprecatedTasks = /*#__PURE__*/function () {
  function DeprecatedTasks() {
    (0,classCallCheck/* default */.A)(this, DeprecatedTasks);
    (0,defineProperty/* default */.A)(this, "filteredTasks", void 0);
    (0,defineProperty/* default */.A)(this, "tasks", void 0);
    /**
     * **Deprecated** Filter Onboarding tasks.
     *
     * @filter woocommerce_admin_onboarding_task_list
     * @deprecated
     * @param {Array} tasks Array of tasks.
     * @param {Array} query Url query parameters.
     */
    this.filteredTasks = (0,hooks_build_module/* applyFilters */.W5)('woocommerce_admin_onboarding_task_list', [], getQuery());
    if (this.filteredTasks && this.filteredTasks.length > 0) {
      (0,deprecated_build_module/* default */.A)('woocommerce_admin_onboarding_task_list', {
        version: '2.10.0',
        alternative: 'TaskLists::add_task()',
        plugin: '@woocommerce/data'
      });
    }
    this.tasks = this.filteredTasks.reduce(function (org, task) {
      return deprecated_tasks_objectSpread(deprecated_tasks_objectSpread({}, org), {}, (0,defineProperty/* default */.A)({}, task.key, task));
    }, {});
  }
  (0,createClass/* default */.A)(DeprecatedTasks, [{
    key: "hasDeprecatedTasks",
    value: function hasDeprecatedTasks() {
      return this.filteredTasks.length > 0;
    }
  }, {
    key: "getPostData",
    value: function getPostData() {
      return this.hasDeprecatedTasks() ? {
        extended_tasks: this.filteredTasks.map(function (task) {
          return {
            title: task.title,
            content: task.content,
            additional_info: task.additionalInfo,
            time: task.time,
            level: task.level ? parseInt(task.level, 10) : 3,
            list_id: task.type || 'extended',
            can_view: task.visible,
            id: task.key,
            is_snoozeable: task.allowRemindMeLater,
            is_dismissable: task.isDismissable,
            is_complete: task.completed
          };
        })
      } : null;
    }
  }, {
    key: "mergeDeprecatedCallbackFunctions",
    value: function mergeDeprecatedCallbackFunctions(taskLists) {
      var _this = this;
      if (this.filteredTasks.length > 0) {
        var _iterator = _createForOfIteratorHelper(taskLists),
          _step;
        try {
          for (_iterator.s(); !(_step = _iterator.n()).done;) {
            var taskList = _step.value;
            // Merge any extended task list items, to keep the callback functions around.
            taskList.tasks = taskList.tasks.map(function (task) {
              if (_this.tasks && _this.tasks[task.id]) {
                return deprecated_tasks_objectSpread(deprecated_tasks_objectSpread(deprecated_tasks_objectSpread({}, _this.tasks[task.id]), task), {}, {
                  isDeprecated: true
                });
              }
              return task;
            });
          }
        } catch (err) {
          _iterator.e(err);
        } finally {
          _iterator.f();
        }
      }
      return taskLists;
    }

    /**
     * Used to keep backwards compatibility with the extended task list filter on the client.
     * This can be removed after version WC Admin 2.10 (see deprecated notice in resolvers.js).
     *
     * @param {Object} task the returned task object.
     * @param {Array}  keys to keep in the task object.
     * @return {Object} task with the keys specified.
     */
  }], [{
    key: "possiblyPruneTaskData",
    value: function possiblyPruneTaskData(task, keys) {
      if (!task.time && !task.title) {
        // client side task
        return keys.reduce(function (simplifiedTask, key) {
          return deprecated_tasks_objectSpread(deprecated_tasks_objectSpread({}, simplifiedTask), {}, (0,defineProperty/* default */.A)({}, key, task[key]));
        }, {
          id: task.id
        });
      }
      return task;
    }
  }]);
  return DeprecatedTasks;
}();
;// CONCATENATED MODULE: ../../packages/js/data/src/onboarding/actions.ts



var onboarding_actions_marked = /*#__PURE__*/regenerator_default().mark(keepCompletedTaskList),
  onboarding_actions_marked2 = /*#__PURE__*/regenerator_default().mark(updateProfileItems),
  actions_marked3 = /*#__PURE__*/regenerator_default().mark(snoozeTask),
  actions_marked4 = /*#__PURE__*/regenerator_default().mark(undoSnoozeTask),
  actions_marked5 = /*#__PURE__*/regenerator_default().mark(dismissTask),
  actions_marked6 = /*#__PURE__*/regenerator_default().mark(undoDismissTask),
  actions_marked7 = /*#__PURE__*/regenerator_default().mark(hideTaskList),
  actions_marked8 = /*#__PURE__*/regenerator_default().mark(unhideTaskList),
  _marked9 = /*#__PURE__*/regenerator_default().mark(optimisticallyCompleteTask),
  _marked10 = /*#__PURE__*/regenerator_default().mark(actionTask),
  _marked11 = /*#__PURE__*/regenerator_default().mark(installAndActivatePluginsAsync),
  _marked12 = /*#__PURE__*/regenerator_default().mark(coreProfilerCompleted);
/**
 * External dependencies
 */



/**
 * Internal dependencies
 */




function getFreeExtensionsError(error) {
  return {
    type: onboarding_action_types.GET_FREE_EXTENSIONS_ERROR,
    error: error
  };
}
function getFreeExtensionsSuccess(freeExtensions) {
  return {
    type: onboarding_action_types.GET_FREE_EXTENSIONS_SUCCESS,
    freeExtensions: freeExtensions
  };
}
function actions_setError(selector, error) {
  return {
    type: onboarding_action_types.SET_ERROR,
    selector: selector,
    error: error
  };
}
function onboarding_actions_setIsRequesting(selector, isRequesting) {
  return {
    type: onboarding_action_types.SET_IS_REQUESTING,
    selector: selector,
    isRequesting: isRequesting
  };
}
function setProfileItems(profileItems) {
  var replace = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : false;
  return {
    type: onboarding_action_types.SET_PROFILE_ITEMS,
    profileItems: profileItems,
    replace: replace
  };
}
function getTaskListsError(error) {
  return {
    type: onboarding_action_types.GET_TASK_LISTS_ERROR,
    error: error
  };
}
function getTaskListsSuccess(taskLists) {
  return {
    type: onboarding_action_types.GET_TASK_LISTS_SUCCESS,
    taskLists: taskLists
  };
}
function snoozeTaskError(taskId, error) {
  return {
    type: onboarding_action_types.SNOOZE_TASK_ERROR,
    taskId: taskId,
    error: error
  };
}
function snoozeTaskRequest(taskId) {
  return {
    type: onboarding_action_types.SNOOZE_TASK_REQUEST,
    taskId: taskId
  };
}
function snoozeTaskSuccess(task) {
  return {
    type: onboarding_action_types.SNOOZE_TASK_SUCCESS,
    task: task
  };
}
function undoSnoozeTaskError(taskId, error) {
  return {
    type: onboarding_action_types.UNDO_SNOOZE_TASK_ERROR,
    taskId: taskId,
    error: error
  };
}
function undoSnoozeTaskRequest(taskId) {
  return {
    type: onboarding_action_types.UNDO_SNOOZE_TASK_REQUEST,
    taskId: taskId
  };
}
function undoSnoozeTaskSuccess(task) {
  return {
    type: onboarding_action_types.UNDO_SNOOZE_TASK_SUCCESS,
    task: task
  };
}
function dismissTaskError(taskId, error) {
  return {
    type: onboarding_action_types.DISMISS_TASK_ERROR,
    taskId: taskId,
    error: error
  };
}
function dismissTaskRequest(taskId) {
  return {
    type: onboarding_action_types.DISMISS_TASK_REQUEST,
    taskId: taskId
  };
}
function dismissTaskSuccess(task) {
  return {
    type: onboarding_action_types.DISMISS_TASK_SUCCESS,
    task: task
  };
}
function undoDismissTaskError(taskId, error) {
  return {
    type: onboarding_action_types.UNDO_DISMISS_TASK_ERROR,
    taskId: taskId,
    error: error
  };
}
function undoDismissTaskRequest(taskId) {
  return {
    type: onboarding_action_types.UNDO_DISMISS_TASK_REQUEST,
    taskId: taskId
  };
}
function undoDismissTaskSuccess(task) {
  return {
    type: onboarding_action_types.UNDO_DISMISS_TASK_SUCCESS,
    task: task
  };
}
function hideTaskListError(taskListId, error) {
  return {
    type: onboarding_action_types.HIDE_TASK_LIST_ERROR,
    taskListId: taskListId,
    error: error
  };
}
function hideTaskListRequest(taskListId) {
  return {
    type: onboarding_action_types.HIDE_TASK_LIST_REQUEST,
    taskListId: taskListId
  };
}
function hideTaskListSuccess(taskList) {
  return {
    type: onboarding_action_types.HIDE_TASK_LIST_SUCCESS,
    taskList: taskList,
    taskListId: taskList.id
  };
}
function unhideTaskListError(taskListId, error) {
  return {
    type: onboarding_action_types.UNHIDE_TASK_LIST_ERROR,
    taskListId: taskListId,
    error: error
  };
}
function unhideTaskListRequest(taskListId) {
  return {
    type: onboarding_action_types.UNHIDE_TASK_LIST_REQUEST,
    taskListId: taskListId
  };
}
function unhideTaskListSuccess(taskList) {
  return {
    type: onboarding_action_types.UNHIDE_TASK_LIST_SUCCESS,
    taskList: taskList,
    taskListId: taskList.id
  };
}
function optimisticallyCompleteTaskRequest(taskId) {
  return {
    type: onboarding_action_types.OPTIMISTICALLY_COMPLETE_TASK_REQUEST,
    taskId: taskId
  };
}
function keepCompletedTaskListSuccess(taskListId, keepCompletedList) {
  return {
    type: onboarding_action_types.KEEP_COMPLETED_TASKS_SUCCESS,
    taskListId: taskListId,
    keepCompletedTaskList: keepCompletedList
  };
}
function visitedTask(taskId) {
  return {
    type: onboarding_action_types.VISITED_TASK,
    taskId: taskId
  };
}
function setPaymentMethods(paymentMethods) {
  return {
    type: onboarding_action_types.GET_PAYMENT_METHODS_SUCCESS,
    paymentMethods: paymentMethods
  };
}
function setEmailPrefill(email) {
  return {
    type: onboarding_action_types.SET_EMAIL_PREFILL,
    emailPrefill: email
  };
}
function actionTaskError(taskId, error) {
  return {
    type: onboarding_action_types.ACTION_TASK_ERROR,
    taskId: taskId,
    error: error
  };
}
function actionTaskRequest(taskId) {
  return {
    type: onboarding_action_types.ACTION_TASK_REQUEST,
    taskId: taskId
  };
}
function actionTaskSuccess(task) {
  return {
    type: onboarding_action_types.ACTION_TASK_SUCCESS,
    task: task
  };
}
function getProductTypesSuccess(productTypes) {
  return {
    type: onboarding_action_types.GET_PRODUCT_TYPES_SUCCESS,
    productTypes: productTypes
  };
}
function getProductTypesError(error) {
  return {
    type: onboarding_action_types.GET_PRODUCT_TYPES_ERROR,
    error: error
  };
}
function keepCompletedTaskList(taskListId) {
  var updateOptionsParams, response;
  return regenerator_default().wrap(function keepCompletedTaskList$(_context) {
    while (1) switch (_context.prev = _context.next) {
      case 0:
        updateOptionsParams = {
          woocommerce_task_list_keep_completed: 'yes'
        };
        _context.next = 3;
        return build_module_controls/* controls */.n.dispatch(options_constants_STORE_NAME, 'updateOptions', updateOptionsParams);
      case 3:
        response = _context.sent;
        if (!(response && response.success)) {
          _context.next = 7;
          break;
        }
        _context.next = 7;
        return keepCompletedTaskListSuccess(taskListId, 'yes');
      case 7:
      case "end":
        return _context.stop();
    }
  }, onboarding_actions_marked);
}
function updateProfileItems(items) {
  var results;
  return regenerator_default().wrap(function updateProfileItems$(_context2) {
    while (1) switch (_context2.prev = _context2.next) {
      case 0:
        _context2.next = 2;
        return onboarding_actions_setIsRequesting('updateProfileItems', true);
      case 2:
        _context2.next = 4;
        return actions_setError('updateProfileItems', null);
      case 4:
        _context2.prev = 4;
        _context2.next = 7;
        return (0,data_controls_build_module/* apiFetch */.nr)({
          path: "".concat(WC_ADMIN_NAMESPACE, "/onboarding/profile"),
          method: 'POST',
          data: items
        });
      case 7:
        results = _context2.sent;
        if (!(results && results.status === 'success')) {
          _context2.next = 14;
          break;
        }
        _context2.next = 11;
        return setProfileItems(items);
      case 11:
        _context2.next = 13;
        return onboarding_actions_setIsRequesting('updateProfileItems', false);
      case 13:
        return _context2.abrupt("return", results);
      case 14:
        throw new Error();
      case 17:
        _context2.prev = 17;
        _context2.t0 = _context2["catch"](4);
        _context2.next = 21;
        return actions_setError('updateProfileItems', _context2.t0);
      case 21:
        _context2.next = 23;
        return onboarding_actions_setIsRequesting('updateProfileItems', false);
      case 23:
        throw _context2.t0;
      case 24:
      case "end":
        return _context2.stop();
    }
  }, onboarding_actions_marked2, null, [[4, 17]]);
}
function snoozeTask(id) {
  var task;
  return regenerator_default().wrap(function snoozeTask$(_context3) {
    while (1) switch (_context3.prev = _context3.next) {
      case 0:
        _context3.next = 2;
        return snoozeTaskRequest(id);
      case 2:
        _context3.prev = 2;
        _context3.next = 5;
        return (0,data_controls_build_module/* apiFetch */.nr)({
          path: "".concat(WC_ADMIN_NAMESPACE, "/onboarding/tasks/").concat(id, "/snooze"),
          method: 'POST'
        });
      case 5:
        task = _context3.sent;
        _context3.next = 8;
        return snoozeTaskSuccess(DeprecatedTasks.possiblyPruneTaskData(task, ['isSnoozed', 'isDismissed', 'snoozedUntil']));
      case 8:
        _context3.next = 15;
        break;
      case 10:
        _context3.prev = 10;
        _context3.t0 = _context3["catch"](2);
        _context3.next = 14;
        return snoozeTaskError(id, _context3.t0);
      case 14:
        throw new Error();
      case 15:
      case "end":
        return _context3.stop();
    }
  }, actions_marked3, null, [[2, 10]]);
}
function undoSnoozeTask(id) {
  var task;
  return regenerator_default().wrap(function undoSnoozeTask$(_context4) {
    while (1) switch (_context4.prev = _context4.next) {
      case 0:
        _context4.next = 2;
        return undoSnoozeTaskRequest(id);
      case 2:
        _context4.prev = 2;
        _context4.next = 5;
        return (0,data_controls_build_module/* apiFetch */.nr)({
          path: "".concat(WC_ADMIN_NAMESPACE, "/onboarding/tasks/").concat(id, "/undo_snooze"),
          method: 'POST'
        });
      case 5:
        task = _context4.sent;
        _context4.next = 8;
        return undoSnoozeTaskSuccess(DeprecatedTasks.possiblyPruneTaskData(task, ['isSnoozed', 'isDismissed', 'snoozedUntil']));
      case 8:
        _context4.next = 15;
        break;
      case 10:
        _context4.prev = 10;
        _context4.t0 = _context4["catch"](2);
        _context4.next = 14;
        return undoSnoozeTaskError(id, _context4.t0);
      case 14:
        throw new Error();
      case 15:
      case "end":
        return _context4.stop();
    }
  }, actions_marked4, null, [[2, 10]]);
}
function dismissTask(id) {
  var task;
  return regenerator_default().wrap(function dismissTask$(_context5) {
    while (1) switch (_context5.prev = _context5.next) {
      case 0:
        _context5.next = 2;
        return dismissTaskRequest(id);
      case 2:
        _context5.prev = 2;
        _context5.next = 5;
        return (0,data_controls_build_module/* apiFetch */.nr)({
          path: "".concat(WC_ADMIN_NAMESPACE, "/onboarding/tasks/").concat(id, "/dismiss"),
          method: 'POST'
        });
      case 5:
        task = _context5.sent;
        _context5.next = 8;
        return dismissTaskSuccess(DeprecatedTasks.possiblyPruneTaskData(task, ['isDismissed', 'isSnoozed']));
      case 8:
        _context5.next = 15;
        break;
      case 10:
        _context5.prev = 10;
        _context5.t0 = _context5["catch"](2);
        _context5.next = 14;
        return dismissTaskError(id, _context5.t0);
      case 14:
        throw new Error();
      case 15:
      case "end":
        return _context5.stop();
    }
  }, actions_marked5, null, [[2, 10]]);
}
function undoDismissTask(id) {
  var task;
  return regenerator_default().wrap(function undoDismissTask$(_context6) {
    while (1) switch (_context6.prev = _context6.next) {
      case 0:
        _context6.next = 2;
        return undoDismissTaskRequest(id);
      case 2:
        _context6.prev = 2;
        _context6.next = 5;
        return (0,data_controls_build_module/* apiFetch */.nr)({
          path: "".concat(WC_ADMIN_NAMESPACE, "/onboarding/tasks/").concat(id, "/undo_dismiss"),
          method: 'POST'
        });
      case 5:
        task = _context6.sent;
        _context6.next = 8;
        return undoDismissTaskSuccess(DeprecatedTasks.possiblyPruneTaskData(task, ['isDismissed', 'isSnoozed']));
      case 8:
        _context6.next = 15;
        break;
      case 10:
        _context6.prev = 10;
        _context6.t0 = _context6["catch"](2);
        _context6.next = 14;
        return undoDismissTaskError(id, _context6.t0);
      case 14:
        throw new Error();
      case 15:
      case "end":
        return _context6.stop();
    }
  }, actions_marked6, null, [[2, 10]]);
}
function hideTaskList(id) {
  var taskList;
  return regenerator_default().wrap(function hideTaskList$(_context7) {
    while (1) switch (_context7.prev = _context7.next) {
      case 0:
        _context7.next = 2;
        return hideTaskListRequest(id);
      case 2:
        _context7.prev = 2;
        _context7.next = 5;
        return (0,data_controls_build_module/* apiFetch */.nr)({
          path: "".concat(WC_ADMIN_NAMESPACE, "/onboarding/tasks/").concat(id, "/hide"),
          method: 'POST'
        });
      case 5:
        taskList = _context7.sent;
        _context7.next = 8;
        return hideTaskListSuccess(taskList);
      case 8:
        _context7.next = 15;
        break;
      case 10:
        _context7.prev = 10;
        _context7.t0 = _context7["catch"](2);
        _context7.next = 14;
        return hideTaskListError(id, _context7.t0);
      case 14:
        throw new Error();
      case 15:
      case "end":
        return _context7.stop();
    }
  }, actions_marked7, null, [[2, 10]]);
}
function unhideTaskList(id) {
  var taskList;
  return regenerator_default().wrap(function unhideTaskList$(_context8) {
    while (1) switch (_context8.prev = _context8.next) {
      case 0:
        _context8.next = 2;
        return unhideTaskListRequest(id);
      case 2:
        _context8.prev = 2;
        _context8.next = 5;
        return (0,data_controls_build_module/* apiFetch */.nr)({
          path: "".concat(WC_ADMIN_NAMESPACE, "/onboarding/tasks/").concat(id, "/unhide"),
          method: 'POST'
        });
      case 5:
        taskList = _context8.sent;
        _context8.next = 8;
        return unhideTaskListSuccess(taskList);
      case 8:
        _context8.next = 15;
        break;
      case 10:
        _context8.prev = 10;
        _context8.t0 = _context8["catch"](2);
        _context8.next = 14;
        return unhideTaskListError(id, _context8.t0);
      case 14:
        throw new Error();
      case 15:
      case "end":
        return _context8.stop();
    }
  }, actions_marked8, null, [[2, 10]]);
}
function optimisticallyCompleteTask(id) {
  return regenerator_default().wrap(function optimisticallyCompleteTask$(_context9) {
    while (1) switch (_context9.prev = _context9.next) {
      case 0:
        _context9.next = 2;
        return optimisticallyCompleteTaskRequest(id);
      case 2:
      case "end":
        return _context9.stop();
    }
  }, _marked9);
}
function actionTask(id) {
  var task;
  return regenerator_default().wrap(function actionTask$(_context10) {
    while (1) switch (_context10.prev = _context10.next) {
      case 0:
        _context10.next = 2;
        return actionTaskRequest(id);
      case 2:
        _context10.prev = 2;
        _context10.next = 5;
        return (0,data_controls_build_module/* apiFetch */.nr)({
          path: "".concat(WC_ADMIN_NAMESPACE, "/onboarding/tasks/").concat(id, "/action"),
          method: 'POST'
        });
      case 5:
        task = _context10.sent;
        _context10.next = 8;
        return actionTaskSuccess(DeprecatedTasks.possiblyPruneTaskData(task, ['isActioned']));
      case 8:
        _context10.next = 15;
        break;
      case 10:
        _context10.prev = 10;
        _context10.t0 = _context10["catch"](2);
        _context10.next = 14;
        return actionTaskError(id, _context10.t0);
      case 14:
        throw new Error();
      case 15:
      case "end":
        return _context10.stop();
    }
  }, _marked10, null, [[2, 10]]);
}
function installAndActivatePluginsAsync(plugins) {
  var results;
  return regenerator_default().wrap(function installAndActivatePluginsAsync$(_context11) {
    while (1) switch (_context11.prev = _context11.next) {
      case 0:
        _context11.next = 2;
        return onboarding_actions_setIsRequesting('installAndActivatePluginsAsync', true);
      case 2:
        _context11.prev = 2;
        _context11.next = 5;
        return (0,data_controls_build_module/* apiFetch */.nr)({
          path: "".concat(WC_ADMIN_NAMESPACE, "/onboarding/plugins/install-and-activate-async"),
          method: 'POST',
          data: {
            plugins: plugins
          }
        });
      case 5:
        results = _context11.sent;
        return _context11.abrupt("return", results);
      case 9:
        _context11.prev = 9;
        _context11.t0 = _context11["catch"](2);
        throw _context11.t0;
      case 12:
        _context11.prev = 12;
        _context11.next = 15;
        return onboarding_actions_setIsRequesting('installAndActivatePluginsAsync', false);
      case 15:
        return _context11.finish(12);
      case 16:
      case "end":
        return _context11.stop();
    }
  }, _marked11, null, [[2, 9, 12, 16]]);
}
function setJetpackAuthUrl(results, redirectUrl) {
  var from = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : '';
  return {
    type: onboarding_action_types.SET_JETPACK_AUTH_URL,
    results: results,
    redirectUrl: redirectUrl,
    from: from
  };
}
function coreProfilerCompletedError(error) {
  return {
    type: onboarding_action_types.CORE_PROFILER_COMPLETED_ERROR,
    error: error
  };
}
function coreProfilerCompletedRequest() {
  return {
    type: onboarding_action_types.CORE_PROFILER_COMPLETED_REQUEST
  };
}
function coreProfilerCompletedSuccess() {
  return {
    type: onboarding_action_types.CORE_PROFILER_COMPLETED_SUCCESS
  };
}
function coreProfilerCompleted() {
  return regenerator_default().wrap(function coreProfilerCompleted$(_context12) {
    while (1) switch (_context12.prev = _context12.next) {
      case 0:
        _context12.next = 2;
        return coreProfilerCompletedRequest();
      case 2:
        _context12.prev = 2;
        _context12.next = 5;
        return (0,data_controls_build_module/* apiFetch */.nr)({
          path: "".concat(WC_ADMIN_NAMESPACE, "/launch-your-store/initialize-coming-soon"),
          method: 'POST'
        });
      case 5:
        _context12.next = 12;
        break;
      case 7:
        _context12.prev = 7;
        _context12.t0 = _context12["catch"](2);
        _context12.next = 11;
        return coreProfilerCompletedError(_context12.t0);
      case 11:
        throw _context12.t0;
      case 12:
        _context12.prev = 12;
        _context12.next = 15;
        return coreProfilerCompletedSuccess();
      case 15:
        return _context12.finish(12);
      case 16:
      case "end":
        return _context12.stop();
    }
  }, _marked12, null, [[2, 7, 12, 16]]);
}
;// CONCATENATED MODULE: ../../packages/js/data/src/onboarding/resolvers.ts


var onboarding_resolvers_marked = /*#__PURE__*/regenerator_default().mark(resolvers_getProfileItems),
  onboarding_resolvers_marked2 = /*#__PURE__*/regenerator_default().mark(resolvers_getEmailPrefill),
  onboarding_resolvers_marked3 = /*#__PURE__*/regenerator_default().mark(resolvers_getTaskLists),
  onboarding_resolvers_marked4 = /*#__PURE__*/regenerator_default().mark(resolvers_getTaskListsByIds),
  onboarding_resolvers_marked5 = /*#__PURE__*/regenerator_default().mark(resolvers_getTaskList),
  onboarding_resolvers_marked6 = /*#__PURE__*/regenerator_default().mark(resolvers_getTask),
  onboarding_resolvers_marked7 = /*#__PURE__*/regenerator_default().mark(resolvers_getFreeExtensions),
  resolvers_marked8 = /*#__PURE__*/regenerator_default().mark(resolvers_getProductTypes),
  resolvers_marked9 = /*#__PURE__*/regenerator_default().mark(resolvers_getJetpackAuthUrl);
/**
 * External dependencies
 */



/**
 * Internal dependencies
 */





var onboarding_resolvers_resolveSelect = build_module_controls/* controls */.n && build_module_controls/* controls */.n.resolveSelect ? build_module_controls/* controls */.n.resolveSelect : data_controls_build_module/* select */.Lt;
function resolvers_getProfileItems() {
  var results;
  return regenerator_default().wrap(function getProfileItems$(_context) {
    while (1) switch (_context.prev = _context.next) {
      case 0:
        _context.prev = 0;
        _context.next = 3;
        return (0,data_controls_build_module/* apiFetch */.nr)({
          path: WC_ADMIN_NAMESPACE + '/onboarding/profile',
          method: 'GET'
        });
      case 3:
        results = _context.sent;
        _context.next = 6;
        return setProfileItems(results, true);
      case 6:
        _context.next = 12;
        break;
      case 8:
        _context.prev = 8;
        _context.t0 = _context["catch"](0);
        _context.next = 12;
        return actions_setError('getProfileItems', _context.t0);
      case 12:
      case "end":
        return _context.stop();
    }
  }, onboarding_resolvers_marked, null, [[0, 8]]);
}
function resolvers_getEmailPrefill() {
  var results;
  return regenerator_default().wrap(function getEmailPrefill$(_context2) {
    while (1) switch (_context2.prev = _context2.next) {
      case 0:
        _context2.prev = 0;
        _context2.next = 3;
        return (0,data_controls_build_module/* apiFetch */.nr)({
          path: WC_ADMIN_NAMESPACE + '/onboarding/profile/experimental_get_email_prefill',
          method: 'GET'
        });
      case 3:
        results = _context2.sent;
        _context2.next = 6;
        return setEmailPrefill(results.email);
      case 6:
        _context2.next = 12;
        break;
      case 8:
        _context2.prev = 8;
        _context2.t0 = _context2["catch"](0);
        _context2.next = 12;
        return actions_setError('getEmailPrefill', _context2.t0);
      case 12:
      case "end":
        return _context2.stop();
    }
  }, onboarding_resolvers_marked2, null, [[0, 8]]);
}
function resolvers_getTaskLists() {
  var deprecatedTasks, results;
  return regenerator_default().wrap(function getTaskLists$(_context3) {
    while (1) switch (_context3.prev = _context3.next) {
      case 0:
        deprecatedTasks = new DeprecatedTasks();
        _context3.prev = 1;
        _context3.next = 4;
        return checkUserCapability('manage_woocommerce');
      case 4:
        _context3.next = 6;
        return (0,data_controls_build_module/* apiFetch */.nr)({
          path: WC_ADMIN_NAMESPACE + '/onboarding/tasks',
          method: deprecatedTasks.hasDeprecatedTasks() ? 'POST' : 'GET',
          data: deprecatedTasks.getPostData()
        });
      case 6:
        results = _context3.sent;
        deprecatedTasks.mergeDeprecatedCallbackFunctions(results);
        _context3.next = 10;
        return getTaskListsSuccess(results);
      case 10:
        _context3.next = 16;
        break;
      case 12:
        _context3.prev = 12;
        _context3.t0 = _context3["catch"](1);
        _context3.next = 16;
        return getTaskListsError(_context3.t0);
      case 16:
      case "end":
        return _context3.stop();
    }
  }, onboarding_resolvers_marked3, null, [[1, 12]]);
}
function resolvers_getTaskListsByIds() {
  return regenerator_default().wrap(function getTaskListsByIds$(_context4) {
    while (1) switch (_context4.prev = _context4.next) {
      case 0:
        _context4.next = 2;
        return onboarding_resolvers_resolveSelect(onboarding_constants_STORE_NAME, 'getTaskLists');
      case 2:
      case "end":
        return _context4.stop();
    }
  }, onboarding_resolvers_marked4);
}
function resolvers_getTaskList() {
  return regenerator_default().wrap(function getTaskList$(_context5) {
    while (1) switch (_context5.prev = _context5.next) {
      case 0:
        _context5.next = 2;
        return onboarding_resolvers_resolveSelect(onboarding_constants_STORE_NAME, 'getTaskLists');
      case 2:
      case "end":
        return _context5.stop();
    }
  }, onboarding_resolvers_marked5);
}
function resolvers_getTask() {
  return regenerator_default().wrap(function getTask$(_context6) {
    while (1) switch (_context6.prev = _context6.next) {
      case 0:
        _context6.next = 2;
        return onboarding_resolvers_resolveSelect(onboarding_constants_STORE_NAME, 'getTaskLists');
      case 2:
      case "end":
        return _context6.stop();
    }
  }, onboarding_resolvers_marked6);
}
function resolvers_getPaymentGatewaySuggestions() {
  var forceDefaultSuggestions = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : false;
  return /*#__PURE__*/regenerator_default().mark(function _callee() {
    var path, results;
    return regenerator_default().wrap(function _callee$(_context7) {
      while (1) switch (_context7.prev = _context7.next) {
        case 0:
          path = WC_ADMIN_NAMESPACE + '/payment-gateway-suggestions';
          if (forceDefaultSuggestions) {
            path += '?force_default_suggestions=true';
          }
          _context7.prev = 2;
          _context7.next = 5;
          return (0,data_controls_build_module/* apiFetch */.nr)({
            path: path,
            method: 'GET'
          });
        case 5:
          results = _context7.sent;
          _context7.next = 8;
          return setPaymentMethods(results);
        case 8:
          _context7.next = 14;
          break;
        case 10:
          _context7.prev = 10;
          _context7.t0 = _context7["catch"](2);
          _context7.next = 14;
          return actions_setError('getPaymentGatewaySuggestions', _context7.t0);
        case 14:
        case "end":
          return _context7.stop();
      }
    }, _callee, null, [[2, 10]]);
  })();
}
function resolvers_getFreeExtensions() {
  var results;
  return regenerator_default().wrap(function getFreeExtensions$(_context8) {
    while (1) switch (_context8.prev = _context8.next) {
      case 0:
        _context8.prev = 0;
        _context8.next = 3;
        return (0,data_controls_build_module/* apiFetch */.nr)({
          path: WC_ADMIN_NAMESPACE + '/onboarding/free-extensions',
          method: 'GET'
        });
      case 3:
        results = _context8.sent;
        _context8.next = 6;
        return getFreeExtensionsSuccess(results);
      case 6:
        _context8.next = 12;
        break;
      case 8:
        _context8.prev = 8;
        _context8.t0 = _context8["catch"](0);
        _context8.next = 12;
        return getFreeExtensionsError(_context8.t0);
      case 12:
      case "end":
        return _context8.stop();
    }
  }, onboarding_resolvers_marked7, null, [[0, 8]]);
}
function resolvers_getProductTypes() {
  var results;
  return regenerator_default().wrap(function getProductTypes$(_context9) {
    while (1) switch (_context9.prev = _context9.next) {
      case 0:
        _context9.prev = 0;
        _context9.next = 3;
        return (0,data_controls_build_module/* apiFetch */.nr)({
          path: WC_ADMIN_NAMESPACE + '/onboarding/product-types',
          method: 'GET'
        });
      case 3:
        results = _context9.sent;
        _context9.next = 6;
        return getProductTypesSuccess(results);
      case 6:
        _context9.next = 12;
        break;
      case 8:
        _context9.prev = 8;
        _context9.t0 = _context9["catch"](0);
        _context9.next = 12;
        return getProductTypesError(_context9.t0);
      case 12:
      case "end":
        return _context9.stop();
    }
  }, resolvers_marked8, null, [[0, 8]]);
}
function resolvers_getJetpackAuthUrl(query) {
  var _query$from, path, results;
  return regenerator_default().wrap(function getJetpackAuthUrl$(_context10) {
    while (1) switch (_context10.prev = _context10.next) {
      case 0:
        _context10.prev = 0;
        path = WC_ADMIN_NAMESPACE + '/onboarding/plugins/jetpack-authorization-url?redirect_url=' + encodeURIComponent(query.redirectUrl);
        if (query.from) {
          path += '&from=' + query.from;
        }
        _context10.next = 5;
        return (0,data_controls_build_module/* apiFetch */.nr)({
          path: path,
          method: 'GET'
        });
      case 5:
        results = _context10.sent;
        _context10.next = 8;
        return setJetpackAuthUrl(results, query.redirectUrl, (_query$from = query.from) !== null && _query$from !== void 0 ? _query$from : '');
      case 8:
        _context10.next = 14;
        break;
      case 10:
        _context10.prev = 10;
        _context10.t0 = _context10["catch"](0);
        _context10.next = 14;
        return actions_setError('getJetpackAuthUrl', _context10.t0);
      case 14:
      case "end":
        return _context10.stop();
    }
  }, resolvers_marked9, null, [[0, 10]]);
}
;// CONCATENATED MODULE: ../../packages/js/data/src/onboarding/reducer.ts









function onboarding_reducer_ownKeys(e, r) {
  var t = Object.keys(e);
  if (Object.getOwnPropertySymbols) {
    var o = Object.getOwnPropertySymbols(e);
    r && (o = o.filter(function (r) {
      return Object.getOwnPropertyDescriptor(e, r).enumerable;
    })), t.push.apply(t, o);
  }
  return t;
}
function onboarding_reducer_objectSpread(e) {
  for (var r = 1; r < arguments.length; r++) {
    var t = null != arguments[r] ? arguments[r] : {};
    r % 2 ? onboarding_reducer_ownKeys(Object(t), !0).forEach(function (r) {
      (0,defineProperty/* default */.A)(e, r, t[r]);
    }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(e, Object.getOwnPropertyDescriptors(t)) : onboarding_reducer_ownKeys(Object(t)).forEach(function (r) {
      Object.defineProperty(e, r, Object.getOwnPropertyDescriptor(t, r));
    });
  }
  return e;
}






/**
 * External dependencies
 */

/**
 * Internal dependencies
 */

var defaultState = {
  errors: {},
  freeExtensions: [],
  profileItems: {
    business_extensions: null,
    completed: null,
    industry: null,
    number_employees: null,
    other_platform: null,
    other_platform_name: null,
    product_count: null,
    product_types: null,
    revenue: null,
    selling_venues: null,
    setup_client: null,
    skipped: null,
    theme: null,
    wccom_connected: null,
    is_agree_marketing: null,
    store_email: null,
    is_store_country_set: null
  },
  emailPrefill: '',
  paymentMethods: [],
  productTypes: {},
  requesting: {},
  taskLists: {},
  jetpackAuthUrls: {}
};
var getUpdatedTaskLists = function getUpdatedTaskLists(taskLists, args) {
  return Object.keys(taskLists).reduce(function (lists, taskListId) {
    return onboarding_reducer_objectSpread(onboarding_reducer_objectSpread({}, lists), {}, (0,defineProperty/* default */.A)({}, taskListId, onboarding_reducer_objectSpread(onboarding_reducer_objectSpread({}, taskLists[taskListId]), {}, {
      tasks: taskLists[taskListId].tasks.map(function (task) {
        if (args.id === task.id) {
          return onboarding_reducer_objectSpread(onboarding_reducer_objectSpread({}, task), args);
        }
        return task;
      })
    })));
  }, onboarding_reducer_objectSpread({}, taskLists));
};
var onboarding_reducer_reducer = function reducer() {
  var state = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : defaultState;
  var action = arguments.length > 1 ? arguments[1] : undefined;
  switch (action.type) {
    case onboarding_action_types.SET_PROFILE_ITEMS:
      return onboarding_reducer_objectSpread(onboarding_reducer_objectSpread({}, state), {}, {
        profileItems: action.replace ? action.profileItems : onboarding_reducer_objectSpread(onboarding_reducer_objectSpread({}, state.profileItems), action.profileItems)
      });
    case onboarding_action_types.SET_EMAIL_PREFILL:
      return onboarding_reducer_objectSpread(onboarding_reducer_objectSpread({}, state), {}, {
        emailPrefill: action.emailPrefill
      });
    case onboarding_action_types.SET_ERROR:
      return onboarding_reducer_objectSpread(onboarding_reducer_objectSpread({}, state), {}, {
        errors: onboarding_reducer_objectSpread(onboarding_reducer_objectSpread({}, state.errors), {}, (0,defineProperty/* default */.A)({}, action.selector, action.error))
      });
    case onboarding_action_types.SET_IS_REQUESTING:
      return onboarding_reducer_objectSpread(onboarding_reducer_objectSpread({}, state), {}, {
        requesting: onboarding_reducer_objectSpread(onboarding_reducer_objectSpread({}, state.requesting), {}, (0,defineProperty/* default */.A)({}, action.selector, action.isRequesting))
      });
    case onboarding_action_types.GET_PAYMENT_METHODS_SUCCESS:
      return onboarding_reducer_objectSpread(onboarding_reducer_objectSpread({}, state), {}, {
        paymentMethods: action.paymentMethods
      });
    case onboarding_action_types.GET_PRODUCT_TYPES_SUCCESS:
      return onboarding_reducer_objectSpread(onboarding_reducer_objectSpread({}, state), {}, {
        productTypes: action.productTypes
      });
    case onboarding_action_types.GET_PRODUCT_TYPES_ERROR:
      return onboarding_reducer_objectSpread(onboarding_reducer_objectSpread({}, state), {}, {
        errors: onboarding_reducer_objectSpread(onboarding_reducer_objectSpread({}, state.errors), {}, {
          productTypes: action.error
        })
      });
    case onboarding_action_types.GET_FREE_EXTENSIONS_ERROR:
      return onboarding_reducer_objectSpread(onboarding_reducer_objectSpread({}, state), {}, {
        errors: onboarding_reducer_objectSpread(onboarding_reducer_objectSpread({}, state.errors), {}, {
          getFreeExtensions: action.error
        })
      });
    case onboarding_action_types.GET_FREE_EXTENSIONS_SUCCESS:
      return onboarding_reducer_objectSpread(onboarding_reducer_objectSpread({}, state), {}, {
        freeExtensions: action.freeExtensions
      });
    case onboarding_action_types.GET_TASK_LISTS_ERROR:
      return onboarding_reducer_objectSpread(onboarding_reducer_objectSpread({}, state), {}, {
        errors: onboarding_reducer_objectSpread(onboarding_reducer_objectSpread({}, state.errors), {}, {
          getTaskLists: action.error
        })
      });
    case onboarding_action_types.GET_TASK_LISTS_SUCCESS:
      return onboarding_reducer_objectSpread(onboarding_reducer_objectSpread({}, state), {}, {
        taskLists: action.taskLists.reduce(function (lists, list) {
          return onboarding_reducer_objectSpread(onboarding_reducer_objectSpread({}, lists), {}, (0,defineProperty/* default */.A)({}, list.id, list));
        }, state.taskLists || {})
      });
    case onboarding_action_types.DISMISS_TASK_ERROR:
      return onboarding_reducer_objectSpread(onboarding_reducer_objectSpread({}, state), {}, {
        errors: onboarding_reducer_objectSpread(onboarding_reducer_objectSpread({}, state.errors), {}, {
          dismissTask: action.error
        }),
        taskLists: getUpdatedTaskLists(state.taskLists, {
          id: action.taskId,
          isDismissed: false
        })
      });
    case onboarding_action_types.DISMISS_TASK_REQUEST:
      return onboarding_reducer_objectSpread(onboarding_reducer_objectSpread({}, state), {}, {
        requesting: onboarding_reducer_objectSpread(onboarding_reducer_objectSpread({}, state.requesting), {}, {
          dismissTask: true
        }),
        taskLists: getUpdatedTaskLists(state.taskLists, {
          id: action.taskId,
          isDismissed: true
        })
      });
    case onboarding_action_types.DISMISS_TASK_SUCCESS:
      return onboarding_reducer_objectSpread(onboarding_reducer_objectSpread({}, state), {}, {
        requesting: onboarding_reducer_objectSpread(onboarding_reducer_objectSpread({}, state.requesting), {}, {
          dismissTask: false
        }),
        taskLists: getUpdatedTaskLists(state.taskLists, action.task)
      });
    case onboarding_action_types.UNDO_DISMISS_TASK_ERROR:
      return onboarding_reducer_objectSpread(onboarding_reducer_objectSpread({}, state), {}, {
        errors: onboarding_reducer_objectSpread(onboarding_reducer_objectSpread({}, state.errors), {}, {
          undoDismissTask: action.error
        }),
        taskLists: getUpdatedTaskLists(state.taskLists, {
          id: action.taskId,
          isDismissed: true
        })
      });
    case onboarding_action_types.UNDO_DISMISS_TASK_REQUEST:
      return onboarding_reducer_objectSpread(onboarding_reducer_objectSpread({}, state), {}, {
        requesting: onboarding_reducer_objectSpread(onboarding_reducer_objectSpread({}, state.requesting), {}, {
          undoDismissTask: true
        }),
        taskLists: getUpdatedTaskLists(state.taskLists, {
          id: action.taskId,
          isDismissed: false
        })
      });
    case onboarding_action_types.UNDO_DISMISS_TASK_SUCCESS:
      return onboarding_reducer_objectSpread(onboarding_reducer_objectSpread({}, state), {}, {
        requesting: onboarding_reducer_objectSpread(onboarding_reducer_objectSpread({}, state.requesting), {}, {
          undoDismissTask: false
        }),
        taskLists: getUpdatedTaskLists(state.taskLists, action.task)
      });
    case onboarding_action_types.SNOOZE_TASK_ERROR:
      return onboarding_reducer_objectSpread(onboarding_reducer_objectSpread({}, state), {}, {
        errors: onboarding_reducer_objectSpread(onboarding_reducer_objectSpread({}, state.errors), {}, {
          snoozeTask: action.error
        }),
        taskLists: getUpdatedTaskLists(state.taskLists, {
          id: action.taskId,
          isSnoozed: false
        })
      });
    case onboarding_action_types.SNOOZE_TASK_REQUEST:
      return onboarding_reducer_objectSpread(onboarding_reducer_objectSpread({}, state), {}, {
        requesting: onboarding_reducer_objectSpread(onboarding_reducer_objectSpread({}, state.requesting), {}, {
          snoozeTask: true
        }),
        taskLists: getUpdatedTaskLists(state.taskLists, {
          id: action.taskId,
          isSnoozed: true
        })
      });
    case onboarding_action_types.SNOOZE_TASK_SUCCESS:
      return onboarding_reducer_objectSpread(onboarding_reducer_objectSpread({}, state), {}, {
        requesting: onboarding_reducer_objectSpread(onboarding_reducer_objectSpread({}, state.requesting), {}, {
          snoozeTask: false
        }),
        taskLists: getUpdatedTaskLists(state.taskLists, action.task)
      });
    case onboarding_action_types.UNDO_SNOOZE_TASK_ERROR:
      return onboarding_reducer_objectSpread(onboarding_reducer_objectSpread({}, state), {}, {
        errors: onboarding_reducer_objectSpread(onboarding_reducer_objectSpread({}, state.errors), {}, {
          undoSnoozeTask: action.error
        }),
        taskLists: getUpdatedTaskLists(state.taskLists, {
          id: action.taskId,
          isSnoozed: true
        })
      });
    case onboarding_action_types.UNDO_SNOOZE_TASK_REQUEST:
      return onboarding_reducer_objectSpread(onboarding_reducer_objectSpread({}, state), {}, {
        requesting: onboarding_reducer_objectSpread(onboarding_reducer_objectSpread({}, state.requesting), {}, {
          undoSnoozeTask: true
        }),
        taskLists: getUpdatedTaskLists(state.taskLists, {
          id: action.taskId,
          isSnoozed: false
        })
      });
    case onboarding_action_types.UNDO_SNOOZE_TASK_SUCCESS:
      return onboarding_reducer_objectSpread(onboarding_reducer_objectSpread({}, state), {}, {
        requesting: onboarding_reducer_objectSpread(onboarding_reducer_objectSpread({}, state.requesting), {}, {
          undoSnoozeTask: false
        }),
        taskLists: getUpdatedTaskLists(state.taskLists, action.task)
      });
    case onboarding_action_types.HIDE_TASK_LIST_ERROR:
      return onboarding_reducer_objectSpread(onboarding_reducer_objectSpread({}, state), {}, {
        errors: onboarding_reducer_objectSpread(onboarding_reducer_objectSpread({}, state.errors), {}, {
          hideTaskList: action.error
        }),
        taskLists: onboarding_reducer_objectSpread(onboarding_reducer_objectSpread({}, state.taskLists), {}, (0,defineProperty/* default */.A)({}, action.taskListId, onboarding_reducer_objectSpread(onboarding_reducer_objectSpread({}, state.taskLists[action.taskListId]), {}, {
          isHidden: false,
          isVisible: true
        })))
      });
    case onboarding_action_types.HIDE_TASK_LIST_REQUEST:
      return onboarding_reducer_objectSpread(onboarding_reducer_objectSpread({}, state), {}, {
        requesting: onboarding_reducer_objectSpread(onboarding_reducer_objectSpread({}, state.requesting), {}, {
          hideTaskList: true
        }),
        taskLists: onboarding_reducer_objectSpread(onboarding_reducer_objectSpread({}, state.taskLists), {}, (0,defineProperty/* default */.A)({}, action.taskListId, onboarding_reducer_objectSpread(onboarding_reducer_objectSpread({}, state.taskLists[action.taskListId]), {}, {
          isHidden: true,
          isVisible: false
        })))
      });
    case onboarding_action_types.HIDE_TASK_LIST_SUCCESS:
      return onboarding_reducer_objectSpread(onboarding_reducer_objectSpread({}, state), {}, {
        requesting: onboarding_reducer_objectSpread(onboarding_reducer_objectSpread({}, state.requesting), {}, {
          hideTaskList: false
        }),
        taskLists: onboarding_reducer_objectSpread(onboarding_reducer_objectSpread({}, state.taskLists), {}, (0,defineProperty/* default */.A)({}, action.taskListId, action.taskList))
      });
    case onboarding_action_types.UNHIDE_TASK_LIST_ERROR:
      return onboarding_reducer_objectSpread(onboarding_reducer_objectSpread({}, state), {}, {
        errors: onboarding_reducer_objectSpread(onboarding_reducer_objectSpread({}, state.errors), {}, {
          unhideTaskList: action.error
        }),
        taskLists: onboarding_reducer_objectSpread(onboarding_reducer_objectSpread({}, state.taskLists), {}, (0,defineProperty/* default */.A)({}, action.taskListId, onboarding_reducer_objectSpread(onboarding_reducer_objectSpread({}, state.taskLists[action.taskListId]), {}, {
          isHidden: true,
          isVisible: false
        })))
      });
    case onboarding_action_types.UNHIDE_TASK_LIST_REQUEST:
      return onboarding_reducer_objectSpread(onboarding_reducer_objectSpread({}, state), {}, {
        requesting: onboarding_reducer_objectSpread(onboarding_reducer_objectSpread({}, state.requesting), {}, {
          unhideTaskList: true
        }),
        taskLists: onboarding_reducer_objectSpread(onboarding_reducer_objectSpread({}, state.taskLists), {}, (0,defineProperty/* default */.A)({}, action.taskListId, onboarding_reducer_objectSpread(onboarding_reducer_objectSpread({}, state.taskLists[action.taskListId]), {}, {
          isHidden: false,
          isVisible: true
        })))
      });
    case onboarding_action_types.UNHIDE_TASK_LIST_SUCCESS:
      return onboarding_reducer_objectSpread(onboarding_reducer_objectSpread({}, state), {}, {
        requesting: onboarding_reducer_objectSpread(onboarding_reducer_objectSpread({}, state.requesting), {}, {
          unhideTaskList: false
        }),
        taskLists: onboarding_reducer_objectSpread(onboarding_reducer_objectSpread({}, state.taskLists), {}, (0,defineProperty/* default */.A)({}, action.taskListId, action.taskList))
      });
    case onboarding_action_types.KEEP_COMPLETED_TASKS_SUCCESS:
      return onboarding_reducer_objectSpread(onboarding_reducer_objectSpread({}, state), {}, {
        taskLists: onboarding_reducer_objectSpread(onboarding_reducer_objectSpread({}, state.taskLists), {}, (0,defineProperty/* default */.A)({}, action.taskListId, onboarding_reducer_objectSpread(onboarding_reducer_objectSpread({}, state.taskLists[action.taskListId]), {}, {
          keepCompletedTaskList: action.keepCompletedTaskList
        })))
      });
    case onboarding_action_types.OPTIMISTICALLY_COMPLETE_TASK_REQUEST:
      return onboarding_reducer_objectSpread(onboarding_reducer_objectSpread({}, state), {}, {
        taskLists: getUpdatedTaskLists(state.taskLists, {
          id: action.taskId,
          isComplete: true
        })
      });
    case onboarding_action_types.VISITED_TASK:
      return onboarding_reducer_objectSpread(onboarding_reducer_objectSpread({}, state), {}, {
        taskLists: getUpdatedTaskLists(state.taskLists, {
          id: action.taskId,
          isVisited: true
        })
      });
    case onboarding_action_types.ACTION_TASK_ERROR:
      return onboarding_reducer_objectSpread(onboarding_reducer_objectSpread({}, state), {}, {
        errors: onboarding_reducer_objectSpread(onboarding_reducer_objectSpread({}, state.errors), {}, {
          actionTask: action.error
        }),
        taskLists: getUpdatedTaskLists(state.taskLists, {
          id: action.taskId,
          isActioned: false
        })
      });
    case onboarding_action_types.ACTION_TASK_REQUEST:
      return onboarding_reducer_objectSpread(onboarding_reducer_objectSpread({}, state), {}, {
        requesting: onboarding_reducer_objectSpread(onboarding_reducer_objectSpread({}, state.requesting), {}, {
          actionTask: true
        }),
        taskLists: getUpdatedTaskLists(state.taskLists, {
          id: action.taskId,
          isActioned: true
        })
      });
    case onboarding_action_types.ACTION_TASK_SUCCESS:
      return onboarding_reducer_objectSpread(onboarding_reducer_objectSpread({}, state), {}, {
        requesting: onboarding_reducer_objectSpread(onboarding_reducer_objectSpread({}, state.requesting), {}, {
          actionTask: false
        }),
        taskLists: getUpdatedTaskLists(state.taskLists, action.task)
      });
    case onboarding_action_types.SET_JETPACK_AUTH_URL:
      return onboarding_reducer_objectSpread(onboarding_reducer_objectSpread({}, state), {}, {
        jetpackAuthUrls: onboarding_reducer_objectSpread(onboarding_reducer_objectSpread({}, state.jetpackAuthUrls), {}, (0,defineProperty/* default */.A)({}, action.redirectUrl, action.results))
      });
    case onboarding_action_types.CORE_PROFILER_COMPLETED_REQUEST:
      return onboarding_reducer_objectSpread(onboarding_reducer_objectSpread({}, state), {}, {
        requesting: onboarding_reducer_objectSpread(onboarding_reducer_objectSpread({}, state.requesting), {}, {
          coreProfilerCompleted: true
        })
      });
    case onboarding_action_types.CORE_PROFILER_COMPLETED_SUCCESS:
      return onboarding_reducer_objectSpread(onboarding_reducer_objectSpread({}, state), {}, {
        requesting: onboarding_reducer_objectSpread(onboarding_reducer_objectSpread({}, state.requesting), {}, {
          coreProfilerCompleted: false
        })
      });
    case onboarding_action_types.CORE_PROFILER_COMPLETED_ERROR:
      return onboarding_reducer_objectSpread(onboarding_reducer_objectSpread({}, state), {}, {
        errors: onboarding_reducer_objectSpread(onboarding_reducer_objectSpread({}, state.errors), {}, {
          coreProfilerCompleted: action.error
        }),
        requesting: onboarding_reducer_objectSpread(onboarding_reducer_objectSpread({}, state.requesting), {}, {
          coreProfilerCompleted: false
        })
      });
    default:
      return state;
  }
};
/* harmony default export */ const onboarding_reducer = (onboarding_reducer_reducer);
;// CONCATENATED MODULE: ../../packages/js/data/src/onboarding/index.ts
/**
 * External dependencies
 */


/**
 * Internal dependencies
 */






(0,data_build_module/* registerStore */.ti)(onboarding_constants_STORE_NAME, {
  reducer: onboarding_reducer,
  actions: onboarding_actions_namespaceObject,
  controls: data_controls_build_module/* controls */.ne,
  selectors: onboarding_selectors_namespaceObject,
  resolvers: onboarding_resolvers_namespaceObject
});
var ONBOARDING_STORE_NAME = (/* unused pure expression or super */ null && (STORE_NAME));
;// CONCATENATED MODULE: ../../packages/js/data/src/reviews/constants.ts
var reviews_constants_STORE_NAME = 'wc/admin/reviews';
;// CONCATENATED MODULE: ../../packages/js/data/src/reviews/selectors.ts

/**
 * Internal dependencies
 */

var getReviews = function getReviews(state, query) {
  var stringifiedQuery = JSON.stringify(query);
  var ids = state.reviews[stringifiedQuery] && state.reviews[stringifiedQuery].data || [];
  return ids.map(function (id) {
    return state.data[id];
  });
};
var getReviewsTotalCount = function getReviewsTotalCount(state, query) {
  var stringifiedQuery = JSON.stringify(query);
  return state.reviews[stringifiedQuery] && state.reviews[stringifiedQuery].totalCount;
};
var getReviewsError = function getReviewsError(state, query) {
  var stringifiedQuery = JSON.stringify(query);
  return state.errors[stringifiedQuery];
};
;// CONCATENATED MODULE: ../../packages/js/data/src/reviews/action-types.ts
var reviews_action_types_TYPES = {
  UPDATE_REVIEWS: 'UPDATE_REVIEWS',
  SET_REVIEW: 'SET_REVIEW',
  SET_ERROR: 'SET_ERROR',
  SET_REVIEW_IS_UPDATING: 'SET_REVIEW_IS_UPDATING'
};
/* harmony default export */ const reviews_action_types = (reviews_action_types_TYPES);
;// CONCATENATED MODULE: ../../packages/js/data/src/reviews/actions.ts


var reviews_actions_marked = /*#__PURE__*/regenerator_default().mark(updateReview),
  reviews_actions_marked2 = /*#__PURE__*/regenerator_default().mark(deleteReview);

/**
 * External dependencies
 */



/**
 * Internal dependencies
 */


function setReviewIsUpdating(reviewId, isUpdating) {
  return {
    type: reviews_action_types.SET_REVIEW_IS_UPDATING,
    reviewId: reviewId,
    isUpdating: isUpdating
  };
}
function setReview(reviewId, reviewData) {
  return {
    type: reviews_action_types.SET_REVIEW,
    reviewId: reviewId,
    reviewData: reviewData
  };
}
function reviews_actions_setError(query, error) {
  return {
    type: reviews_action_types.SET_ERROR,
    query: query,
    error: error
  };
}
function updateReviews(query, reviews, totalCount) {
  return {
    type: reviews_action_types.UPDATE_REVIEWS,
    reviews: reviews,
    query: query,
    totalCount: totalCount
  };
}
function updateReview(reviewId, reviewFields, query) {
  var url, review;
  return regenerator_default().wrap(function updateReview$(_context) {
    while (1) switch (_context.prev = _context.next) {
      case 0:
        _context.next = 2;
        return setReviewIsUpdating(reviewId, true);
      case 2:
        _context.prev = 2;
        url = (0,add_query_args/* addQueryArgs */.F)("".concat(NAMESPACE, "/products/reviews/").concat(reviewId), query || {});
        _context.next = 6;
        return (0,data_controls_build_module/* apiFetch */.nr)({
          path: url,
          method: 'PUT',
          data: reviewFields
        });
      case 6:
        review = _context.sent;
        _context.next = 9;
        return setReview(reviewId, review);
      case 9:
        _context.next = 11;
        return setReviewIsUpdating(reviewId, false);
      case 11:
        _context.next = 20;
        break;
      case 13:
        _context.prev = 13;
        _context.t0 = _context["catch"](2);
        _context.next = 17;
        return reviews_actions_setError('updateReview', _context.t0);
      case 17:
        _context.next = 19;
        return setReviewIsUpdating(reviewId, false);
      case 19:
        throw new Error();
      case 20:
      case "end":
        return _context.stop();
    }
  }, reviews_actions_marked, null, [[2, 13]]);
}
function deleteReview(reviewId) {
  var url, response;
  return regenerator_default().wrap(function deleteReview$(_context2) {
    while (1) switch (_context2.prev = _context2.next) {
      case 0:
        _context2.next = 2;
        return setReviewIsUpdating(reviewId, true);
      case 2:
        _context2.prev = 2;
        url = "".concat(NAMESPACE, "/products/reviews/").concat(reviewId);
        _context2.next = 6;
        return (0,data_controls_build_module/* apiFetch */.nr)({
          path: url,
          method: 'DELETE'
        });
      case 6:
        response = _context2.sent;
        _context2.next = 9;
        return setReview(reviewId, response);
      case 9:
        _context2.next = 11;
        return setReviewIsUpdating(reviewId, false);
      case 11:
        return _context2.abrupt("return", response);
      case 14:
        _context2.prev = 14;
        _context2.t0 = _context2["catch"](2);
        _context2.next = 18;
        return reviews_actions_setError('deleteReview', _context2.t0);
      case 18:
        _context2.next = 20;
        return setReviewIsUpdating(reviewId, false);
      case 20:
        throw new Error();
      case 21:
      case "end":
        return _context2.stop();
    }
  }, reviews_actions_marked2, null, [[2, 14]]);
}
;// CONCATENATED MODULE: ../../packages/js/data/src/reviews/resolvers.ts


var reviews_resolvers_marked = /*#__PURE__*/regenerator_default().mark(resolvers_getReviews),
  reviews_resolvers_marked2 = /*#__PURE__*/regenerator_default().mark(resolvers_getReviewsTotalCount);

/**
 * External dependencies
 */


/**
 * Internal dependencies
 */



function resolvers_getReviews(query) {
  var url, response, totalCountFromHeader, totalCount;
  return regenerator_default().wrap(function getReviews$(_context) {
    while (1) switch (_context.prev = _context.next) {
      case 0:
        _context.prev = 0;
        url = (0,add_query_args/* addQueryArgs */.F)("".concat(NAMESPACE, "/products/reviews"), query);
        _context.next = 4;
        return fetchWithHeaders({
          path: url,
          method: 'GET'
        });
      case 4:
        response = _context.sent;
        totalCountFromHeader = response.headers.get('x-wp-total');
        if (!(totalCountFromHeader === undefined)) {
          _context.next = 8;
          break;
        }
        throw new Error("Malformed response from server. 'x-wp-total' header is missing when retrieving ./products/reviews.");
      case 8:
        totalCount = parseInt(totalCountFromHeader, 10);
        _context.next = 11;
        return updateReviews(query, response.data, totalCount);
      case 11:
        _context.next = 17;
        break;
      case 13:
        _context.prev = 13;
        _context.t0 = _context["catch"](0);
        _context.next = 17;
        return reviews_actions_setError(JSON.stringify(query), _context.t0);
      case 17:
      case "end":
        return _context.stop();
    }
  }, reviews_resolvers_marked, null, [[0, 13]]);
}
function resolvers_getReviewsTotalCount(query) {
  return regenerator_default().wrap(function getReviewsTotalCount$(_context2) {
    while (1) switch (_context2.prev = _context2.next) {
      case 0:
        _context2.next = 2;
        return resolvers_getReviews(query);
      case 2:
      case "end":
        return _context2.stop();
    }
  }, reviews_resolvers_marked2);
}
;// CONCATENATED MODULE: ../../packages/js/data/src/reviews/reducer.ts

function reviews_reducer_ownKeys(e, r) {
  var t = Object.keys(e);
  if (Object.getOwnPropertySymbols) {
    var o = Object.getOwnPropertySymbols(e);
    r && (o = o.filter(function (r) {
      return Object.getOwnPropertyDescriptor(e, r).enumerable;
    })), t.push.apply(t, o);
  }
  return t;
}
function reviews_reducer_objectSpread(e) {
  for (var r = 1; r < arguments.length; r++) {
    var t = null != arguments[r] ? arguments[r] : {};
    r % 2 ? reviews_reducer_ownKeys(Object(t), !0).forEach(function (r) {
      (0,defineProperty/* default */.A)(e, r, t[r]);
    }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(e, Object.getOwnPropertyDescriptors(t)) : reviews_reducer_ownKeys(Object(t)).forEach(function (r) {
      Object.defineProperty(e, r, Object.getOwnPropertyDescriptor(t, r));
    });
  }
  return e;
}











/**
 * External dependencies
 */

/**
 * Internal dependencies
 */

var initialState = {
  reviews: {},
  errors: {},
  data: {}
};
var reviews_reducer_reducer = function reducer() {
  var state = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : initialState;
  var action = arguments.length > 1 ? arguments[1] : undefined;
  switch (action.type) {
    case reviews_action_types.UPDATE_REVIEWS:
      var ids = [];
      var nextReviews = action.reviews.reduce(function (result, review) {
        ids.push(review.id);
        result[review.id] = reviews_reducer_objectSpread(reviews_reducer_objectSpread({}, state.data[review.id] || {}), review);
        return result;
      }, {});
      return reviews_reducer_objectSpread(reviews_reducer_objectSpread({}, state), {}, {
        reviews: reviews_reducer_objectSpread(reviews_reducer_objectSpread({}, state.reviews), {}, (0,defineProperty/* default */.A)({}, JSON.stringify(action.query), {
          data: ids,
          totalCount: action.totalCount
        })),
        data: reviews_reducer_objectSpread(reviews_reducer_objectSpread({}, state.data), nextReviews)
      });
    case reviews_action_types.SET_REVIEW:
      return reviews_reducer_objectSpread(reviews_reducer_objectSpread({}, state), {}, {
        data: reviews_reducer_objectSpread(reviews_reducer_objectSpread({}, state.data), {}, (0,defineProperty/* default */.A)({}, action.reviewId, action.reviewData))
      });
    case reviews_action_types.SET_ERROR:
      return reviews_reducer_objectSpread(reviews_reducer_objectSpread({}, state), {}, {
        errors: reviews_reducer_objectSpread(reviews_reducer_objectSpread({}, state.errors), {}, (0,defineProperty/* default */.A)({}, action.query, action.error))
      });
    case reviews_action_types.SET_REVIEW_IS_UPDATING:
      return reviews_reducer_objectSpread(reviews_reducer_objectSpread({}, state), {}, {
        data: reviews_reducer_objectSpread(reviews_reducer_objectSpread({}, state.data), {}, (0,defineProperty/* default */.A)({}, action.reviewId, reviews_reducer_objectSpread(reviews_reducer_objectSpread({}, state.data[action.reviewId]), {}, {
          isUpdating: action.isUpdating
        })))
      });
    default:
      return state;
  }
};
/* harmony default export */ const reviews_reducer = (reviews_reducer_reducer);
;// CONCATENATED MODULE: ../../packages/js/data/src/reviews/index.ts
/**
 * External dependencies
 */


/**
 * Internal dependencies
 */







(0,data_build_module/* registerStore */.ti)(reviews_constants_STORE_NAME, {
  reducer: reviews_reducer,
  actions: reviews_actions_namespaceObject,
  controls: src_controls,
  selectors: reviews_selectors_namespaceObject,
  resolvers: reviews_resolvers_namespaceObject
});
var REVIEWS_STORE_NAME = (/* unused pure expression or super */ null && (STORE_NAME));
;// CONCATENATED MODULE: ../../packages/js/data/src/notes/constants.ts
/**
 * Internal dependencies
 */
var notes_constants_STORE_NAME = 'wc/admin/notes';
;// CONCATENATED MODULE: ../../packages/js/data/src/notes/selectors.ts

/**
 * External dependencies
 */


/**
 * Internal dependencies
 */

var getNotes = (0,rememo/* default */.A)(function (state, query) {
  var noteIds = state.noteQueries[JSON.stringify(query)] || [];
  return noteIds.map(function (id) {
    return state.notes[id];
  });
}, function (state, query) {
  return [state.noteQueries[JSON.stringify(query)], state.notes];
});
var getNotesError = function getNotesError(state, selector) {
  return state.errors[selector] || false;
};
var isNotesRequesting = function isNotesRequesting(state, selector) {
  return state.requesting[selector] || false;
};
;// CONCATENATED MODULE: ../../packages/js/data/src/notes/action-types.ts
var notes_action_types_TYPES = {
  SET_ERROR: 'SET_ERROR',
  SET_NOTE: 'SET_NOTE',
  SET_NOTE_IS_UPDATING: 'SET_NOTE_IS_UPDATING',
  SET_NOTES: 'SET_NOTES',
  SET_NOTES_QUERY: 'SET_NOTES_QUERY',
  SET_IS_REQUESTING: 'SET_IS_REQUESTING'
};
/* harmony default export */ const notes_action_types = (notes_action_types_TYPES);
;// CONCATENATED MODULE: ../../packages/js/data/src/notes/actions.ts











function notes_actions_ownKeys(e, r) {
  var t = Object.keys(e);
  if (Object.getOwnPropertySymbols) {
    var o = Object.getOwnPropertySymbols(e);
    r && (o = o.filter(function (r) {
      return Object.getOwnPropertyDescriptor(e, r).enumerable;
    })), t.push.apply(t, o);
  }
  return t;
}
function notes_actions_objectSpread(e) {
  for (var r = 1; r < arguments.length; r++) {
    var t = null != arguments[r] ? arguments[r] : {};
    r % 2 ? notes_actions_ownKeys(Object(t), !0).forEach(function (r) {
      (0,defineProperty/* default */.A)(e, r, t[r]);
    }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(e, Object.getOwnPropertyDescriptors(t)) : notes_actions_ownKeys(Object(t)).forEach(function (r) {
      Object.defineProperty(e, r, Object.getOwnPropertyDescriptor(t, r));
    });
  }
  return e;
}


var notes_actions_marked = /*#__PURE__*/regenerator_default().mark(updateNote),
  notes_actions_marked2 = /*#__PURE__*/regenerator_default().mark(triggerNoteAction),
  notes_actions_marked3 = /*#__PURE__*/regenerator_default().mark(removeNote),
  notes_actions_marked4 = /*#__PURE__*/regenerator_default().mark(batchUpdateNotes);

/**
 * External dependencies
 */


/**
 * Internal dependencies
 */


function setNote(noteId, noteFields) {
  return {
    type: notes_action_types.SET_NOTE,
    noteId: noteId,
    noteFields: noteFields
  };
}
function setNoteIsUpdating(noteId, isUpdating) {
  return {
    type: notes_action_types.SET_NOTE_IS_UPDATING,
    noteId: noteId,
    isUpdating: isUpdating
  };
}
function setNotes(notes) {
  return {
    type: notes_action_types.SET_NOTES,
    notes: notes
  };
}
function setNotesQuery(query, noteIds) {
  return {
    type: notes_action_types.SET_NOTES_QUERY,
    query: query,
    noteIds: noteIds
  };
}
function notes_actions_setError(selector, error) {
  return {
    type: notes_action_types.SET_ERROR,
    error: error,
    selector: selector
  };
}
function notes_actions_setIsRequesting(selector, isRequesting) {
  return {
    type: notes_action_types.SET_IS_REQUESTING,
    selector: selector,
    isRequesting: isRequesting
  };
}
function updateNote(noteId, noteFields) {
  var url, note;
  return regenerator_default().wrap(function updateNote$(_context) {
    while (1) switch (_context.prev = _context.next) {
      case 0:
        _context.next = 2;
        return notes_actions_setIsRequesting('updateNote', true);
      case 2:
        _context.next = 4;
        return setNoteIsUpdating(noteId, true);
      case 4:
        _context.prev = 4;
        url = "".concat(NAMESPACE, "/admin/notes/").concat(noteId);
        _context.next = 8;
        return (0,data_controls_build_module/* apiFetch */.nr)({
          path: url,
          method: 'PUT',
          data: noteFields
        });
      case 8:
        note = _context.sent;
        _context.next = 11;
        return setNote(noteId, note);
      case 11:
        _context.next = 13;
        return notes_actions_setIsRequesting('updateNote', false);
      case 13:
        _context.next = 15;
        return setNoteIsUpdating(noteId, false);
      case 15:
        _context.next = 26;
        break;
      case 17:
        _context.prev = 17;
        _context.t0 = _context["catch"](4);
        _context.next = 21;
        return notes_actions_setError('updateNote', _context.t0);
      case 21:
        _context.next = 23;
        return notes_actions_setIsRequesting('updateNote', false);
      case 23:
        _context.next = 25;
        return setNoteIsUpdating(noteId, false);
      case 25:
        throw new Error();
      case 26:
      case "end":
        return _context.stop();
    }
  }, notes_actions_marked, null, [[4, 17]]);
}
function triggerNoteAction(noteId, actionId) {
  var url, result;
  return regenerator_default().wrap(function triggerNoteAction$(_context2) {
    while (1) switch (_context2.prev = _context2.next) {
      case 0:
        _context2.next = 2;
        return notes_actions_setIsRequesting('triggerNoteAction', true);
      case 2:
        url = "".concat(NAMESPACE, "/admin/notes/").concat(noteId, "/action/").concat(actionId);
        _context2.prev = 3;
        _context2.next = 6;
        return (0,data_controls_build_module/* apiFetch */.nr)({
          path: url,
          method: 'POST'
        });
      case 6:
        result = _context2.sent;
        _context2.next = 9;
        return updateNote(noteId, result);
      case 9:
        _context2.next = 11;
        return notes_actions_setIsRequesting('triggerNoteAction', false);
      case 11:
        _context2.next = 20;
        break;
      case 13:
        _context2.prev = 13;
        _context2.t0 = _context2["catch"](3);
        _context2.next = 17;
        return notes_actions_setError('triggerNoteAction', _context2.t0);
      case 17:
        _context2.next = 19;
        return notes_actions_setIsRequesting('triggerNoteAction', false);
      case 19:
        throw new Error();
      case 20:
      case "end":
        return _context2.stop();
    }
  }, notes_actions_marked2, null, [[3, 13]]);
}
function removeNote(noteId) {
  var url, response;
  return regenerator_default().wrap(function removeNote$(_context3) {
    while (1) switch (_context3.prev = _context3.next) {
      case 0:
        _context3.next = 2;
        return notes_actions_setIsRequesting('removeNote', true);
      case 2:
        _context3.next = 4;
        return setNoteIsUpdating(noteId, true);
      case 4:
        _context3.prev = 4;
        url = "".concat(NAMESPACE, "/admin/notes/delete/").concat(noteId);
        _context3.next = 8;
        return (0,data_controls_build_module/* apiFetch */.nr)({
          path: url,
          method: 'DELETE'
        });
      case 8:
        response = _context3.sent;
        _context3.next = 11;
        return setNote(noteId, response);
      case 11:
        _context3.next = 13;
        return notes_actions_setIsRequesting('removeNote', false);
      case 13:
        return _context3.abrupt("return", response);
      case 16:
        _context3.prev = 16;
        _context3.t0 = _context3["catch"](4);
        _context3.next = 20;
        return notes_actions_setError('removeNote', _context3.t0);
      case 20:
        _context3.next = 22;
        return notes_actions_setIsRequesting('removeNote', false);
      case 22:
        _context3.next = 24;
        return setNoteIsUpdating(noteId, false);
      case 24:
        throw new Error();
      case 25:
      case "end":
        return _context3.stop();
    }
  }, notes_actions_marked3, null, [[4, 16]]);
}
function removeAllNotes() {
  var query = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};
  return /*#__PURE__*/regenerator_default().mark(function _callee() {
    var url, notes;
    return regenerator_default().wrap(function _callee$(_context4) {
      while (1) switch (_context4.prev = _context4.next) {
        case 0:
          _context4.next = 2;
          return notes_actions_setIsRequesting('removeAllNotes', true);
        case 2:
          _context4.prev = 2;
          url = (0,add_query_args/* addQueryArgs */.F)("".concat(NAMESPACE, "/admin/notes/delete/all"), query);
          _context4.next = 6;
          return (0,data_controls_build_module/* apiFetch */.nr)({
            path: url,
            method: 'DELETE'
          });
        case 6:
          notes = _context4.sent;
          _context4.next = 9;
          return setNotes(notes);
        case 9:
          _context4.next = 11;
          return notes_actions_setIsRequesting('removeAllNotes', false);
        case 11:
          return _context4.abrupt("return", notes);
        case 14:
          _context4.prev = 14;
          _context4.t0 = _context4["catch"](2);
          _context4.next = 18;
          return notes_actions_setError('removeAllNotes', _context4.t0);
        case 18:
          _context4.next = 20;
          return notes_actions_setIsRequesting('removeAllNotes', false);
        case 20:
          throw new Error();
        case 21:
        case "end":
          return _context4.stop();
      }
    }, _callee, null, [[2, 14]]);
  })();
}
function batchUpdateNotes(noteIds, noteFields) {
  var url, notes;
  return regenerator_default().wrap(function batchUpdateNotes$(_context5) {
    while (1) switch (_context5.prev = _context5.next) {
      case 0:
        _context5.next = 2;
        return notes_actions_setIsRequesting('batchUpdateNotes', true);
      case 2:
        _context5.prev = 2;
        url = "".concat(NAMESPACE, "/admin/notes/update");
        _context5.next = 6;
        return (0,data_controls_build_module/* apiFetch */.nr)({
          path: url,
          method: 'PUT',
          data: notes_actions_objectSpread({
            noteIds: noteIds
          }, noteFields)
        });
      case 6:
        notes = _context5.sent;
        _context5.next = 9;
        return setNotes(notes);
      case 9:
        _context5.next = 11;
        return notes_actions_setIsRequesting('batchUpdateNotes', false);
      case 11:
        _context5.next = 20;
        break;
      case 13:
        _context5.prev = 13;
        _context5.t0 = _context5["catch"](2);
        _context5.next = 17;
        return notes_actions_setError('updateNote', _context5.t0);
      case 17:
        _context5.next = 19;
        return notes_actions_setIsRequesting('batchUpdateNotes', false);
      case 19:
        throw new Error();
      case 20:
      case "end":
        return _context5.stop();
    }
  }, notes_actions_marked4, null, [[2, 13]]);
}
;// CONCATENATED MODULE: ../../packages/js/data/src/notes/resolvers.ts



/**
 * External dependencies
 */



/**
 * Internal dependencies
 */



function resolvers_getNotes() {
  var query = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};
  return /*#__PURE__*/regenerator_default().mark(function _callee() {
    var url, notes;
    return regenerator_default().wrap(function _callee$(_context) {
      while (1) switch (_context.prev = _context.next) {
        case 0:
          url = (0,add_query_args/* addQueryArgs */.F)("".concat(NAMESPACE, "/admin/notes"), query);
          _context.prev = 1;
          _context.next = 4;
          return checkUserCapability('manage_woocommerce');
        case 4:
          _context.next = 6;
          return (0,data_controls_build_module/* apiFetch */.nr)({
            path: url
          });
        case 6:
          notes = _context.sent;
          _context.next = 9;
          return setNotes(notes);
        case 9:
          _context.next = 11;
          return setNotesQuery(query, notes.map(function (note) {
            return note.id;
          }));
        case 11:
          _context.next = 17;
          break;
        case 13:
          _context.prev = 13;
          _context.t0 = _context["catch"](1);
          _context.next = 17;
          return notes_actions_setError('getNotes', _context.t0);
        case 17:
        case "end":
          return _context.stop();
      }
    }, _callee, null, [[1, 13]]);
  })();
}
;// CONCATENATED MODULE: ../../packages/js/data/src/notes/reducer.ts












function notes_reducer_ownKeys(e, r) {
  var t = Object.keys(e);
  if (Object.getOwnPropertySymbols) {
    var o = Object.getOwnPropertySymbols(e);
    r && (o = o.filter(function (r) {
      return Object.getOwnPropertyDescriptor(e, r).enumerable;
    })), t.push.apply(t, o);
  }
  return t;
}
function notes_reducer_objectSpread(e) {
  for (var r = 1; r < arguments.length; r++) {
    var t = null != arguments[r] ? arguments[r] : {};
    r % 2 ? notes_reducer_ownKeys(Object(t), !0).forEach(function (r) {
      (0,defineProperty/* default */.A)(e, r, t[r]);
    }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(e, Object.getOwnPropertyDescriptors(t)) : notes_reducer_ownKeys(Object(t)).forEach(function (r) {
      Object.defineProperty(e, r, Object.getOwnPropertyDescriptor(t, r));
    });
  }
  return e;
}
/**
 * External dependencies
 */

/**
 * Internal dependencies
 */

var notes_reducer_reducer = function reducer() {
  var state = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {
    errors: {},
    noteQueries: {},
    notes: {},
    requesting: {}
  };
  var action = arguments.length > 1 ? arguments[1] : undefined;
  switch (action.type) {
    case notes_action_types.SET_NOTES:
      state = notes_reducer_objectSpread(notes_reducer_objectSpread({}, state), {}, {
        notes: notes_reducer_objectSpread(notes_reducer_objectSpread({}, state.notes), action.notes.reduce(function (acc, item) {
          acc[item.id] = item;
          return acc;
        }, {}))
      });
      break;
    case notes_action_types.SET_NOTES_QUERY:
      state = notes_reducer_objectSpread(notes_reducer_objectSpread({}, state), {}, {
        noteQueries: notes_reducer_objectSpread(notes_reducer_objectSpread({}, state.noteQueries), {}, (0,defineProperty/* default */.A)({}, JSON.stringify(action.query), action.noteIds))
      });
      break;
    case notes_action_types.SET_ERROR:
      state = notes_reducer_objectSpread(notes_reducer_objectSpread({}, state), {}, {
        errors: notes_reducer_objectSpread(notes_reducer_objectSpread({}, state.errors), {}, (0,defineProperty/* default */.A)({}, action.selector, action.error))
      });
      break;
    case notes_action_types.SET_NOTE:
      state = notes_reducer_objectSpread(notes_reducer_objectSpread({}, state), {}, {
        notes: notes_reducer_objectSpread(notes_reducer_objectSpread({}, state.notes), {}, (0,defineProperty/* default */.A)({}, action.noteId, action.noteFields))
      });
      break;
    case notes_action_types.SET_NOTE_IS_UPDATING:
      state = notes_reducer_objectSpread(notes_reducer_objectSpread({}, state), {}, {
        notes: notes_reducer_objectSpread(notes_reducer_objectSpread({}, state.notes), {}, (0,defineProperty/* default */.A)({}, action.noteId, notes_reducer_objectSpread(notes_reducer_objectSpread({}, state.notes[action.noteId]), {}, {
          isUpdating: action.isUpdating
        })))
      });
      break;
    case notes_action_types.SET_IS_REQUESTING:
      state = notes_reducer_objectSpread(notes_reducer_objectSpread({}, state), {}, {
        requesting: notes_reducer_objectSpread(notes_reducer_objectSpread({}, state.requesting), {}, (0,defineProperty/* default */.A)({}, action.selector, action.isRequesting))
      });
      break;
  }
  return state;
};
/* harmony default export */ const notes_reducer = (notes_reducer_reducer);
;// CONCATENATED MODULE: ../../packages/js/data/src/notes/index.ts
/**
 * External dependencies
 */



/**
 * Internal dependencies
 */







(0,data_build_module/* registerStore */.ti)(notes_constants_STORE_NAME, {
  reducer: notes_reducer,
  actions: notes_actions_namespaceObject,
  controls: data_controls_build_module/* controls */.ne,
  selectors: notes_selectors_namespaceObject,
  resolvers: notes_resolvers_namespaceObject
});
var NOTES_STORE_NAME = (/* unused pure expression or super */ null && (STORE_NAME));
;// CONCATENATED MODULE: ../../packages/js/data/src/reports/constants.ts
/**
 * Internal dependencies
 */
var reports_constants_STORE_NAME = 'wc/admin/reports';
;// CONCATENATED MODULE: ../../packages/js/data/src/reports/selectors.ts
/**
 * Internal dependencies
 */

var EMPTY_OBJECT = {};
var getReportItemsError = function getReportItemsError(state, endpoint, query) {
  var resourceName = utils_getResourceName(endpoint, query);
  return state.itemErrors[resourceName] || false;
};
var getReportItems = function getReportItems(state, endpoint, query) {
  var resourceName = utils_getResourceName(endpoint, query);
  return state.items[resourceName] || EMPTY_OBJECT;
};
var getReportStats = function getReportStats(state, endpoint, query) {
  var resourceName = utils_getResourceName(endpoint, query);
  return state.stats[resourceName] || EMPTY_OBJECT;
};
var getReportStatsError = function getReportStatsError(state, endpoint, query) {
  var resourceName = utils_getResourceName(endpoint, query);
  return state.statErrors[resourceName] || false;
};
;// CONCATENATED MODULE: ../../packages/js/data/src/reports/action-types.ts
var reports_action_types_TYPES = {
  SET_ITEM_ERROR: 'SET_ITEM_ERROR',
  SET_STAT_ERROR: 'SET_STAT_ERROR',
  SET_REPORT_ITEMS: 'SET_REPORT_ITEMS',
  SET_REPORT_STATS: 'SET_REPORT_STATS'
};
/* harmony default export */ const reports_action_types = (reports_action_types_TYPES);
;// CONCATENATED MODULE: ../../packages/js/data/src/reports/actions.ts
/**
 * Internal dependencies
 */


function setReportItemsError(endpoint, query, error) {
  var resourceName = utils_getResourceName(endpoint, query);
  return {
    type: reports_action_types.SET_ITEM_ERROR,
    resourceName: resourceName,
    error: error
  };
}
function setReportItems(endpoint, query, items) {
  var resourceName = utils_getResourceName(endpoint, query);
  return {
    type: reports_action_types.SET_REPORT_ITEMS,
    resourceName: resourceName,
    items: items
  };
}
function setReportStats(endpoint, query, stats) {
  var resourceName = utils_getResourceName(endpoint, query);
  return {
    type: reports_action_types.SET_REPORT_STATS,
    resourceName: resourceName,
    stats: stats
  };
}
function setReportStatsError(endpoint, query, error) {
  var resourceName = utils_getResourceName(endpoint, query);
  return {
    type: reports_action_types.SET_STAT_ERROR,
    resourceName: resourceName,
    error: error
  };
}
;// CONCATENATED MODULE: ../../packages/js/data/src/reports/resolvers.ts



var reports_resolvers_marked = /*#__PURE__*/regenerator_default().mark(resolvers_getReportItems),
  reports_resolvers_marked2 = /*#__PURE__*/regenerator_default().mark(resolvers_getReportStats);



/**
 * External dependencies
 */


/**
 * Internal dependencies
 */



var getIntHeaderValues = function getIntHeaderValues(endpoint, response, keys) {
  return keys.map(function (key) {
    var value = response.headers.get(key);
    if (value === undefined) {
      throw new Error("Malformed response from server. '".concat(key, "' header is missing when retrieving ./report/").concat(endpoint, "."));
    }
    return parseInt(value, 10);
  });
};
function resolvers_getReportItems(endpoint, query) {
  var fetchArgs, response, data, _getIntHeaderValues, _getIntHeaderValues2, totalResults, totalPages;
  return regenerator_default().wrap(function getReportItems$(_context) {
    while (1) switch (_context.prev = _context.next) {
      case 0:
        fetchArgs = {
          parse: false,
          path: (0,add_query_args/* addQueryArgs */.F)("".concat(NAMESPACE, "/reports/").concat(endpoint), query)
        };
        if (!(endpoint === 'performance-indicators' && !query.stats)) {
          _context.next = 5;
          break;
        }
        _context.next = 4;
        return setReportItems(endpoint, query, {
          data: [],
          totalResults: 0,
          totalPages: 0
        });
      case 4:
        return _context.abrupt("return");
      case 5:
        _context.prev = 5;
        _context.next = 8;
        return fetchWithHeaders(fetchArgs);
      case 8:
        response = _context.sent;
        data = response.data;
        _getIntHeaderValues = getIntHeaderValues(endpoint, response, ['x-wp-total', 'x-wp-totalpages']), _getIntHeaderValues2 = (0,slicedToArray/* default */.A)(_getIntHeaderValues, 2), totalResults = _getIntHeaderValues2[0], totalPages = _getIntHeaderValues2[1];
        _context.next = 13;
        return setReportItems(endpoint, query, {
          data: data,
          totalResults: totalResults,
          totalPages: totalPages
        });
      case 13:
        _context.next = 19;
        break;
      case 15:
        _context.prev = 15;
        _context.t0 = _context["catch"](5);
        _context.next = 19;
        return setReportItemsError(endpoint, query, _context.t0);
      case 19:
      case "end":
        return _context.stop();
    }
  }, reports_resolvers_marked, null, [[5, 15]]);
}
function resolvers_getReportStats(endpoint, query) {
  var fetchArgs, response, data, _getIntHeaderValues3, _getIntHeaderValues4, totalResults, totalPages;
  return regenerator_default().wrap(function getReportStats$(_context2) {
    while (1) switch (_context2.prev = _context2.next) {
      case 0:
        fetchArgs = {
          parse: false,
          path: (0,add_query_args/* addQueryArgs */.F)("".concat(NAMESPACE, "/reports/").concat(endpoint, "/stats"), query)
        };
        _context2.prev = 1;
        _context2.next = 4;
        return fetchWithHeaders(fetchArgs);
      case 4:
        response = _context2.sent;
        data = response.data;
        _getIntHeaderValues3 = getIntHeaderValues(endpoint, response, ['x-wp-total', 'x-wp-totalpages']), _getIntHeaderValues4 = (0,slicedToArray/* default */.A)(_getIntHeaderValues3, 2), totalResults = _getIntHeaderValues4[0], totalPages = _getIntHeaderValues4[1];
        _context2.next = 9;
        return setReportStats(endpoint, query, {
          data: data,
          totalResults: totalResults,
          totalPages: totalPages
        });
      case 9:
        _context2.next = 15;
        break;
      case 11:
        _context2.prev = 11;
        _context2.t0 = _context2["catch"](1);
        _context2.next = 15;
        return setReportStatsError(endpoint, query, _context2.t0);
      case 15:
      case "end":
        return _context2.stop();
    }
  }, reports_resolvers_marked2, null, [[1, 11]]);
}
;// CONCATENATED MODULE: ../../packages/js/data/src/reports/reducer.ts











function reports_reducer_ownKeys(e, r) {
  var t = Object.keys(e);
  if (Object.getOwnPropertySymbols) {
    var o = Object.getOwnPropertySymbols(e);
    r && (o = o.filter(function (r) {
      return Object.getOwnPropertyDescriptor(e, r).enumerable;
    })), t.push.apply(t, o);
  }
  return t;
}
function reports_reducer_objectSpread(e) {
  for (var r = 1; r < arguments.length; r++) {
    var t = null != arguments[r] ? arguments[r] : {};
    r % 2 ? reports_reducer_ownKeys(Object(t), !0).forEach(function (r) {
      (0,defineProperty/* default */.A)(e, r, t[r]);
    }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(e, Object.getOwnPropertyDescriptors(t)) : reports_reducer_ownKeys(Object(t)).forEach(function (r) {
      Object.defineProperty(e, r, Object.getOwnPropertyDescriptor(t, r));
    });
  }
  return e;
}
/**
 * External dependencies
 */

/**
 * Internal dependencies
 */

var reducer_initialState = {
  itemErrors: {},
  items: {},
  statErrors: {},
  stats: {}
};
var reports_reducer_reducer = function reducer() {
  var state = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : reducer_initialState;
  var action = arguments.length > 1 ? arguments[1] : undefined;
  switch (action.type) {
    case reports_action_types.SET_REPORT_ITEMS:
      return reports_reducer_objectSpread(reports_reducer_objectSpread({}, state), {}, {
        items: reports_reducer_objectSpread(reports_reducer_objectSpread({}, state.items), {}, (0,defineProperty/* default */.A)({}, action.resourceName, action.items))
      });
    case reports_action_types.SET_REPORT_STATS:
      return reports_reducer_objectSpread(reports_reducer_objectSpread({}, state), {}, {
        stats: reports_reducer_objectSpread(reports_reducer_objectSpread({}, state.stats), {}, (0,defineProperty/* default */.A)({}, action.resourceName, action.stats))
      });
    case reports_action_types.SET_ITEM_ERROR:
      return reports_reducer_objectSpread(reports_reducer_objectSpread({}, state), {}, {
        itemErrors: reports_reducer_objectSpread(reports_reducer_objectSpread({}, state.itemErrors), {}, (0,defineProperty/* default */.A)({}, action.resourceName, action.error))
      });
    case reports_action_types.SET_STAT_ERROR:
      return reports_reducer_objectSpread(reports_reducer_objectSpread({}, state), {}, {
        statErrors: reports_reducer_objectSpread(reports_reducer_objectSpread({}, state.statErrors), {}, (0,defineProperty/* default */.A)({}, action.resourceName, action.error))
      });
    default:
      return state;
  }
};
/* harmony default export */ const reports_reducer = (reports_reducer_reducer);
;// CONCATENATED MODULE: ../../packages/js/data/src/reports/index.ts
/**
 * External dependencies
 */


/**
 * Internal dependencies
 */







(0,data_build_module/* registerStore */.ti)(reports_constants_STORE_NAME, {
  reducer: reports_reducer,
  actions: reports_actions_namespaceObject,
  controls: src_controls,
  selectors: reports_selectors_namespaceObject,
  resolvers: reports_resolvers_namespaceObject
});
var REPORTS_STORE_NAME = (/* unused pure expression or super */ null && (STORE_NAME));
;// CONCATENATED MODULE: ../../packages/js/data/src/countries/constants.ts
var countries_constants_STORE_NAME = 'wc/admin/countries';
;// CONCATENATED MODULE: ../../packages/js/data/src/countries/selectors.ts


/**
 * Internal dependencies
 */

var getLocales = function getLocales(state) {
  return state.locales;
};
var getLocale = function getLocale(state, id) {
  var country = id.split(':')[0];
  return state.locales[country];
};
var getCountries = function getCountries(state) {
  return state.countries;
};
var getCountry = function getCountry(state, code) {
  return state.countries.find(function (country) {
    return country.code === code;
  });
};
var geolocate = function geolocate(state) {
  return state.geolocation;
};
;// CONCATENATED MODULE: ../../packages/js/data/src/countries/action-types.ts
var countries_action_types_TYPES = /*#__PURE__*/function (TYPES) {
  TYPES["GET_LOCALES_ERROR"] = "GET_LOCALES_ERROR";
  TYPES["GET_LOCALES_SUCCESS"] = "GET_LOCALES_SUCCESS";
  TYPES["GET_COUNTRIES_ERROR"] = "GET_COUNTRIES_ERROR";
  TYPES["GET_COUNTRIES_SUCCESS"] = "GET_COUNTRIES_SUCCESS";
  TYPES["GEOLOCATION_SUCCESS"] = "GEOLOCATION_SUCCESS";
  TYPES["GEOLOCATION_ERROR"] = "GEOLOCATION_ERROR";
  return TYPES;
}({});
/* harmony default export */ const countries_action_types = (countries_action_types_TYPES);
;// CONCATENATED MODULE: ../../packages/js/data/src/countries/actions.ts
/**
 * Internal dependencies
 */

function getLocalesSuccess(locales) {
  return {
    type: countries_action_types.GET_LOCALES_SUCCESS,
    locales: locales
  };
}
function getLocalesError(error) {
  return {
    type: countries_action_types.GET_LOCALES_ERROR,
    error: error
  };
}
function getCountriesSuccess(countries) {
  return {
    type: countries_action_types.GET_COUNTRIES_SUCCESS,
    countries: countries
  };
}
function getCountriesError(error) {
  return {
    type: countries_action_types.GET_COUNTRIES_ERROR,
    error: error
  };
}
function geolocationSuccess(geolocation) {
  return {
    type: countries_action_types.GEOLOCATION_SUCCESS,
    geolocation: geolocation
  };
}
function geolocationError(error) {
  return {
    type: countries_action_types.GEOLOCATION_ERROR,
    error: error
  };
}
;// CONCATENATED MODULE: ../../packages/js/data/src/countries/resolvers.ts






var countries_resolvers_marked = /*#__PURE__*/regenerator_default().mark(resolvers_getLocale),
  countries_resolvers_marked2 = /*#__PURE__*/regenerator_default().mark(resolvers_getLocales),
  countries_resolvers_marked3 = /*#__PURE__*/regenerator_default().mark(resolvers_getCountry),
  countries_resolvers_marked4 = /*#__PURE__*/regenerator_default().mark(resolvers_getCountries);
/**
 * External dependencies
 */



/**
 * Internal dependencies
 */




var countries_resolvers_resolveSelect = build_module_controls/* controls */.n && build_module_controls/* controls */.n.resolveSelect ? build_module_controls/* controls */.n.resolveSelect : data_controls_build_module/* select */.Lt;
function resolvers_getLocale() {
  return regenerator_default().wrap(function getLocale$(_context) {
    while (1) switch (_context.prev = _context.next) {
      case 0:
        _context.next = 2;
        return countries_resolvers_resolveSelect(countries_constants_STORE_NAME, 'getLocales');
      case 2:
      case "end":
        return _context.stop();
    }
  }, countries_resolvers_marked);
}
function resolvers_getLocales() {
  var url, results;
  return regenerator_default().wrap(function getLocales$(_context2) {
    while (1) switch (_context2.prev = _context2.next) {
      case 0:
        _context2.prev = 0;
        url = NAMESPACE + '/data/countries/locales';
        _context2.next = 4;
        return (0,data_controls_build_module/* apiFetch */.nr)({
          path: url,
          method: 'GET'
        });
      case 4:
        results = _context2.sent;
        return _context2.abrupt("return", getLocalesSuccess(results));
      case 8:
        _context2.prev = 8;
        _context2.t0 = _context2["catch"](0);
        return _context2.abrupt("return", getLocalesError(_context2.t0));
      case 11:
      case "end":
        return _context2.stop();
    }
  }, countries_resolvers_marked2, null, [[0, 8]]);
}
function resolvers_getCountry() {
  return regenerator_default().wrap(function getCountry$(_context3) {
    while (1) switch (_context3.prev = _context3.next) {
      case 0:
        _context3.next = 2;
        return countries_resolvers_resolveSelect(countries_constants_STORE_NAME, 'getCountries');
      case 2:
      case "end":
        return _context3.stop();
    }
  }, countries_resolvers_marked3);
}
function resolvers_getCountries() {
  var url, results;
  return regenerator_default().wrap(function getCountries$(_context4) {
    while (1) switch (_context4.prev = _context4.next) {
      case 0:
        _context4.prev = 0;
        url = NAMESPACE + '/data/countries';
        _context4.next = 4;
        return (0,data_controls_build_module/* apiFetch */.nr)({
          path: url,
          method: 'GET'
        });
      case 4:
        results = _context4.sent;
        return _context4.abrupt("return", getCountriesSuccess(results));
      case 8:
        _context4.prev = 8;
        _context4.t0 = _context4["catch"](0);
        return _context4.abrupt("return", getCountriesError(_context4.t0));
      case 11:
      case "end":
        return _context4.stop();
    }
  }, countries_resolvers_marked4, null, [[0, 8]]);
}
var resolvers_geolocate = function geolocate() {
  return /*#__PURE__*/function () {
    var _ref2 = (0,asyncToGenerator/* default */.A)( /*#__PURE__*/regenerator_default().mark(function _callee(_ref) {
      var dispatch, url, response, result;
      return regenerator_default().wrap(function _callee$(_context5) {
        while (1) switch (_context5.prev = _context5.next) {
          case 0:
            dispatch = _ref.dispatch;
            _context5.prev = 1;
            url = "https://public-api.wordpress.com/geo/?v=".concat(new Date().getTime());
            _context5.next = 5;
            return fetch(url, {
              method: 'GET'
            });
          case 5:
            response = _context5.sent;
            _context5.next = 8;
            return response.json();
          case 8:
            result = _context5.sent;
            dispatch.geolocationSuccess(result);
            _context5.next = 15;
            break;
          case 12:
            _context5.prev = 12;
            _context5.t0 = _context5["catch"](1);
            dispatch.geolocationError(_context5.t0);
          case 15:
          case "end":
            return _context5.stop();
        }
      }, _callee, null, [[1, 12]]);
    }));
    return function (_x) {
      return _ref2.apply(this, arguments);
    };
  }();
};
;// CONCATENATED MODULE: ../../packages/js/data/src/countries/reducer.ts











function countries_reducer_ownKeys(e, r) {
  var t = Object.keys(e);
  if (Object.getOwnPropertySymbols) {
    var o = Object.getOwnPropertySymbols(e);
    r && (o = o.filter(function (r) {
      return Object.getOwnPropertyDescriptor(e, r).enumerable;
    })), t.push.apply(t, o);
  }
  return t;
}
function countries_reducer_objectSpread(e) {
  for (var r = 1; r < arguments.length; r++) {
    var t = null != arguments[r] ? arguments[r] : {};
    r % 2 ? countries_reducer_ownKeys(Object(t), !0).forEach(function (r) {
      (0,defineProperty/* default */.A)(e, r, t[r]);
    }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(e, Object.getOwnPropertyDescriptors(t)) : countries_reducer_ownKeys(Object(t)).forEach(function (r) {
      Object.defineProperty(e, r, Object.getOwnPropertyDescriptor(t, r));
    });
  }
  return e;
}
/**
 * External dependencies
 */

/**
 * Internal dependencies
 */

var countries_reducer_reducer = function reducer() {
  var state = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {
    errors: {},
    locales: {},
    countries: [],
    geolocation: undefined
  };
  var action = arguments.length > 1 ? arguments[1] : undefined;
  switch (action.type) {
    case countries_action_types.GET_LOCALES_SUCCESS:
      state = countries_reducer_objectSpread(countries_reducer_objectSpread({}, state), {}, {
        locales: action.locales
      });
      break;
    case countries_action_types.GET_LOCALES_ERROR:
      state = countries_reducer_objectSpread(countries_reducer_objectSpread({}, state), {}, {
        errors: countries_reducer_objectSpread(countries_reducer_objectSpread({}, state.errors), {}, {
          locales: action.error
        })
      });
      break;
    case countries_action_types.GET_COUNTRIES_SUCCESS:
      state = countries_reducer_objectSpread(countries_reducer_objectSpread({}, state), {}, {
        countries: action.countries
      });
      break;
    case countries_action_types.GET_COUNTRIES_ERROR:
      state = countries_reducer_objectSpread(countries_reducer_objectSpread({}, state), {}, {
        errors: countries_reducer_objectSpread(countries_reducer_objectSpread({}, state.errors), {}, {
          countries: action.error
        })
      });
      break;
    case countries_action_types.GEOLOCATION_SUCCESS:
      state = countries_reducer_objectSpread(countries_reducer_objectSpread({}, state), {}, {
        geolocation: action.geolocation
      });
      break;
    case countries_action_types.GEOLOCATION_ERROR:
      state = countries_reducer_objectSpread(countries_reducer_objectSpread({}, state), {}, {
        errors: countries_reducer_objectSpread(countries_reducer_objectSpread({}, state.errors), {}, {
          geolocation: action.error
        })
      });
      break;
  }
  return state;
};
/* harmony default export */ const countries_reducer = (countries_reducer_reducer);
;// CONCATENATED MODULE: ../../packages/js/data/src/countries/index.ts
/**
 * External dependencies
 */


/**
 * Internal dependencies
 */






(0,data_build_module/* registerStore */.ti)(countries_constants_STORE_NAME, {
  reducer: countries_reducer,
  actions: countries_actions_namespaceObject,
  controls: data_controls_build_module/* controls */.ne,
  selectors: countries_selectors_namespaceObject,
  resolvers: countries_resolvers_namespaceObject
});
var COUNTRIES_STORE_NAME = (/* unused pure expression or super */ null && (STORE_NAME));
;// CONCATENATED MODULE: ../../packages/js/data/src/navigation/constants.ts
var navigation_constants_STORE_NAME = 'woocommerce-navigation';
;// CONCATENATED MODULE: ../../packages/js/data/src/navigation/selectors.ts
/**
 * External dependencies
 */


/**
 * Internal dependencies
 */

var MENU_ITEMS_HOOK = 'woocommerce_navigation_menu_items';
var getMenuItems = function getMenuItems(state) {
  /**
   * Navigation Menu Items.
   *
   * @filter woocommerce_navigation_menu_items
   * @param {Array.<Object>} menuItems Array of Navigation menu items.
   */
  return (0,hooks_build_module/* applyFilters */.W5)(MENU_ITEMS_HOOK, state.menuItems);
};
var getFavorites = function getFavorites(state) {
  return state.favorites || [];
};
var isNavigationRequesting = function isNavigationRequesting(state, selector) {
  return state.requesting[selector] || false;
};
var getPersistedQuery = function getPersistedQuery(state) {
  return state.persistedQuery || {};
};
// EXTERNAL MODULE: ../../packages/js/navigation/src/index.js + 3 modules
var navigation_src = __webpack_require__("../../packages/js/navigation/src/index.js");
;// CONCATENATED MODULE: ../../packages/js/data/src/navigation/action-types.ts
var navigation_action_types_TYPES = {
  ADD_MENU_ITEMS: 'ADD_MENU_ITEMS',
  SET_MENU_ITEMS: 'SET_MENU_ITEMS',
  ON_HISTORY_CHANGE: 'ON_HISTORY_CHANGE',
  ADD_FAVORITE_FAILURE: 'ADD_FAVORITE_FAILURE',
  ADD_FAVORITE_REQUEST: 'ADD_FAVORITE_REQUEST',
  ADD_FAVORITE_SUCCESS: 'ADD_FAVORITE_SUCCESS',
  GET_FAVORITES_FAILURE: 'GET_FAVORITES_FAILURE',
  GET_FAVORITES_REQUEST: 'GET_FAVORITES_REQUEST',
  GET_FAVORITES_SUCCESS: 'GET_FAVORITES_SUCCESS',
  REMOVE_FAVORITE_FAILURE: 'REMOVE_FAVORITE_FAILURE',
  REMOVE_FAVORITE_REQUEST: 'REMOVE_FAVORITE_REQUEST',
  REMOVE_FAVORITE_SUCCESS: 'REMOVE_FAVORITE_SUCCESS'
};
/* harmony default export */ const navigation_action_types = (navigation_action_types_TYPES);
;// CONCATENATED MODULE: ../../packages/js/data/src/navigation/actions.ts


var navigation_actions_marked = /*#__PURE__*/regenerator_default().mark(onHistoryChange),
  navigation_actions_marked2 = /*#__PURE__*/regenerator_default().mark(onLoad),
  navigation_actions_marked3 = /*#__PURE__*/regenerator_default().mark(addFavorite),
  navigation_actions_marked4 = /*#__PURE__*/regenerator_default().mark(removeFavorite);

/**
 * External dependencies
 */



/**
 * Internal dependencies
 */


function setMenuItems(menuItems) {
  return {
    type: navigation_action_types.SET_MENU_ITEMS,
    menuItems: menuItems
  };
}
function addMenuItems(menuItems) {
  return {
    type: navigation_action_types.ADD_MENU_ITEMS,
    menuItems: menuItems
  };
}
function getFavoritesFailure(error) {
  return {
    type: navigation_action_types.GET_FAVORITES_FAILURE,
    error: error
  };
}
function getFavoritesRequest(favorites) {
  return {
    type: navigation_action_types.GET_FAVORITES_REQUEST,
    favorites: favorites
  };
}
function getFavoritesSuccess(favorites) {
  return {
    type: navigation_action_types.GET_FAVORITES_SUCCESS,
    favorites: favorites
  };
}
function addFavoriteRequest(favorite) {
  return {
    type: navigation_action_types.ADD_FAVORITE_REQUEST,
    favorite: favorite
  };
}
function addFavoriteFailure(favorite, error) {
  return {
    type: navigation_action_types.ADD_FAVORITE_FAILURE,
    favorite: favorite,
    error: error
  };
}
function addFavoriteSuccess(favorite) {
  return {
    type: navigation_action_types.ADD_FAVORITE_SUCCESS,
    favorite: favorite
  };
}
function removeFavoriteRequest(favorite) {
  return {
    type: navigation_action_types.REMOVE_FAVORITE_REQUEST,
    favorite: favorite
  };
}
function removeFavoriteFailure(favorite, error) {
  return {
    type: navigation_action_types.REMOVE_FAVORITE_FAILURE,
    favorite: favorite,
    error: error
  };
}
function removeFavoriteSuccess(favorite) {
  return {
    type: navigation_action_types.REMOVE_FAVORITE_SUCCESS,
    favorite: favorite
  };
}
function onHistoryChange() {
  var persistedQuery;
  return regenerator_default().wrap(function onHistoryChange$(_context) {
    while (1) switch (_context.prev = _context.next) {
      case 0:
        persistedQuery = (0,navigation_src/* getPersistedQuery */.aK)();
        if (Object.keys(persistedQuery).length) {
          _context.next = 3;
          break;
        }
        return _context.abrupt("return", null);
      case 3:
        _context.next = 5;
        return {
          type: navigation_action_types.ON_HISTORY_CHANGE,
          persistedQuery: persistedQuery
        };
      case 5:
      case "end":
        return _context.stop();
    }
  }, navigation_actions_marked);
}
function onLoad() {
  return regenerator_default().wrap(function onLoad$(_context2) {
    while (1) switch (_context2.prev = _context2.next) {
      case 0:
        _context2.next = 2;
        return onHistoryChange();
      case 2:
      case "end":
        return _context2.stop();
    }
  }, navigation_actions_marked2);
}
function addFavorite(favorite) {
  var results;
  return regenerator_default().wrap(function addFavorite$(_context3) {
    while (1) switch (_context3.prev = _context3.next) {
      case 0:
        _context3.next = 2;
        return addFavoriteRequest(favorite);
      case 2:
        _context3.prev = 2;
        _context3.next = 5;
        return (0,api_fetch_build_module/* default */.A)({
          path: "".concat(WC_ADMIN_NAMESPACE, "/navigation/favorites/me"),
          method: 'POST',
          data: {
            item_id: favorite
          }
        });
      case 5:
        results = _context3.sent;
        if (!results) {
          _context3.next = 10;
          break;
        }
        _context3.next = 9;
        return addFavoriteSuccess(favorite);
      case 9:
        return _context3.abrupt("return", results);
      case 10:
        throw new Error();
      case 13:
        _context3.prev = 13;
        _context3.t0 = _context3["catch"](2);
        _context3.next = 17;
        return addFavoriteFailure(favorite, _context3.t0);
      case 17:
        throw new Error();
      case 18:
      case "end":
        return _context3.stop();
    }
  }, navigation_actions_marked3, null, [[2, 13]]);
}
function removeFavorite(favorite) {
  var results;
  return regenerator_default().wrap(function removeFavorite$(_context4) {
    while (1) switch (_context4.prev = _context4.next) {
      case 0:
        _context4.next = 2;
        return removeFavoriteRequest(favorite);
      case 2:
        _context4.prev = 2;
        _context4.next = 5;
        return (0,api_fetch_build_module/* default */.A)({
          path: "".concat(WC_ADMIN_NAMESPACE, "/navigation/favorites/me"),
          method: 'DELETE',
          data: {
            item_id: favorite
          }
        });
      case 5:
        results = _context4.sent;
        if (!results) {
          _context4.next = 10;
          break;
        }
        _context4.next = 9;
        return removeFavoriteSuccess(favorite);
      case 9:
        return _context4.abrupt("return", results);
      case 10:
        throw new Error();
      case 13:
        _context4.prev = 13;
        _context4.t0 = _context4["catch"](2);
        _context4.next = 17;
        return removeFavoriteFailure(favorite, _context4.t0);
      case 17:
        throw new Error();
      case 18:
      case "end":
        return _context4.stop();
    }
  }, navigation_actions_marked4, null, [[2, 13]]);
}
;// CONCATENATED MODULE: ../../packages/js/data/src/navigation/reducer.ts
















function navigation_reducer_ownKeys(e, r) {
  var t = Object.keys(e);
  if (Object.getOwnPropertySymbols) {
    var o = Object.getOwnPropertySymbols(e);
    r && (o = o.filter(function (r) {
      return Object.getOwnPropertyDescriptor(e, r).enumerable;
    })), t.push.apply(t, o);
  }
  return t;
}
function navigation_reducer_objectSpread(e) {
  for (var r = 1; r < arguments.length; r++) {
    var t = null != arguments[r] ? arguments[r] : {};
    r % 2 ? navigation_reducer_ownKeys(Object(t), !0).forEach(function (r) {
      (0,defineProperty/* default */.A)(e, r, t[r]);
    }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(e, Object.getOwnPropertyDescriptors(t)) : navigation_reducer_ownKeys(Object(t)).forEach(function (r) {
      Object.defineProperty(e, r, Object.getOwnPropertyDescriptor(t, r));
    });
  }
  return e;
}
/**
 * External dependencies
 */

/**
 * Internal dependencies
 */

var navigation_reducer_reducer = function reducer() {
  var state = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {
    error: null,
    menuItems: [],
    favorites: [],
    requesting: {},
    persistedQuery: {}
  };
  var action = arguments.length > 1 ? arguments[1] : undefined;
  switch (action.type) {
    case navigation_action_types.SET_MENU_ITEMS:
      state = navigation_reducer_objectSpread(navigation_reducer_objectSpread({}, state), {}, {
        menuItems: action.menuItems
      });
      break;
    case navigation_action_types.ADD_MENU_ITEMS:
      state = navigation_reducer_objectSpread(navigation_reducer_objectSpread({}, state), {}, {
        menuItems: [].concat((0,toConsumableArray/* default */.A)(state.menuItems), (0,toConsumableArray/* default */.A)(action.menuItems))
      });
      break;
    case navigation_action_types.ON_HISTORY_CHANGE:
      state = navigation_reducer_objectSpread(navigation_reducer_objectSpread({}, state), {}, {
        persistedQuery: action.persistedQuery
      });
      break;
    case navigation_action_types.GET_FAVORITES_FAILURE:
      state = navigation_reducer_objectSpread(navigation_reducer_objectSpread({}, state), {}, {
        requesting: navigation_reducer_objectSpread(navigation_reducer_objectSpread({}, state.requesting), {}, {
          getFavorites: false
        })
      });
      break;
    case navigation_action_types.GET_FAVORITES_REQUEST:
      state = navigation_reducer_objectSpread(navigation_reducer_objectSpread({}, state), {}, {
        requesting: navigation_reducer_objectSpread(navigation_reducer_objectSpread({}, state.requesting), {}, {
          getFavorites: true
        })
      });
      break;
    case navigation_action_types.GET_FAVORITES_SUCCESS:
      state = navigation_reducer_objectSpread(navigation_reducer_objectSpread({}, state), {}, {
        favorites: action.favorites,
        requesting: navigation_reducer_objectSpread(navigation_reducer_objectSpread({}, state.requesting), {}, {
          getFavorites: false
        })
      });
      break;
    case navigation_action_types.ADD_FAVORITE_FAILURE:
      state = navigation_reducer_objectSpread(navigation_reducer_objectSpread({}, state), {}, {
        error: action.error,
        requesting: navigation_reducer_objectSpread(navigation_reducer_objectSpread({}, state.requesting), {}, {
          addFavorite: false
        })
      });
      break;
    case navigation_action_types.ADD_FAVORITE_REQUEST:
      state = navigation_reducer_objectSpread(navigation_reducer_objectSpread({}, state), {}, {
        requesting: navigation_reducer_objectSpread(navigation_reducer_objectSpread({}, state.requesting), {}, {
          addFavorite: true
        })
      });
      break;
    case navigation_action_types.ADD_FAVORITE_SUCCESS:
      var newFavorites = !state.favorites.includes(action.favorite) ? [].concat((0,toConsumableArray/* default */.A)(state.favorites), [action.favorite]) : state.favorites;
      state = navigation_reducer_objectSpread(navigation_reducer_objectSpread({}, state), {}, {
        favorites: newFavorites,
        menuItems: state.menuItems.map(function (item) {
          if (item.id === action.favorite) {
            return navigation_reducer_objectSpread(navigation_reducer_objectSpread({}, item), {}, {
              menuId: 'favorites'
            });
          }
          return item;
        }),
        requesting: navigation_reducer_objectSpread(navigation_reducer_objectSpread({}, state.requesting), {}, {
          addFavorite: false
        })
      });
      break;
    case navigation_action_types.REMOVE_FAVORITE_FAILURE:
      state = navigation_reducer_objectSpread(navigation_reducer_objectSpread({}, state), {}, {
        requesting: navigation_reducer_objectSpread(navigation_reducer_objectSpread({}, state.requesting), {}, {
          error: action.error,
          removeFavorite: false
        })
      });
      break;
    case navigation_action_types.REMOVE_FAVORITE_REQUEST:
      state = navigation_reducer_objectSpread(navigation_reducer_objectSpread({}, state), {}, {
        requesting: navigation_reducer_objectSpread(navigation_reducer_objectSpread({}, state.requesting), {}, {
          removeFavorite: true
        })
      });
      break;
    case navigation_action_types.REMOVE_FAVORITE_SUCCESS:
      var filteredFavorites = state.favorites.filter(function (f) {
        return f !== action.favorite;
      });
      state = navigation_reducer_objectSpread(navigation_reducer_objectSpread({}, state), {}, {
        favorites: filteredFavorites,
        menuItems: state.menuItems.map(function (item) {
          if (item.id === action.favorite) {
            return navigation_reducer_objectSpread(navigation_reducer_objectSpread({}, item), {}, {
              menuId: 'plugins'
            });
          }
          return item;
        }),
        requesting: navigation_reducer_objectSpread(navigation_reducer_objectSpread({}, state.requesting), {}, {
          removeFavorite: false
        })
      });
      break;
  }
  return state;
};
/* harmony default export */ const navigation_reducer = (navigation_reducer_reducer);
;// CONCATENATED MODULE: ../../packages/js/data/src/navigation/resolvers.ts


var navigation_resolvers_marked = /*#__PURE__*/regenerator_default().mark(resolvers_getFavorites);
/**
 * External dependencies
 */


/**
 * Internal dependencies
 */


function resolvers_getFavorites() {
  var results;
  return regenerator_default().wrap(function getFavorites$(_context) {
    while (1) switch (_context.prev = _context.next) {
      case 0:
        _context.next = 2;
        return getFavoritesRequest();
      case 2:
        _context.prev = 2;
        _context.next = 5;
        return (0,data_controls_build_module/* apiFetch */.nr)({
          path: "".concat(WC_ADMIN_NAMESPACE, "/navigation/favorites/me")
        });
      case 5:
        results = _context.sent;
        if (!results) {
          _context.next = 10;
          break;
        }
        _context.next = 9;
        return getFavoritesSuccess(results);
      case 9:
        return _context.abrupt("return");
      case 10:
        throw new Error();
      case 13:
        _context.prev = 13;
        _context.t0 = _context["catch"](2);
        _context.next = 17;
        return getFavoritesFailure(_context.t0);
      case 17:
        throw new Error();
      case 18:
      case "end":
        return _context.stop();
    }
  }, navigation_resolvers_marked, null, [[2, 13]]);
}
;// CONCATENATED MODULE: ../../packages/js/data/src/navigation/dispatchers.ts




/**
 * External dependencies
 */



/**
 * Internal dependencies
 */

/* harmony default export */ const dispatchers = (/*#__PURE__*/(0,asyncToGenerator/* default */.A)( /*#__PURE__*/regenerator_default().mark(function _callee3() {
  var _dispatch, onLoad, onHistoryChange;
  return regenerator_default().wrap(function _callee3$(_context3) {
    while (1) switch (_context3.prev = _context3.next) {
      case 0:
        _dispatch = (0,data_build_module/* dispatch */.JD)(navigation_constants_STORE_NAME), onLoad = _dispatch.onLoad, onHistoryChange = _dispatch.onHistoryChange;
        _context3.next = 3;
        return onLoad();
      case 3:
        (0,navigation_src/* addHistoryListener */.Oo)( /*#__PURE__*/(0,asyncToGenerator/* default */.A)( /*#__PURE__*/regenerator_default().mark(function _callee2() {
          return regenerator_default().wrap(function _callee2$(_context2) {
            while (1) switch (_context2.prev = _context2.next) {
              case 0:
                setTimeout( /*#__PURE__*/(0,asyncToGenerator/* default */.A)( /*#__PURE__*/regenerator_default().mark(function _callee() {
                  return regenerator_default().wrap(function _callee$(_context) {
                    while (1) switch (_context.prev = _context.next) {
                      case 0:
                        _context.next = 2;
                        return onHistoryChange();
                      case 2:
                      case "end":
                        return _context.stop();
                    }
                  }, _callee);
                })), 0);
              case 1:
              case "end":
                return _context2.stop();
            }
          }, _callee2);
        })));
      case 4:
      case "end":
        return _context3.stop();
    }
  }, _callee3);
})));
;// CONCATENATED MODULE: ../../packages/js/data/src/navigation/index.ts
/**
 * External dependencies
 */


/**
 * Internal dependencies
 */






(0,data_build_module/* registerStore */.ti)(navigation_constants_STORE_NAME, {
  reducer: navigation_reducer,
  actions: navigation_actions_namespaceObject,
  controls: data_controls_build_module/* controls */.ne,
  resolvers: navigation_resolvers_namespaceObject,
  selectors: navigation_selectors_namespaceObject
});
dispatchers();
var NAVIGATION_STORE_NAME = (/* unused pure expression or super */ null && (STORE_NAME));
;// CONCATENATED MODULE: ../../packages/js/data/src/items/constants.ts
var items_constants_STORE_NAME = 'wc/admin/items';
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.map.js
var es_map = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.map.js");
// EXTERNAL MODULE: ../../packages/js/date/src/index.ts
var date_src = __webpack_require__("../../packages/js/date/src/index.ts");
;// CONCATENATED MODULE: ../../packages/js/data/src/items/utils.ts









var utils_excluded = ["_fields", "page", "per_page"];



function items_utils_ownKeys(e, r) {
  var t = Object.keys(e);
  if (Object.getOwnPropertySymbols) {
    var o = Object.getOwnPropertySymbols(e);
    r && (o = o.filter(function (r) {
      return Object.getOwnPropertyDescriptor(e, r).enumerable;
    })), t.push.apply(t, o);
  }
  return t;
}
function items_utils_objectSpread(e) {
  for (var r = 1; r < arguments.length; r++) {
    var t = null != arguments[r] ? arguments[r] : {};
    r % 2 ? items_utils_ownKeys(Object(t), !0).forEach(function (r) {
      (0,defineProperty/* default */.A)(e, r, t[r]);
    }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(e, Object.getOwnPropertyDescriptors(t)) : items_utils_ownKeys(Object(t)).forEach(function (r) {
      Object.defineProperty(e, r, Object.getOwnPropertyDescriptor(t, r));
    });
  }
  return e;
}
/**
 * External dependencies
 */

/**
 * Internal dependencies
 */


/**
 * Returns leaderboard data to render a leaderboard table.
 *
 * @param {Object} options                  arguments
 * @param {string} options.id               Leaderboard ID
 * @param {number} options.per_page         Per page limit
 * @param {Object} options.persisted_query  Persisted query passed to endpoint
 * @param {Object} options.query            Query parameters in the url
 * @param {Object} options.filterQuery      Query parameters to filter the leaderboard
 * @param {Object} options.select           Instance of @wordpress/select
 * @param {string} options.defaultDateRange User specified default date range.
 * @return {Object} Object containing leaderboard responses.
 */
function getLeaderboard(options) {
  var endpoint = 'leaderboards';
  var perPage = options.per_page,
    persistedQuery = options.persisted_query,
    query = options.query,
    select = options.select,
    filterQuery = options.filterQuery;
  var _select = select(STORE_NAME),
    getItems = _select.getItems,
    getItemsError = _select.getItemsError,
    isResolving = _select.isResolving;
  var response = {
    isRequesting: false,
    isError: false,
    rows: []
  };
  var datesFromQuery = getCurrentDates(query, options.defaultDateRange);
  var leaderboardQuery = items_utils_objectSpread(items_utils_objectSpread({}, filterQuery), {}, {
    after: appendTimestamp(datesFromQuery.primary.after, 'start'),
    before: appendTimestamp(datesFromQuery.primary.before, 'end'),
    per_page: perPage,
    persisted_query: JSON.stringify(persistedQuery)
  });

  // Disable eslint rule requiring `getItems` to be defined below because the next two statements
  // depend on `getItems` to have been called.
  // eslint-disable-next-line @wordpress/no-unused-vars-before-return
  var leaderboards = getItems(endpoint, leaderboardQuery);
  if (isResolving('getItems', [endpoint, leaderboardQuery])) {
    return items_utils_objectSpread(items_utils_objectSpread({}, response), {}, {
      isRequesting: true
    });
  } else if (getItemsError(endpoint, leaderboardQuery)) {
    return items_utils_objectSpread(items_utils_objectSpread({}, response), {}, {
      isError: true
    });
  }
  var leaderboard = leaderboards.get(options.id);
  return items_utils_objectSpread(items_utils_objectSpread({}, response), {}, {
    rows: leaderboard === null || leaderboard === void 0 ? void 0 : leaderboard.rows
  });
}
/**
 * Returns items based on a search query.
 *
 * @param {Object}   selector Instance of @wordpress/select response
 * @param {string}   endpoint Report API Endpoint
 * @param {string[]} search   Array of search strings.
 * @param {Object}   options  Query options.
 * @return {Object}   Object containing API request information and the matching items.
 */
function searchItemsByString(selector, endpoint, search) {
  var options = arguments.length > 3 && arguments[3] !== undefined ? arguments[3] : {};
  var getItems = selector.getItems,
    getItemsError = selector.getItemsError,
    isResolving = selector.isResolving;
  var items = {};
  var isRequesting = false;
  var isError = false;
  search.forEach(function (searchWord) {
    var query = items_utils_objectSpread({
      search: searchWord,
      per_page: 10
    }, options);
    var newItems = getItems(endpoint, query);
    newItems.forEach(function (item, id) {
      items[id] = item;
    });
    if (isResolving('getItems', [endpoint, query])) {
      isRequesting = true;
    }
    if (getItemsError(endpoint, query)) {
      isError = true;
    }
  });
  return {
    items: items,
    isRequesting: isRequesting,
    isError: isError
  };
}

/**
 * Generate a resource name for item totals count.
 *
 * It omits query parameters from the identifier that don't affect
 * totals values like pagination and response field filtering.
 *
 * @param {string} itemType Item type for totals count.
 * @param {Object} query    Query for item totals count.
 * @return {string} Resource name for item totals.
 */
function utils_getTotalCountResourceName(itemType, query) {
  var _fields = query._fields,
    page = query.page,
    per_page = query.per_page,
    totalsQuery = (0,objectWithoutProperties/* default */.A)(query, utils_excluded);
  return utils_getResourceName('total-' + itemType, items_utils_objectSpread({}, totalsQuery));
}
;// CONCATENATED MODULE: ../../packages/js/data/src/items/selectors.ts







/**
 * External dependencies
 */


/**
 * Internal dependencies
 */


var getItems = (0,rememo/* default */.A)(function (state, itemType, query) {
  var defaultValue = arguments.length > 3 && arguments[3] !== undefined ? arguments[3] : new Map();
  var resourceName = utils_getResourceName(itemType, query);
  var ids;
  if (state.items[resourceName] && (0,esm_typeof/* default */.A)(state.items[resourceName]) === 'object') {
    ids = state.items[resourceName].data;
  }
  if (!ids) {
    return defaultValue;
  }
  return ids.reduce(function (map, id) {
    var _state$data$itemType;
    map.set(id, (_state$data$itemType = state.data[itemType]) === null || _state$data$itemType === void 0 ? void 0 : _state$data$itemType[id]);
    return map;
  }, new Map());
}, function (state, itemType, query) {
  var resourceName = utils_getResourceName(itemType, query);
  return [state.items[resourceName]];
});
var getItemsTotalCount = function getItemsTotalCount(state, itemType, query) {
  var defaultValue = arguments.length > 3 && arguments[3] !== undefined ? arguments[3] : 0;
  var resourceName = utils_getTotalCountResourceName(itemType, query);
  var totalCount = state.items.hasOwnProperty(resourceName) ? state.items[resourceName] : defaultValue;
  return totalCount;
};
var getItemsError = function getItemsError(state, itemType, query) {
  var resourceName = utils_getResourceName(itemType, query);
  return state.errors[resourceName];
};
;// CONCATENATED MODULE: ../../packages/js/data/src/items/action-types.ts
var items_action_types_TYPES = {
  SET_ITEM: 'SET_ITEM',
  SET_ITEMS: 'SET_ITEMS',
  SET_ITEMS_TOTAL_COUNT: 'SET_ITEMS_TOTAL_COUNT',
  SET_ERROR: 'SET_ERROR'
};
/* harmony default export */ const items_action_types = (items_action_types_TYPES);
;// CONCATENATED MODULE: ../../packages/js/data/src/items/actions.ts













var items_actions_marked = /*#__PURE__*/regenerator_default().mark(updateProductStock),
  items_actions_marked2 = /*#__PURE__*/regenerator_default().mark(createProductFromTemplate);

function items_actions_ownKeys(e, r) {
  var t = Object.keys(e);
  if (Object.getOwnPropertySymbols) {
    var o = Object.getOwnPropertySymbols(e);
    r && (o = o.filter(function (r) {
      return Object.getOwnPropertyDescriptor(e, r).enumerable;
    })), t.push.apply(t, o);
  }
  return t;
}
function items_actions_objectSpread(e) {
  for (var r = 1; r < arguments.length; r++) {
    var t = null != arguments[r] ? arguments[r] : {};
    r % 2 ? items_actions_ownKeys(Object(t), !0).forEach(function (r) {
      (0,defineProperty/* default */.A)(e, r, t[r]);
    }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(e, Object.getOwnPropertyDescriptors(t)) : items_actions_ownKeys(Object(t)).forEach(function (r) {
      Object.defineProperty(e, r, Object.getOwnPropertyDescriptor(t, r));
    });
  }
  return e;
}
/**
 * External dependencies
 */



/**
 * Internal dependencies
 */


function setItem(itemType, id, item) {
  return {
    type: items_action_types.SET_ITEM,
    id: id,
    item: item,
    itemType: itemType
  };
}
function setItems(itemType, query, items, totalCount) {
  return {
    type: items_action_types.SET_ITEMS,
    items: items,
    itemType: itemType,
    query: query,
    totalCount: totalCount
  };
}
function setItemsTotalCount(itemType, query, totalCount) {
  return {
    type: items_action_types.SET_ITEMS_TOTAL_COUNT,
    itemType: itemType,
    query: query,
    totalCount: totalCount
  };
}
function items_actions_setError(itemType, query, error) {
  return {
    type: items_action_types.SET_ERROR,
    itemType: itemType,
    query: query,
    error: error
  };
}
function updateProductStock(product, quantity) {
  var updatedProduct, id, parentId, type, url;
  return regenerator_default().wrap(function updateProductStock$(_context) {
    while (1) switch (_context.prev = _context.next) {
      case 0:
        updatedProduct = items_actions_objectSpread(items_actions_objectSpread({}, product), {}, {
          stock_quantity: quantity
        });
        id = updatedProduct.id, parentId = updatedProduct.parent_id, type = updatedProduct.type; // Optimistically update product stock.
        _context.next = 4;
        return setItem('products', id, updatedProduct);
      case 4:
        url = NAMESPACE;
        _context.t0 = type;
        _context.next = _context.t0 === 'variation' ? 8 : _context.t0 === 'variable' ? 10 : _context.t0 === 'simple' ? 10 : 10;
        break;
      case 8:
        url += "/products/".concat(parentId, "/variations/").concat(id);
        return _context.abrupt("break", 11);
      case 10:
        url += "/products/".concat(id);
      case 11:
        _context.prev = 11;
        _context.next = 14;
        return (0,data_controls_build_module/* apiFetch */.nr)({
          path: url,
          method: 'PUT',
          data: updatedProduct
        });
      case 14:
        return _context.abrupt("return", true);
      case 17:
        _context.prev = 17;
        _context.t1 = _context["catch"](11);
        _context.next = 21;
        return setItem('products', id, product);
      case 21:
        _context.next = 23;
        return items_actions_setError('products', {
          id: id
        }, _context.t1);
      case 23:
        return _context.abrupt("return", false);
      case 24:
      case "end":
        return _context.stop();
    }
  }, items_actions_marked, null, [[11, 17]]);
}
function createProductFromTemplate(itemFields, query) {
  var url, newItem;
  return regenerator_default().wrap(function createProductFromTemplate$(_context2) {
    while (1) switch (_context2.prev = _context2.next) {
      case 0:
        _context2.prev = 0;
        url = (0,add_query_args/* addQueryArgs */.F)("".concat(WC_ADMIN_NAMESPACE, "/onboarding/tasks/create_product_from_template"), query || {});
        _context2.next = 4;
        return (0,data_controls_build_module/* apiFetch */.nr)({
          path: url,
          method: 'POST',
          data: itemFields
        });
      case 4:
        newItem = _context2.sent;
        _context2.next = 7;
        return setItem('products', newItem.id, newItem);
      case 7:
        return _context2.abrupt("return", newItem);
      case 10:
        _context2.prev = 10;
        _context2.t0 = _context2["catch"](0);
        _context2.next = 14;
        return items_actions_setError('createProductFromTemplate', query, _context2.t0);
      case 14:
        throw _context2.t0;
      case 15:
      case "end":
        return _context2.stop();
    }
  }, items_actions_marked2, null, [[0, 10]]);
}
;// CONCATENATED MODULE: ../../packages/js/data/src/items/resolvers.ts











function resolvers_ownKeys(e, r) {
  var t = Object.keys(e);
  if (Object.getOwnPropertySymbols) {
    var o = Object.getOwnPropertySymbols(e);
    r && (o = o.filter(function (r) {
      return Object.getOwnPropertyDescriptor(e, r).enumerable;
    })), t.push.apply(t, o);
  }
  return t;
}
function resolvers_objectSpread(e) {
  for (var r = 1; r < arguments.length; r++) {
    var t = null != arguments[r] ? arguments[r] : {};
    r % 2 ? resolvers_ownKeys(Object(t), !0).forEach(function (r) {
      (0,defineProperty/* default */.A)(e, r, t[r]);
    }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(e, Object.getOwnPropertyDescriptors(t)) : resolvers_ownKeys(Object(t)).forEach(function (r) {
      Object.defineProperty(e, r, Object.getOwnPropertyDescriptor(t, r));
    });
  }
  return e;
}


var items_resolvers_marked = /*#__PURE__*/regenerator_default().mark(resolvers_getItems),
  items_resolvers_marked2 = /*#__PURE__*/regenerator_default().mark(resolvers_getItemsTotalCount),
  items_resolvers_marked3 = /*#__PURE__*/regenerator_default().mark(items_resolvers_getReviewsTotalCount);

/**
 * Internal dependencies
 */



function resolvers_getItems(itemType, query) {
  var endpoint, _yield$request, items, totalCount;
  return regenerator_default().wrap(function getItems$(_context) {
    while (1) switch (_context.prev = _context.next) {
      case 0:
        _context.prev = 0;
        endpoint = itemType === 'categories' ? 'products/categories' : itemType;
        _context.next = 4;
        return request("".concat(NAMESPACE, "/").concat(endpoint), query);
      case 4:
        _yield$request = _context.sent;
        items = _yield$request.items;
        totalCount = _yield$request.totalCount;
        _context.next = 9;
        return setItemsTotalCount(itemType, query, totalCount);
      case 9:
        _context.next = 11;
        return setItems(itemType, query, items);
      case 11:
        _context.next = 17;
        break;
      case 13:
        _context.prev = 13;
        _context.t0 = _context["catch"](0);
        _context.next = 17;
        return items_actions_setError(itemType, query, _context.t0);
      case 17:
      case "end":
        return _context.stop();
    }
  }, items_resolvers_marked, null, [[0, 13]]);
}
function resolvers_getItemsTotalCount(itemType, query) {
  var totalsQuery, endpoint, _yield$request2, totalCount;
  return regenerator_default().wrap(function getItemsTotalCount$(_context2) {
    while (1) switch (_context2.prev = _context2.next) {
      case 0:
        _context2.prev = 0;
        totalsQuery = resolvers_objectSpread(resolvers_objectSpread({}, query), {}, {
          page: 1,
          per_page: 1
        });
        endpoint = itemType === 'categories' ? 'products/categories' : itemType;
        _context2.next = 5;
        return request("".concat(NAMESPACE, "/").concat(endpoint), totalsQuery);
      case 5:
        _yield$request2 = _context2.sent;
        totalCount = _yield$request2.totalCount;
        _context2.next = 9;
        return setItemsTotalCount(itemType, query, totalCount);
      case 9:
        _context2.next = 15;
        break;
      case 11:
        _context2.prev = 11;
        _context2.t0 = _context2["catch"](0);
        _context2.next = 15;
        return items_actions_setError(itemType, query, _context2.t0);
      case 15:
      case "end":
        return _context2.stop();
    }
  }, items_resolvers_marked2, null, [[0, 11]]);
}
function items_resolvers_getReviewsTotalCount(itemType, query) {
  return regenerator_default().wrap(function getReviewsTotalCount$(_context3) {
    while (1) switch (_context3.prev = _context3.next) {
      case 0:
        _context3.next = 2;
        return resolvers_getItemsTotalCount(itemType, query);
      case 2:
      case "end":
        return _context3.stop();
    }
  }, items_resolvers_marked3);
}
;// CONCATENATED MODULE: ../../packages/js/data/src/items/reducer.ts












function items_reducer_ownKeys(e, r) {
  var t = Object.keys(e);
  if (Object.getOwnPropertySymbols) {
    var o = Object.getOwnPropertySymbols(e);
    r && (o = o.filter(function (r) {
      return Object.getOwnPropertyDescriptor(e, r).enumerable;
    })), t.push.apply(t, o);
  }
  return t;
}
function items_reducer_objectSpread(e) {
  for (var r = 1; r < arguments.length; r++) {
    var t = null != arguments[r] ? arguments[r] : {};
    r % 2 ? items_reducer_ownKeys(Object(t), !0).forEach(function (r) {
      (0,defineProperty/* default */.A)(e, r, t[r]);
    }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(e, Object.getOwnPropertyDescriptors(t)) : items_reducer_ownKeys(Object(t)).forEach(function (r) {
      Object.defineProperty(e, r, Object.getOwnPropertyDescriptor(t, r));
    });
  }
  return e;
}
/**
 * External dependencies
 */

/**
 * Internal dependencies
 */



var items_reducer_initialState = {
  items: {},
  errors: {},
  data: {}
};
var items_reducer_reducer = function reducer() {
  var state = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : items_reducer_initialState;
  var action = arguments.length > 1 ? arguments[1] : undefined;
  switch (action.type) {
    case items_action_types.SET_ITEM:
      var itemData = state.data[action.itemType] || {};
      return items_reducer_objectSpread(items_reducer_objectSpread({}, state), {}, {
        data: items_reducer_objectSpread(items_reducer_objectSpread({}, state.data), {}, (0,defineProperty/* default */.A)({}, action.itemType, items_reducer_objectSpread(items_reducer_objectSpread({}, itemData), {}, (0,defineProperty/* default */.A)({}, action.id, items_reducer_objectSpread(items_reducer_objectSpread({}, itemData[action.id] || {}), action.item)))))
      });
    case items_action_types.SET_ITEMS:
      var ids = [];
      var nextItems = action.items.reduce(function (result, theItem) {
        ids.push(theItem.id);
        result[theItem.id] = theItem;
        return result;
      }, {});
      var resourceName = utils_getResourceName(action.itemType, action.query);
      return items_reducer_objectSpread(items_reducer_objectSpread({}, state), {}, {
        items: items_reducer_objectSpread(items_reducer_objectSpread({}, state.items), {}, (0,defineProperty/* default */.A)({}, resourceName, {
          data: ids
        })),
        data: items_reducer_objectSpread(items_reducer_objectSpread({}, state.data), {}, (0,defineProperty/* default */.A)({}, action.itemType, items_reducer_objectSpread(items_reducer_objectSpread({}, state.data[action.itemType]), nextItems)))
      });
    case items_action_types.SET_ITEMS_TOTAL_COUNT:
      var totalResourceName = utils_getTotalCountResourceName(action.itemType, action.query);
      return items_reducer_objectSpread(items_reducer_objectSpread({}, state), {}, {
        items: items_reducer_objectSpread(items_reducer_objectSpread({}, state.items), {}, (0,defineProperty/* default */.A)({}, totalResourceName, action.totalCount))
      });
    case items_action_types.SET_ERROR:
      return items_reducer_objectSpread(items_reducer_objectSpread({}, state), {}, {
        errors: items_reducer_objectSpread(items_reducer_objectSpread({}, state.errors), {}, (0,defineProperty/* default */.A)({}, utils_getResourceName(action.itemType, action.query), action.error))
      });
    default:
      return state;
  }
};
/* harmony default export */ const items_reducer = (items_reducer_reducer);
;// CONCATENATED MODULE: ../../packages/js/data/src/items/index.ts
/**
 * External dependencies
 */

/**
 * Internal dependencies
 */







(0,data_build_module/* registerStore */.ti)(items_constants_STORE_NAME, {
  reducer: items_reducer,
  actions: items_actions_namespaceObject,
  controls: src_controls,
  selectors: items_selectors_namespaceObject,
  resolvers: items_resolvers_namespaceObject
});
var ITEMS_STORE_NAME = (/* unused pure expression or super */ null && (STORE_NAME));
;// CONCATENATED MODULE: ../../packages/js/data/src/payment-gateways/action-types.ts
var action_types_ACTION_TYPES = /*#__PURE__*/function (ACTION_TYPES) {
  ACTION_TYPES["GET_PAYMENT_GATEWAYS_REQUEST"] = "GET_PAYMENT_GATEWAYS_REQUEST";
  ACTION_TYPES["GET_PAYMENT_GATEWAYS_SUCCESS"] = "GET_PAYMENT_GATEWAYS_SUCCESS";
  ACTION_TYPES["GET_PAYMENT_GATEWAYS_ERROR"] = "GET_PAYMENT_GATEWAYS_ERROR";
  ACTION_TYPES["UPDATE_PAYMENT_GATEWAY_REQUEST"] = "UPDATE_PAYMENT_GATEWAY_REQUEST";
  ACTION_TYPES["UPDATE_PAYMENT_GATEWAY_SUCCESS"] = "UPDATE_PAYMENT_GATEWAY_SUCCESS";
  ACTION_TYPES["UPDATE_PAYMENT_GATEWAY_ERROR"] = "UPDATE_PAYMENT_GATEWAY_ERROR";
  ACTION_TYPES["GET_PAYMENT_GATEWAY_REQUEST"] = "GET_PAYMENT_GATEWAY_REQUEST";
  ACTION_TYPES["GET_PAYMENT_GATEWAY_SUCCESS"] = "GET_PAYMENT_GATEWAY_SUCCESS";
  ACTION_TYPES["GET_PAYMENT_GATEWAY_ERROR"] = "GET_PAYMENT_GATEWAY_ERROR";
  return ACTION_TYPES;
}({});
;// CONCATENATED MODULE: ../../packages/js/data/src/payment-gateways/constants.ts
var constants_STORE_KEY = 'wc/payment-gateways';
var API_NAMESPACE = 'wc/v3';
;// CONCATENATED MODULE: ../../packages/js/data/src/payment-gateways/actions.ts


var payment_gateways_actions_marked = /*#__PURE__*/regenerator_default().mark(updatePaymentGateway);
/**
 * External dependencies
 */


/**
 * Internal dependencies
 */


function getPaymentGatewaysRequest() {
  return {
    type: action_types_ACTION_TYPES.GET_PAYMENT_GATEWAYS_REQUEST
  };
}
function getPaymentGatewaysSuccess(paymentGateways) {
  return {
    type: action_types_ACTION_TYPES.GET_PAYMENT_GATEWAYS_SUCCESS,
    paymentGateways: paymentGateways
  };
}
function getPaymentGatewaysError(error) {
  return {
    type: action_types_ACTION_TYPES.GET_PAYMENT_GATEWAYS_ERROR,
    error: error
  };
}
function getPaymentGatewayRequest() {
  return {
    type: action_types_ACTION_TYPES.GET_PAYMENT_GATEWAY_REQUEST
  };
}
function getPaymentGatewayError(error) {
  return {
    type: action_types_ACTION_TYPES.GET_PAYMENT_GATEWAY_ERROR,
    error: error
  };
}
function getPaymentGatewaySuccess(paymentGateway) {
  return {
    type: action_types_ACTION_TYPES.GET_PAYMENT_GATEWAY_SUCCESS,
    paymentGateway: paymentGateway
  };
}
function updatePaymentGatewaySuccess(paymentGateway) {
  return {
    type: action_types_ACTION_TYPES.UPDATE_PAYMENT_GATEWAY_SUCCESS,
    paymentGateway: paymentGateway
  };
}
function updatePaymentGatewayRequest() {
  return {
    type: action_types_ACTION_TYPES.UPDATE_PAYMENT_GATEWAY_REQUEST
  };
}
function updatePaymentGatewayError(error) {
  return {
    type: action_types_ACTION_TYPES.UPDATE_PAYMENT_GATEWAY_ERROR,
    error: error
  };
}
function updatePaymentGateway(id, data) {
  var response;
  return regenerator_default().wrap(function updatePaymentGateway$(_context) {
    while (1) switch (_context.prev = _context.next) {
      case 0:
        _context.prev = 0;
        _context.next = 3;
        return updatePaymentGatewayRequest();
      case 3:
        _context.next = 5;
        return (0,data_controls_build_module/* apiFetch */.nr)({
          method: 'PUT',
          path: API_NAMESPACE + '/payment_gateways/' + id,
          body: JSON.stringify(data)
        });
      case 5:
        response = _context.sent;
        if (!(response && response.id === id)) {
          _context.next = 10;
          break;
        }
        _context.next = 9;
        return updatePaymentGatewaySuccess(response);
      case 9:
        return _context.abrupt("return", response);
      case 10:
        _context.next = 17;
        break;
      case 12:
        _context.prev = 12;
        _context.t0 = _context["catch"](0);
        _context.next = 16;
        return updatePaymentGatewayError(_context.t0);
      case 16:
        throw _context.t0;
      case 17:
      case "end":
        return _context.stop();
    }
  }, payment_gateways_actions_marked, null, [[0, 12]]);
}
;// CONCATENATED MODULE: ../../packages/js/data/src/payment-gateways/resolvers.ts


var payment_gateways_resolvers_marked = /*#__PURE__*/regenerator_default().mark(getPaymentGateways),
  payment_gateways_resolvers_marked2 = /*#__PURE__*/regenerator_default().mark(getPaymentGateway);
/**
 * External dependencies
 */



/**
 * Internal dependencies
 */


// Can be removed in WP 5.9.
var resolvers_dispatch = build_module_controls/* controls */.n && build_module_controls/* controls */.n.dispatch ? build_module_controls/* controls */.n.dispatch : data_controls_build_module/* dispatch */.JD;
function getPaymentGateways() {
  var response, i;
  return regenerator_default().wrap(function getPaymentGateways$(_context) {
    while (1) switch (_context.prev = _context.next) {
      case 0:
        _context.next = 2;
        return getPaymentGatewaysRequest();
      case 2:
        _context.prev = 2;
        _context.next = 5;
        return (0,data_controls_build_module/* apiFetch */.nr)({
          path: API_NAMESPACE + '/payment_gateways'
        });
      case 5:
        response = _context.sent;
        _context.next = 8;
        return getPaymentGatewaysSuccess(response);
      case 8:
        i = 0;
      case 9:
        if (!(i < response.length)) {
          _context.next = 15;
          break;
        }
        _context.next = 12;
        return resolvers_dispatch(constants_STORE_KEY, 'finishResolution', 'getPaymentGateway', [response[i].id]);
      case 12:
        i++;
        _context.next = 9;
        break;
      case 15:
        _context.next = 21;
        break;
      case 17:
        _context.prev = 17;
        _context.t0 = _context["catch"](2);
        _context.next = 21;
        return getPaymentGatewaysError(_context.t0);
      case 21:
      case "end":
        return _context.stop();
    }
  }, payment_gateways_resolvers_marked, null, [[2, 17]]);
}
function getPaymentGateway(id) {
  var response;
  return regenerator_default().wrap(function getPaymentGateway$(_context2) {
    while (1) switch (_context2.prev = _context2.next) {
      case 0:
        _context2.next = 2;
        return getPaymentGatewayRequest();
      case 2:
        _context2.prev = 2;
        _context2.next = 5;
        return (0,data_controls_build_module/* apiFetch */.nr)({
          path: API_NAMESPACE + '/payment_gateways/' + id
        });
      case 5:
        response = _context2.sent;
        if (!(response && response.id)) {
          _context2.next = 10;
          break;
        }
        _context2.next = 9;
        return getPaymentGatewaySuccess(response);
      case 9:
        return _context2.abrupt("return", response);
      case 10:
        _context2.next = 16;
        break;
      case 12:
        _context2.prev = 12;
        _context2.t0 = _context2["catch"](2);
        _context2.next = 16;
        return getPaymentGatewayError(_context2.t0);
      case 16:
      case "end":
        return _context2.stop();
    }
  }, payment_gateways_resolvers_marked2, null, [[2, 12]]);
}
;// CONCATENATED MODULE: ../../packages/js/data/src/payment-gateways/selectors.ts


/**
 * Internal dependencies
 */

function selectors_getPaymentGateway(state, id) {
  return state.paymentGateways.find(function (paymentGateway) {
    return paymentGateway.id === id;
  });
}
function selectors_getPaymentGateways(state) {
  return state.paymentGateways;
}
function selectors_getPaymentGatewayError(state, selector) {
  return state.errors[selector] || null;
}
function isPaymentGatewayUpdating(state) {
  return state.isUpdating || false;
}
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.find-index.js
var es_array_find_index = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.find-index.js");
;// CONCATENATED MODULE: ../../packages/js/data/src/payment-gateways/reducer.ts












function payment_gateways_reducer_ownKeys(e, r) {
  var t = Object.keys(e);
  if (Object.getOwnPropertySymbols) {
    var o = Object.getOwnPropertySymbols(e);
    r && (o = o.filter(function (r) {
      return Object.getOwnPropertyDescriptor(e, r).enumerable;
    })), t.push.apply(t, o);
  }
  return t;
}
function payment_gateways_reducer_objectSpread(e) {
  for (var r = 1; r < arguments.length; r++) {
    var t = null != arguments[r] ? arguments[r] : {};
    r % 2 ? payment_gateways_reducer_ownKeys(Object(t), !0).forEach(function (r) {
      (0,defineProperty/* default */.A)(e, r, t[r]);
    }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(e, Object.getOwnPropertyDescriptors(t)) : payment_gateways_reducer_ownKeys(Object(t)).forEach(function (r) {
      Object.defineProperty(e, r, Object.getOwnPropertyDescriptor(t, r));
    });
  }
  return e;
}



/**
 * Internal dependencies
 */

function updatePaymentGatewayList(state, paymentGateway) {
  var targetIndex = state.paymentGateways.findIndex(function (gateway) {
    return gateway.id === paymentGateway.id;
  });
  if (targetIndex === -1) {
    return payment_gateways_reducer_objectSpread(payment_gateways_reducer_objectSpread({}, state), {}, {
      paymentGateways: [].concat((0,toConsumableArray/* default */.A)(state.paymentGateways), [paymentGateway]),
      isUpdating: false
    });
  }
  return payment_gateways_reducer_objectSpread(payment_gateways_reducer_objectSpread({}, state), {}, {
    paymentGateways: [].concat((0,toConsumableArray/* default */.A)(state.paymentGateways.slice(0, targetIndex)), [paymentGateway], (0,toConsumableArray/* default */.A)(state.paymentGateways.slice(targetIndex + 1))),
    isUpdating: false
  });
}
var payment_gateways_reducer_reducer = function reducer() {
  var state = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {
    paymentGateways: [],
    isUpdating: false,
    errors: {}
  };
  var payload = arguments.length > 1 ? arguments[1] : undefined;
  if (payload && 'type' in payload) {
    switch (payload.type) {
      case action_types_ACTION_TYPES.GET_PAYMENT_GATEWAYS_REQUEST:
      case action_types_ACTION_TYPES.GET_PAYMENT_GATEWAY_REQUEST:
        return state;
      case action_types_ACTION_TYPES.GET_PAYMENT_GATEWAYS_SUCCESS:
        return payment_gateways_reducer_objectSpread(payment_gateways_reducer_objectSpread({}, state), {}, {
          paymentGateways: payload.paymentGateways
        });
      case action_types_ACTION_TYPES.GET_PAYMENT_GATEWAYS_ERROR:
        return payment_gateways_reducer_objectSpread(payment_gateways_reducer_objectSpread({}, state), {}, {
          errors: payment_gateways_reducer_objectSpread(payment_gateways_reducer_objectSpread({}, state.errors), {}, {
            getPaymentGateways: payload.error
          })
        });
      case action_types_ACTION_TYPES.GET_PAYMENT_GATEWAY_ERROR:
        return payment_gateways_reducer_objectSpread(payment_gateways_reducer_objectSpread({}, state), {}, {
          errors: payment_gateways_reducer_objectSpread(payment_gateways_reducer_objectSpread({}, state.errors), {}, {
            getPaymentGateway: payload.error
          })
        });
      case action_types_ACTION_TYPES.UPDATE_PAYMENT_GATEWAY_REQUEST:
        return payment_gateways_reducer_objectSpread(payment_gateways_reducer_objectSpread({}, state), {}, {
          isUpdating: true
        });
      case action_types_ACTION_TYPES.UPDATE_PAYMENT_GATEWAY_SUCCESS:
        return updatePaymentGatewayList(state, payload.paymentGateway);
      case action_types_ACTION_TYPES.GET_PAYMENT_GATEWAY_SUCCESS:
        return updatePaymentGatewayList(state, payload.paymentGateway);
      case action_types_ACTION_TYPES.UPDATE_PAYMENT_GATEWAY_ERROR:
        return payment_gateways_reducer_objectSpread(payment_gateways_reducer_objectSpread({}, state), {}, {
          errors: payment_gateways_reducer_objectSpread(payment_gateways_reducer_objectSpread({}, state.errors), {}, {
            updatePaymentGateway: payload.error
          }),
          isUpdating: false
        });
    }
  }
  return state;
};
/* harmony default export */ const payment_gateways_reducer = (payment_gateways_reducer_reducer);
;// CONCATENATED MODULE: ../../packages/js/data/src/payment-gateways/index.ts
/**
 * External dependencies
 */


/**
 * Internal dependencies
 */





var PAYMENT_GATEWAYS_STORE_NAME = (/* unused pure expression or super */ null && (STORE_KEY));
(0,data_build_module/* registerStore */.ti)(constants_STORE_KEY, {
  actions: payment_gateways_actions_namespaceObject,
  selectors: payment_gateways_selectors_namespaceObject,
  resolvers: payment_gateways_resolvers_namespaceObject,
  controls: data_controls_build_module/* controls */.ne,
  reducer: payment_gateways_reducer
});
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+data@6.6.1_react@17.0.2/node_modules/@wordpress/data/build-module/redux-store/index.js + 7 modules
var redux_store = __webpack_require__("../../node_modules/.pnpm/@wordpress+data@6.6.1_react@17.0.2/node_modules/@wordpress/data/build-module/redux-store/index.js");
;// CONCATENATED MODULE: ../../packages/js/data/src/shipping-methods/action-types.ts
var shipping_methods_action_types_ACTION_TYPES = /*#__PURE__*/function (ACTION_TYPES) {
  ACTION_TYPES["GET_SHIPPING_METHODS_REQUEST"] = "GET_SHIPPING_METHODS_REQUEST";
  ACTION_TYPES["GET_SHIPPING_METHODS_SUCCESS"] = "GET_SHIPPING_METHODS_SUCCESS";
  ACTION_TYPES["GET_SHIPPING_METHODS_ERROR"] = "GET_SHIPPING_METHODS_ERROR";
  return ACTION_TYPES;
}({});
;// CONCATENATED MODULE: ../../packages/js/data/src/shipping-methods/actions.ts
/**
 * Internal dependencies
 */

function getShippingMethodsRequest() {
  return {
    type: shipping_methods_action_types_ACTION_TYPES.GET_SHIPPING_METHODS_REQUEST
  };
}
function getShippingMethodsSuccess(shippingMethods) {
  return {
    type: shipping_methods_action_types_ACTION_TYPES.GET_SHIPPING_METHODS_SUCCESS,
    shippingMethods: shippingMethods
  };
}
function getShippingMethodsError(error) {
  return {
    type: shipping_methods_action_types_ACTION_TYPES.GET_SHIPPING_METHODS_ERROR,
    error: error
  };
}
;// CONCATENATED MODULE: ../../packages/js/data/src/shipping-methods/resolvers.ts


/**
 * External dependencies
 */


/**
 * Internal dependencies
 */


function getShippingMethods() {
  var forceDefaultSuggestions = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : false;
  return /*#__PURE__*/regenerator_default().mark(function _callee() {
    var path, results;
    return regenerator_default().wrap(function _callee$(_context) {
      while (1) switch (_context.prev = _context.next) {
        case 0:
          path = WC_ADMIN_NAMESPACE + '/shipping-partner-suggestions';
          if (forceDefaultSuggestions) {
            path += '?force_default_suggestions=true';
          }
          _context.next = 4;
          return getShippingMethodsRequest();
        case 4:
          _context.prev = 4;
          _context.next = 7;
          return (0,data_controls_build_module/* apiFetch */.nr)({
            path: path,
            method: 'GET'
          });
        case 7:
          results = _context.sent;
          _context.next = 10;
          return getShippingMethodsSuccess(results);
        case 10:
          _context.next = 16;
          break;
        case 12:
          _context.prev = 12;
          _context.t0 = _context["catch"](4);
          _context.next = 16;
          return getShippingMethodsError(_context.t0);
        case 16:
        case "end":
          return _context.stop();
      }
    }, _callee, null, [[4, 12]]);
  })();
}
;// CONCATENATED MODULE: ../../packages/js/data/src/shipping-methods/selectors.ts
/**
 * Internal dependencies
 */

var selectors_getShippingMethods = function getShippingMethods(state) {
  return state.shippingMethods || [];
};
function isShippingMethodsUpdating(state) {
  return state.isUpdating || false;
}
;// CONCATENATED MODULE: ../../packages/js/data/src/shipping-methods/reducer.ts











function shipping_methods_reducer_ownKeys(e, r) {
  var t = Object.keys(e);
  if (Object.getOwnPropertySymbols) {
    var o = Object.getOwnPropertySymbols(e);
    r && (o = o.filter(function (r) {
      return Object.getOwnPropertyDescriptor(e, r).enumerable;
    })), t.push.apply(t, o);
  }
  return t;
}
function shipping_methods_reducer_objectSpread(e) {
  for (var r = 1; r < arguments.length; r++) {
    var t = null != arguments[r] ? arguments[r] : {};
    r % 2 ? shipping_methods_reducer_ownKeys(Object(t), !0).forEach(function (r) {
      (0,defineProperty/* default */.A)(e, r, t[r]);
    }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(e, Object.getOwnPropertyDescriptors(t)) : shipping_methods_reducer_ownKeys(Object(t)).forEach(function (r) {
      Object.defineProperty(e, r, Object.getOwnPropertyDescriptor(t, r));
    });
  }
  return e;
}
/**
 * External dependencies
 */

/**
 * Internal dependencies
 */

var shipping_methods_reducer_reducer = function reducer() {
  var state = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {
    shippingMethods: [],
    isUpdating: false,
    errors: {}
  };
  var payload = arguments.length > 1 ? arguments[1] : undefined;
  if (payload && 'type' in payload) {
    switch (payload.type) {
      case shipping_methods_action_types_ACTION_TYPES.GET_SHIPPING_METHODS_REQUEST:
        return shipping_methods_reducer_objectSpread(shipping_methods_reducer_objectSpread({}, state), {}, {
          isUpdating: true
        });
      case shipping_methods_action_types_ACTION_TYPES.GET_SHIPPING_METHODS_SUCCESS:
        return shipping_methods_reducer_objectSpread(shipping_methods_reducer_objectSpread({}, state), {}, {
          shippingMethods: payload.shippingMethods,
          isUpdating: false
        });
      case shipping_methods_action_types_ACTION_TYPES.GET_SHIPPING_METHODS_ERROR:
        return shipping_methods_reducer_objectSpread(shipping_methods_reducer_objectSpread({}, state), {}, {
          isUpdating: false,
          errors: shipping_methods_reducer_objectSpread(shipping_methods_reducer_objectSpread({}, state.errors), {}, {
            getShippingMethods: payload.error
          })
        });
    }
  }
  return state;
};
/* harmony default export */ const shipping_methods_reducer = (shipping_methods_reducer_reducer);
;// CONCATENATED MODULE: ../../packages/js/data/src/shipping-methods/constants.ts
var shipping_methods_constants_STORE_KEY = 'wc/shipping-methods';
;// CONCATENATED MODULE: ../../packages/js/data/src/shipping-methods/index.ts
/**
 * External dependencies
 */



/**
 * Internal dependencies
 */





var SHIPPING_METHODS_STORE_NAME = shipping_methods_constants_STORE_KEY;
var store = (0,redux_store/* default */.A)(SHIPPING_METHODS_STORE_NAME, {
  reducer: shipping_methods_reducer,
  selectors: shipping_methods_selectors_namespaceObject,
  resolvers: shipping_methods_resolvers_namespaceObject,
  controls: data_controls_build_module/* controls */.ne,
  actions: shipping_methods_actions_namespaceObject
});
(0,data_build_module/* register */.kz)(store);
;// CONCATENATED MODULE: ../../packages/js/data/src/products/constants.ts
var products_constants_STORE_NAME = 'wc/admin/products';
var WC_PRODUCT_NAMESPACE = '/wc/v3/products';
var PERMALINK_PRODUCT_REGEX = /%(?:postname|pagename)%/;
var WC_V3_ENDPOINT_SUGGESTED_PRODUCTS = "".concat(WC_PRODUCT_NAMESPACE, "/suggested-products");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.string.split.js
var es_string_split = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.string.split.js");
;// CONCATENATED MODULE: ../../packages/js/data/src/products/utils.ts











var products_utils_excluded = ["_fields", "page", "per_page"];
function products_utils_ownKeys(e, r) {
  var t = Object.keys(e);
  if (Object.getOwnPropertySymbols) {
    var o = Object.getOwnPropertySymbols(e);
    r && (o = o.filter(function (r) {
      return Object.getOwnPropertyDescriptor(e, r).enumerable;
    })), t.push.apply(t, o);
  }
  return t;
}
function products_utils_objectSpread(e) {
  for (var r = 1; r < arguments.length; r++) {
    var t = null != arguments[r] ? arguments[r] : {};
    r % 2 ? products_utils_ownKeys(Object(t), !0).forEach(function (r) {
      (0,defineProperty/* default */.A)(e, r, t[r]);
    }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(e, Object.getOwnPropertyDescriptors(t)) : products_utils_ownKeys(Object(t)).forEach(function (r) {
      Object.defineProperty(e, r, Object.getOwnPropertyDescriptor(t, r));
    });
  }
  return e;
}


/**
 * Internal dependencies
 */

var PRODUCT_PREFIX = 'product';

/**
 * Generate a resource name for products.
 *
 * @param {Object} query Query for products.
 * @return {string} Resource name for products.
 */
function getProductResourceName(query) {
  return utils_getResourceName(PRODUCT_PREFIX, query);
}

/**
 * Generate a resource name for product totals count.
 *
 * It omits query parameters from the identifier that don't affect
 * totals values like pagination and response field filtering.
 *
 * @param {Object} query Query for product totals count.
 * @return {string} Resource name for product totals.
 */
function getTotalProductCountResourceName(query) {
  var _fields = query._fields,
    page = query.page,
    per_page = query.per_page,
    totalsQuery = (0,objectWithoutProperties/* default */.A)(query, products_utils_excluded);
  return getProductResourceName(totalsQuery);
}

/**
 * Create a unique string ID based the options object.
 *
 * @param {GetSuggestedProductsOptions} options - Options to create the ID from.
 * @return {string} Unique ID.
 */
function createIdFromOptions() {
  var _options$categories, _options$tags, _options$attributes;
  var options = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};
  if (!Object.keys(options).length) {
    return 'default';
  }
  var optionsForKey = products_utils_objectSpread({}, options);
  (_options$categories = options.categories) === null || _options$categories === void 0 || _options$categories.sort();
  (_options$tags = options.tags) === null || _options$tags === void 0 || _options$tags.sort();
  (_options$attributes = options.attributes) === null || _options$attributes === void 0 || _options$attributes.sort();
  return JSON.stringify(optionsForKey);
}
;// CONCATENATED MODULE: ../../packages/js/data/src/products/selectors.ts












function selectors_ownKeys(e, r) {
  var t = Object.keys(e);
  if (Object.getOwnPropertySymbols) {
    var o = Object.getOwnPropertySymbols(e);
    r && (o = o.filter(function (r) {
      return Object.getOwnPropertyDescriptor(e, r).enumerable;
    })), t.push.apply(t, o);
  }
  return t;
}
function selectors_objectSpread(e) {
  for (var r = 1; r < arguments.length; r++) {
    var t = null != arguments[r] ? arguments[r] : {};
    r % 2 ? selectors_ownKeys(Object(t), !0).forEach(function (r) {
      (0,defineProperty/* default */.A)(e, r, t[r]);
    }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(e, Object.getOwnPropertyDescriptors(t)) : selectors_ownKeys(Object(t)).forEach(function (r) {
      Object.defineProperty(e, r, Object.getOwnPropertyDescriptor(t, r));
    });
  }
  return e;
}






/**
 * External dependencies
 */


/**
 * Internal dependencies
 */


var getProduct = function getProduct(state, productId) {
  var defaultValue = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : undefined;
  return state.data[productId] || defaultValue;
};
var getProducts = (0,rememo/* default */.A)(function (state, query) {
  var defaultValue = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : undefined;
  var resourceName = getProductResourceName(query);
  var ids = state.products[resourceName] ? state.products[resourceName].data : undefined;
  if (!ids) {
    return defaultValue;
  }
  if (query && typeof query._fields !== 'undefined') {
    var fields = query._fields;
    return ids.map(function (id) {
      return fields.reduce(function (product, field) {
        return selectors_objectSpread(selectors_objectSpread({}, product), {}, (0,defineProperty/* default */.A)({}, field, state.data[id][field]));
      }, {});
    });
  }
  return ids.map(function (id) {
    return state.data[id];
  });
}, function (state, query) {
  var resourceName = getProductResourceName(query);
  var ids = state.products[resourceName] ? state.products[resourceName].data : undefined;
  return [state.products[resourceName]].concat((0,toConsumableArray/* default */.A)((ids || []).map(function (id) {
    return state.data[id];
  })));
});
var getProductsTotalCount = function getProductsTotalCount(state, query) {
  var defaultValue = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : undefined;
  var resourceName = getTotalProductCountResourceName(query);
  var totalCount = state.productsCount.hasOwnProperty(resourceName) ? state.productsCount[resourceName] : defaultValue;
  return totalCount;
};
var getProductsError = function getProductsError(state, query) {
  var resourceName = getProductResourceName(query);
  return state.errors[resourceName];
};
var getCreateProductError = function getCreateProductError(state, query) {
  var resourceName = getProductResourceName(query);
  return state.errors[resourceName];
};
var getUpdateProductError = function getUpdateProductError(state, id, query) {
  var resourceName = getProductResourceName(query);
  return state.errors["update/".concat(id, "/").concat(resourceName)];
};
var getDeleteProductError = function getDeleteProductError(state, id) {
  return state.errors["delete/".concat(id)];
};
var isPending = function isPending(state, action, productId) {
  if (productId !== undefined && action !== 'createProduct') {
    var _state$pending$action;
    return ((_state$pending$action = state.pending[action]) === null || _state$pending$action === void 0 ? void 0 : _state$pending$action[productId]) || false;
  } else if (action === 'createProduct') {
    return state.pending[action] || false;
  }
  return false;
};
var getPermalinkParts = (0,rememo/* default */.A)(function (state, productId) {
  var product = state.data[productId];
  if (product && product.permalink_template) {
    var postName = product.slug || product.generated_slug;
    var _product$permalink_te = product.permalink_template.split(PERMALINK_PRODUCT_REGEX),
      _product$permalink_te2 = (0,slicedToArray/* default */.A)(_product$permalink_te, 2),
      prefix = _product$permalink_te2[0],
      suffix = _product$permalink_te2[1];
    return {
      prefix: prefix,
      postName: postName,
      suffix: suffix
    };
  }
  return null;
}, function (state, productId) {
  return [state.data[productId]];
});

/**
 * Returns an array of related products for a given product ID.
 *
 * @param {ProductState} state     - The current state.
 * @param {number}       productId - The product ID.
 * @return {PartialProduct[]}        The related products.
 */
var getRelatedProducts = (0,rememo/* default */.A)(function (state, productId) {
  var product = state.data[productId];
  if (!(product !== null && product !== void 0 && product.related_ids)) {
    return [];
  }
  var relatedProducts = getProducts(state, {
    include: product.related_ids
  });
  return relatedProducts || [];
}, function (state, productId) {
  return [state.data[productId]];
});

/**
 * Return an array of suggested products the
 * given options.
 *
 * @param {ProductState}                state   - The current state.
 * @param {GetSuggestedProductsOptions} options - The options.
 * @return {PartialProduct[]}                     The suggested products.
 */
function getSuggestedProducts(state, options) {
  var key = createIdFromOptions(options);
  if (!state.suggestedProducts[key]) {
    return [];
  }
  return state.suggestedProducts[key].items;
}
;// CONCATENATED MODULE: ../../packages/js/data/src/products/action-types.ts
var products_action_types_TYPES = /*#__PURE__*/function (TYPES) {
  TYPES["CREATE_PRODUCT_START"] = "CREATE_PRODUCT_START";
  TYPES["CREATE_PRODUCT_ERROR"] = "CREATE_PRODUCT_ERROR";
  TYPES["CREATE_PRODUCT_SUCCESS"] = "CREATE_PRODUCT_SUCCESS";
  TYPES["GET_PRODUCT_SUCCESS"] = "GET_PRODUCT_SUCCESS";
  TYPES["GET_PRODUCT_ERROR"] = "GET_PRODUCT_ERROR";
  TYPES["GET_PRODUCTS_SUCCESS"] = "GET_PRODUCTS_SUCCESS";
  TYPES["GET_PRODUCTS_ERROR"] = "GET_PRODUCTS_ERROR";
  TYPES["GET_PRODUCTS_TOTAL_COUNT_SUCCESS"] = "GET_PRODUCTS_TOTAL_COUNT_SUCCESS";
  TYPES["GET_PRODUCTS_TOTAL_COUNT_ERROR"] = "GET_PRODUCTS_TOTAL_COUNT_ERROR";
  TYPES["UPDATE_PRODUCT_START"] = "UPDATE_PRODUCT_START";
  TYPES["UPDATE_PRODUCT_ERROR"] = "UPDATE_PRODUCT_ERROR";
  TYPES["UPDATE_PRODUCT_SUCCESS"] = "UPDATE_PRODUCT_SUCCESS";
  TYPES["DELETE_PRODUCT_START"] = "DELETE_PRODUCT_START";
  TYPES["DELETE_PRODUCT_ERROR"] = "DELETE_PRODUCT_ERROR";
  TYPES["DELETE_PRODUCT_SUCCESS"] = "DELETE_PRODUCT_SUCCESS";
  TYPES["DUPLICATE_PRODUCT_START"] = "DUPLICATE_PRODUCT_START";
  TYPES["DUPLICATE_PRODUCT_ERROR"] = "DUPLICATE_PRODUCT_ERROR";
  TYPES["DUPLICATE_PRODUCT_SUCCESS"] = "DUPLICATE_PRODUCT_SUCCESS";
  TYPES["SET_SUGGESTED_PRODUCTS"] = "SET_SUGGESTED_PRODUCTS";
  return TYPES;
}({});
/* harmony default export */ const products_action_types = (products_action_types_TYPES);
;// CONCATENATED MODULE: ../../packages/js/data/src/products/actions.ts



var products_actions_marked = /*#__PURE__*/regenerator_default().mark(createProduct),
  products_actions_marked2 = /*#__PURE__*/regenerator_default().mark(updateProduct),
  products_actions_marked3 = /*#__PURE__*/regenerator_default().mark(duplicateProduct);
/**
 * External dependencies
 */

/**
 * Internal dependencies
 */


function getProductSuccess(id, product) {
  return {
    type: products_action_types.GET_PRODUCT_SUCCESS,
    id: id,
    product: product
  };
}
function getProductError(productId, error) {
  return {
    type: products_action_types.GET_PRODUCT_ERROR,
    productId: productId,
    error: error
  };
}
function createProductStart() {
  return {
    type: products_action_types.CREATE_PRODUCT_START
  };
}
function createProductSuccess(id, product) {
  return {
    type: products_action_types.CREATE_PRODUCT_SUCCESS,
    id: id,
    product: product
  };
}
function createProductError(query, error) {
  return {
    type: products_action_types.CREATE_PRODUCT_ERROR,
    query: query,
    error: error
  };
}
function duplicateProductStart(id) {
  return {
    type: products_action_types.DUPLICATE_PRODUCT_START,
    id: id
  };
}
function duplicateProductSuccess(id, product) {
  return {
    type: products_action_types.DUPLICATE_PRODUCT_SUCCESS,
    id: id,
    product: product
  };
}
function duplicateProductError(id, error) {
  return {
    type: products_action_types.DUPLICATE_PRODUCT_ERROR,
    id: id,
    error: error
  };
}
function updateProductStart(id) {
  return {
    type: products_action_types.UPDATE_PRODUCT_START,
    id: id
  };
}
function updateProductSuccess(id, product) {
  return {
    type: products_action_types.UPDATE_PRODUCT_SUCCESS,
    id: id,
    product: product
  };
}
function updateProductError(id, error) {
  return {
    type: products_action_types.UPDATE_PRODUCT_ERROR,
    id: id,
    error: error
  };
}
function getProductsSuccess(query, products, totalCount) {
  return {
    type: products_action_types.GET_PRODUCTS_SUCCESS,
    products: products,
    query: query,
    totalCount: totalCount
  };
}
function actions_getProductsError(query, error) {
  return {
    type: products_action_types.GET_PRODUCTS_ERROR,
    query: query,
    error: error
  };
}
function getProductsTotalCountSuccess(query, totalCount) {
  return {
    type: products_action_types.GET_PRODUCTS_TOTAL_COUNT_SUCCESS,
    query: query,
    totalCount: totalCount
  };
}
function getProductsTotalCountError(query, error) {
  return {
    type: products_action_types.GET_PRODUCTS_TOTAL_COUNT_ERROR,
    query: query,
    error: error
  };
}
function createProduct(data) {
  var product;
  return regenerator_default().wrap(function createProduct$(_context) {
    while (1) switch (_context.prev = _context.next) {
      case 0:
        _context.next = 2;
        return createProductStart();
      case 2:
        _context.prev = 2;
        _context.next = 5;
        return (0,data_controls_build_module/* apiFetch */.nr)({
          path: WC_PRODUCT_NAMESPACE,
          method: 'POST',
          data: data
        });
      case 5:
        product = _context.sent;
        _context.next = 8;
        return createProductSuccess(product.id, product);
      case 8:
        return _context.abrupt("return", product);
      case 11:
        _context.prev = 11;
        _context.t0 = _context["catch"](2);
        _context.next = 15;
        return createProductError(data, _context.t0);
      case 15:
        throw _context.t0;
      case 16:
      case "end":
        return _context.stop();
    }
  }, products_actions_marked, null, [[2, 11]]);
}
function updateProduct(id, data) {
  var product;
  return regenerator_default().wrap(function updateProduct$(_context2) {
    while (1) switch (_context2.prev = _context2.next) {
      case 0:
        _context2.next = 2;
        return updateProductStart(id);
      case 2:
        _context2.prev = 2;
        _context2.next = 5;
        return (0,data_controls_build_module/* apiFetch */.nr)({
          path: "".concat(WC_PRODUCT_NAMESPACE, "/").concat(id),
          method: 'PUT',
          data: data
        });
      case 5:
        product = _context2.sent;
        _context2.next = 8;
        return updateProductSuccess(product.id, product);
      case 8:
        return _context2.abrupt("return", product);
      case 11:
        _context2.prev = 11;
        _context2.t0 = _context2["catch"](2);
        _context2.next = 15;
        return updateProductError(id, _context2.t0);
      case 15:
        throw _context2.t0;
      case 16:
      case "end":
        return _context2.stop();
    }
  }, products_actions_marked2, null, [[2, 11]]);
}
function duplicateProduct(id, data) {
  var product;
  return regenerator_default().wrap(function duplicateProduct$(_context3) {
    while (1) switch (_context3.prev = _context3.next) {
      case 0:
        _context3.next = 2;
        return duplicateProductStart(id);
      case 2:
        _context3.prev = 2;
        _context3.next = 5;
        return (0,data_controls_build_module/* apiFetch */.nr)({
          path: "".concat(WC_PRODUCT_NAMESPACE, "/").concat(id, "/duplicate"),
          method: 'POST',
          data: data
        });
      case 5:
        product = _context3.sent;
        _context3.next = 8;
        return duplicateProductSuccess(product.id, product);
      case 8:
        return _context3.abrupt("return", product);
      case 11:
        _context3.prev = 11;
        _context3.t0 = _context3["catch"](2);
        _context3.next = 15;
        return duplicateProductError(id, _context3.t0);
      case 15:
        throw _context3.t0;
      case 16:
      case "end":
        return _context3.stop();
    }
  }, products_actions_marked3, null, [[2, 11]]);
}
function deleteProductStart(id) {
  return {
    type: products_action_types.DELETE_PRODUCT_START,
    id: id
  };
}
function deleteProductSuccess(id, product, force) {
  return {
    type: products_action_types.DELETE_PRODUCT_SUCCESS,
    id: id,
    product: product,
    force: force
  };
}
function deleteProductError(id, error) {
  return {
    type: products_action_types.DELETE_PRODUCT_ERROR,
    id: id,
    error: error
  };
}
function deleteProduct(id) {
  var force = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : false;
  return /*#__PURE__*/regenerator_default().mark(function _callee() {
    var url, product;
    return regenerator_default().wrap(function _callee$(_context4) {
      while (1) switch (_context4.prev = _context4.next) {
        case 0:
          _context4.next = 2;
          return deleteProductStart(id);
        case 2:
          _context4.prev = 2;
          url = force ? "".concat(WC_PRODUCT_NAMESPACE, "/").concat(id, "?force=true") : "".concat(WC_PRODUCT_NAMESPACE, "/").concat(id);
          _context4.next = 6;
          return (0,data_controls_build_module/* apiFetch */.nr)({
            path: url,
            method: 'DELETE'
          });
        case 6:
          product = _context4.sent;
          _context4.next = 9;
          return deleteProductSuccess(product.id, product, force);
        case 9:
          return _context4.abrupt("return", product);
        case 12:
          _context4.prev = 12;
          _context4.t0 = _context4["catch"](2);
          _context4.next = 16;
          return deleteProductError(id, _context4.t0);
        case 16:
          throw _context4.t0;
        case 17:
        case "end":
          return _context4.stop();
      }
    }, _callee, null, [[2, 12]]);
  })();
}
function setSuggestedProductAction(key, items) {
  return {
    type: products_action_types.SET_SUGGESTED_PRODUCTS,
    key: key,
    items: items
  };
}
;// CONCATENATED MODULE: ../../packages/js/data/src/products/resolvers.ts















var products_resolvers_marked = /*#__PURE__*/regenerator_default().mark(resolvers_getProducts),
  products_resolvers_marked2 = /*#__PURE__*/regenerator_default().mark(resolvers_getProduct),
  products_resolvers_marked3 = /*#__PURE__*/regenerator_default().mark(resolvers_getRelatedProducts),
  products_resolvers_marked4 = /*#__PURE__*/regenerator_default().mark(resolvers_getProductsTotalCount),
  products_resolvers_marked5 = /*#__PURE__*/regenerator_default().mark(resolvers_getPermalinkParts);



function products_resolvers_ownKeys(e, r) {
  var t = Object.keys(e);
  if (Object.getOwnPropertySymbols) {
    var o = Object.getOwnPropertySymbols(e);
    r && (o = o.filter(function (r) {
      return Object.getOwnPropertyDescriptor(e, r).enumerable;
    })), t.push.apply(t, o);
  }
  return t;
}
function products_resolvers_objectSpread(e) {
  for (var r = 1; r < arguments.length; r++) {
    var t = null != arguments[r] ? arguments[r] : {};
    r % 2 ? products_resolvers_ownKeys(Object(t), !0).forEach(function (r) {
      (0,defineProperty/* default */.A)(e, r, t[r]);
    }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(e, Object.getOwnPropertyDescriptors(t)) : products_resolvers_ownKeys(Object(t)).forEach(function (r) {
      Object.defineProperty(e, r, Object.getOwnPropertyDescriptor(t, r));
    });
  }
  return e;
}
/**
 * External dependencies
 */





/**
 * Internal dependencies
 */




var products_resolvers_dispatch = build_module_controls/* controls */.n && build_module_controls/* controls */.n.dispatch ? build_module_controls/* controls */.n.dispatch : data_controls_build_module/* dispatch */.JD;
var products_resolvers_resolveSelect = build_module_controls/* controls */.n && build_module_controls/* controls */.n.resolveSelect ? build_module_controls/* controls */.n.resolveSelect : data_controls_build_module/* select */.Lt;
function resolvers_getProducts(query) {
  var productsQuery, _yield$request, items, totalCount;
  return regenerator_default().wrap(function getProducts$(_context) {
    while (1) switch (_context.prev = _context.next) {
      case 0:
        // id is always required.
        productsQuery = products_resolvers_objectSpread({}, query);
        if (productsQuery && productsQuery._fields && !productsQuery._fields.includes('id')) {
          productsQuery._fields = ['id'].concat((0,toConsumableArray/* default */.A)(productsQuery._fields));
        }
        _context.prev = 2;
        _context.next = 5;
        return request(WC_PRODUCT_NAMESPACE, productsQuery);
      case 5:
        _yield$request = _context.sent;
        items = _yield$request.items;
        totalCount = _yield$request.totalCount;
        _context.next = 10;
        return getProductsTotalCountSuccess(query, totalCount);
      case 10:
        _context.next = 12;
        return getProductsSuccess(query, items, totalCount);
      case 12:
        return _context.abrupt("return", items);
      case 15:
        _context.prev = 15;
        _context.t0 = _context["catch"](2);
        _context.next = 19;
        return actions_getProductsError(query, _context.t0);
      case 19:
        throw _context.t0;
      case 20:
      case "end":
        return _context.stop();
    }
  }, products_resolvers_marked, null, [[2, 15]]);
}
function resolvers_getProduct(productId) {
  var product;
  return regenerator_default().wrap(function getProduct$(_context2) {
    while (1) switch (_context2.prev = _context2.next) {
      case 0:
        _context2.prev = 0;
        _context2.next = 3;
        return (0,data_controls_build_module/* apiFetch */.nr)({
          path: (0,add_query_args/* addQueryArgs */.F)("".concat(WC_PRODUCT_NAMESPACE, "/").concat(productId), {
            context: 'edit'
          }),
          method: 'GET'
        });
      case 3:
        product = _context2.sent;
        _context2.next = 6;
        return getProductSuccess(productId, product);
      case 6:
        _context2.next = 8;
        return products_resolvers_dispatch(products_constants_STORE_NAME, 'finishResolution', 'getPermalinkParts', [productId]);
      case 8:
        return _context2.abrupt("return", product);
      case 11:
        _context2.prev = 11;
        _context2.t0 = _context2["catch"](0);
        _context2.next = 15;
        return getProductError(productId, _context2.t0);
      case 15:
        throw _context2.t0;
      case 16:
      case "end":
        return _context2.stop();
    }
  }, products_resolvers_marked2, null, [[0, 11]]);
}
function resolvers_getRelatedProducts(productId) {
  var product, relatedProductsIds, relatedProducts;
  return regenerator_default().wrap(function getRelatedProducts$(_context3) {
    while (1) switch (_context3.prev = _context3.next) {
      case 0:
        _context3.prev = 0;
        _context3.next = 3;
        return products_resolvers_resolveSelect(products_constants_STORE_NAME, 'getProduct', productId);
      case 3:
        product = _context3.sent;
        // Pick the related products IDs.
        relatedProductsIds = product.related_ids;
        if (relatedProductsIds !== null && relatedProductsIds !== void 0 && relatedProductsIds.length) {
          _context3.next = 7;
          break;
        }
        return _context3.abrupt("return", []);
      case 7:
        _context3.next = 9;
        return products_resolvers_resolveSelect(products_constants_STORE_NAME, 'getProducts', {
          include: relatedProductsIds
        });
      case 9:
        relatedProducts = _context3.sent;
        return _context3.abrupt("return", relatedProducts);
      case 13:
        _context3.prev = 13;
        _context3.t0 = _context3["catch"](0);
        throw _context3.t0;
      case 16:
      case "end":
        return _context3.stop();
    }
  }, products_resolvers_marked3, null, [[0, 13]]);
}
function resolvers_getProductsTotalCount(query) {
  var totalsQuery, _yield$request2, totalCount;
  return regenerator_default().wrap(function getProductsTotalCount$(_context4) {
    while (1) switch (_context4.prev = _context4.next) {
      case 0:
        _context4.prev = 0;
        totalsQuery = products_resolvers_objectSpread(products_resolvers_objectSpread({}, query), {}, {
          page: 1,
          per_page: 1
        });
        _context4.next = 4;
        return request(WC_PRODUCT_NAMESPACE, totalsQuery);
      case 4:
        _yield$request2 = _context4.sent;
        totalCount = _yield$request2.totalCount;
        _context4.next = 8;
        return getProductsTotalCountSuccess(query, totalCount);
      case 8:
        return _context4.abrupt("return", totalCount);
      case 11:
        _context4.prev = 11;
        _context4.t0 = _context4["catch"](0);
        _context4.next = 15;
        return getProductsTotalCountError(query, _context4.t0);
      case 15:
        throw _context4.t0;
      case 16:
      case "end":
        return _context4.stop();
    }
  }, products_resolvers_marked4, null, [[0, 11]]);
}
function resolvers_getPermalinkParts(productId) {
  return regenerator_default().wrap(function getPermalinkParts$(_context5) {
    while (1) switch (_context5.prev = _context5.next) {
      case 0:
        _context5.next = 2;
        return products_resolvers_resolveSelect(products_constants_STORE_NAME, 'getProduct', [productId]);
      case 2:
      case "end":
        return _context5.stop();
    }
  }, products_resolvers_marked5);
}
var resolvers_getSuggestedProducts = function getSuggestedProducts(options) {
  return /*#__PURE__*/(
    // @ts-expect-error There are no types for this.
    function () {
      var _ref2 = (0,asyncToGenerator/* default */.A)( /*#__PURE__*/regenerator_default().mark(function _callee(_ref) {
        var contextualDispatch, key, data;
        return regenerator_default().wrap(function _callee$(_context6) {
          while (1) switch (_context6.prev = _context6.next) {
            case 0:
              contextualDispatch = _ref.dispatch;
              key = createIdFromOptions(options);
              _context6.next = 4;
              return (0,api_fetch_build_module/* default */.A)({
                path: (0,add_query_args/* addQueryArgs */.F)(WC_V3_ENDPOINT_SUGGESTED_PRODUCTS, options)
              });
            case 4:
              data = _context6.sent;
              contextualDispatch.setSuggestedProductAction(key, data);
            case 6:
            case "end":
              return _context6.stop();
          }
        }, _callee);
      }));
      return function (_x) {
        return _ref2.apply(this, arguments);
      };
    }()
  );
};
;// CONCATENATED MODULE: ../../packages/js/data/src/products/reducer.ts












function products_reducer_ownKeys(e, r) {
  var t = Object.keys(e);
  if (Object.getOwnPropertySymbols) {
    var o = Object.getOwnPropertySymbols(e);
    r && (o = o.filter(function (r) {
      return Object.getOwnPropertyDescriptor(e, r).enumerable;
    })), t.push.apply(t, o);
  }
  return t;
}
function products_reducer_objectSpread(e) {
  for (var r = 1; r < arguments.length; r++) {
    var t = null != arguments[r] ? arguments[r] : {};
    r % 2 ? products_reducer_ownKeys(Object(t), !0).forEach(function (r) {
      (0,defineProperty/* default */.A)(e, r, t[r]);
    }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(e, Object.getOwnPropertyDescriptors(t)) : products_reducer_ownKeys(Object(t)).forEach(function (r) {
      Object.defineProperty(e, r, Object.getOwnPropertyDescriptor(t, r));
    });
  }
  return e;
}
/**
 * External dependencies
 */

/**
 * Internal dependencies
 */


var products_reducer_reducer = function reducer() {
  var state = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {
    products: {},
    productsCount: {},
    errors: {},
    data: {},
    pending: {},
    suggestedProducts: {}
  };
  var payload = arguments.length > 1 ? arguments[1] : undefined;
  if (payload && 'type' in payload) {
    switch (payload.type) {
      case products_action_types.CREATE_PRODUCT_START:
        return products_reducer_objectSpread(products_reducer_objectSpread({}, state), {}, {
          pending: {
            createProduct: true
          }
        });
      case products_action_types.UPDATE_PRODUCT_START:
        return products_reducer_objectSpread(products_reducer_objectSpread({}, state), {}, {
          pending: {
            updateProduct: products_reducer_objectSpread(products_reducer_objectSpread({}, state.pending.updateProduct || {}), {}, (0,defineProperty/* default */.A)({}, payload.id, true))
          }
        });
      case products_action_types.DUPLICATE_PRODUCT_START:
        return products_reducer_objectSpread(products_reducer_objectSpread({}, state), {}, {
          pending: {
            duplicateProduct: products_reducer_objectSpread(products_reducer_objectSpread({}, state.pending.duplicateProduct || {}), {}, (0,defineProperty/* default */.A)({}, payload.id, true))
          }
        });
      case products_action_types.CREATE_PRODUCT_SUCCESS:
      case products_action_types.GET_PRODUCT_SUCCESS:
      case products_action_types.UPDATE_PRODUCT_SUCCESS:
      case products_action_types.DUPLICATE_PRODUCT_SUCCESS:
        var productData = state.data || {};
        return products_reducer_objectSpread(products_reducer_objectSpread({}, state), {}, {
          data: products_reducer_objectSpread(products_reducer_objectSpread({}, productData), {}, (0,defineProperty/* default */.A)({}, payload.id, products_reducer_objectSpread(products_reducer_objectSpread({}, productData[payload.id] || {}), payload.product))),
          pending: {
            createProduct: false,
            duplicateProduct: products_reducer_objectSpread(products_reducer_objectSpread({}, state.pending.duplicateProduct || {}), {}, (0,defineProperty/* default */.A)({}, payload.id, false)),
            updateProduct: products_reducer_objectSpread(products_reducer_objectSpread({}, state.pending.updateProduct || {}), {}, (0,defineProperty/* default */.A)({}, payload.id, false))
          }
        });
      case products_action_types.GET_PRODUCTS_SUCCESS:
        var ids = [];
        var nextProducts = payload.products.reduce(function (result, product) {
          ids.push(product.id);
          result[product.id] = products_reducer_objectSpread(products_reducer_objectSpread({}, state.data[product.id] || {}), product);
          return result;
        }, {});
        var resourceName = getProductResourceName(payload.query);
        return products_reducer_objectSpread(products_reducer_objectSpread({}, state), {}, {
          products: products_reducer_objectSpread(products_reducer_objectSpread({}, state.products), {}, (0,defineProperty/* default */.A)({}, resourceName, {
            data: ids
          })),
          data: products_reducer_objectSpread(products_reducer_objectSpread({}, state.data), nextProducts)
        });
      case products_action_types.GET_PRODUCTS_TOTAL_COUNT_SUCCESS:
        var totalResourceName = getTotalProductCountResourceName(payload.query);
        return products_reducer_objectSpread(products_reducer_objectSpread({}, state), {}, {
          productsCount: products_reducer_objectSpread(products_reducer_objectSpread({}, state.productsCount), {}, (0,defineProperty/* default */.A)({}, totalResourceName, payload.totalCount))
        });
      case products_action_types.GET_PRODUCT_ERROR:
        return products_reducer_objectSpread(products_reducer_objectSpread({}, state), {}, {
          errors: products_reducer_objectSpread(products_reducer_objectSpread({}, state.errors), {}, (0,defineProperty/* default */.A)({}, payload.productId, payload.error))
        });
      case products_action_types.GET_PRODUCTS_ERROR:
      case products_action_types.GET_PRODUCTS_TOTAL_COUNT_ERROR:
      case products_action_types.CREATE_PRODUCT_ERROR:
        return products_reducer_objectSpread(products_reducer_objectSpread({}, state), {}, {
          errors: products_reducer_objectSpread(products_reducer_objectSpread({}, state.errors), {}, (0,defineProperty/* default */.A)({}, getProductResourceName(payload.query), payload.error)),
          pending: {
            createProduct: false
          }
        });
      case products_action_types.UPDATE_PRODUCT_ERROR:
        return products_reducer_objectSpread(products_reducer_objectSpread({}, state), {}, {
          errors: products_reducer_objectSpread(products_reducer_objectSpread({}, state.errors), {}, (0,defineProperty/* default */.A)({}, "update/".concat(payload.id), payload.error))
        });
      case products_action_types.DUPLICATE_PRODUCT_ERROR:
        return products_reducer_objectSpread(products_reducer_objectSpread({}, state), {}, {
          errors: products_reducer_objectSpread(products_reducer_objectSpread({}, state.errors), {}, (0,defineProperty/* default */.A)({}, "duplicate/".concat(payload.id), payload.error))
        });
      case products_action_types.DELETE_PRODUCT_START:
        return products_reducer_objectSpread(products_reducer_objectSpread({}, state), {}, {
          pending: {
            deleteProduct: products_reducer_objectSpread(products_reducer_objectSpread({}, state.pending.deleteProduct || {}), {}, (0,defineProperty/* default */.A)({}, payload.id, true))
          }
        });
      case products_action_types.DELETE_PRODUCT_ERROR:
        return products_reducer_objectSpread(products_reducer_objectSpread({}, state), {}, {
          errors: products_reducer_objectSpread(products_reducer_objectSpread({}, state.errors), {}, (0,defineProperty/* default */.A)({}, "delete/".concat(payload.id), payload.error)),
          pending: {
            deleteProduct: products_reducer_objectSpread(products_reducer_objectSpread({}, state.pending.deleteProduct || {}), {}, (0,defineProperty/* default */.A)({}, payload.id, false))
          }
        });
      case products_action_types.DELETE_PRODUCT_SUCCESS:
        var prData = state.data || {};
        return products_reducer_objectSpread(products_reducer_objectSpread({}, state), {}, {
          data: products_reducer_objectSpread(products_reducer_objectSpread({}, prData), {}, (0,defineProperty/* default */.A)({}, payload.id, products_reducer_objectSpread(products_reducer_objectSpread(products_reducer_objectSpread({}, prData[payload.id] || {}), payload.product), {}, {
            status: payload.force ? 'deleted' : 'trash'
          }))),
          pending: {
            deleteProduct: products_reducer_objectSpread(products_reducer_objectSpread({}, state.pending.deleteProduct || {}), {}, (0,defineProperty/* default */.A)({}, payload.id, false))
          }
        });
      case products_action_types.SET_SUGGESTED_PRODUCTS:
        {
          return products_reducer_objectSpread(products_reducer_objectSpread({}, state), {}, {
            suggestedProducts: products_reducer_objectSpread(products_reducer_objectSpread({}, state.suggestedProducts), {}, (0,defineProperty/* default */.A)({}, payload.key, {
              items: payload.items || []
            }))
          });
        }
      default:
        return state;
    }
  }
  return state;
};
/* harmony default export */ const products_reducer = (products_reducer_reducer);
;// CONCATENATED MODULE: ../../packages/js/data/src/products/index.ts
/**
 * External dependencies
 */


/**
 * Internal dependencies
 */






(0,data_build_module/* registerStore */.ti)(products_constants_STORE_NAME, {
  // @ts-expect-error There are no types for this.
  __experimentalUseThunks: true,
  reducer: products_reducer,
  actions: products_actions_namespaceObject,
  controls: src_controls,
  selectors: products_selectors_namespaceObject,
  resolvers: products_resolvers_namespaceObject
});
var PRODUCTS_STORE_NAME = (/* unused pure expression or super */ null && (STORE_NAME));
;// CONCATENATED MODULE: ../../packages/js/data/src/orders/constants.ts
var orders_constants_STORE_NAME = 'wc/admin/orders';
var WC_ORDERS_NAMESPACE = '/wc/v3/orders';
;// CONCATENATED MODULE: ../../packages/js/data/src/orders/utils.ts

var orders_utils_excluded = ["_fields", "page", "per_page"];
/**
 * Internal dependencies
 */

var utils_PRODUCT_PREFIX = 'order';

/**
 * Generate a resource name for orders.
 *
 * @param {Object} query Query for orders.
 * @return {string} Resource name for orders.
 */
function getOrderResourceName(query) {
  return utils_getResourceName(utils_PRODUCT_PREFIX, query);
}

/**
 * Generate a resource name for order totals count.
 *
 * It omits query parameters from the identifier that don't affect
 * totals values like pagination and response field filtering.
 *
 * @param {Object} query Query for order totals count.
 * @return {string} Resource name for order totals.
 */
function getTotalOrderCountResourceName(query) {
  var _fields = query._fields,
    page = query.page,
    per_page = query.per_page,
    totalsQuery = (0,objectWithoutProperties/* default */.A)(query, orders_utils_excluded);
  return getOrderResourceName(totalsQuery);
}
;// CONCATENATED MODULE: ../../packages/js/data/src/orders/selectors.ts











function orders_selectors_ownKeys(e, r) {
  var t = Object.keys(e);
  if (Object.getOwnPropertySymbols) {
    var o = Object.getOwnPropertySymbols(e);
    r && (o = o.filter(function (r) {
      return Object.getOwnPropertyDescriptor(e, r).enumerable;
    })), t.push.apply(t, o);
  }
  return t;
}
function orders_selectors_objectSpread(e) {
  for (var r = 1; r < arguments.length; r++) {
    var t = null != arguments[r] ? arguments[r] : {};
    r % 2 ? orders_selectors_ownKeys(Object(t), !0).forEach(function (r) {
      (0,defineProperty/* default */.A)(e, r, t[r]);
    }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(e, Object.getOwnPropertyDescriptors(t)) : orders_selectors_ownKeys(Object(t)).forEach(function (r) {
      Object.defineProperty(e, r, Object.getOwnPropertyDescriptor(t, r));
    });
  }
  return e;
}




/**
 * External dependencies
 */


/**
 * Internal dependencies
 */

var getOrders = (0,rememo/* default */.A)(function (state, query) {
  var defaultValue = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : undefined;
  var resourceName = getOrderResourceName(query);
  var ids = state.orders[resourceName] ? state.orders[resourceName].data : undefined;
  if (!ids) {
    return defaultValue;
  }
  if (query && typeof query._fields !== 'undefined') {
    var fields = query._fields;
    return ids.map(function (id) {
      return fields.reduce(function (product, field) {
        return orders_selectors_objectSpread(orders_selectors_objectSpread({}, product), {}, (0,defineProperty/* default */.A)({}, field, state.data[id][field]));
      }, {});
    });
  }
  return ids.map(function (id) {
    return state.data[id];
  });
}, function (state, query) {
  var resourceName = getOrderResourceName(query);
  var ids = state.orders[resourceName] ? state.orders[resourceName].data : [];
  return [state.orders[resourceName]].concat((0,toConsumableArray/* default */.A)(ids.map(function (id) {
    return state.data[id];
  })));
});
var getOrdersTotalCount = function getOrdersTotalCount(state, query) {
  var defaultValue = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : undefined;
  var resourceName = getTotalOrderCountResourceName(query);
  var totalCount = state.ordersCount.hasOwnProperty(resourceName) ? state.ordersCount[resourceName] : defaultValue;
  return totalCount;
};
var getOrdersError = function getOrdersError(state, query) {
  var resourceName = getOrderResourceName(query);
  return state.errors[resourceName];
};
;// CONCATENATED MODULE: ../../packages/js/data/src/orders/action-types.ts
var orders_action_types_TYPES = /*#__PURE__*/function (TYPES) {
  TYPES["GET_ORDER_SUCCESS"] = "GET_ORDER_SUCCESS";
  TYPES["GET_ORDER_ERROR"] = "GET_ORDER_ERROR";
  TYPES["GET_ORDERS_SUCCESS"] = "GET_ORDERS_SUCCESS";
  TYPES["GET_ORDERS_ERROR"] = "GET_ORDERS_ERROR";
  TYPES["GET_ORDERS_TOTAL_COUNT_SUCCESS"] = "GET_ORDERS_TOTAL_COUNT_SUCCESS";
  TYPES["GET_ORDERS_TOTAL_COUNT_ERROR"] = "GET_ORDERS_TOTAL_COUNT_ERROR";
  return TYPES;
}({});
/* harmony default export */ const orders_action_types = (orders_action_types_TYPES);
;// CONCATENATED MODULE: ../../packages/js/data/src/orders/actions.ts
/**
 * Internal dependencies
 */

function getOrderSuccess(id, order) {
  return {
    type: orders_action_types.GET_ORDER_SUCCESS,
    id: id,
    order: order
  };
}
function getOrderError(query, error) {
  return {
    type: orders_action_types.GET_ORDER_ERROR,
    query: query,
    error: error
  };
}
function getOrdersSuccess(query, orders, totalCount) {
  return {
    type: orders_action_types.GET_ORDERS_SUCCESS,
    orders: orders,
    query: query,
    totalCount: totalCount
  };
}
function actions_getOrdersError(query, error) {
  return {
    type: orders_action_types.GET_ORDERS_ERROR,
    query: query,
    error: error
  };
}
function getOrdersTotalCountSuccess(query, totalCount) {
  return {
    type: orders_action_types.GET_ORDERS_TOTAL_COUNT_SUCCESS,
    query: query,
    totalCount: totalCount
  };
}
function getOrdersTotalCountError(query, error) {
  return {
    type: orders_action_types.GET_ORDERS_TOTAL_COUNT_ERROR,
    query: query,
    error: error
  };
}
;// CONCATENATED MODULE: ../../packages/js/data/src/orders/resolvers.ts














var orders_resolvers_marked = /*#__PURE__*/regenerator_default().mark(resolvers_getOrders),
  orders_resolvers_marked2 = /*#__PURE__*/regenerator_default().mark(resolvers_getOrdersTotalCount);



function orders_resolvers_ownKeys(e, r) {
  var t = Object.keys(e);
  if (Object.getOwnPropertySymbols) {
    var o = Object.getOwnPropertySymbols(e);
    r && (o = o.filter(function (r) {
      return Object.getOwnPropertyDescriptor(e, r).enumerable;
    })), t.push.apply(t, o);
  }
  return t;
}
function orders_resolvers_objectSpread(e) {
  for (var r = 1; r < arguments.length; r++) {
    var t = null != arguments[r] ? arguments[r] : {};
    r % 2 ? orders_resolvers_ownKeys(Object(t), !0).forEach(function (r) {
      (0,defineProperty/* default */.A)(e, r, t[r]);
    }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(e, Object.getOwnPropertyDescriptors(t)) : orders_resolvers_ownKeys(Object(t)).forEach(function (r) {
      Object.defineProperty(e, r, Object.getOwnPropertyDescriptor(t, r));
    });
  }
  return e;
}
/**
 * Internal dependencies
 */



function resolvers_getOrders(query) {
  var ordersQuery, _yield$request, items, totalCount;
  return regenerator_default().wrap(function getOrders$(_context) {
    while (1) switch (_context.prev = _context.next) {
      case 0:
        // id is always required.
        ordersQuery = orders_resolvers_objectSpread({}, query);
        if (ordersQuery && ordersQuery._fields && !ordersQuery._fields.includes('id')) {
          ordersQuery._fields = ['id'].concat((0,toConsumableArray/* default */.A)(ordersQuery._fields));
        }
        _context.prev = 2;
        _context.next = 5;
        return request(WC_ORDERS_NAMESPACE, ordersQuery);
      case 5:
        _yield$request = _context.sent;
        items = _yield$request.items;
        totalCount = _yield$request.totalCount;
        _context.next = 10;
        return getOrdersTotalCountSuccess(query, totalCount);
      case 10:
        _context.next = 12;
        return getOrdersSuccess(query, items, totalCount);
      case 12:
        return _context.abrupt("return", items);
      case 15:
        _context.prev = 15;
        _context.t0 = _context["catch"](2);
        _context.next = 19;
        return actions_getOrdersError(query, _context.t0);
      case 19:
        return _context.abrupt("return", _context.t0);
      case 20:
      case "end":
        return _context.stop();
    }
  }, orders_resolvers_marked, null, [[2, 15]]);
}
function resolvers_getOrdersTotalCount(query) {
  var totalsQuery, _yield$request2, totalCount;
  return regenerator_default().wrap(function getOrdersTotalCount$(_context2) {
    while (1) switch (_context2.prev = _context2.next) {
      case 0:
        _context2.prev = 0;
        totalsQuery = orders_resolvers_objectSpread(orders_resolvers_objectSpread({}, query), {}, {
          page: 1,
          per_page: 1
        });
        _context2.next = 4;
        return request(WC_ORDERS_NAMESPACE, totalsQuery);
      case 4:
        _yield$request2 = _context2.sent;
        totalCount = _yield$request2.totalCount;
        _context2.next = 8;
        return getOrdersTotalCountSuccess(query, totalCount);
      case 8:
        return _context2.abrupt("return", totalCount);
      case 11:
        _context2.prev = 11;
        _context2.t0 = _context2["catch"](0);
        _context2.next = 15;
        return getOrdersTotalCountError(query, _context2.t0);
      case 15:
        return _context2.abrupt("return", _context2.t0);
      case 16:
      case "end":
        return _context2.stop();
    }
  }, orders_resolvers_marked2, null, [[0, 11]]);
}
;// CONCATENATED MODULE: ../../packages/js/data/src/orders/reducer.ts












function orders_reducer_ownKeys(e, r) {
  var t = Object.keys(e);
  if (Object.getOwnPropertySymbols) {
    var o = Object.getOwnPropertySymbols(e);
    r && (o = o.filter(function (r) {
      return Object.getOwnPropertyDescriptor(e, r).enumerable;
    })), t.push.apply(t, o);
  }
  return t;
}
function orders_reducer_objectSpread(e) {
  for (var r = 1; r < arguments.length; r++) {
    var t = null != arguments[r] ? arguments[r] : {};
    r % 2 ? orders_reducer_ownKeys(Object(t), !0).forEach(function (r) {
      (0,defineProperty/* default */.A)(e, r, t[r]);
    }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(e, Object.getOwnPropertyDescriptors(t)) : orders_reducer_ownKeys(Object(t)).forEach(function (r) {
      Object.defineProperty(e, r, Object.getOwnPropertyDescriptor(t, r));
    });
  }
  return e;
}
/**
 * External dependencies
 */

/**
 * Internal dependencies
 */


var orders_reducer_reducer = function reducer() {
  var state = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {
    orders: {},
    ordersCount: {},
    errors: {},
    data: {}
  };
  var payload = arguments.length > 1 ? arguments[1] : undefined;
  if (payload && 'type' in payload) {
    switch (payload.type) {
      case orders_action_types.GET_ORDER_SUCCESS:
        var orderData = state.data || {};
        return orders_reducer_objectSpread(orders_reducer_objectSpread({}, state), {}, {
          data: orders_reducer_objectSpread(orders_reducer_objectSpread({}, orderData), {}, (0,defineProperty/* default */.A)({}, payload.id, orders_reducer_objectSpread(orders_reducer_objectSpread({}, orderData[payload.id] || {}), payload.order)))
        });
      case orders_action_types.GET_ORDERS_SUCCESS:
        var ids = [];
        var nextOrders = payload.orders.reduce(function (result, order) {
          ids.push(order.id);
          result[order.id] = orders_reducer_objectSpread(orders_reducer_objectSpread({}, state.data[order.id] || {}), order);
          return result;
        }, {});
        var resourceName = getOrderResourceName(payload.query);
        return orders_reducer_objectSpread(orders_reducer_objectSpread({}, state), {}, {
          orders: orders_reducer_objectSpread(orders_reducer_objectSpread({}, state.orders), {}, (0,defineProperty/* default */.A)({}, resourceName, {
            data: ids
          })),
          data: orders_reducer_objectSpread(orders_reducer_objectSpread({}, state.data), nextOrders)
        });
      case orders_action_types.GET_ORDERS_TOTAL_COUNT_SUCCESS:
        var totalResourceName = getTotalOrderCountResourceName(payload.query);
        return orders_reducer_objectSpread(orders_reducer_objectSpread({}, state), {}, {
          ordersCount: orders_reducer_objectSpread(orders_reducer_objectSpread({}, state.ordersCount), {}, (0,defineProperty/* default */.A)({}, totalResourceName, payload.totalCount))
        });
      case orders_action_types.GET_ORDER_ERROR:
      case orders_action_types.GET_ORDERS_ERROR:
      case orders_action_types.GET_ORDERS_TOTAL_COUNT_ERROR:
        return orders_reducer_objectSpread(orders_reducer_objectSpread({}, state), {}, {
          errors: orders_reducer_objectSpread(orders_reducer_objectSpread({}, state.errors), {}, (0,defineProperty/* default */.A)({}, getOrderResourceName(payload.query), payload.error))
        });
      default:
        return state;
    }
  }
  return state;
};
/* harmony default export */ const orders_reducer = (orders_reducer_reducer);
;// CONCATENATED MODULE: ../../packages/js/data/src/orders/index.ts
/**
 * External dependencies
 */


/**
 * Internal dependencies
 */






(0,data_build_module/* registerStore */.ti)(orders_constants_STORE_NAME, {
  reducer: orders_reducer,
  actions: orders_actions_namespaceObject,
  controls: src_controls,
  selectors: orders_selectors_namespaceObject,
  resolvers: orders_resolvers_namespaceObject
});
var ORDERS_STORE_NAME = (/* unused pure expression or super */ null && (STORE_NAME));
;// CONCATENATED MODULE: ../../packages/js/data/src/product-attributes/constants.ts
var product_attributes_constants_STORE_NAME = 'wc/admin/products/attributes';
var WC_PRODUCT_ATTRIBUTES_NAMESPACE = '/wc/v3/products/attributes';
// EXTERNAL MODULE: ../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/toArray.js
var toArray = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/toArray.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.string.match.js
var es_string_match = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.string.match.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.regexp.constructor.js
var es_regexp_constructor = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.regexp.constructor.js");
;// CONCATENATED MODULE: ../../packages/js/data/src/crud/crud-actions.ts
var CRUD_ACTIONS = /*#__PURE__*/function (CRUD_ACTIONS) {
  CRUD_ACTIONS["CREATE_ITEM"] = "CREATE_ITEM";
  CRUD_ACTIONS["DELETE_ITEM"] = "DELETE_ITEM";
  CRUD_ACTIONS["GET_ITEM"] = "GET_ITEM";
  CRUD_ACTIONS["GET_ITEMS"] = "GET_ITEMS";
  CRUD_ACTIONS["GET_ITEMS_TOTAL_COUNT"] = "GET_ITEMS_TOTAL_COUNT";
  CRUD_ACTIONS["UPDATE_ITEM"] = "UPDATE_ITEM";
  return CRUD_ACTIONS;
}({});
/* harmony default export */ const crud_actions = (CRUD_ACTIONS);
;// CONCATENATED MODULE: ../../packages/js/data/src/crud/utils.ts










function crud_utils_ownKeys(e, r) {
  var t = Object.keys(e);
  if (Object.getOwnPropertySymbols) {
    var o = Object.getOwnPropertySymbols(e);
    r && (o = o.filter(function (r) {
      return Object.getOwnPropertyDescriptor(e, r).enumerable;
    })), t.push.apply(t, o);
  }
  return t;
}
function crud_utils_objectSpread(e) {
  for (var r = 1; r < arguments.length; r++) {
    var t = null != arguments[r] ? arguments[r] : {};
    r % 2 ? crud_utils_ownKeys(Object(t), !0).forEach(function (r) {
      (0,defineProperty/* default */.A)(e, r, t[r]);
    }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(e, Object.getOwnPropertyDescriptors(t)) : crud_utils_ownKeys(Object(t)).forEach(function (r) {
      Object.defineProperty(e, r, Object.getOwnPropertyDescriptor(t, r));
    });
  }
  return e;
}















/**
 * External dependencies
 */


/**
 * Internal dependencies
 */


/**
 * Get a REST path given a template path and URL params.
 *
 * @param templatePath Path with variable names.
 * @param query        Item query.
 * @param parameters   Array of items to replace in the templatePath.
 * @return string REST path.
 */
var getRestPath = function getRestPath(templatePath, query, parameters) {
  var _path$match;
  var path = templatePath;
  (_path$match = path.match(/{(.*?)}/g)) === null || _path$match === void 0 || _path$match.forEach(function (str, i) {
    path = path.replace(str, parameters[i].toString());
  });
  var regex = new RegExp(/{|}/);
  if (regex.test(path.toString())) {
    throw new Error('Not all URL parameters were replaced');
  }
  return (0,add_query_args/* addQueryArgs */.F)(path, query);
};

/**
 * Get a key from an item ID and optional parent.
 *
 * @param query         Item Query.
 * @param urlParameters Parameters used for URL.
 * @return string
 */
var getKey = function getKey(query) {
  var urlParameters = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : [];
  var id = typeof query === 'string' || typeof query === 'number' ? query : query.id;
  if (!urlParameters.length) {
    return id;
  }
  return urlParameters.join('/') + '/' + id;
};
/**
 * This function takes an array of items and reduces it into a single object,
 * where each key is a unique identifier generated by
 * combining the item ID and optional URL parameters.
 * It also returns an array of these keys (`ids`).
 *
 * @param {Array<Item>}          items         - The items to process.
 * @param {Array<IdType>}        urlParameters - The URL parameters used to generate keys.
 * @param {Record<string, Item>} currentState  - The current state data to merge with.
 * @return {organizeItemsByIdReturn} An object with two properties: `objItems` and `ids`.
 */
var organizeItemsById = function organizeItemsById(items) {
  var urlParameters = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : [];
  var currentState = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : {};
  var ids = [];
  var objItems = {};
  var hasUrlParams = urlParameters.length > 0;
  items.forEach(function (item) {
    var key = hasUrlParams ? getKey(item.id, urlParameters) : item.id;
    ids.push(key);
    objItems[key] = crud_utils_objectSpread(crud_utils_objectSpread({}, currentState[key] || {}), item);
  });
  return {
    objItems: objItems,
    ids: ids
  };
};

/**
 * Filters the input data object, returning a new object that contains only the keys
 * specified in the keys array.
 *
 * @param {Record<string, unknown>} data - The original data object to filter.
 * @param {IdType[]}                keys - An array of keys that should be included in the returned object.
 * @return {Record<string, unknown>} A new object containing only the specified keys.
 */
function filterDataByKeys(data, keys) {
  return keys.reduce(function (acc, key) {
    if (data[key]) {
      acc[key] = data[key];
    }
    return acc;
  }, {});
}

/**
 * Parse an ID query into a ID string.
 *
 * @param query Id Query
 * @return string ID.
 */
var parseId = function parseId(query) {
  var urlParameters = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : [];
  if (typeof query === 'string' || typeof query === 'number') {
    return {
      id: query,
      key: query
    };
  }
  return {
    id: query.id,
    key: getKey(query, urlParameters)
  };
};

/**
 * Create a new function that adds in the namespace.
 *
 * @param fn        Function to wrap.
 * @param namespace Namespace to pass to last argument of function.
 * @return Wrapped function
 */
// eslint-disable-next-line @typescript-eslint/no-explicit-any
var applyNamespace = function applyNamespace(fn, namespace) {
  var defaultArgs = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : [];
  return function () {
    for (var _len = arguments.length, args = new Array(_len), _key = 0; _key < _len; _key++) {
      args[_key] = arguments[_key];
    }
    defaultArgs.forEach(function (defaultArg, index) {
      // skip first item, as that is the state.
      if (args[index + 1] === undefined) {
        args[index + 1] = defaultArg;
      }
    });
    return fn.apply(void 0, args.concat([namespace]));
  };
};

/**
 * Get the key names from a namespace string.
 *
 * @param namespace Namespace to get keys from.
 * @return Array of keys.
 */
var getNamespaceKeys = function getNamespaceKeys(namespace) {
  var _namespace$match;
  var keys = [];
  (_namespace$match = namespace.match(/{(.*?)}/g)) === null || _namespace$match === void 0 || _namespace$match.forEach(function (match) {
    var key = match.substr(1, match.length - 2);
    keys.push(key);
  });
  return keys;
};

/**
 * Get URL parameters from namespace and provided query.
 *
 * @param namespace Namespace string to replace params in.
 * @param query     Query object with key values.
 * @return Array of URL parameter values.
 */
var getUrlParameters = function getUrlParameters(namespace, query) {
  if ((0,esm_typeof/* default */.A)(query) !== 'object') {
    return [];
  }
  var params = [];
  var keys = getNamespaceKeys(namespace);
  keys.forEach(function (key) {
    if (query.hasOwnProperty(key)) {
      params.push(query[key]);
    }
  });
  return params;
};

/**
 * Check to see if an argument is a valid type of ID query.
 *
 * @param arg       Unknow argument to check.
 * @param namespace The namespace string
 * @return boolean
 */
var isValidIdQuery = function isValidIdQuery(arg, namespace) {
  if (typeof arg === 'string' || typeof arg === 'number') {
    return true;
  }
  var validKeys = ['id'].concat((0,toConsumableArray/* default */.A)(getNamespaceKeys(namespace)));
  if (arg && (0,esm_typeof/* default */.A)(arg) === 'object' && arg.hasOwnProperty('id') && JSON.stringify(validKeys.sort()) === JSON.stringify(Object.keys(arg).sort())) {
    return true;
  }
  return false;
};

/**
 * Replace the initial argument with a key if it's a valid ID query.
 *
 * @param args      Args to check.
 * @param namespace Namespace.
 * @return Sanitized arguments.
 */
var maybeReplaceIdQuery = function maybeReplaceIdQuery(args, namespace) {
  var _args = (0,toArray/* default */.A)(args),
    firstArgument = _args[0],
    rest = _args.slice(1);
  if (!firstArgument || !isValidIdQuery(firstArgument, namespace)) {
    return args;
  }
  var urlParameters = getUrlParameters(namespace, firstArgument);
  var _parseId = parseId(firstArgument, urlParameters),
    key = _parseId.key;
  return [key].concat((0,toConsumableArray/* default */.A)(rest));
};

/**
 * Clean a query of all namespaced params.
 *
 * @param query     Query to clean.
 * @param namespace
 * @return Cleaned query object.
 */
var cleanQuery = function cleanQuery(query, namespace) {
  var cleaned = crud_utils_objectSpread({}, query);
  var keys = getNamespaceKeys(namespace);
  keys.forEach(function (key) {
    delete cleaned[key];
  });
  return cleaned;
};

/**
 * Get the identifier for a request provided its arguments.
 *
 * @param name Name of action or selector.
 * @param args Arguments for the request.
 * @return Key to identify the request.
 */
var getRequestIdentifier = utils_getResourceName;

/**
 * Get a generic action name from a resource action name if one exists.
 *
 * @param action       Action name to check.
 * @param resourceName Resource name.
 * @return Generic action name if one exists, otherwise the passed action name.
 */
var getGenericActionName = function getGenericActionName(action, resourceName) {
  switch (action) {
    case "create".concat(resourceName):
      return crud_actions.CREATE_ITEM;
    case "delete".concat(resourceName):
      return crud_actions.DELETE_ITEM;
    case "update".concat(resourceName):
      return crud_actions.UPDATE_ITEM;
  }
  return action;
};
;// CONCATENATED MODULE: ../../packages/js/data/src/crud/selectors.ts










function crud_selectors_ownKeys(e, r) {
  var t = Object.keys(e);
  if (Object.getOwnPropertySymbols) {
    var o = Object.getOwnPropertySymbols(e);
    r && (o = o.filter(function (r) {
      return Object.getOwnPropertyDescriptor(e, r).enumerable;
    })), t.push.apply(t, o);
  }
  return t;
}
function crud_selectors_objectSpread(e) {
  for (var r = 1; r < arguments.length; r++) {
    var t = null != arguments[r] ? arguments[r] : {};
    r % 2 ? crud_selectors_ownKeys(Object(t), !0).forEach(function (r) {
      (0,defineProperty/* default */.A)(e, r, t[r]);
    }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(e, Object.getOwnPropertyDescriptors(t)) : crud_selectors_ownKeys(Object(t)).forEach(function (r) {
      Object.defineProperty(e, r, Object.getOwnPropertyDescriptor(t, r));
    });
  }
  return e;
}





/**
 * External dependencies
 */


/**
 * Internal dependencies
 */



var getItemCreateError = function getItemCreateError(state, query) {
  var itemQuery = getRequestIdentifier(crud_actions.CREATE_ITEM, query);
  return state.errors[itemQuery];
};
var getItemDeleteError = function getItemDeleteError(state, idQuery, namespace) {
  var urlParameters = getUrlParameters(namespace, idQuery);
  var _parseId = parseId(idQuery, urlParameters),
    key = _parseId.key;
  var itemQuery = getRequestIdentifier(crud_actions.DELETE_ITEM, key);
  return state.errors[itemQuery];
};
var getItem = function getItem(state, idQuery, namespace) {
  var urlParameters = getUrlParameters(namespace, idQuery);
  var _parseId2 = parseId(idQuery, urlParameters),
    key = _parseId2.key;
  return state.data[key];
};
var getItemError = function getItemError(state, idQuery, namespace) {
  var urlParameters = getUrlParameters(namespace, idQuery);
  var _parseId3 = parseId(idQuery, urlParameters),
    key = _parseId3.key;
  var itemQuery = getRequestIdentifier(crud_actions.GET_ITEM, key);
  return state.errors[itemQuery];
};
var selectors_getItems = (0,rememo/* default */.A)(function (state, query) {
  var itemQuery = getRequestIdentifier(crud_actions.GET_ITEMS, query || {});
  var ids = state.items[itemQuery] ? state.items[itemQuery].data : undefined;
  if (!ids) {
    return null;
  }
  if (query && typeof query._fields !== 'undefined') {
    var fields = query._fields;
    return ids.map(function (id) {
      return fields.reduce(function (item, field) {
        return crud_selectors_objectSpread(crud_selectors_objectSpread({}, item), {}, (0,defineProperty/* default */.A)({}, field, state.data[id][field]));
      }, {});
    });
  }
  var data = ids.map(function (id) {
    return state.data[id];
  }).filter(function (item) {
    return item !== undefined;
  });
  return data;
}, function (state, query) {
  var itemQuery = getRequestIdentifier(crud_actions.GET_ITEMS, query || {});
  var ids = state.items[itemQuery] ? state.items[itemQuery].data : undefined;
  return [state.items[itemQuery]].concat((0,toConsumableArray/* default */.A)((ids || []).map(function (id) {
    return state.data[id];
  })));
});
var selectors_getItemsTotalCount = function getItemsTotalCount(state, query) {
  var defaultValue = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : undefined;
  var itemQuery = getTotalCountResourceName(crud_actions.GET_ITEMS, query || {});
  var totalCount = state.itemsCount.hasOwnProperty(itemQuery) ? state.itemsCount[itemQuery] : defaultValue;
  return totalCount;
};
var selectors_getItemsError = function getItemsError(state, query) {
  var itemQuery = getRequestIdentifier(crud_actions.GET_ITEMS, query || {});
  return state.errors[itemQuery];
};
var getItemUpdateError = function getItemUpdateError(state, idQuery, urlParameters) {
  var _parseId4 = parseId(idQuery, urlParameters),
    key = _parseId4.key;
  var itemQuery = getRequestIdentifier(crud_actions.UPDATE_ITEM, key);
  return state.errors[itemQuery];
};
var selectors_EMPTY_OBJECT = {};
var createSelectors = function createSelectors(_ref) {
  var resourceName = _ref.resourceName,
    pluralResourceName = _ref.pluralResourceName,
    namespace = _ref.namespace;
  var hasFinishedRequest = function hasFinishedRequest(state, action) {
    var args = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : [];
    var sanitizedArgs = maybeReplaceIdQuery(args, namespace);
    var actionName = getGenericActionName(action, resourceName);
    var requestId = getRequestIdentifier.apply(void 0, [actionName].concat((0,toConsumableArray/* default */.A)(sanitizedArgs)));
    if (action) return state.requesting.hasOwnProperty(requestId) && !state.requesting[requestId];
  };
  var isRequesting = function isRequesting(state, action) {
    var args = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : [];
    var sanitizedArgs = maybeReplaceIdQuery(args, namespace);
    var actionName = getGenericActionName(action, resourceName);
    var requestId = getRequestIdentifier.apply(void 0, [actionName].concat((0,toConsumableArray/* default */.A)(sanitizedArgs)));
    return state.requesting[requestId];
  };
  return (0,defineProperty/* default */.A)((0,defineProperty/* default */.A)((0,defineProperty/* default */.A)((0,defineProperty/* default */.A)((0,defineProperty/* default */.A)((0,defineProperty/* default */.A)((0,defineProperty/* default */.A)((0,defineProperty/* default */.A)((0,defineProperty/* default */.A)((0,defineProperty/* default */.A)({}, "get".concat(resourceName), applyNamespace(getItem, namespace)), "get".concat(resourceName, "Error"), applyNamespace(getItemError, namespace)), "get".concat(pluralResourceName), applyNamespace(selectors_getItems, namespace, [selectors_EMPTY_OBJECT])), "get".concat(pluralResourceName, "TotalCount"), applyNamespace(selectors_getItemsTotalCount, namespace, [selectors_EMPTY_OBJECT, undefined])), "get".concat(pluralResourceName, "Error"), applyNamespace(selectors_getItemsError, namespace)), "get".concat(resourceName, "CreateError"), applyNamespace(getItemCreateError, namespace)), "get".concat(resourceName, "DeleteError"), applyNamespace(getItemDeleteError, namespace)), "get".concat(resourceName, "UpdateError"), applyNamespace(getItemUpdateError, namespace)), "hasFinishedRequest", hasFinishedRequest), "isRequesting", isRequesting);
};
;// CONCATENATED MODULE: ../../packages/js/data/src/crud/action-types.ts
var crud_action_types_TYPES = /*#__PURE__*/function (TYPES) {
  TYPES["CREATE_ITEM_ERROR"] = "CREATE_ITEM_ERROR";
  TYPES["CREATE_ITEM_REQUEST"] = "CREATE_ITEM_REQUEST";
  TYPES["CREATE_ITEM_SUCCESS"] = "CREATE_ITEM_SUCCESS";
  TYPES["DELETE_ITEM_ERROR"] = "DELETE_ITEM_ERROR";
  TYPES["DELETE_ITEM_REQUEST"] = "DELETE_ITEM_REQUEST";
  TYPES["DELETE_ITEM_SUCCESS"] = "DELETE_ITEM_SUCCESS";
  TYPES["GET_ITEM_ERROR"] = "GET_ITEM_ERROR";
  TYPES["GET_ITEM_SUCCESS"] = "GET_ITEM_SUCCESS";
  TYPES["GET_ITEMS_ERROR"] = "GET_ITEMS_ERROR";
  TYPES["GET_ITEMS_SUCCESS"] = "GET_ITEMS_SUCCESS";
  TYPES["UPDATE_ITEM_ERROR"] = "UPDATE_ITEM_ERROR";
  TYPES["UPDATE_ITEM_REQUEST"] = "UPDATE_ITEM_REQUEST";
  TYPES["UPDATE_ITEM_SUCCESS"] = "UPDATE_ITEM_SUCCESS";
  TYPES["GET_ITEMS_TOTAL_COUNT_SUCCESS"] = "GET_ITEMS_TOTAL_COUNT_SUCCESS";
  TYPES["GET_ITEMS_TOTAL_COUNT_ERROR"] = "GET_ITEMS_TOTAL_COUNT_ERROR";
  return TYPES;
}({});
/* harmony default export */ const crud_action_types = (crud_action_types_TYPES);
;// CONCATENATED MODULE: ../../packages/js/data/src/crud/actions.ts




/**
 * External dependencies
 */


/**
 * Internal dependencies
 */



function createItemError(query, error) {
  return {
    type: crud_action_types.CREATE_ITEM_ERROR,
    query: query,
    error: error,
    errorType: crud_actions.CREATE_ITEM
  };
}
function createItemRequest(query) {
  return {
    type: crud_action_types.CREATE_ITEM_REQUEST,
    query: query
  };
}
function createItemSuccess(key, item, query, options) {
  return {
    type: crud_action_types.CREATE_ITEM_SUCCESS,
    key: key,
    item: item,
    query: query,
    options: options
  };
}
function deleteItemError(key, error, force) {
  return {
    type: crud_action_types.DELETE_ITEM_ERROR,
    key: key,
    error: error,
    errorType: crud_actions.DELETE_ITEM,
    force: force
  };
}
function deleteItemRequest(key, force) {
  return {
    type: crud_action_types.DELETE_ITEM_REQUEST,
    key: key,
    force: force
  };
}
function deleteItemSuccess(key, force, item) {
  return {
    type: crud_action_types.DELETE_ITEM_SUCCESS,
    key: key,
    force: force,
    item: item
  };
}
function actions_getItemError(key, error) {
  return {
    type: crud_action_types.GET_ITEM_ERROR,
    key: key,
    error: error,
    errorType: crud_actions.GET_ITEM
  };
}
function getItemSuccess(key, item) {
  return {
    type: crud_action_types.GET_ITEM_SUCCESS,
    key: key,
    item: item
  };
}
function actions_getItemsError(query, error) {
  return {
    type: crud_action_types.GET_ITEMS_ERROR,
    query: query,
    error: error,
    errorType: crud_actions.GET_ITEMS
  };
}
function getItemsSuccess(query, items, urlParameters) {
  return {
    type: crud_action_types.GET_ITEMS_SUCCESS,
    items: items,
    query: query,
    urlParameters: urlParameters
  };
}
function getItemsTotalCountSuccess(query, totalCount) {
  return {
    type: crud_action_types.GET_ITEMS_TOTAL_COUNT_SUCCESS,
    query: query,
    totalCount: totalCount
  };
}
function getItemsTotalCountError(query, error) {
  return {
    type: crud_action_types.GET_ITEMS_TOTAL_COUNT_ERROR,
    query: query,
    error: error,
    errorType: crud_actions.GET_ITEMS_TOTAL_COUNT
  };
}
function updateItemError(key, error, query) {
  return {
    type: crud_action_types.UPDATE_ITEM_ERROR,
    key: key,
    error: error,
    errorType: crud_actions.UPDATE_ITEM,
    query: query
  };
}
function updateItemRequest(key, query) {
  return {
    type: crud_action_types.UPDATE_ITEM_REQUEST,
    key: key,
    query: query
  };
}
function updateItemSuccess(key, item, query) {
  return {
    type: crud_action_types.UPDATE_ITEM_SUCCESS,
    key: key,
    item: item,
    query: query
  };
}
var createDispatchActions = function createDispatchActions(_ref) {
  var namespace = _ref.namespace,
    resourceName = _ref.resourceName;
  var createItem = /*#__PURE__*/regenerator_default().mark(function createItem(query, options) {
    var urlParameters, item, _parseId, key;
    return regenerator_default().wrap(function createItem$(_context) {
      while (1) switch (_context.prev = _context.next) {
        case 0:
          _context.next = 2;
          return createItemRequest(query);
        case 2:
          urlParameters = getUrlParameters(namespace, query);
          _context.prev = 3;
          _context.next = 6;
          return (0,data_controls_build_module/* apiFetch */.nr)({
            path: getRestPath(namespace, cleanQuery(query, namespace), urlParameters),
            method: 'POST'
          });
        case 6:
          item = _context.sent;
          _parseId = parseId(item.id, urlParameters), key = _parseId.key;
          _context.next = 10;
          return createItemSuccess(key, item, query, options);
        case 10:
          return _context.abrupt("return", item);
        case 13:
          _context.prev = 13;
          _context.t0 = _context["catch"](3);
          _context.next = 17;
          return createItemError(query, _context.t0);
        case 17:
          throw _context.t0;
        case 18:
        case "end":
          return _context.stop();
      }
    }, createItem, null, [[3, 13]]);
  });
  var deleteItem = function deleteItem(idQuery) {
    var force = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : true;
    return /*#__PURE__*/regenerator_default().mark(function _callee() {
      var urlParameters, _parseId2, id, key, item;
      return regenerator_default().wrap(function _callee$(_context2) {
        while (1) switch (_context2.prev = _context2.next) {
          case 0:
            urlParameters = getUrlParameters(namespace, idQuery);
            _parseId2 = parseId(idQuery, urlParameters), id = _parseId2.id, key = _parseId2.key;
            _context2.next = 4;
            return deleteItemRequest(key, force);
          case 4:
            _context2.prev = 4;
            _context2.next = 7;
            return (0,data_controls_build_module/* apiFetch */.nr)({
              path: getRestPath("".concat(namespace, "/").concat(id), {
                force: force
              }, urlParameters),
              method: 'DELETE'
            });
          case 7:
            item = _context2.sent;
            _context2.next = 10;
            return deleteItemSuccess(key, force, item);
          case 10:
            return _context2.abrupt("return", item);
          case 13:
            _context2.prev = 13;
            _context2.t0 = _context2["catch"](4);
            _context2.next = 17;
            return deleteItemError(key, _context2.t0, force);
          case 17:
            throw _context2.t0;
          case 18:
          case "end":
            return _context2.stop();
        }
      }, _callee, null, [[4, 13]]);
    })();
  };
  var updateItem = /*#__PURE__*/regenerator_default().mark(function updateItem(idQuery, query) {
    var urlParameters, _parseId3, id, key, item;
    return regenerator_default().wrap(function updateItem$(_context3) {
      while (1) switch (_context3.prev = _context3.next) {
        case 0:
          urlParameters = getUrlParameters(namespace, idQuery);
          _parseId3 = parseId(idQuery, urlParameters), id = _parseId3.id, key = _parseId3.key;
          _context3.next = 4;
          return updateItemRequest(key, query);
        case 4:
          _context3.prev = 4;
          _context3.next = 7;
          return (0,data_controls_build_module/* apiFetch */.nr)({
            path: getRestPath("".concat(namespace, "/").concat(id), {}, urlParameters),
            method: 'PUT',
            data: query
          });
        case 7:
          item = _context3.sent;
          _context3.next = 10;
          return updateItemSuccess(key, item, query);
        case 10:
          return _context3.abrupt("return", item);
        case 13:
          _context3.prev = 13;
          _context3.t0 = _context3["catch"](4);
          _context3.next = 17;
          return updateItemError(key, _context3.t0, query);
        case 17:
          throw _context3.t0;
        case 18:
        case "end":
          return _context3.stop();
      }
    }, updateItem, null, [[4, 13]]);
  });
  return (0,defineProperty/* default */.A)((0,defineProperty/* default */.A)((0,defineProperty/* default */.A)({}, "create".concat(resourceName), createItem), "delete".concat(resourceName), deleteItem), "update".concat(resourceName), updateItem);
};
;// CONCATENATED MODULE: ../../packages/js/data/src/crud/resolvers.ts


function crud_resolvers_ownKeys(e, r) {
  var t = Object.keys(e);
  if (Object.getOwnPropertySymbols) {
    var o = Object.getOwnPropertySymbols(e);
    r && (o = o.filter(function (r) {
      return Object.getOwnPropertyDescriptor(e, r).enumerable;
    })), t.push.apply(t, o);
  }
  return t;
}
function crud_resolvers_objectSpread(e) {
  for (var r = 1; r < arguments.length; r++) {
    var t = null != arguments[r] ? arguments[r] : {};
    r % 2 ? crud_resolvers_ownKeys(Object(t), !0).forEach(function (r) {
      (0,defineProperty/* default */.A)(e, r, t[r]);
    }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(e, Object.getOwnPropertyDescriptors(t)) : crud_resolvers_ownKeys(Object(t)).forEach(function (r) {
      Object.defineProperty(e, r, Object.getOwnPropertyDescriptor(t, r));
    });
  }
  return e;
}
function resolvers_createForOfIteratorHelper(o, allowArrayLike) {
  var it = typeof Symbol !== "undefined" && o[Symbol.iterator] || o["@@iterator"];
  if (!it) {
    if (Array.isArray(o) || (it = resolvers_unsupportedIterableToArray(o)) || allowArrayLike && o && typeof o.length === "number") {
      if (it) o = it;
      var i = 0;
      var F = function F() {};
      return {
        s: F,
        n: function n() {
          if (i >= o.length) return {
            done: true
          };
          return {
            done: false,
            value: o[i++]
          };
        },
        e: function e(_e) {
          throw _e;
        },
        f: F
      };
    }
    throw new TypeError("Invalid attempt to iterate non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method.");
  }
  var normalCompletion = true,
    didErr = false,
    err;
  return {
    s: function s() {
      it = it.call(o);
    },
    n: function n() {
      var step = it.next();
      normalCompletion = step.done;
      return step;
    },
    e: function e(_e2) {
      didErr = true;
      err = _e2;
    },
    f: function f() {
      try {
        if (!normalCompletion && it["return"] != null) it["return"]();
      } finally {
        if (didErr) throw err;
      }
    }
  };
}
function resolvers_unsupportedIterableToArray(o, minLen) {
  if (!o) return;
  if (typeof o === "string") return resolvers_arrayLikeToArray(o, minLen);
  var n = Object.prototype.toString.call(o).slice(8, -1);
  if (n === "Object" && o.constructor) n = o.constructor.name;
  if (n === "Map" || n === "Set") return Array.from(o);
  if (n === "Arguments" || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)) return resolvers_arrayLikeToArray(o, minLen);
}
function resolvers_arrayLikeToArray(arr, len) {
  if (len == null || len > arr.length) len = arr.length;
  for (var i = 0, arr2 = new Array(len); i < len; i++) arr2[i] = arr[i];
  return arr2;
}



























/**
 * External dependencies
 */



/**
 * Internal dependencies
 */



var createResolvers = function createResolvers(_ref) {
  var storeName = _ref.storeName,
    resourceName = _ref.resourceName,
    pluralResourceName = _ref.pluralResourceName,
    namespace = _ref.namespace;
  var getItem = /*#__PURE__*/regenerator_default().mark(function getItem(idQuery) {
    var urlParameters, _parseId, id, key, item;
    return regenerator_default().wrap(function getItem$(_context) {
      while (1) switch (_context.prev = _context.next) {
        case 0:
          urlParameters = getUrlParameters(namespace, idQuery);
          _parseId = parseId(idQuery, urlParameters), id = _parseId.id, key = _parseId.key;
          _context.prev = 2;
          _context.next = 5;
          return (0,data_controls_build_module/* apiFetch */.nr)({
            path: getRestPath("".concat(namespace, "/").concat(id), {}, urlParameters),
            method: 'GET'
          });
        case 5:
          item = _context.sent;
          _context.next = 8;
          return getItemSuccess(key, item);
        case 8:
          return _context.abrupt("return", item);
        case 11:
          _context.prev = 11;
          _context.t0 = _context["catch"](2);
          _context.next = 15;
          return actions_getItemError(key, _context.t0);
        case 15:
          throw _context.t0;
        case 16:
        case "end":
          return _context.stop();
      }
    }, getItem, null, [[2, 11]]);
  });
  var getItems = /*#__PURE__*/regenerator_default().mark(function getItems(query) {
    var urlParameters, resourceQuery, path, _yield$request, items, totalCount, _iterator, _step, i;
    return regenerator_default().wrap(function getItems$(_context2) {
      while (1) switch (_context2.prev = _context2.next) {
        case 0:
          urlParameters = getUrlParameters(namespace, query || {});
          resourceQuery = cleanQuery(query || {}, namespace);
          _context2.next = 4;
          return build_module_controls/* controls */.n.dispatch(storeName, 'startResolution', "get".concat(pluralResourceName, "TotalCount"), [query]);
        case 4:
          // Require ID when requesting specific fields to later update the resource data.
          if (resourceQuery && resourceQuery._fields && !resourceQuery._fields.includes('id')) {
            resourceQuery._fields = ['id'].concat((0,toConsumableArray/* default */.A)(resourceQuery._fields));
          }
          _context2.prev = 5;
          path = getRestPath(namespace, query || {}, urlParameters);
          _context2.next = 9;
          return request(path, resourceQuery);
        case 9:
          _yield$request = _context2.sent;
          items = _yield$request.items;
          totalCount = _yield$request.totalCount;
          _context2.next = 14;
          return getItemsTotalCountSuccess(query, totalCount);
        case 14:
          _context2.next = 16;
          return build_module_controls/* controls */.n.dispatch(storeName, 'finishResolution', "get".concat(pluralResourceName, "TotalCount"), [query]);
        case 16:
          _context2.next = 18;
          return getItemsSuccess(query, items, urlParameters);
        case 18:
          _iterator = resolvers_createForOfIteratorHelper(items);
          _context2.prev = 19;
          _iterator.s();
        case 21:
          if ((_step = _iterator.n()).done) {
            _context2.next = 28;
            break;
          }
          i = _step.value;
          if (!i.id) {
            _context2.next = 26;
            break;
          }
          _context2.next = 26;
          return build_module_controls/* controls */.n.dispatch(storeName, 'finishResolution', "get".concat(resourceName), [i.id]);
        case 26:
          _context2.next = 21;
          break;
        case 28:
          _context2.next = 33;
          break;
        case 30:
          _context2.prev = 30;
          _context2.t0 = _context2["catch"](19);
          _iterator.e(_context2.t0);
        case 33:
          _context2.prev = 33;
          _iterator.f();
          return _context2.finish(33);
        case 36:
          return _context2.abrupt("return", items);
        case 39:
          _context2.prev = 39;
          _context2.t1 = _context2["catch"](5);
          _context2.next = 43;
          return getItemsTotalCountError(query, _context2.t1);
        case 43:
          _context2.next = 45;
          return actions_getItemsError(query, _context2.t1);
        case 45:
          throw _context2.t1;
        case 46:
        case "end":
          return _context2.stop();
      }
    }, getItems, null, [[5, 39], [19, 30, 33, 36]]);
  });
  var getItemsTotalCount = /*#__PURE__*/regenerator_default().mark(function getItemsTotalCount(query) {
    var startedTotalCountUsingGetItems, totalsQuery, urlParameters, resourceQuery, path, _yield$request2, totalCount;
    return regenerator_default().wrap(function getItemsTotalCount$(_context3) {
      while (1) switch (_context3.prev = _context3.next) {
        case 0:
          _context3.next = 2;
          return build_module_controls/* controls */.n.select(storeName, 'hasStartedResolution', "get".concat(pluralResourceName), [query]);
        case 2:
          startedTotalCountUsingGetItems = _context3.sent;
          if (!startedTotalCountUsingGetItems) {
            _context3.next = 5;
            break;
          }
          return _context3.abrupt("return");
        case 5:
          totalsQuery = crud_resolvers_objectSpread(crud_resolvers_objectSpread({}, query || {}), {}, {
            page: 1,
            per_page: 1
          });
          urlParameters = getUrlParameters(namespace, totalsQuery);
          resourceQuery = cleanQuery(totalsQuery, namespace); // Require ID when requesting specific fields to later update the resource data.
          if (resourceQuery && resourceQuery._fields && !resourceQuery._fields.includes('id')) {
            resourceQuery._fields = ['id'].concat((0,toConsumableArray/* default */.A)(resourceQuery._fields));
          }
          _context3.prev = 9;
          path = getRestPath(namespace, {}, urlParameters);
          _context3.next = 13;
          return request(path, totalsQuery);
        case 13:
          _yield$request2 = _context3.sent;
          totalCount = _yield$request2.totalCount;
          _context3.next = 17;
          return getItemsTotalCountSuccess(query, totalCount);
        case 17:
          return _context3.abrupt("return", totalCount);
        case 20:
          _context3.prev = 20;
          _context3.t0 = _context3["catch"](9);
          _context3.next = 24;
          return getItemsTotalCountError(query, _context3.t0);
        case 24:
          return _context3.abrupt("return", _context3.t0);
        case 25:
        case "end":
          return _context3.stop();
      }
    }, getItemsTotalCount, null, [[9, 20]]);
  });
  return (0,defineProperty/* default */.A)((0,defineProperty/* default */.A)((0,defineProperty/* default */.A)({}, "get".concat(resourceName), getItem), "get".concat(pluralResourceName), getItems), "get".concat(pluralResourceName, "TotalCount"), getItemsTotalCount);
};
;// CONCATENATED MODULE: ../../packages/js/data/src/crud/reducer.ts


















function crud_reducer_ownKeys(e, r) {
  var t = Object.keys(e);
  if (Object.getOwnPropertySymbols) {
    var o = Object.getOwnPropertySymbols(e);
    r && (o = o.filter(function (r) {
      return Object.getOwnPropertyDescriptor(e, r).enumerable;
    })), t.push.apply(t, o);
  }
  return t;
}
function crud_reducer_objectSpread(e) {
  for (var r = 1; r < arguments.length; r++) {
    var t = null != arguments[r] ? arguments[r] : {};
    r % 2 ? crud_reducer_ownKeys(Object(t), !0).forEach(function (r) {
      (0,defineProperty/* default */.A)(e, r, t[r]);
    }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(e, Object.getOwnPropertyDescriptors(t)) : crud_reducer_ownKeys(Object(t)).forEach(function (r) {
      Object.defineProperty(e, r, Object.getOwnPropertyDescriptor(t, r));
    });
  }
  return e;
}
/**
 * External dependencies
 */

/**
 * Internal dependencies
 */





var createReducer = function createReducer(additionalReducer) {
  var reducer = function reducer() {
    var state = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {
      items: {},
      data: {},
      itemsCount: {},
      errors: {},
      requesting: {}
    };
    var payload = arguments.length > 1 ? arguments[1] : undefined;
    var itemData = state.data || {};
    if (payload && 'type' in payload) {
      switch (payload.type) {
        case crud_action_types_TYPES.CREATE_ITEM_ERROR:
          var createItemErrorRequestId = getRequestIdentifier(payload.errorType, payload.query || {});
          return crud_reducer_objectSpread(crud_reducer_objectSpread({}, state), {}, {
            errors: crud_reducer_objectSpread(crud_reducer_objectSpread({}, state.errors), {}, (0,defineProperty/* default */.A)({}, createItemErrorRequestId, payload.error)),
            requesting: crud_reducer_objectSpread(crud_reducer_objectSpread({}, state.requesting), {}, (0,defineProperty/* default */.A)({}, createItemErrorRequestId, false))
          });
        case crud_action_types_TYPES.GET_ITEMS_TOTAL_COUNT_ERROR:
        case crud_action_types_TYPES.GET_ITEMS_ERROR:
          return crud_reducer_objectSpread(crud_reducer_objectSpread({}, state), {}, {
            errors: crud_reducer_objectSpread(crud_reducer_objectSpread({}, state.errors), {}, (0,defineProperty/* default */.A)({}, getRequestIdentifier(payload.errorType, payload.query || {}), payload.error))
          });
        case crud_action_types_TYPES.GET_ITEMS_TOTAL_COUNT_SUCCESS:
          return crud_reducer_objectSpread(crud_reducer_objectSpread({}, state), {}, {
            itemsCount: crud_reducer_objectSpread(crud_reducer_objectSpread({}, state.itemsCount), {}, (0,defineProperty/* default */.A)({}, getTotalCountResourceName(crud_actions.GET_ITEMS, payload.query || {}), payload.totalCount))
          });
        case crud_action_types_TYPES.CREATE_ITEM_SUCCESS:
          {
            var _currentItems$getItem;
            var _payload$options = payload.options,
              options = _payload$options === void 0 ? {} : _payload$options;
            var _organizeItemsById = organizeItemsById([payload.item], options.optimisticUrlParameters, itemData),
              _objItems = _organizeItemsById.objItems,
              _ids = _organizeItemsById.ids;
            var data = crud_reducer_objectSpread(crud_reducer_objectSpread({}, itemData), _objItems);
            var createItemSuccessRequestId = getRequestIdentifier(crud_actions.CREATE_ITEM, _ids[0], payload.query);
            var getItemQueryId = getRequestIdentifier(crud_actions.GET_ITEMS, options.optimisticQueryUpdate);
            var getItemCountQueryId = getTotalCountResourceName(crud_actions.GET_ITEMS, (options === null || options === void 0 ? void 0 : options.optimisticQueryUpdate) || {});
            var currentItems = state.items;
            var currentItemsByQueryId = ((_currentItems$getItem = currentItems[getItemQueryId]) === null || _currentItems$getItem === void 0 ? void 0 : _currentItems$getItem.data) || [];
            var nextItemsData = [].concat((0,toConsumableArray/* default */.A)(currentItemsByQueryId), (0,toConsumableArray/* default */.A)(_ids));
            var itemsCount = state.itemsCount;

            /*
             * Check it needs to update the store with the new item,
             * optimistically.
             */
            if (options !== null && options !== void 0 && options.optimisticQueryUpdate) {
              var _options$optimisticQu;
              /*
               * If the query has an order_by property, sort the items
               * by the order_by property.
               *
               * The sort criteria could be different from the
               * the server side.
               * Ensure to keep in sync with the server side, for instance,
               * by invalidating the cache.
               *
               * Todo: Add a mechanism to use the server side sorting criteria.
               */
              if ((_options$optimisticQu = options.optimisticQueryUpdate) !== null && _options$optimisticQu !== void 0 && _options$optimisticQu.order_by) {
                var _options$optimisticQu2;
                var order_by = (_options$optimisticQu2 = options.optimisticQueryUpdate) === null || _options$optimisticQu2 === void 0 ? void 0 : _options$optimisticQu2.order_by;

                /*
                 * Pick the data to sort by the order_by property,
                 * from the data store,
                 * based on the nextItemsData ids.
                 */
                var sourceDataToOrderBy = Object.values(filterDataByKeys(data, nextItemsData));
                sourceDataToOrderBy = sourceDataToOrderBy.sort(function (a, b) {
                  return String(a[order_by]).toLowerCase().localeCompare(String(b[order_by]).toLowerCase());
                });

                // Pick the ids from the sorted data.
                var _organizeItemsById2 = organizeItemsById(sourceDataToOrderBy, options.optimisticUrlParameters),
                  sortedIds = _organizeItemsById2.ids;

                // Update the items data with the sorted ids.
                nextItemsData = sortedIds;
              }
              currentItems = crud_reducer_objectSpread(crud_reducer_objectSpread({}, currentItems), {}, (0,defineProperty/* default */.A)({}, getItemQueryId, {
                data: nextItemsData
              }));
              itemsCount = crud_reducer_objectSpread(crud_reducer_objectSpread({}, state.itemsCount), {}, (0,defineProperty/* default */.A)({}, getItemCountQueryId, nextItemsData.length));
            }
            return crud_reducer_objectSpread(crud_reducer_objectSpread({}, state), {}, {
              items: currentItems,
              itemsCount: itemsCount,
              data: data,
              requesting: crud_reducer_objectSpread(crud_reducer_objectSpread({}, state.requesting), {}, (0,defineProperty/* default */.A)({}, createItemSuccessRequestId, false))
            });
          }
        case crud_action_types_TYPES.GET_ITEM_SUCCESS:
          return crud_reducer_objectSpread(crud_reducer_objectSpread({}, state), {}, {
            data: crud_reducer_objectSpread(crud_reducer_objectSpread({}, itemData), {}, (0,defineProperty/* default */.A)({}, payload.key, crud_reducer_objectSpread(crud_reducer_objectSpread({}, itemData[payload.key] || {}), payload.item)))
          });
        case crud_action_types_TYPES.UPDATE_ITEM_SUCCESS:
          var updateItemSuccessRequestId = getRequestIdentifier(crud_actions.UPDATE_ITEM, payload.key, payload.query);
          return crud_reducer_objectSpread(crud_reducer_objectSpread({}, state), {}, {
            data: crud_reducer_objectSpread(crud_reducer_objectSpread({}, itemData), {}, (0,defineProperty/* default */.A)({}, payload.key, crud_reducer_objectSpread(crud_reducer_objectSpread({}, itemData[payload.key] || {}), payload.item))),
            requesting: crud_reducer_objectSpread(crud_reducer_objectSpread({}, state.requesting), {}, (0,defineProperty/* default */.A)({}, updateItemSuccessRequestId, false))
          });
        case crud_action_types_TYPES.DELETE_ITEM_SUCCESS:
          var deleteItemSuccessRequestId = getRequestIdentifier(crud_actions.DELETE_ITEM, payload.key, payload.force);
          var itemKeys = Object.keys(state.data);
          var nextData = itemKeys.reduce(function (items, key) {
            if (key !== payload.key.toString()) {
              items[key] = state.data[key];
              return items;
            }
            if (payload.force) {
              return items;
            }
            items[key] = payload.item;
            return items;
          }, {});
          return crud_reducer_objectSpread(crud_reducer_objectSpread({}, state), {}, {
            data: nextData,
            requesting: crud_reducer_objectSpread(crud_reducer_objectSpread({}, state.requesting), {}, (0,defineProperty/* default */.A)({}, deleteItemSuccessRequestId, false))
          });
        case crud_action_types_TYPES.DELETE_ITEM_ERROR:
          var deleteItemErrorRequestId = getRequestIdentifier(payload.errorType, payload.key, payload.force);
          return crud_reducer_objectSpread(crud_reducer_objectSpread({}, state), {}, {
            errors: crud_reducer_objectSpread(crud_reducer_objectSpread({}, state.errors), {}, (0,defineProperty/* default */.A)({}, deleteItemErrorRequestId, payload.error)),
            requesting: crud_reducer_objectSpread(crud_reducer_objectSpread({}, state.requesting), {}, (0,defineProperty/* default */.A)({}, deleteItemErrorRequestId, false))
          });
        case crud_action_types_TYPES.GET_ITEM_ERROR:
          return crud_reducer_objectSpread(crud_reducer_objectSpread({}, state), {}, {
            errors: crud_reducer_objectSpread(crud_reducer_objectSpread({}, state.errors), {}, (0,defineProperty/* default */.A)({}, getRequestIdentifier(payload.errorType, payload.key), payload.error))
          });
        case crud_action_types_TYPES.UPDATE_ITEM_ERROR:
          var updateItemErrorRequestId = getRequestIdentifier(payload.errorType, payload.key, payload.query);
          return crud_reducer_objectSpread(crud_reducer_objectSpread({}, state), {}, {
            errors: crud_reducer_objectSpread(crud_reducer_objectSpread({}, state.errors), {}, (0,defineProperty/* default */.A)({}, updateItemErrorRequestId, payload.error)),
            requesting: crud_reducer_objectSpread(crud_reducer_objectSpread({}, state.requesting), {}, (0,defineProperty/* default */.A)({}, updateItemErrorRequestId, false))
          });
        case crud_action_types_TYPES.GET_ITEMS_SUCCESS:
          var _organizeItemsById3 = organizeItemsById(payload.items, payload.urlParameters, itemData),
            objItems = _organizeItemsById3.objItems,
            ids = _organizeItemsById3.ids;
          var itemQuery = getRequestIdentifier(crud_actions.GET_ITEMS, payload.query || {});
          return crud_reducer_objectSpread(crud_reducer_objectSpread({}, state), {}, {
            items: crud_reducer_objectSpread(crud_reducer_objectSpread({}, state.items), {}, (0,defineProperty/* default */.A)({}, itemQuery, {
              data: ids
            })),
            data: crud_reducer_objectSpread(crud_reducer_objectSpread({}, state.data), objItems)
          });
        case crud_action_types_TYPES.CREATE_ITEM_REQUEST:
          return crud_reducer_objectSpread(crud_reducer_objectSpread({}, state), {}, {
            requesting: crud_reducer_objectSpread(crud_reducer_objectSpread({}, state.requesting), {}, (0,defineProperty/* default */.A)({}, getRequestIdentifier(crud_actions.CREATE_ITEM, payload.query), true))
          });
        case crud_action_types_TYPES.DELETE_ITEM_REQUEST:
          return crud_reducer_objectSpread(crud_reducer_objectSpread({}, state), {}, {
            requesting: crud_reducer_objectSpread(crud_reducer_objectSpread({}, state.requesting), {}, (0,defineProperty/* default */.A)({}, getRequestIdentifier(crud_actions.DELETE_ITEM, payload.key, payload.force), true))
          });
        case crud_action_types_TYPES.UPDATE_ITEM_REQUEST:
          return crud_reducer_objectSpread(crud_reducer_objectSpread({}, state), {}, {
            requesting: crud_reducer_objectSpread(crud_reducer_objectSpread({}, state.requesting), {}, (0,defineProperty/* default */.A)({}, getRequestIdentifier(crud_actions.UPDATE_ITEM, payload.key, payload.query), true))
          });
      }
    }
    if (additionalReducer) {
      return additionalReducer(state, payload);
    }
    return state;
  };
  return reducer;
};
;// CONCATENATED MODULE: ../../packages/js/data/src/crud/index.ts











function crud_ownKeys(e, r) {
  var t = Object.keys(e);
  if (Object.getOwnPropertySymbols) {
    var o = Object.getOwnPropertySymbols(e);
    r && (o = o.filter(function (r) {
      return Object.getOwnPropertyDescriptor(e, r).enumerable;
    })), t.push.apply(t, o);
  }
  return t;
}
function crud_objectSpread(e) {
  for (var r = 1; r < arguments.length; r++) {
    var t = null != arguments[r] ? arguments[r] : {};
    r % 2 ? crud_ownKeys(Object(t), !0).forEach(function (r) {
      (0,defineProperty/* default */.A)(e, r, t[r]);
    }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(e, Object.getOwnPropertyDescriptors(t)) : crud_ownKeys(Object(t)).forEach(function (r) {
      Object.defineProperty(e, r, Object.getOwnPropertyDescriptor(t, r));
    });
  }
  return e;
}
/**
 * External dependencies
 */

/**
 * Internal dependencies
 */





var createCrudDataStore = function createCrudDataStore(_ref) {
  var storeName = _ref.storeName,
    resourceName = _ref.resourceName,
    namespace = _ref.namespace,
    pluralResourceName = _ref.pluralResourceName,
    _ref$storeConfig = _ref.storeConfig,
    storeConfig = _ref$storeConfig === void 0 ? {} : _ref$storeConfig;
  var crudActions = createDispatchActions({
    resourceName: resourceName,
    namespace: namespace
  });
  var crudResolvers = createResolvers({
    storeName: storeName,
    resourceName: resourceName,
    pluralResourceName: pluralResourceName,
    namespace: namespace
  });
  var crudSelectors = createSelectors({
    resourceName: resourceName,
    pluralResourceName: pluralResourceName,
    namespace: namespace
  });
  var reducer = storeConfig.reducer,
    _storeConfig$actions = storeConfig.actions,
    actions = _storeConfig$actions === void 0 ? {} : _storeConfig$actions,
    _storeConfig$selector = storeConfig.selectors,
    selectors = _storeConfig$selector === void 0 ? {} : _storeConfig$selector,
    _storeConfig$resolver = storeConfig.resolvers,
    resolvers = _storeConfig$resolver === void 0 ? {} : _storeConfig$resolver,
    _storeConfig$controls = storeConfig.controls,
    controls = _storeConfig$controls === void 0 ? {} : _storeConfig$controls;
  var crudReducer = createReducer(reducer);
  (0,data_build_module/* registerStore */.ti)(storeName, {
    reducer: crudReducer,
    actions: crud_objectSpread(crud_objectSpread({}, crudActions), actions),
    selectors: crud_objectSpread(crud_objectSpread({}, crudSelectors), selectors),
    resolvers: crud_objectSpread(crud_objectSpread({}, crudResolvers), resolvers),
    controls: crud_objectSpread(crud_objectSpread({}, src_controls), controls)
  });
};
;// CONCATENATED MODULE: ../../packages/js/data/src/product-attributes/index.ts
/**
 * Internal dependencies
 */


createCrudDataStore({
  storeName: product_attributes_constants_STORE_NAME,
  resourceName: 'ProductAttribute',
  pluralResourceName: 'ProductAttributes',
  namespace: WC_PRODUCT_ATTRIBUTES_NAMESPACE
});
var EXPERIMENTAL_PRODUCT_ATTRIBUTES_STORE_NAME = (/* unused pure expression or super */ null && (STORE_NAME));
;// CONCATENATED MODULE: ../../packages/js/data/src/product-shipping-classes/constants.ts
var product_shipping_classes_constants_STORE_NAME = 'experimental/wc/admin/products/shipping-classes';
var WC_PRODUCT_SHIPPING_CLASSES_NAMESPACE = '/wc/v3/products/shipping_classes';
;// CONCATENATED MODULE: ../../packages/js/data/src/product-shipping-classes/index.ts
/**
 * Internal dependencies
 */


createCrudDataStore({
  storeName: product_shipping_classes_constants_STORE_NAME,
  resourceName: 'ProductShippingClass',
  pluralResourceName: 'ProductShippingClasses',
  namespace: WC_PRODUCT_SHIPPING_CLASSES_NAMESPACE
});
var EXPERIMENTAL_PRODUCT_SHIPPING_CLASSES_STORE_NAME = (/* unused pure expression or super */ null && (STORE_NAME));
;// CONCATENATED MODULE: ../../packages/js/data/src/shipping-zones/constants.ts
var shipping_zones_constants_STORE_NAME = 'experimental/wc/admin/shipping/zones';
var WC_SHIPPING_ZONES_NAMESPACE = '/wc/v3/shipping/zones';
;// CONCATENATED MODULE: ../../packages/js/data/src/shipping-zones/index.ts
/**
 * Internal dependencies
 */


createCrudDataStore({
  storeName: shipping_zones_constants_STORE_NAME,
  resourceName: 'ShippingZone',
  pluralResourceName: 'ShippingZones',
  namespace: WC_SHIPPING_ZONES_NAMESPACE
});
var EXPERIMENTAL_SHIPPING_ZONES_STORE_NAME = (/* unused pure expression or super */ null && (STORE_NAME));
;// CONCATENATED MODULE: ../../packages/js/data/src/product-tags/constants.ts
var product_tags_constants_STORE_NAME = 'wc/admin/products/tags';
var WC_PRODUCT_TAGS_NAMESPACE = '/wc/v3/products/tags';
;// CONCATENATED MODULE: ../../packages/js/data/src/product-tags/index.ts
/**
 * Internal dependencies
 */


createCrudDataStore({
  storeName: product_tags_constants_STORE_NAME,
  resourceName: 'ProductTag',
  pluralResourceName: 'ProductTags',
  namespace: WC_PRODUCT_TAGS_NAMESPACE
});
var EXPERIMENTAL_PRODUCT_TAGS_STORE_NAME = (/* unused pure expression or super */ null && (STORE_NAME));
;// CONCATENATED MODULE: ../../packages/js/data/src/product-categories/constants.ts
var product_categories_constants_STORE_NAME = 'experimental/wc/admin/products/categories';
var WC_PRODUCT_CATEGORIES_NAMESPACE = '/wc/v3/products/categories';
;// CONCATENATED MODULE: ../../packages/js/data/src/product-categories/index.ts
/**
 * Internal dependencies
 */


createCrudDataStore({
  storeName: product_categories_constants_STORE_NAME,
  resourceName: 'ProductCategory',
  pluralResourceName: 'ProductCategories',
  namespace: WC_PRODUCT_CATEGORIES_NAMESPACE
});
var EXPERIMENTAL_PRODUCT_CATEGORIES_STORE_NAME = (/* unused pure expression or super */ null && (STORE_NAME));
;// CONCATENATED MODULE: ../../packages/js/data/src/product-attribute-terms/constants.ts
var product_attribute_terms_constants_STORE_NAME = 'wc/admin/products/attributes/terms';
var WC_PRODUCT_ATTRIBUTE_TERMS_NAMESPACE = '/wc/v3/products/attributes/{attribute_id}/terms';
;// CONCATENATED MODULE: ../../packages/js/data/src/product-attribute-terms/index.ts
/**
 * External dependencies
 */

/**
 * Internal dependencies
 */


createCrudDataStore({
  storeName: product_attribute_terms_constants_STORE_NAME,
  resourceName: 'ProductAttributeTerm',
  pluralResourceName: 'ProductAttributeTerms',
  namespace: WC_PRODUCT_ATTRIBUTE_TERMS_NAMESPACE
});
var EXPERIMENTAL_PRODUCT_ATTRIBUTE_TERMS_STORE_NAME = (/* unused pure expression or super */ null && (STORE_NAME));
;// CONCATENATED MODULE: ../../packages/js/data/src/product-variations/constants.ts
var product_variations_constants_STORE_NAME = 'wc/admin/products/variations';
var WC_PRODUCT_VARIATIONS_NAMESPACE = '/wc/v3/products/{product_id}/variations';
;// CONCATENATED MODULE: ../../packages/js/data/src/product-variations/action-types.ts
var product_variations_action_types_TYPES = /*#__PURE__*/function (TYPES) {
  TYPES["GENERATE_VARIATIONS_REQUEST"] = "GENERATE_VARIATIONS_REQUEST";
  TYPES["GENERATE_VARIATIONS_SUCCESS"] = "GENERATE_VARIATIONS_SUCCESS";
  TYPES["GENERATE_VARIATIONS_ERROR"] = "GENERATE_VARIATIONS_ERROR";
  TYPES["BATCH_UPDATE_VARIATIONS_ERROR"] = "BATCH_UPDATE_VARIATIONS_ERROR";
  return TYPES;
}({});
/* harmony default export */ const product_variations_action_types = (product_variations_action_types_TYPES);
;// CONCATENATED MODULE: ../../packages/js/data/src/product-variations/crud-actions.ts
var crud_actions_CRUD_ACTIONS = /*#__PURE__*/function (CRUD_ACTIONS) {
  CRUD_ACTIONS["GENERATE_VARIATIONS"] = "GENERATE_VARIATIONS";
  return CRUD_ACTIONS;
}({});
/* harmony default export */ const product_variations_crud_actions = (crud_actions_CRUD_ACTIONS);
;// CONCATENATED MODULE: ../../packages/js/data/src/product-variations/actions.ts











var product_variations_actions_marked = /*#__PURE__*/regenerator_default().mark(batchUpdateProductVariations);


function product_variations_actions_ownKeys(e, r) {
  var t = Object.keys(e);
  if (Object.getOwnPropertySymbols) {
    var o = Object.getOwnPropertySymbols(e);
    r && (o = o.filter(function (r) {
      return Object.getOwnPropertyDescriptor(e, r).enumerable;
    })), t.push.apply(t, o);
  }
  return t;
}
function product_variations_actions_objectSpread(e) {
  for (var r = 1; r < arguments.length; r++) {
    var t = null != arguments[r] ? arguments[r] : {};
    r % 2 ? product_variations_actions_ownKeys(Object(t), !0).forEach(function (r) {
      (0,defineProperty/* default */.A)(e, r, t[r]);
    }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(e, Object.getOwnPropertyDescriptors(t)) : product_variations_actions_ownKeys(Object(t)).forEach(function (r) {
      Object.defineProperty(e, r, Object.getOwnPropertyDescriptor(t, r));
    });
  }
  return e;
}
/**
 * External dependencies
 */



/**
 * Internal dependencies
 */




function generateProductVariationsError(key, error) {
  return {
    type: product_variations_action_types.GENERATE_VARIATIONS_ERROR,
    key: key,
    error: error,
    errorType: product_variations_crud_actions.GENERATE_VARIATIONS
  };
}
function generateProductVariationsRequest(key) {
  return {
    type: product_variations_action_types.GENERATE_VARIATIONS_REQUEST,
    key: key
  };
}
function generateProductVariationsSuccess(key) {
  return {
    type: product_variations_action_types.GENERATE_VARIATIONS_SUCCESS,
    key: key
  };
}
var generateProductVariations = function generateProductVariations(idQuery, productData, data) {
  var saveAttributes = arguments.length > 3 && arguments[3] !== undefined ? arguments[3] : true;
  return /*#__PURE__*/regenerator_default().mark(function _callee() {
    var urlParameters, _parseId, key, result;
    return regenerator_default().wrap(function _callee$(_context) {
      while (1) switch (_context.prev = _context.next) {
        case 0:
          urlParameters = getUrlParameters(WC_PRODUCT_VARIATIONS_NAMESPACE, idQuery);
          _parseId = parseId(idQuery, urlParameters), key = _parseId.key;
          _context.next = 4;
          return generateProductVariationsRequest(key);
        case 4:
          if (!saveAttributes) {
            _context.next = 15;
            break;
          }
          _context.prev = 5;
          _context.next = 8;
          return build_module_controls/* controls */.n.dispatch('core', 'saveEntityRecord', 'postType', 'product', product_variations_actions_objectSpread({
            id: urlParameters[0]
          }, productData));
        case 8:
          _context.next = 15;
          break;
        case 10:
          _context.prev = 10;
          _context.t0 = _context["catch"](5);
          _context.next = 14;
          return generateProductVariationsError(key, _context.t0);
        case 14:
          throw _context.t0;
        case 15:
          _context.prev = 15;
          _context.next = 18;
          return (0,data_controls_build_module/* apiFetch */.nr)({
            path: getRestPath("".concat(WC_PRODUCT_VARIATIONS_NAMESPACE, "/generate"), {}, urlParameters),
            method: 'POST',
            data: data
          });
        case 18:
          result = _context.sent;
          _context.next = 21;
          return generateProductVariationsSuccess(key);
        case 21:
          return _context.abrupt("return", result);
        case 24:
          _context.prev = 24;
          _context.t1 = _context["catch"](15);
          _context.next = 28;
          return generateProductVariationsError(key, _context.t1);
        case 28:
          throw _context.t1;
        case 29:
        case "end":
          return _context.stop();
      }
    }, _callee, null, [[5, 10], [15, 24]]);
  })();
};
function batchUpdateProductVariationsError(key, error) {
  return {
    type: product_variations_action_types.BATCH_UPDATE_VARIATIONS_ERROR,
    key: key,
    error: error,
    errorType: 'BATCH_UPDATE_VARIATIONS'
  };
}
function batchUpdateProductVariations(idQuery, data) {
  var urlParameters, result, _parseId2, key;
  return regenerator_default().wrap(function batchUpdateProductVariations$(_context2) {
    while (1) switch (_context2.prev = _context2.next) {
      case 0:
        urlParameters = getUrlParameters(WC_PRODUCT_VARIATIONS_NAMESPACE, idQuery);
        _context2.prev = 1;
        _context2.next = 4;
        return (0,data_controls_build_module/* apiFetch */.nr)({
          path: getRestPath("".concat(WC_PRODUCT_VARIATIONS_NAMESPACE, "/batch"), {}, urlParameters),
          method: 'POST',
          data: data
        });
      case 4:
        result = _context2.sent;
        return _context2.abrupt("return", result);
      case 8:
        _context2.prev = 8;
        _context2.t0 = _context2["catch"](1);
        _parseId2 = parseId(idQuery, urlParameters), key = _parseId2.key;
        _context2.next = 13;
        return batchUpdateProductVariationsError(key, _context2.t0);
      case 13:
        throw _context2.t0;
      case 14:
      case "end":
        return _context2.stop();
    }
  }, product_variations_actions_marked, null, [[1, 8]]);
}
;// CONCATENATED MODULE: ../../packages/js/data/src/product-variations/selectors.ts
/**
 * Internal dependencies
 */




var isGeneratingVariations = function isGeneratingVariations(state, idQuery) {
  var urlParameters = getUrlParameters(WC_PRODUCT_VARIATIONS_NAMESPACE, idQuery);
  var _parseId = parseId(idQuery, urlParameters),
    key = _parseId.key;
  var itemQuery = getRequestIdentifier(product_variations_crud_actions.GENERATE_VARIATIONS, key);
  return state.requesting[itemQuery];
};
var selectors_generateProductVariationsError = function generateProductVariationsError(state, idQuery) {
  var urlParameters = getUrlParameters(WC_PRODUCT_VARIATIONS_NAMESPACE, idQuery);
  var _parseId2 = parseId(idQuery, urlParameters),
    key = _parseId2.key;
  var itemQuery = getRequestIdentifier(product_variations_crud_actions.GENERATE_VARIATIONS, key);
  return state.errors[itemQuery];
};
;// CONCATENATED MODULE: ../../packages/js/data/src/product-variations/reducer.ts











function product_variations_reducer_ownKeys(e, r) {
  var t = Object.keys(e);
  if (Object.getOwnPropertySymbols) {
    var o = Object.getOwnPropertySymbols(e);
    r && (o = o.filter(function (r) {
      return Object.getOwnPropertyDescriptor(e, r).enumerable;
    })), t.push.apply(t, o);
  }
  return t;
}
function product_variations_reducer_objectSpread(e) {
  for (var r = 1; r < arguments.length; r++) {
    var t = null != arguments[r] ? arguments[r] : {};
    r % 2 ? product_variations_reducer_ownKeys(Object(t), !0).forEach(function (r) {
      (0,defineProperty/* default */.A)(e, r, t[r]);
    }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(e, Object.getOwnPropertyDescriptors(t)) : product_variations_reducer_ownKeys(Object(t)).forEach(function (r) {
      Object.defineProperty(e, r, Object.getOwnPropertyDescriptor(t, r));
    });
  }
  return e;
}
/**
 * External dependencies
 */

/**
 * Internal dependencies
 */




var product_variations_reducer_reducer = function reducer() {
  var state = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {
    items: {},
    data: {},
    itemsCount: {},
    errors: {},
    requesting: {}
  };
  var payload = arguments.length > 1 ? arguments[1] : undefined;
  if (payload && 'type' in payload) {
    switch (payload.type) {
      case product_variations_action_types_TYPES.GENERATE_VARIATIONS_REQUEST:
        return product_variations_reducer_objectSpread(product_variations_reducer_objectSpread({}, state), {}, {
          requesting: product_variations_reducer_objectSpread(product_variations_reducer_objectSpread({}, state.requesting), {}, (0,defineProperty/* default */.A)({}, getRequestIdentifier(product_variations_crud_actions.GENERATE_VARIATIONS, payload.key), true))
        });
      case product_variations_action_types_TYPES.GENERATE_VARIATIONS_SUCCESS:
        return product_variations_reducer_objectSpread(product_variations_reducer_objectSpread({}, state), {}, {
          requesting: product_variations_reducer_objectSpread(product_variations_reducer_objectSpread({}, state.requesting), {}, (0,defineProperty/* default */.A)({}, getRequestIdentifier(product_variations_crud_actions.GENERATE_VARIATIONS, payload.key), false)),
          errors: product_variations_reducer_objectSpread(product_variations_reducer_objectSpread({}, state.errors), {}, (0,defineProperty/* default */.A)({}, getRequestIdentifier(product_variations_crud_actions.GENERATE_VARIATIONS, payload.key), undefined))
        });
      case product_variations_action_types_TYPES.GENERATE_VARIATIONS_ERROR:
        return product_variations_reducer_objectSpread(product_variations_reducer_objectSpread({}, state), {}, {
          errors: product_variations_reducer_objectSpread(product_variations_reducer_objectSpread({}, state.errors), {}, (0,defineProperty/* default */.A)({}, getRequestIdentifier(payload.errorType, payload.key), payload.error)),
          requesting: product_variations_reducer_objectSpread(product_variations_reducer_objectSpread({}, state.requesting), {}, (0,defineProperty/* default */.A)({}, getRequestIdentifier(product_variations_crud_actions.GENERATE_VARIATIONS, payload.key), false))
        });
      default:
        return state;
    }
  }
  return state;
};

;// CONCATENATED MODULE: ../../packages/js/data/src/product-variations/index.ts
/**
 * External dependencies
 */

/**
 * Internal dependencies
 */





createCrudDataStore({
  storeName: product_variations_constants_STORE_NAME,
  resourceName: 'ProductVariation',
  pluralResourceName: 'ProductVariations',
  namespace: WC_PRODUCT_VARIATIONS_NAMESPACE,
  storeConfig: {
    reducer: product_variations_reducer_reducer,
    actions: product_variations_actions_namespaceObject,
    selectors: product_variations_selectors_namespaceObject
  }
});
var EXPERIMENTAL_PRODUCT_VARIATIONS_STORE_NAME = (/* unused pure expression or super */ null && (STORE_NAME));
;// CONCATENATED MODULE: ../../packages/js/data/src/product-form/constants.ts
var product_form_constants_STORE_NAME = 'experimental/wc/admin/product-form';
;// CONCATENATED MODULE: ../../packages/js/data/src/product-form/selectors.ts

var selectors_excluded = ["errors"];


/**
 * Internal dependencies
 */

var getFields = function getFields(state) {
  return state.fields;
};
var getField = function getField(state, id) {
  return state.fields.find(function (field) {
    return field.id === id;
  });
};
var getProductForm = function getProductForm(state) {
  var errors = state.errors,
    form = (0,objectWithoutProperties/* default */.A)(state, selectors_excluded);
  return form;
};
;// CONCATENATED MODULE: ../../packages/js/data/src/product-form/action-types.ts
var product_form_action_types_TYPES = /*#__PURE__*/function (TYPES) {
  TYPES["GET_FIELDS_ERROR"] = "GET_FIELDS_ERROR";
  TYPES["GET_FIELDS_SUCCESS"] = "GET_FIELDS_SUCCESS";
  TYPES["GET_PRODUCT_FORM_ERROR"] = "GET_PRODUCT_FORM_ERROR";
  TYPES["GET_PRODUCT_FORM_SUCCESS"] = "GET_PRODUCT_FORM_SUCCESS";
  return TYPES;
}({});
/* harmony default export */ const product_form_action_types = (product_form_action_types_TYPES);
;// CONCATENATED MODULE: ../../packages/js/data/src/product-form/actions.ts
/**
 * Internal dependencies
 */

function getFieldsSuccess(fields) {
  return {
    type: product_form_action_types.GET_FIELDS_SUCCESS,
    fields: fields
  };
}
function getFieldsError(error) {
  return {
    type: product_form_action_types.GET_FIELDS_ERROR,
    error: error
  };
}
function getProductFormSuccess(productForm) {
  return {
    type: product_form_action_types.GET_PRODUCT_FORM_SUCCESS,
    fields: productForm.fields,
    sections: productForm.sections,
    subsections: productForm.subsections,
    tabs: productForm.tabs
  };
}
function getProductFormError(error) {
  return {
    type: product_form_action_types.GET_PRODUCT_FORM_ERROR,
    error: error
  };
}
;// CONCATENATED MODULE: ../../packages/js/data/src/product-form/resolvers.ts


var product_form_resolvers_marked = /*#__PURE__*/regenerator_default().mark(resolvers_getFields),
  product_form_resolvers_marked2 = /*#__PURE__*/regenerator_default().mark(resolvers_getProductForm);
/**
 * External dependencies
 */


/**
 * Internal dependencies
 */


function resolvers_getFields() {
  var url, results;
  return regenerator_default().wrap(function getFields$(_context) {
    while (1) switch (_context.prev = _context.next) {
      case 0:
        _context.prev = 0;
        url = WC_ADMIN_NAMESPACE + '/product-form/fields';
        _context.next = 4;
        return (0,data_controls_build_module/* apiFetch */.nr)({
          path: url,
          method: 'GET'
        });
      case 4:
        results = _context.sent;
        return _context.abrupt("return", getFieldsSuccess(results));
      case 8:
        _context.prev = 8;
        _context.t0 = _context["catch"](0);
        return _context.abrupt("return", getFieldsError(_context.t0));
      case 11:
      case "end":
        return _context.stop();
    }
  }, product_form_resolvers_marked, null, [[0, 8]]);
}
function resolvers_getProductForm() {
  var url, results;
  return regenerator_default().wrap(function getProductForm$(_context2) {
    while (1) switch (_context2.prev = _context2.next) {
      case 0:
        _context2.prev = 0;
        url = WC_ADMIN_NAMESPACE + '/product-form';
        _context2.next = 4;
        return (0,data_controls_build_module/* apiFetch */.nr)({
          path: url,
          method: 'GET'
        });
      case 4:
        results = _context2.sent;
        return _context2.abrupt("return", getProductFormSuccess(results));
      case 8:
        _context2.prev = 8;
        _context2.t0 = _context2["catch"](0);
        return _context2.abrupt("return", getProductFormError(_context2.t0));
      case 11:
      case "end":
        return _context2.stop();
    }
  }, product_form_resolvers_marked2, null, [[0, 8]]);
}
;// CONCATENATED MODULE: ../../packages/js/data/src/product-form/reducer.ts











function product_form_reducer_ownKeys(e, r) {
  var t = Object.keys(e);
  if (Object.getOwnPropertySymbols) {
    var o = Object.getOwnPropertySymbols(e);
    r && (o = o.filter(function (r) {
      return Object.getOwnPropertyDescriptor(e, r).enumerable;
    })), t.push.apply(t, o);
  }
  return t;
}
function product_form_reducer_objectSpread(e) {
  for (var r = 1; r < arguments.length; r++) {
    var t = null != arguments[r] ? arguments[r] : {};
    r % 2 ? product_form_reducer_ownKeys(Object(t), !0).forEach(function (r) {
      (0,defineProperty/* default */.A)(e, r, t[r]);
    }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(e, Object.getOwnPropertyDescriptors(t)) : product_form_reducer_ownKeys(Object(t)).forEach(function (r) {
      Object.defineProperty(e, r, Object.getOwnPropertyDescriptor(t, r));
    });
  }
  return e;
}
/**
 * External dependencies
 */

/**
 * Internal dependencies
 */

var product_form_reducer_reducer = function reducer() {
  var state = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {
    errors: {},
    fields: [],
    sections: [],
    subsections: [],
    tabs: []
  };
  var action = arguments.length > 1 ? arguments[1] : undefined;
  switch (action.type) {
    case product_form_action_types.GET_FIELDS_SUCCESS:
      state = product_form_reducer_objectSpread(product_form_reducer_objectSpread({}, state), {}, {
        fields: action.fields
      });
      break;
    case product_form_action_types.GET_FIELDS_ERROR:
      state = product_form_reducer_objectSpread(product_form_reducer_objectSpread({}, state), {}, {
        errors: product_form_reducer_objectSpread(product_form_reducer_objectSpread({}, state.errors), {}, {
          fields: action.error
        })
      });
      break;
    case product_form_action_types.GET_PRODUCT_FORM_SUCCESS:
      state = product_form_reducer_objectSpread(product_form_reducer_objectSpread({}, state), {}, {
        fields: action.fields,
        sections: action.sections,
        subsections: action.subsections,
        tabs: action.tabs
      });
      break;
    case product_form_action_types.GET_PRODUCT_FORM_ERROR:
      state = product_form_reducer_objectSpread(product_form_reducer_objectSpread({}, state), {}, {
        errors: product_form_reducer_objectSpread(product_form_reducer_objectSpread({}, state.errors), {}, {
          fields: action.error,
          sections: action.error,
          subsections: action.error
        })
      });
      break;
  }
  return state;
};
/* harmony default export */ const product_form_reducer = (product_form_reducer_reducer);
;// CONCATENATED MODULE: ../../packages/js/data/src/product-form/index.ts
/**
 * External dependencies
 */


/**
 * Internal dependencies
 */






(0,data_build_module/* registerStore */.ti)(product_form_constants_STORE_NAME, {
  reducer: product_form_reducer,
  actions: product_form_actions_namespaceObject,
  controls: data_controls_build_module/* controls */.ne,
  selectors: product_form_selectors_namespaceObject,
  resolvers: product_form_resolvers_namespaceObject
});
var EXPERIMENTAL_PRODUCT_FORM_STORE_NAME = (/* unused pure expression or super */ null && (STORE_NAME));
;// CONCATENATED MODULE: ../../packages/js/data/src/tax-classes/constants.ts
var tax_classes_constants_STORE_NAME = 'experimental/wc/admin/tax-classes';
var WC_TAX_CLASSES_NAMESPACE = '/wc/v3/taxes/classes';
;// CONCATENATED MODULE: ../../packages/js/data/src/tax-classes/resolvers.ts













var tax_classes_resolvers_marked = /*#__PURE__*/regenerator_default().mark(getTaxClasses);
function tax_classes_resolvers_ownKeys(e, r) {
  var t = Object.keys(e);
  if (Object.getOwnPropertySymbols) {
    var o = Object.getOwnPropertySymbols(e);
    r && (o = o.filter(function (r) {
      return Object.getOwnPropertyDescriptor(e, r).enumerable;
    })), t.push.apply(t, o);
  }
  return t;
}
function tax_classes_resolvers_objectSpread(e) {
  for (var r = 1; r < arguments.length; r++) {
    var t = null != arguments[r] ? arguments[r] : {};
    r % 2 ? tax_classes_resolvers_ownKeys(Object(t), !0).forEach(function (r) {
      (0,defineProperty/* default */.A)(e, r, t[r]);
    }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(e, Object.getOwnPropertyDescriptors(t)) : tax_classes_resolvers_ownKeys(Object(t)).forEach(function (r) {
      Object.defineProperty(e, r, Object.getOwnPropertyDescriptor(t, r));
    });
  }
  return e;
}

/**
 * Internal dependencies
 */




function getTaxClasses(query) {
  var urlParameters, resourceQuery, path, _yield$request, items;
  return regenerator_default().wrap(function getTaxClasses$(_context) {
    while (1) switch (_context.prev = _context.next) {
      case 0:
        urlParameters = getUrlParameters(WC_TAX_CLASSES_NAMESPACE, query || {});
        resourceQuery = cleanQuery(query || {}, WC_TAX_CLASSES_NAMESPACE);
        _context.prev = 2;
        path = getRestPath(WC_TAX_CLASSES_NAMESPACE, query || {}, urlParameters);
        _context.next = 6;
        return request(path, resourceQuery);
      case 6:
        _yield$request = _context.sent;
        items = _yield$request.items;
        _context.next = 10;
        return getItemsTotalCountSuccess(query, items.length);
      case 10:
        _context.next = 12;
        return getItemsSuccess(query, items.map(function (item) {
          var _item$id;
          return tax_classes_resolvers_objectSpread(tax_classes_resolvers_objectSpread({}, item), {}, {
            id: (_item$id = item.id) !== null && _item$id !== void 0 ? _item$id : item.slug
          });
        }), urlParameters);
      case 12:
        return _context.abrupt("return", items);
      case 15:
        _context.prev = 15;
        _context.t0 = _context["catch"](2);
        _context.next = 19;
        return getItemsTotalCountError(query, _context.t0);
      case 19:
        _context.next = 21;
        return actions_getItemsError(query, _context.t0);
      case 21:
        throw _context.t0;
      case 22:
      case "end":
        return _context.stop();
    }
  }, tax_classes_resolvers_marked, null, [[2, 15]]);
}
;// CONCATENATED MODULE: ../../packages/js/data/src/tax-classes/index.ts
/**
 * Internal dependencies
 */



createCrudDataStore({
  storeName: tax_classes_constants_STORE_NAME,
  resourceName: 'TaxClass',
  pluralResourceName: 'TaxClasses',
  namespace: WC_TAX_CLASSES_NAMESPACE,
  storeConfig: {
    resolvers: tax_classes_resolvers_namespaceObject
  }
});
var EXPERIMENTAL_TAX_CLASSES_STORE_NAME = (/* unused pure expression or super */ null && (STORE_NAME));
;// CONCATENATED MODULE: ../../packages/js/data/src/plugins/with-plugins-hydration.tsx

/**
 * External dependencies
 */



/**
 * Internal dependencies
 */

var withPluginsHydration = function withPluginsHydration(data) {
  return createHigherOrderComponent(function (OriginalComponent) {
    return function (props) {
      var shouldHydrate = useSelect(function (select) {
        if (!data) {
          return;
        }
        var _select = select(STORE_NAME),
          isResolving = _select.isResolving,
          hasFinishedResolution = _select.hasFinishedResolution;
        return !isResolving('getActivePlugins', []) && !hasFinishedResolution('getActivePlugins', []);
      }, []);
      var _useDispatch = useDispatch(STORE_NAME),
        startResolution = _useDispatch.startResolution,
        finishResolution = _useDispatch.finishResolution,
        updateActivePlugins = _useDispatch.updateActivePlugins,
        updateInstalledPlugins = _useDispatch.updateInstalledPlugins,
        updateIsJetpackConnected = _useDispatch.updateIsJetpackConnected;
      useEffect(function () {
        if (!shouldHydrate) {
          return;
        }
        startResolution('getActivePlugins', []);
        startResolution('getInstalledPlugins', []);
        startResolution('isJetpackConnected', []);
        updateActivePlugins(data.activePlugins, true);
        updateInstalledPlugins(data.installedPlugins, true);
        updateIsJetpackConnected(data.jetpackStatus && data.jetpackStatus.isActive ? true : false);
        finishResolution('getActivePlugins', []);
        finishResolution('getInstalledPlugins', []);
        finishResolution('isJetpackConnected', []);
      }, [shouldHydrate]);
      return createElement(OriginalComponent, props);
    };
  }, 'withPluginsHydration');
};
try {
    // @ts-ignore
    withPluginsHydration.displayName = "withPluginsHydration";
    // @ts-ignore
    withPluginsHydration.__docgenInfo = { "description": "", "displayName": "withPluginsHydration", "props": { "installedPlugins": { "defaultValue": null, "description": "", "name": "installedPlugins", "required": true, "type": { "name": "string[]" } }, "activePlugins": { "defaultValue": null, "description": "", "name": "activePlugins", "required": true, "type": { "name": "string[]" } }, "jetpackStatus": { "defaultValue": null, "description": "", "name": "jetpackStatus", "required": false, "type": { "name": "{ isActive: boolean; }" } } } };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/data/src/plugins/with-plugins-hydration.tsx#withPluginsHydration"] = { docgenInfo: withPluginsHydration.__docgenInfo, name: "withPluginsHydration", path: "../../packages/js/data/src/plugins/with-plugins-hydration.tsx#withPluginsHydration" };
}
catch (__react_docgen_typescript_loader_error) { }
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.from-entries.js
var es_object_from_entries = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.from-entries.js");
;// CONCATENATED MODULE: ../../packages/js/data/src/options/with-options-hydration.tsx











/**
 * External dependencies
 */




/**
 * Internal dependencies
 */

var useOptionsHydration = function useOptionsHydration(data) {
  var shouldHydrate = useSelect(function (select) {
    var _select = select(STORE_NAME),
      isResolving = _select.isResolving,
      hasFinishedResolution = _select.hasFinishedResolution;
    if (!data) {
      return {};
    }
    return Object.fromEntries(Object.keys(data).map(function (name) {
      var hydrate = !isResolving('getOption', [name]) && !hasFinishedResolution('getOption', [name]);
      return [name, hydrate];
    }));
  }, []);
  var _useDispatch = useDispatch(STORE_NAME),
    startResolution = _useDispatch.startResolution,
    finishResolution = _useDispatch.finishResolution,
    receiveOptions = _useDispatch.receiveOptions;
  useEffect(function () {
    Object.entries(shouldHydrate).forEach(function (_ref) {
      var _ref2 = _slicedToArray(_ref, 2),
        name = _ref2[0],
        hydrate = _ref2[1];
      if (hydrate) {
        startResolution('getOption', [name]);
        receiveOptions(_defineProperty({}, name, data[name]));
        finishResolution('getOption', [name]);
      }
    });
  }, [shouldHydrate]);
};
var withOptionsHydration = function withOptionsHydration(data) {
  return createHigherOrderComponent(function (OriginalComponent) {
    return function (props) {
      useOptionsHydration(data);
      return createElement(OriginalComponent, props);
    };
  }, 'withOptionsHydration');
};
try {
    // @ts-ignore
    useOptionsHydration.displayName = "useOptionsHydration";
    // @ts-ignore
    useOptionsHydration.__docgenInfo = { "description": "", "displayName": "useOptionsHydration", "props": {} };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/data/src/options/with-options-hydration.tsx#useOptionsHydration"] = { docgenInfo: useOptionsHydration.__docgenInfo, name: "useOptionsHydration", path: "../../packages/js/data/src/options/with-options-hydration.tsx#useOptionsHydration" };
}
catch (__react_docgen_typescript_loader_error) { }
try {
    // @ts-ignore
    withOptionsHydration.displayName = "withOptionsHydration";
    // @ts-ignore
    withOptionsHydration.__docgenInfo = { "description": "", "displayName": "withOptionsHydration", "props": {} };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/data/src/options/with-options-hydration.tsx#withOptionsHydration"] = { docgenInfo: withOptionsHydration.__docgenInfo, name: "withOptionsHydration", path: "../../packages/js/data/src/options/with-options-hydration.tsx#withOptionsHydration" };
}
catch (__react_docgen_typescript_loader_error) { }
;// CONCATENATED MODULE: ../../packages/js/data/src/settings/use-settings.ts


function use_settings_ownKeys(e, r) {
  var t = Object.keys(e);
  if (Object.getOwnPropertySymbols) {
    var o = Object.getOwnPropertySymbols(e);
    r && (o = o.filter(function (r) {
      return Object.getOwnPropertyDescriptor(e, r).enumerable;
    })), t.push.apply(t, o);
  }
  return t;
}
function use_settings_objectSpread(e) {
  for (var r = 1; r < arguments.length; r++) {
    var t = null != arguments[r] ? arguments[r] : {};
    r % 2 ? use_settings_ownKeys(Object(t), !0).forEach(function (r) {
      _defineProperty(e, r, t[r]);
    }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(e, Object.getOwnPropertyDescriptors(t)) : use_settings_ownKeys(Object(t)).forEach(function (r) {
      Object.defineProperty(e, r, Object.getOwnPropertyDescriptor(t, r));
    });
  }
  return e;
}












/**
 * External dependencies
 */



/**
 * Internal dependencies
 */

var useSettings = function useSettings(group) {
  var settingsKeys = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : [];
  var _useSelect = useSelect(function (select) {
      var _select = select(STORE_NAME),
        getLastSettingsErrorForGroup = _select.getLastSettingsErrorForGroup,
        getSettingsForGroup = _select.getSettingsForGroup,
        getIsDirty = _select.getIsDirty,
        isUpdateSettingsRequesting = _select.isUpdateSettingsRequesting;
      return {
        requestedSettings: getSettingsForGroup(group, settingsKeys),
        settingsError: Boolean(getLastSettingsErrorForGroup(group)),
        isRequesting: isUpdateSettingsRequesting(group),
        isDirty: getIsDirty(group, settingsKeys)
      };
    }, [group].concat(_toConsumableArray(settingsKeys.sort()))),
    requestedSettings = _useSelect.requestedSettings,
    settingsError = _useSelect.settingsError,
    isRequesting = _useSelect.isRequesting,
    isDirty = _useSelect.isDirty;
  var _useDispatch = useDispatch(STORE_NAME),
    persistSettingsForGroup = _useDispatch.persistSettingsForGroup,
    updateAndPersistSettingsForGroup = _useDispatch.updateAndPersistSettingsForGroup,
    updateSettingsForGroup = _useDispatch.updateSettingsForGroup;
  var updateSettings = useCallback(function (name, data) {
    updateSettingsForGroup(group, _defineProperty({}, name, data));
  }, [group]);
  var persistSettings = useCallback(function () {
    // this action would simply persist all settings marked as dirty in the
    // store state and then remove the dirty record in the isDirtyMap
    persistSettingsForGroup(group);
  }, [group]);
  var updateAndPersistSettings = useCallback(function (name, data) {
    updateAndPersistSettingsForGroup(group, _defineProperty({}, name, data));
  }, [group]);
  return use_settings_objectSpread(use_settings_objectSpread({
    settingsError: settingsError,
    isRequesting: isRequesting,
    isDirty: isDirty
  }, requestedSettings), {}, {
    persistSettings: persistSettings,
    updateAndPersistSettings: updateAndPersistSettings,
    updateSettings: updateSettings
  });
};
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+data@6.6.1_react@17.0.2/node_modules/@wordpress/data/build-module/components/use-dispatch/use-dispatch.js
var use_dispatch = __webpack_require__("../../node_modules/.pnpm/@wordpress+data@6.6.1_react@17.0.2/node_modules/@wordpress/data/build-module/components/use-dispatch/use-dispatch.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+data@6.6.1_react@17.0.2/node_modules/@wordpress/data/build-module/components/use-select/index.js + 2 modules
var use_select = __webpack_require__("../../node_modules/.pnpm/@wordpress+data@6.6.1_react@17.0.2/node_modules/@wordpress/data/build-module/components/use-select/index.js");
;// CONCATENATED MODULE: ../../packages/js/data/src/user/use-user-preferences.ts











function use_user_preferences_ownKeys(e, r) {
  var t = Object.keys(e);
  if (Object.getOwnPropertySymbols) {
    var o = Object.getOwnPropertySymbols(e);
    r && (o = o.filter(function (r) {
      return Object.getOwnPropertyDescriptor(e, r).enumerable;
    })), t.push.apply(t, o);
  }
  return t;
}
function use_user_preferences_objectSpread(e) {
  for (var r = 1; r < arguments.length; r++) {
    var t = null != arguments[r] ? arguments[r] : {};
    r % 2 ? use_user_preferences_ownKeys(Object(t), !0).forEach(function (r) {
      (0,defineProperty/* default */.A)(e, r, t[r]);
    }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(e, Object.getOwnPropertyDescriptors(t)) : use_user_preferences_ownKeys(Object(t)).forEach(function (r) {
      Object.defineProperty(e, r, Object.getOwnPropertyDescriptor(t, r));
    });
  }
  return e;
}



/**
 * External dependencies
 */



/**
 * Internal dependencies
 */

/**
 * Retrieve and decode the user's WooCommerce meta values.
 *
 * @param {Object} user WP User object.
 * @return {Object} User's WooCommerce preferences.
 */
var getWooCommerceMeta = function getWooCommerceMeta(user) {
  var wooMeta = user.woocommerce_meta || {};
  var userData = (0,lodash.mapValues)(wooMeta, function (data) {
    if (!data || data.length === 0) {
      return '';
    }
    try {
      return JSON.parse(data);
    } catch (e) {
      // If we can't parse the value, return the raw data. The meta value could be a string like 'yes' or 'no'.
      return data;
    }
  });
  return userData;
};

// Create wrapper for updating user's `woocommerce_meta`.
function updateUserPrefs(_x, _x2, _x3, _x4, _x5) {
  return _updateUserPrefs.apply(this, arguments);
}
/**
 * Custom react hook for retrieving thecurrent user's WooCommerce preferences.
 *
 * This is a wrapper around @wordpress/core-data's getCurrentUser() and saveUser().
 */
function _updateUserPrefs() {
  _updateUserPrefs = (0,asyncToGenerator/* default */.A)( /*#__PURE__*/regenerator_default().mark(function _callee2(receiveCurrentUser, user, saveUser, getLastEntitySaveError, userPrefs) {
    var metaData, updatedUser, error, updatedUserResponse;
    return regenerator_default().wrap(function _callee2$(_context2) {
      while (1) switch (_context2.prev = _context2.next) {
        case 0:
          // @todo Handle unresolved getCurrentUser() here.
          // Prep fields for update.
          metaData = (0,lodash.mapValues)(userPrefs, function (value) {
            if (typeof value === 'string') {
              // If the value is a string, we don't need to serialize it.
              return value;
            }
            return JSON.stringify(value);
          });
          if (!(Object.keys(metaData).length === 0)) {
            _context2.next = 3;
            break;
          }
          return _context2.abrupt("return", {
            error: new Error('Invalid woocommerce_meta data for update.'),
            updatedUser: undefined
          });
        case 3:
          // Optimistically propagate new woocommerce_meta to the store for instant update.
          receiveCurrentUser(use_user_preferences_objectSpread(use_user_preferences_objectSpread({}, user), {}, {
            woocommerce_meta: use_user_preferences_objectSpread(use_user_preferences_objectSpread({}, user.woocommerce_meta), metaData)
          }));
          // Use saveUser() to update WooCommerce meta values.
          _context2.next = 6;
          return saveUser({
            id: user.id,
            woocommerce_meta: metaData
          });
        case 6:
          updatedUser = _context2.sent;
          if (!(undefined === updatedUser)) {
            _context2.next = 10;
            break;
          }
          // Return the encountered error to the caller.
          error = getLastEntitySaveError('root', 'user', user.id);
          return _context2.abrupt("return", {
            error: error,
            updatedUser: updatedUser
          });
        case 10:
          // Decode the WooCommerce meta after save.
          updatedUserResponse = use_user_preferences_objectSpread(use_user_preferences_objectSpread({}, updatedUser), {}, {
            woocommerce_meta: getWooCommerceMeta(updatedUser)
          });
          return _context2.abrupt("return", {
            updatedUser: updatedUserResponse
          });
        case 12:
        case "end":
          return _context2.stop();
      }
    }, _callee2);
  }));
  return _updateUserPrefs.apply(this, arguments);
}
var useUserPreferences = function useUserPreferences() {
  // Get our dispatch methods now - this can't happen inside the callback below.
  var dispatch = (0,use_dispatch/* default */.A)(user_constants_STORE_NAME);
  var addEntities = dispatch.addEntities,
    receiveCurrentUser = dispatch.receiveCurrentUser,
    saveEntityRecord = dispatch.saveEntityRecord;
  // eslint-disable-next-line @typescript-eslint/ban-ts-comment
  // @ts-ignore
  var saveUser = dispatch.saveUser;
  var userData = (0,use_select/* default */.A)(function (select) {
    var _select = select(user_constants_STORE_NAME),
      getCurrentUser = _select.getCurrentUser,
      getEntity = _select.getEntity,
      getEntityRecord = _select.getEntityRecord,
      getLastEntitySaveError = _select.getLastEntitySaveError,
      hasStartedResolution = _select.hasStartedResolution,
      hasFinishedResolution = _select.hasFinishedResolution;
    return {
      isRequesting: hasStartedResolution('getCurrentUser') && !hasFinishedResolution('getCurrentUser'),
      user: getCurrentUser(),
      getCurrentUser: getCurrentUser,
      getEntity: getEntity,
      getEntityRecord: getEntityRecord,
      getLastEntitySaveError: getLastEntitySaveError
    };
  });
  var updateUserPreferences = function updateUserPreferences(userPrefs) {
    // WP 5.3.x doesn't have the User entity defined.
    if (typeof saveUser !== 'function') {
      // eslint-disable-next-line @typescript-eslint/ban-ts-comment
      // @ts-ignore
      saveUser = /*#__PURE__*/function () {
        var _ref = (0,asyncToGenerator/* default */.A)( /*#__PURE__*/regenerator_default().mark(function _callee(userToSave) {
          var entityDefined;
          return regenerator_default().wrap(function _callee$(_context) {
            while (1) switch (_context.prev = _context.next) {
              case 0:
                entityDefined = Boolean(userData.getEntity('root', 'user'));
                if (entityDefined) {
                  _context.next = 4;
                  break;
                }
                _context.next = 4;
                return addEntities([{
                  name: 'user',
                  kind: 'root',
                  baseURL: '/wp/v2/users',
                  plural: 'users'
                }]);
              case 4:
                _context.next = 6;
                return saveEntityRecord('root', 'user', userToSave);
              case 6:
                return _context.abrupt("return", userData.getEntityRecord('root', 'user', userToSave.id));
              case 7:
              case "end":
                return _context.stop();
            }
          }, _callee);
        }));
        return function saveUser(_x6) {
          return _ref.apply(this, arguments);
        };
      }();
    }
    // Get most recent user before update.
    var currentUser = userData.getCurrentUser();
    return updateUserPrefs(receiveCurrentUser, currentUser, saveUser, userData.getLastEntitySaveError, userPrefs);
  };
  var userPreferences = userData.user ? getWooCommerceMeta(userData.user) : {};
  return use_user_preferences_objectSpread(use_user_preferences_objectSpread({
    isRequesting: userData.isRequesting
  }, userPreferences), {}, {
    updateUserPreferences: updateUserPreferences
  });
};
;// CONCATENATED MODULE: ../../packages/js/data/src/user/use-user.ts
/**
 * External dependencies
 */


/**
 * Internal dependencies
 */

/**
 * Custom react hook for shortcut methods around user.
 *
 * This is a wrapper around @wordpress/core-data's getCurrentUser().
 */
var useUser = function useUser() {
  var userData = (0,use_select/* default */.A)(function (select) {
    // eslint-disable-next-line @typescript-eslint/ban-ts-comment
    // @ts-ignore
    var _select = select(user_constants_STORE_NAME),
      getCurrentUser = _select.getCurrentUser,
      hasStartedResolution = _select.hasStartedResolution,
      hasFinishedResolution = _select.hasFinishedResolution;
    return {
      isRequesting: hasStartedResolution('getCurrentUser') && !hasFinishedResolution('getCurrentUser'),
      // We register additional user data in backend so we need to use a type assertion here for WC user.
      user: getCurrentUser(),
      getCurrentUser: getCurrentUser
    };
  });
  var currentUserCan = function currentUserCan(capability) {
    if (userData.user && userData.user.is_super_admin) {
      return true;
    }
    if (userData.user && userData.user.capabilities[capability]) {
      return true;
    }
    return false;
  };
  return {
    currentUserCan: currentUserCan,
    user: userData.user,
    isRequesting: userData.isRequesting
  };
};
;// CONCATENATED MODULE: ../../packages/js/data/src/onboarding/utils.ts


/**
 * Internal dependencies
 */

/**
 * Filters tasks to only visible tasks, taking in account snoozed tasks.
 */
var getVisibleTasks = function getVisibleTasks(tasks) {
  return tasks.filter(function (task) {
    return !task.isDismissed;
  });
};
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.assign.js
var es_object_assign = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.assign.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/moment.js
var moment_moment = __webpack_require__("../../node_modules/.pnpm/moment@2.29.4/node_modules/moment/moment.js");
var moment_default = /*#__PURE__*/__webpack_require__.n(moment_moment);
;// CONCATENATED MODULE: ../../packages/js/data/src/reports/utils.ts











function reports_utils_ownKeys(e, r) {
  var t = Object.keys(e);
  if (Object.getOwnPropertySymbols) {
    var o = Object.getOwnPropertySymbols(e);
    r && (o = o.filter(function (r) {
      return Object.getOwnPropertyDescriptor(e, r).enumerable;
    })), t.push.apply(t, o);
  }
  return t;
}
function reports_utils_objectSpread(e) {
  for (var r = 1; r < arguments.length; r++) {
    var t = null != arguments[r] ? arguments[r] : {};
    r % 2 ? reports_utils_ownKeys(Object(t), !0).forEach(function (r) {
      _defineProperty(e, r, t[r]);
    }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(e, Object.getOwnPropertyDescriptors(t)) : reports_utils_ownKeys(Object(t)).forEach(function (r) {
      Object.defineProperty(e, r, Object.getOwnPropertyDescriptor(t, r));
    });
  }
  return e;
}










/**
 * External dependencies
 */





/**
 * Internal dependencies
 */




/**
 * Add timestamp to advanced filter parameters involving date. The api
 * expects a timestamp for these values similar to `before` and `after`.
 *
 * @param {Object} config       - advancedFilters config object.
 * @param {Object} activeFilter - an active filter.
 * @return {Object} - an active filter with timestamp added to date values.
 */
function timeStampFilterDates(config, activeFilter) {
  var advancedFilterConfig = config.filters[activeFilter.key];
  if (get(advancedFilterConfig, ['input', 'component']) !== 'Date') {
    return activeFilter;
  }
  var rule = activeFilter.rule,
    value = activeFilter.value;
  var timeOfDayMap = {
    after: 'start',
    before: 'end'
  };
  // If the value is an array, it signifies "between" values which must have a timestamp
  // appended to each value.
  if (Array.isArray(value)) {
    var _value = _slicedToArray(value, 2),
      after = _value[0],
      before = _value[1];
    return Object.assign({}, activeFilter, {
      value: [appendTimestamp(moment(after), timeOfDayMap.after), appendTimestamp(moment(before), timeOfDayMap.before)]
    });
  }
  return Object.assign({}, activeFilter, {
    value: appendTimestamp(moment(value), timeOfDayMap[rule])
  });
}
function getQueryFromConfig(config, advancedFilters, query) {
  var queryValue = query[config.param];
  if (!queryValue) {
    return {};
  }
  if (queryValue === 'advanced') {
    var activeFilters = getActiveFiltersFromQuery(query, advancedFilters.filters);
    if (activeFilters.length === 0) {
      return {};
    }
    var filterQuery = getQueryFromActiveFilters(activeFilters.map(function (filter) {
      return timeStampFilterDates(advancedFilters, filter);
    }), {}, advancedFilters.filters);
    return reports_utils_objectSpread({
      match: query.match || 'all'
    }, filterQuery);
  }
  var filter = find(flattenFilters(config.filters), {
    value: queryValue
  });
  if (!filter) {
    return {};
  }
  if (filter.settings && filter.settings.param) {
    var param = filter.settings.param;
    if (query[param]) {
      return _defineProperty({}, param, query[param]);
    }
    return {};
  }
  return _defineProperty({}, config.param, queryValue);
}

/**
 * Add filters and advanced filters values to a query object.
 *
 * @param {Object} options                   arguments
 * @param {string} options.endpoint          Report API Endpoint
 * @param {Object} options.query             Query parameters in the url
 * @param {Array}  options.limitBy           Properties used to limit the results. It will be used in the API call to send the IDs.
 * @param {Array}  [options.filters]         config filters
 * @param {Object} [options.advancedFilters] config advanced filters
 * @return {Object} A query object with the values from filters and advanced fitlters applied.
 */
function getFilterQuery(options) {
  var endpoint = options.endpoint,
    query = options.query,
    limitBy = options.limitBy,
    _options$filters = options.filters,
    filters = _options$filters === void 0 ? [] : _options$filters,
    _options$advancedFilt = options.advancedFilters,
    advancedFilters = _options$advancedFilt === void 0 ? {} : _options$advancedFilt;
  if (query.search) {
    var limitProperties = limitBy || [endpoint];
    return limitProperties.reduce(function (result, limitProperty) {
      result[limitProperty] = query[limitProperty];
      return result;
    }, {});
  }
  return filters.map(function (filter) {
    return getQueryFromConfig(filter, advancedFilters, query);
  }).reduce(function (result, configQuery) {
    return Object.assign(result, configQuery);
  }, {});
}

// Some stats endpoints don't have interval data, so they can ignore after/before params and omit that part of the response.
var noIntervalEndpoints = (/* unused pure expression or super */ null && (['stock', 'customers']));
/**
 * Returns true if a report object is empty.
 *
 * @param {Object} report   Report to check
 * @param {string} endpoint Endpoint slug
 * @return {boolean}        True if report is data is empty.
 */
function isReportDataEmpty(report, endpoint) {
  if (!report) {
    return true;
  }
  if (!report.data) {
    return true;
  }
  if (!report.data.totals || isNull(report.data.totals)) {
    return true;
  }
  var checkIntervals = !includes(noIntervalEndpoints, endpoint);
  if (checkIntervals && (!report.data.intervals || report.data.intervals.length === 0)) {
    return true;
  }
  return false;
}

/**
 * Constructs and returns a query associated with a Report data request.
 *
 * @param {Object} options                   arguments
 * @param {string} options.endpoint          Report API Endpoint
 * @param {string} options.dataType          'primary' or 'secondary'.
 * @param {Object} options.query             Query parameters in the url.
 * @param {Array}  [options.filters]         config filters
 * @param {Object} [options.advancedFilters] config advanced filters
 * @param {Array}  options.limitBy           Properties used to limit the results. It will be used in the API call to send the IDs.
 * @param {string} options.defaultDateRange  User specified default date range.
 * @return {Object} data request query parameters.
 */
function getRequestQuery(options) {
  var endpoint = options.endpoint,
    dataType = options.dataType,
    query = options.query,
    fields = options.fields,
    defaultDateRange = options.defaultDateRange;
  var datesFromQuery = getCurrentDates(query, defaultDateRange);
  var interval = getIntervalForQuery(query, defaultDateRange);
  var filterQuery = getFilterQuery(options);
  var end = datesFromQuery[dataType].before;
  var noIntervals = includes(noIntervalEndpoints, endpoint);
  return noIntervals ? reports_utils_objectSpread(reports_utils_objectSpread({}, filterQuery), {}, {
    fields: fields
  }) : reports_utils_objectSpread({
    order: 'asc',
    interval: interval,
    per_page: MAX_PER_PAGE,
    after: appendTimestamp(datesFromQuery[dataType].after, 'start'),
    before: appendTimestamp(end, 'end'),
    segmentby: query.segmentby,
    fields: fields
  }, filterQuery);
}

/**
 * Returns summary number totals needed to render a report page.
 *
 * @param {Object} options                   arguments
 * @param {string} options.endpoint          Report API Endpoint
 * @param {Object} options.query             Query parameters in the url
 * @param {Object} options.select            Instance of @wordpress/select
 * @param {Array}  [options.filters]         config filters
 * @param {Object} [options.advancedFilters] config advanced filters
 * @param {Array}  options.limitBy           Properties used to limit the results. It will be used in the API call to send the IDs.
 * @param {string} options.defaultDateRange  User specified default date range.
 * @return {Object} Object containing summary number responses.
 */
function getSummaryNumbers(options) {
  var endpoint = options.endpoint,
    select = options.select;
  var _select = select(STORE_NAME),
    getReportStats = _select.getReportStats,
    getReportStatsError = _select.getReportStatsError,
    isResolving = _select.isResolving;
  var response = {
    isRequesting: false,
    isError: false,
    totals: {
      primary: null,
      secondary: null
    }
  };
  var primaryQuery = getRequestQuery(reports_utils_objectSpread(reports_utils_objectSpread({}, options), {}, {
    dataType: 'primary'
  }));

  // Disable eslint rule requiring `getReportStats` to be defined below because the next two statements
  // depend on `getReportStats` to have been called.
  // eslint-disable-next-line @wordpress/no-unused-vars-before-return
  var primary = getReportStats(endpoint, primaryQuery);
  if (isResolving('getReportStats', [endpoint, primaryQuery])) {
    return reports_utils_objectSpread(reports_utils_objectSpread({}, response), {}, {
      isRequesting: true
    });
  } else if (getReportStatsError(endpoint, primaryQuery)) {
    return reports_utils_objectSpread(reports_utils_objectSpread({}, response), {}, {
      isError: true
    });
  }
  var primaryTotals = primary && primary.data && primary.data.totals || null;
  var secondaryQuery = getRequestQuery(reports_utils_objectSpread(reports_utils_objectSpread({}, options), {}, {
    dataType: 'secondary'
  }));

  // Disable eslint rule requiring `getReportStats` to be defined below because the next two statements
  // depend on `getReportStats` to have been called.
  // eslint-disable-next-line @wordpress/no-unused-vars-before-return
  var secondary = getReportStats(endpoint, secondaryQuery);
  if (isResolving('getReportStats', [endpoint, secondaryQuery])) {
    return reports_utils_objectSpread(reports_utils_objectSpread({}, response), {}, {
      isRequesting: true
    });
  } else if (getReportStatsError(endpoint, secondaryQuery)) {
    return reports_utils_objectSpread(reports_utils_objectSpread({}, response), {}, {
      isError: true
    });
  }
  var secondaryTotals = secondary && secondary.data && secondary.data.totals || null;
  return reports_utils_objectSpread(reports_utils_objectSpread({}, response), {}, {
    totals: {
      primary: primaryTotals,
      secondary: secondaryTotals
    }
  });
}

/**
 * Static responses object to avoid returning new references each call.
 */
var reportChartDataResponses = {
  requesting: {
    isEmpty: false,
    isError: false,
    isRequesting: true,
    data: {
      totals: {},
      intervals: []
    }
  },
  error: {
    isEmpty: false,
    isError: true,
    isRequesting: false,
    data: {
      totals: {},
      intervals: []
    }
  },
  empty: {
    isEmpty: true,
    isError: false,
    isRequesting: false,
    data: {
      totals: {},
      intervals: []
    }
  }
};
var EMPTY_ARRAY = (/* unused pure expression or super */ null && ([]));

/**
 * Cache helper for returning the full chart dataset after multiple
 * requests. Memoized on the request query (string), only called after
 * all the requests have resolved successfully.
 */
var getReportChartDataResponse = (0,lodash.memoize)(function (_requestString, totals, intervals) {
  return {
    isEmpty: false,
    isError: false,
    isRequesting: false,
    data: {
      totals: totals,
      intervals: intervals
    }
  };
}, function (requestString, totals, intervals) {
  return [requestString, totals.length, intervals.length].join(':');
});

/**
 * Returns all of the data needed to render a chart with summary numbers on a report page.
 *
 * @param {Object} options                  arguments
 * @param {string} options.endpoint         Report API Endpoint
 * @param {string} options.dataType         'primary' or 'secondary'
 * @param {Object} options.query            Query parameters in the url
 * @param {Object} options.selector         Instance of @wordpress/select response
 * @param {Object} options.select           (Depreciated) Instance of @wordpress/select
 * @param {Array}  options.limitBy          Properties used to limit the results. It will be used in the API call to send the IDs.
 * @param {string} options.defaultDateRange User specified default date range.
 * @return {Object}  Object containing API request information (response, fetching, and error details)
 */
function getReportChartData(options) {
  var endpoint = options.endpoint;
  var reportSelectors = options.selector;
  if (options.select && !options.selector) {
    deprecated('option.select', {
      version: '1.7.0',
      hint: 'You can pass the report selectors through option.selector now.'
    });
    reportSelectors = options.select(STORE_NAME);
  }
  var _reportSelectors = reportSelectors,
    getReportStats = _reportSelectors.getReportStats,
    getReportStatsError = _reportSelectors.getReportStatsError,
    isResolving = _reportSelectors.isResolving;
  var requestQuery = getRequestQuery(options);
  // Disable eslint rule requiring `stats` to be defined below because the next two if statements
  // depend on `getReportStats` to have been called.
  // eslint-disable-next-line @wordpress/no-unused-vars-before-return
  var stats = getReportStats(endpoint, requestQuery);
  if (isResolving('getReportStats', [endpoint, requestQuery])) {
    return reportChartDataResponses.requesting;
  }
  if (getReportStatsError(endpoint, requestQuery)) {
    return reportChartDataResponses.error;
  }
  if (isReportDataEmpty(stats, endpoint)) {
    return reportChartDataResponses.empty;
  }
  var totals = stats && stats.data && stats.data.totals || null;
  var intervals = stats && stats.data && stats.data.intervals || EMPTY_ARRAY;

  // If we have more than 100 results for this time period,
  // we need to make additional requests to complete the response.
  if (stats.totalResults > MAX_PER_PAGE) {
    var isFetching = true;
    var isError = false;
    var pagedData = [];
    var totalPages = Math.ceil(stats.totalResults / MAX_PER_PAGE);
    var pagesFetched = 1;
    for (var i = 2; i <= totalPages; i++) {
      var nextQuery = reports_utils_objectSpread(reports_utils_objectSpread({}, requestQuery), {}, {
        page: i
      });
      var _data = getReportStats(endpoint, nextQuery);
      if (isResolving('getReportStats', [endpoint, nextQuery])) {
        continue;
      }
      if (getReportStatsError(endpoint, nextQuery)) {
        isError = true;
        isFetching = false;
        break;
      }
      pagedData.push(_data);
      pagesFetched++;
      if (pagesFetched === totalPages) {
        isFetching = false;
        break;
      }
    }
    if (isFetching) {
      return reportChartDataResponses.requesting;
    } else if (isError) {
      return reportChartDataResponses.error;
    }
    forEach(pagedData, function (_data) {
      if (_data.data && _data.data.intervals && Array.isArray(_data.data.intervals)) {
        intervals = intervals.concat(_data.data.intervals);
      }
    });
  }
  return getReportChartDataResponse(getResourceName(endpoint, requestQuery), totals, intervals);
}

/**
 * Returns a formatting function or string to be used by d3-format
 *
 * @param {string}   type         Type of number, 'currency', 'number', 'percent', 'average'
 * @param {Function} formatAmount format currency function
 * @return {string|Function}  returns a number format based on the type or an overriding formatting function
 */
function getTooltipValueFormat(type, formatAmount) {
  switch (type) {
    case 'currency':
      return formatAmount;
    case 'percent':
      return '.0%';
    case 'number':
      return ',';
    case 'average':
      return ',.2r';
    default:
      return ',';
  }
}

/**
 * Returns query needed for a request to populate a table.
 *
 * @param {Object} options                  arguments
 * @param {string} options.endpoint         Report API Endpoint
 * @param {Object} options.query            Query parameters in the url
 * @param {Object} options.tableQuery       Query parameters specific for that endpoint
 * @param {string} options.defaultDateRange User specified default date range.
 * @return {Object} Object    Table data response
 */
function getReportTableQuery(options) {
  var query = options.query,
    _options$tableQuery = options.tableQuery,
    tableQuery = _options$tableQuery === void 0 ? {} : _options$tableQuery;
  var filterQuery = getFilterQuery(options);
  var datesFromQuery = getCurrentDates(query, options.defaultDateRange);
  var noIntervals = includes(noIntervalEndpoints, options.endpoint);
  return reports_utils_objectSpread(reports_utils_objectSpread({
    orderby: query.orderby || 'date',
    order: query.order || 'desc',
    after: noIntervals ? undefined : appendTimestamp(datesFromQuery.primary.after, 'start'),
    before: noIntervals ? undefined : appendTimestamp(datesFromQuery.primary.before, 'end'),
    page: query.paged || '1',
    per_page: query.per_page || QUERY_DEFAULTS.pageSize
  }, filterQuery), tableQuery);
}

/**
 * Returns table data needed to render a report page.
 *
 * @param {Object} options                  arguments
 * @param {string} options.endpoint         Report API Endpoint
 * @param {Object} options.query            Query parameters in the url
 * @param {Object} options.selector         Instance of @wordpress/select response
 * @param {Object} options.select           (depreciated) Instance of @wordpress/select
 * @param {Object} options.tableQuery       Query parameters specific for that endpoint
 * @param {string} options.defaultDateRange User specified default date range.
 * @return {Object} Object    Table data response
 */
function getReportTableData(options) {
  var endpoint = options.endpoint;
  var reportSelectors = options.selector;
  if (options.select && !options.selector) {
    deprecated('option.select', {
      version: '1.7.0',
      hint: 'You can pass the report selectors through option.selector now.'
    });
    reportSelectors = options.select(STORE_NAME);
  }
  var _reportSelectors2 = reportSelectors,
    getReportItems = _reportSelectors2.getReportItems,
    getReportItemsError = _reportSelectors2.getReportItemsError,
    hasFinishedResolution = _reportSelectors2.hasFinishedResolution;
  var tableQuery = reportsUtils.getReportTableQuery(options);
  var response = {
    query: tableQuery,
    isRequesting: false,
    isError: false,
    items: {
      data: [],
      totalResults: 0
    }
  };

  // Disable eslint rule requiring `items` to be defined below because the next two if statements
  // depend on `getReportItems` to have been called.
  // eslint-disable-next-line @wordpress/no-unused-vars-before-return
  var items = getReportItems(endpoint, tableQuery);
  var queryResolved = hasFinishedResolution('getReportItems', [endpoint, tableQuery]);
  if (!queryResolved) {
    return reports_utils_objectSpread(reports_utils_objectSpread({}, response), {}, {
      isRequesting: true
    });
  }
  if (getReportItemsError(endpoint, tableQuery)) {
    return reports_utils_objectSpread(reports_utils_objectSpread({}, response), {}, {
      isError: true
    });
  }
  return reports_utils_objectSpread(reports_utils_objectSpread({}, response), {}, {
    items: items
  });
}
;// CONCATENATED MODULE: ../../packages/js/data/src/export/constants.ts
/**
 * Internal dependencies
 */
var export_constants_STORE_NAME = 'wc/admin/export';
// EXTERNAL MODULE: ../../node_modules/.pnpm/md5@2.3.0/node_modules/md5/md5.js
var md5 = __webpack_require__("../../node_modules/.pnpm/md5@2.3.0/node_modules/md5/md5.js");
var md5_default = /*#__PURE__*/__webpack_require__.n(md5);
;// CONCATENATED MODULE: ../../packages/js/data/src/export/utils.ts
/**
 * External dependencies
 */


/**
 * Internal dependencies
 */

var hashExportArgs = function hashExportArgs(args) {
  return md5_default()(utils_getResourceName('export', args));
};
;// CONCATENATED MODULE: ../../packages/js/data/src/export/selectors.ts
/**
 * Internal dependencies
 */

var isExportRequesting = function isExportRequesting(state, selector, selectorArgs) {
  return Boolean(state.requesting[selector] && state.requesting[selector][hashExportArgs(selectorArgs)]);
};
var getExportId = function getExportId(state, exportType, exportArgs) {
  return state.exportIds[exportType] && state.exportIds[exportType][hashExportArgs(exportArgs)];
};
var getError = function getError(state, selector, selectorArgs) {
  return state.errors[selector] && state.errors[selector][hashExportArgs(selectorArgs)];
};
;// CONCATENATED MODULE: ../../packages/js/data/src/export/action-types.ts
var export_action_types_TYPES = {
  START_EXPORT: 'START_EXPORT',
  SET_EXPORT_ID: 'SET_EXPORT_ID',
  SET_ERROR: 'SET_ERROR',
  SET_IS_REQUESTING: 'SET_IS_REQUESTING'
};
/* harmony default export */ const export_action_types = (export_action_types_TYPES);
;// CONCATENATED MODULE: ../../packages/js/data/src/export/actions.ts


var export_actions_marked = /*#__PURE__*/regenerator_default().mark(startExport);

/**
 * Internal dependencies
 */



function setExportId(exportType, exportArgs, exportId) {
  return {
    type: export_action_types.SET_EXPORT_ID,
    exportType: exportType,
    exportArgs: exportArgs,
    exportId: exportId
  };
}
function export_actions_setIsRequesting(selector, selectorArgs, isRequesting) {
  return {
    type: export_action_types.SET_IS_REQUESTING,
    selector: selector,
    selectorArgs: selectorArgs,
    isRequesting: isRequesting
  };
}
function export_actions_setError(selector, selectorArgs, error) {
  return {
    type: export_action_types.SET_ERROR,
    selector: selector,
    selectorArgs: selectorArgs,
    error: error
  };
}
function startExport(type, args) {
  var response, _response$data, exportId, message;
  return regenerator_default().wrap(function startExport$(_context) {
    while (1) switch (_context.prev = _context.next) {
      case 0:
        _context.next = 2;
        return export_actions_setIsRequesting('startExport', {
          type: type,
          args: args
        }, true);
      case 2:
        _context.prev = 2;
        _context.next = 5;
        return fetchWithHeaders({
          path: "".concat(NAMESPACE, "/reports/").concat(type, "/export"),
          method: 'POST',
          data: {
            report_args: args,
            email: true
          }
        });
      case 5:
        response = _context.sent;
        _context.next = 8;
        return export_actions_setIsRequesting('startExport', {
          type: type,
          args: args
        }, false);
      case 8:
        _response$data = response.data, exportId = _response$data.export_id, message = _response$data.message;
        if (!exportId) {
          _context.next = 14;
          break;
        }
        _context.next = 12;
        return setExportId(type, args, exportId);
      case 12:
        _context.next = 15;
        break;
      case 14:
        throw new Error(message);
      case 15:
        return _context.abrupt("return", response.data);
      case 18:
        _context.prev = 18;
        _context.t0 = _context["catch"](2);
        if (!(_context.t0 instanceof Error)) {
          _context.next = 25;
          break;
        }
        _context.next = 23;
        return export_actions_setError('startExport', {
          type: type,
          args: args
        }, _context.t0.message);
      case 23:
        _context.next = 26;
        break;
      case 25:
        // eslint-disable-next-line no-console
        console.error("Unexpected Error: ".concat(JSON.stringify(_context.t0)));
      // eslint-enable-next-line no-console
      case 26:
        _context.next = 28;
        return export_actions_setIsRequesting('startExport', {
          type: type,
          args: args
        }, false);
      case 28:
        throw _context.t0;
      case 29:
      case "end":
        return _context.stop();
    }
  }, export_actions_marked, null, [[2, 18]]);
}
;// CONCATENATED MODULE: ../../packages/js/data/src/export/reducer.ts











function export_reducer_ownKeys(e, r) {
  var t = Object.keys(e);
  if (Object.getOwnPropertySymbols) {
    var o = Object.getOwnPropertySymbols(e);
    r && (o = o.filter(function (r) {
      return Object.getOwnPropertyDescriptor(e, r).enumerable;
    })), t.push.apply(t, o);
  }
  return t;
}
function export_reducer_objectSpread(e) {
  for (var r = 1; r < arguments.length; r++) {
    var t = null != arguments[r] ? arguments[r] : {};
    r % 2 ? export_reducer_ownKeys(Object(t), !0).forEach(function (r) {
      (0,defineProperty/* default */.A)(e, r, t[r]);
    }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(e, Object.getOwnPropertyDescriptors(t)) : export_reducer_ownKeys(Object(t)).forEach(function (r) {
      Object.defineProperty(e, r, Object.getOwnPropertyDescriptor(t, r));
    });
  }
  return e;
}
/**
 * External dependencies
 */

/**
 * Internal dependencies
 */


var export_reducer_reducer = function reducer() {
  var state = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {
    errors: {},
    requesting: {},
    exportMeta: {},
    exportIds: {}
  };
  var action = arguments.length > 1 ? arguments[1] : undefined;
  switch (action.type) {
    case export_action_types.SET_IS_REQUESTING:
      return export_reducer_objectSpread(export_reducer_objectSpread({}, state), {}, {
        requesting: export_reducer_objectSpread(export_reducer_objectSpread({}, state.requesting), {}, (0,defineProperty/* default */.A)({}, action.selector, export_reducer_objectSpread(export_reducer_objectSpread({}, state.requesting[action.selector]), {}, (0,defineProperty/* default */.A)({}, hashExportArgs(action.selectorArgs), action.isRequesting))))
      });
    case export_action_types.SET_EXPORT_ID:
      var exportType = action.exportType,
        exportArgs = action.exportArgs,
        exportId = action.exportId;
      return export_reducer_objectSpread(export_reducer_objectSpread({}, state), {}, {
        exportMeta: export_reducer_objectSpread(export_reducer_objectSpread({}, state.exportMeta), {}, (0,defineProperty/* default */.A)({}, exportId, {
          exportType: exportType,
          exportArgs: exportArgs
        })),
        exportIds: export_reducer_objectSpread(export_reducer_objectSpread({}, state.exportIds), {}, (0,defineProperty/* default */.A)({}, exportType, export_reducer_objectSpread(export_reducer_objectSpread({}, state.exportIds[exportType]), {}, (0,defineProperty/* default */.A)({}, hashExportArgs({
          type: exportType,
          args: exportArgs
        }), exportId))))
      });
    case export_action_types.SET_ERROR:
      return export_reducer_objectSpread(export_reducer_objectSpread({}, state), {}, {
        errors: export_reducer_objectSpread(export_reducer_objectSpread({}, state.errors), {}, (0,defineProperty/* default */.A)({}, action.selector, export_reducer_objectSpread(export_reducer_objectSpread({}, state.errors[action.selector]), {}, (0,defineProperty/* default */.A)({}, hashExportArgs(action.selectorArgs), action.error))))
      });
    default:
      return state;
  }
};
/* harmony default export */ const export_reducer = (export_reducer_reducer);
;// CONCATENATED MODULE: ../../packages/js/data/src/export/index.ts
/**
 * External dependencies
 */

/**
 * Internal dependencies
 */






(0,data_build_module/* registerStore */.ti)(export_constants_STORE_NAME, {
  reducer: export_reducer,
  actions: export_actions_namespaceObject,
  controls: src_controls,
  selectors: export_selectors_namespaceObject
});
var EXPORT_STORE_NAME = (/* unused pure expression or super */ null && (STORE_NAME));
;// CONCATENATED MODULE: ../../packages/js/data/src/import/constants.ts
/**
 * Internal dependencies
 */
var import_constants_STORE_NAME = 'wc/admin/import';
;// CONCATENATED MODULE: ../../packages/js/data/src/import/selectors.ts











function import_selectors_ownKeys(e, r) {
  var t = Object.keys(e);
  if (Object.getOwnPropertySymbols) {
    var o = Object.getOwnPropertySymbols(e);
    r && (o = o.filter(function (r) {
      return Object.getOwnPropertyDescriptor(e, r).enumerable;
    })), t.push.apply(t, o);
  }
  return t;
}
function import_selectors_objectSpread(e) {
  for (var r = 1; r < arguments.length; r++) {
    var t = null != arguments[r] ? arguments[r] : {};
    r % 2 ? import_selectors_ownKeys(Object(t), !0).forEach(function (r) {
      (0,defineProperty/* default */.A)(e, r, t[r]);
    }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(e, Object.getOwnPropertyDescriptors(t)) : import_selectors_ownKeys(Object(t)).forEach(function (r) {
      Object.defineProperty(e, r, Object.getOwnPropertyDescriptor(t, r));
    });
  }
  return e;
}
/**
 * Internal dependencies
 */

var getImportStarted = function getImportStarted(state) {
  var activeImport = state.activeImport,
    lastImportStartTimestamp = state.lastImportStartTimestamp;
  return {
    activeImport: activeImport,
    lastImportStartTimestamp: lastImportStartTimestamp
  } || {};
};
var getFormSettings = function getFormSettings(state) {
  var period = state.period,
    skipPrevious = state.skipPrevious;
  return {
    period: period,
    skipPrevious: skipPrevious
  } || {};
};
var getImportStatus = function getImportStatus(state, query) {
  var stringifiedQuery = JSON.stringify(query);
  return state.importStatus[stringifiedQuery] || {};
};
var getImportTotals = function getImportTotals(state, query) {
  var importTotals = state.importTotals,
    lastImportStartTimestamp = state.lastImportStartTimestamp;
  var stringifiedQuery = JSON.stringify(query);
  return import_selectors_objectSpread(import_selectors_objectSpread({}, importTotals[stringifiedQuery]), {}, {
    lastImportStartTimestamp: lastImportStartTimestamp
  }) || {};
};
var getImportError = function getImportError(state, query) {
  var stringifiedQuery = JSON.stringify(query);
  return state.errors[stringifiedQuery] || false;
};
;// CONCATENATED MODULE: ../../packages/js/data/src/import/action-types.ts
var import_action_types_TYPES = {
  SET_IMPORT_DATE: 'SET_IMPORT_DATE',
  SET_IMPORT_ERROR: 'SET_IMPORT_ERROR',
  SET_IMPORT_PERIOD: 'SET_IMPORT_PERIOD',
  SET_IMPORT_STARTED: 'SET_IMPORT_STARTED',
  SET_IMPORT_STATUS: 'SET_IMPORT_STATUS',
  SET_IMPORT_TOTALS: 'SET_IMPORT_TOTALS',
  SET_SKIP_IMPORTED: 'SET_SKIP_IMPORTED'
};
/* harmony default export */ const import_action_types = (import_action_types_TYPES);
;// CONCATENATED MODULE: ../../packages/js/data/src/import/actions.ts


/**
 * External dependencies
 */


/**
 * Internal dependencies
 */

function setImportStarted(activeImport) {
  return {
    type: import_action_types.SET_IMPORT_STARTED,
    activeImport: activeImport
  };
}
function setImportPeriod(date, dateModified) {
  if (!dateModified) {
    return {
      type: import_action_types.SET_IMPORT_PERIOD,
      date: date
    };
  }
  return {
    type: import_action_types.SET_IMPORT_DATE,
    date: date
  };
}
function setSkipPrevious(skipPrevious) {
  return {
    type: import_action_types.SET_SKIP_IMPORTED,
    skipPrevious: skipPrevious
  };
}
function setImportStatus(query, importStatus) {
  return {
    type: import_action_types.SET_IMPORT_STATUS,
    importStatus: importStatus,
    query: query
  };
}
function setImportTotals(query, importTotals) {
  return {
    type: import_action_types.SET_IMPORT_TOTALS,
    importTotals: importTotals,
    query: query
  };
}
function setImportError(queryOrPath, error) {
  return {
    type: import_action_types.SET_IMPORT_ERROR,
    error: error,
    query: queryOrPath
  };
}
function updateImportation(path) {
  var importStarted = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : false;
  return /*#__PURE__*/regenerator_default().mark(function _callee() {
    var response;
    return regenerator_default().wrap(function _callee$(_context) {
      while (1) switch (_context.prev = _context.next) {
        case 0:
          _context.next = 2;
          return setImportStarted(importStarted);
        case 2:
          _context.prev = 2;
          _context.next = 5;
          return (0,data_controls_build_module/* apiFetch */.nr)({
            path: path,
            method: 'POST'
          });
        case 5:
          response = _context.sent;
          return _context.abrupt("return", response);
        case 9:
          _context.prev = 9;
          _context.t0 = _context["catch"](2);
          _context.next = 13;
          return setImportError(path, _context.t0);
        case 13:
          throw _context.t0;
        case 14:
        case "end":
          return _context.stop();
      }
    }, _callee, null, [[2, 9]]);
  })();
}
;// CONCATENATED MODULE: ../../packages/js/data/src/import/resolvers.ts



var import_resolvers_marked = /*#__PURE__*/regenerator_default().mark(resolvers_getImportStatus),
  import_resolvers_marked2 = /*#__PURE__*/regenerator_default().mark(resolvers_getImportTotals);
/**
 * External dependencies
 */




/**
 * Internal dependencies
 */


function resolvers_getImportStatus(query) {
  var url, response;
  return regenerator_default().wrap(function getImportStatus$(_context) {
    while (1) switch (_context.prev = _context.next) {
      case 0:
        _context.prev = 0;
        url = (0,add_query_args/* addQueryArgs */.F)("".concat(NAMESPACE, "/reports/import/status"), (0,esm_typeof/* default */.A)(query) === 'object' ? (0,lodash.omit)(query, ['timestamp']) : {});
        _context.next = 4;
        return (0,data_controls_build_module/* apiFetch */.nr)({
          path: url
        });
      case 4:
        response = _context.sent;
        _context.next = 7;
        return setImportStatus(query, response);
      case 7:
        _context.next = 13;
        break;
      case 9:
        _context.prev = 9;
        _context.t0 = _context["catch"](0);
        _context.next = 13;
        return setImportError(query, _context.t0);
      case 13:
      case "end":
        return _context.stop();
    }
  }, import_resolvers_marked, null, [[0, 9]]);
}
function resolvers_getImportTotals(query) {
  var url, response;
  return regenerator_default().wrap(function getImportTotals$(_context2) {
    while (1) switch (_context2.prev = _context2.next) {
      case 0:
        _context2.prev = 0;
        url = (0,add_query_args/* addQueryArgs */.F)("".concat(NAMESPACE, "/reports/import/totals"), query);
        _context2.next = 4;
        return (0,data_controls_build_module/* apiFetch */.nr)({
          path: url
        });
      case 4:
        response = _context2.sent;
        _context2.next = 7;
        return setImportTotals(query, response);
      case 7:
        _context2.next = 13;
        break;
      case 9:
        _context2.prev = 9;
        _context2.t0 = _context2["catch"](0);
        _context2.next = 13;
        return setImportError(query, _context2.t0);
      case 13:
      case "end":
        return _context2.stop();
    }
  }, import_resolvers_marked2, null, [[0, 9]]);
}
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.date.now.js
var es_date_now = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.date.now.js");
;// CONCATENATED MODULE: ../../packages/js/data/src/import/reducer.ts













function import_reducer_ownKeys(e, r) {
  var t = Object.keys(e);
  if (Object.getOwnPropertySymbols) {
    var o = Object.getOwnPropertySymbols(e);
    r && (o = o.filter(function (r) {
      return Object.getOwnPropertyDescriptor(e, r).enumerable;
    })), t.push.apply(t, o);
  }
  return t;
}
function import_reducer_objectSpread(e) {
  for (var r = 1; r < arguments.length; r++) {
    var t = null != arguments[r] ? arguments[r] : {};
    r % 2 ? import_reducer_ownKeys(Object(t), !0).forEach(function (r) {
      (0,defineProperty/* default */.A)(e, r, t[r]);
    }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(e, Object.getOwnPropertyDescriptors(t)) : import_reducer_ownKeys(Object(t)).forEach(function (r) {
      Object.defineProperty(e, r, Object.getOwnPropertyDescriptor(t, r));
    });
  }
  return e;
}
/**
 * External dependencies
 */


/**
 * Internal dependencies
 */

var import_reducer_reducer = function reducer() {
  var state = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {
    activeImport: false,
    importStatus: {},
    importTotals: {},
    errors: {},
    lastImportStartTimestamp: 0,
    period: {
      date: moment_default()().format((0,i18n_build_module.__)('MM/DD/YYYY', 'woocommerce')),
      label: 'all'
    },
    skipPrevious: true
  };
  var action = arguments.length > 1 ? arguments[1] : undefined;
  switch (action.type) {
    case import_action_types.SET_IMPORT_STARTED:
      var activeImport = action.activeImport;
      state = import_reducer_objectSpread(import_reducer_objectSpread({}, state), {}, {
        activeImport: activeImport,
        lastImportStartTimestamp: activeImport ? Date.now() : state.lastImportStartTimestamp
      });
      break;
    case import_action_types.SET_IMPORT_PERIOD:
      state = import_reducer_objectSpread(import_reducer_objectSpread({}, state), {}, {
        period: import_reducer_objectSpread(import_reducer_objectSpread({}, state.period), {}, {
          label: action.date
        }),
        activeImport: false
      });
      break;
    case import_action_types.SET_IMPORT_DATE:
      state = import_reducer_objectSpread(import_reducer_objectSpread({}, state), {}, {
        period: {
          date: action.date,
          label: 'custom'
        },
        activeImport: false
      });
      break;
    case import_action_types.SET_SKIP_IMPORTED:
      state = import_reducer_objectSpread(import_reducer_objectSpread({}, state), {}, {
        skipPrevious: action.skipPrevious,
        activeImport: false
      });
      break;
    case import_action_types.SET_IMPORT_STATUS:
      var query = action.query,
        importStatus = action.importStatus;
      state = import_reducer_objectSpread(import_reducer_objectSpread({}, state), {}, {
        importStatus: import_reducer_objectSpread(import_reducer_objectSpread({}, state.importStatus), {}, (0,defineProperty/* default */.A)({}, JSON.stringify(query), importStatus)),
        errors: import_reducer_objectSpread(import_reducer_objectSpread({}, state.errors), {}, (0,defineProperty/* default */.A)({}, JSON.stringify(query), false))
      });
      break;
    case import_action_types.SET_IMPORT_TOTALS:
      state = import_reducer_objectSpread(import_reducer_objectSpread({}, state), {}, {
        importTotals: import_reducer_objectSpread(import_reducer_objectSpread({}, state.importTotals), {}, (0,defineProperty/* default */.A)({}, JSON.stringify(action.query), action.importTotals))
      });
      break;
    case import_action_types.SET_IMPORT_ERROR:
      state = import_reducer_objectSpread(import_reducer_objectSpread({}, state), {}, {
        errors: import_reducer_objectSpread(import_reducer_objectSpread({}, state.errors), {}, (0,defineProperty/* default */.A)({}, JSON.stringify(action.query), action.error))
      });
      break;
  }
  return state;
};
/* harmony default export */ const import_reducer = (import_reducer_reducer);
;// CONCATENATED MODULE: ../../packages/js/data/src/import/index.ts
/**
 * External dependencies
 */


/**
 * Internal dependencies
 */






(0,data_build_module/* registerStore */.ti)(import_constants_STORE_NAME, {
  reducer: import_reducer,
  actions: import_actions_namespaceObject,
  controls: data_controls_build_module/* controls */.ne,
  selectors: import_selectors_namespaceObject,
  resolvers: import_resolvers_namespaceObject
});
var IMPORT_STORE_NAME = (/* unused pure expression or super */ null && (STORE_NAME));
;// CONCATENATED MODULE: ../../packages/js/data/src/index.ts
/**
 * External dependencies
 */


// Export store names



























// Export hooks










// Export utils




// Export constants





// Export types


















/**
 * Internal dependencies
 */

/**
 * Internal dependencies
 */

// As we add types to all the package selectors we can fill out these unknown types with real ones. See one
// of the already typed selectors for an example of how you can do this.

// Other exports











/***/ }),

/***/ "../../packages/js/tracks/src/index.ts":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {


// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  yM: () => (/* binding */ recordEvent)
});

// UNUSED EXPORTS: bumpStat, queueRecordEvent, recordPageView

// EXTERNAL MODULE: ../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/toConsumableArray.js + 2 modules
var toConsumableArray = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/toConsumableArray.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/typeof.js
var esm_typeof = __webpack_require__("../../node_modules/.pnpm/@babel+runtime@7.23.5/node_modules/@babel/runtime/helpers/esm/typeof.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.keys.js
var es_object_keys = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.keys.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.is-array.js
var es_array_is_array = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.is-array.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.slice.js
var es_array_slice = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.slice.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.for-each.js
var es_array_for_each = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.for-each.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.to-string.js
var es_object_to_string = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.to-string.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/web.dom-collections.for-each.js
var web_dom_collections_for_each = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/web.dom-collections.for-each.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.symbol.js
var es_symbol = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.symbol.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.filter.js
var es_array_filter = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.filter.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.get-own-property-descriptor.js
var es_object_get_own_property_descriptor = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.get-own-property-descriptor.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.get-own-property-descriptors.js
var es_object_get_own_property_descriptors = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.get-own-property-descriptors.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.define-properties.js
var es_object_define_properties = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.define-properties.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.define-property.js
var es_object_define_property = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.define-property.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/debug@4.3.4_supports-color@8.1.1/node_modules/debug/src/browser.js
var browser = __webpack_require__("../../node_modules/.pnpm/debug@4.3.4_supports-color@8.1.1/node_modules/debug/src/browser.js");
var browser_default = /*#__PURE__*/__webpack_require__.n(browser);
;// CONCATENATED MODULE: ../../packages/js/tracks/src/utils.js
var utils_isDevelopmentMode = "production" === 'development';
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.iterator.js
var es_array_iterator = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.iterator.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.string.iterator.js
var es_string_iterator = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.string.iterator.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/web.dom-collections.iterator.js
var web_dom_collections_iterator = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/web.dom-collections.iterator.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/web.url-search-params.js
var web_url_search_params = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/web.url-search-params.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.concat.js
var es_array_concat = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.array.concat.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.entries.js
var es_object_entries = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.object.entries.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.date.to-string.js
var es_date_to_string = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.date.to-string.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.regexp.to-string.js
var es_regexp_to_string = __webpack_require__("../../node_modules/.pnpm/core-js@3.34.0/node_modules/core-js/modules/es.regexp.to-string.js");
;// CONCATENATED MODULE: ../../packages/js/tracks/src/stats.ts













/**
 * External dependencies
 */


/**
 * Internal dependencies
 */


/**
 * Module variables
 */
var tracksDebug = browser_default()('wc-admin:tracks:stats');
var GROUP_PREFIX = 'x_woocommerce-';

/**
 * Builds a query parameters from the given group and name parameters.
 *
 * This will automatically add the prefix `x_woocommerce-` to the group name.
 *
 * @param {Record<string, string> | string} group  - The group of stats or a single stat name.
 * @param {string}                          [name] - The name of the stat if group is a string.
 *
 * @return {URLSearchParams} The constructed query.
 */
function buildQueryParams(group, name) {
  var params = new URLSearchParams();
  params.append('v', 'wpcom-no-pv');
  if (_typeof(group) !== 'object') {
    params.append("".concat(GROUP_PREFIX).concat(group), name);
  } else {
    Object.entries(group).forEach(function (_ref) {
      var _ref2 = _slicedToArray(_ref, 2),
        key = _ref2[0],
        value = _ref2[1];
      params.append("".concat(GROUP_PREFIX).concat(key), value);
    });
  }

  // Add a random number to the query string to avoid caching.
  params.append('t', Math.random().toString());
  return params;
}

/**
 * Bumps a stat or group of stats.
 *
 * @param {Record<string, string> | string} group  - The group of stats or a single stat name.
 * @param {string}                          [name] - The name of the stat if group is a string.
 * @return {boolean} True if the stat was successfully bumped, false otherwise.
 */
function bumpStat(group) {
  var name = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : '';
  if (_typeof(group) === 'object') {
    tracksDebug('Bumping stats %o', group);
  } else {
    tracksDebug('Bumping stat %s:%s', group, name);
    if (!name) {
      tracksDebug('No stat name provided for group %s', group);
      return false;
    }
  }
  var shouldBumpStat = !isDevelopmentMode && !!window.wcTracks && !!window.wcTracks.isEnabled;
  if (!shouldBumpStat) {
    return false;
  }
  var params = buildQueryParams(group, name);
  new window.Image().src = "".concat(document.location.protocol, "//pixel.wp.com/g.gif?").concat(params.toString());
  return true;
}
;// CONCATENATED MODULE: ../../packages/js/tracks/src/index.ts



function ownKeys(e, r) {
  var t = Object.keys(e);
  if (Object.getOwnPropertySymbols) {
    var o = Object.getOwnPropertySymbols(e);
    r && (o = o.filter(function (r) {
      return Object.getOwnPropertyDescriptor(e, r).enumerable;
    })), t.push.apply(t, o);
  }
  return t;
}
function _objectSpread(e) {
  for (var r = 1; r < arguments.length; r++) {
    var t = null != arguments[r] ? arguments[r] : {};
    r % 2 ? ownKeys(Object(t), !0).forEach(function (r) {
      _defineProperty(e, r, t[r]);
    }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(e, Object.getOwnPropertyDescriptors(t)) : ownKeys(Object(t)).forEach(function (r) {
      Object.defineProperty(e, r, Object.getOwnPropertyDescriptor(t, r));
    });
  }
  return e;
}












/**
 * External dependencies
 */


/**
 * Internal dependencies
 */



/**
 * Module variables
 */
var src_tracksDebug = browser_default()('wc-admin:tracks');
var isRecordEventArgs = function isRecordEventArgs(args) {
  return args.length === 2 && typeof args[0] === 'string';
};

/**
 * Record an event to Tracks
 *
 * @param {string} eventName       The name of the event to record, don't include the wcadmin_ prefix
 * @param {Object} eventProperties event properties to include in the event
 */
function recordEvent(eventName, eventProperties) {
  src_tracksDebug('recordevent %s %o', 'wcadmin_' + eventName, eventProperties, {
    _tqk: window._tkq,
    shouldRecord: !utils_isDevelopmentMode && !!window._tkq && !!window.wcTracks && !!window.wcTracks.isEnabled
  });
  if (!window.wcTracks || typeof window.wcTracks.recordEvent !== 'function') {
    return false;
  }
  if (utils_isDevelopmentMode) {
    window.wcTracks.validateEvent(eventName, eventProperties);
    return false;
  }
  window.wcTracks.recordEvent(eventName, eventProperties);
}
var tracksQueue = {
  localStorageKey: function localStorageKey() {
    return 'tracksQueue';
  },
  clear: function clear() {
    if (!window.localStorage) {
      return;
    }
    window.localStorage.removeItem(tracksQueue.localStorageKey());
  },
  get: function get() {
    if (!window.localStorage) {
      return [];
    }
    var items = window.localStorage.getItem(tracksQueue.localStorageKey());
    var parsedJSONItems = items ? JSON.parse(items) : [];
    var arrayItems = Array.isArray(parsedJSONItems) ? parsedJSONItems : [];
    return arrayItems;
  },
  add: function add() {
    for (var _len = arguments.length, args = new Array(_len), _key = 0; _key < _len; _key++) {
      args[_key] = arguments[_key];
    }
    if (!window.localStorage) {
      // If unable to queue, run it now.
      src_tracksDebug('Unable to queue, running now', {
        args: args
      });
      if (isRecordEventArgs(args)) {
        recordEvent.apply(void 0, args);
      } else {
        src_tracksDebug('Invalid args', args);
      }
      return;
    }
    var items = tracksQueue.get();
    var newItem = {
      args: args
    };
    items.push(newItem);
    items = items.slice(-100); // Upper limit.

    src_tracksDebug('Adding new item to queue.', newItem);
    window.localStorage.setItem(tracksQueue.localStorageKey(), JSON.stringify(items));
  },
  process: function process() {
    if (!window.localStorage) {
      return; // Not possible.
    }
    var items = tracksQueue.get();
    tracksQueue.clear();
    src_tracksDebug('Processing items in queue.', items);
    items.forEach(function (item) {
      if ((0,esm_typeof/* default */.A)(item) === 'object') {
        src_tracksDebug('Processing item in queue.', item);
        var args = item.args;
        if (isRecordEventArgs(args)) {
          recordEvent.apply(void 0, (0,toConsumableArray/* default */.A)(args));
        } else {
          src_tracksDebug('Invalid item args', item.args);
        }
      }
    });
  }
};

/**
 * Queue a tracks event.
 *
 * This allows you to delay tracks  events that would otherwise cause a race condition.
 * For example, when we trigger `wcadmin_tasklist_appearance_continue_setup` we're simultaneously moving the user to a new page via
 * `window.location`. This is an example of a race condition that should be avoided by enqueueing the event,
 * and therefore running it on the next pageview.
 *
 * @param {string} eventName       The name of the event to record, don't include the wcadmin_ prefix
 * @param {Object} eventProperties event properties to include in the event
 */

function queueRecordEvent(eventName, eventProperties) {
  tracksQueue.add(eventName, eventProperties);
}

/**
 * Record a page view to Tracks
 *
 * @param {string} path            the page/path to record a page view for
 * @param {Object} extraProperties extra event properties to include in the event
 */

function recordPageView(path, extraProperties) {
  if (!path) {
    return;
  }
  recordEvent('page_view', _objectSpread({
    path: path
  }, extraProperties));

  // Process queue.
  tracksQueue.process();
}

/***/ })

}]);