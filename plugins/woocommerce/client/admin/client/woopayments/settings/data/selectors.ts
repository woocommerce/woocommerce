type SettingsRootState = {
	settings?: {
		data?: Record< string, unknown >;
		isSaving?: boolean;
		isDirty?: boolean;
		savingError?: unknown;
	};
};

type SettingsSliceState = NonNullable< SettingsRootState[ 'settings' ] >;

const EMPTY_OBJ: Record< string, unknown > = {};
const EMPTY_ARR: string[] = [];

const getSettingsState = ( state?: SettingsRootState ): SettingsSliceState => {
	if ( ! state ) {
		return EMPTY_OBJ;
	}

	return state.settings || EMPTY_OBJ;
};

export const getSettings = ( state: SettingsRootState ) => {
	return getSettingsState( state ).data || EMPTY_OBJ;
};

export const getDuplicatedPaymentMethodIds = ( state: SettingsRootState ) => {
	return getSettings( state ).duplicated_payment_method_ids || EMPTY_OBJ;
};

export const getIsWCPayEnabled = ( state: SettingsRootState ) => {
	return getSettings( state ).is_wcpay_enabled || false;
};

export const getEnabledPaymentMethodIds = ( state: SettingsRootState ) => {
	return getSettings( state ).enabled_payment_method_ids || EMPTY_ARR;
};

export const getAvailablePaymentMethodIds = ( state: SettingsRootState ) => {
	return getSettings( state ).available_payment_method_ids || EMPTY_ARR;
};

export const getPaymentMethodStatuses = ( state: SettingsRootState ) => {
	return getSettings( state ).payment_method_statuses || EMPTY_OBJ;
};

export const isSavingSettings = ( state: SettingsRootState ) => {
	return getSettingsState( state ).isSaving || false;
};

export const isDirty = ( state: SettingsRootState ) => {
	return getSettingsState( state ).isDirty || false;
};

export const getAccountStatementDescriptor = ( state: SettingsRootState ) => {
	return getSettings( state ).account_statement_descriptor || '';
};

export const getAccountStatementDescriptorKanji = (
	state: SettingsRootState
) => {
	return getSettings( state ).account_statement_descriptor_kanji || '';
};

export const getAccountStatementDescriptorKana = (
	state: SettingsRootState
) => {
	return getSettings( state ).account_statement_descriptor_kana || '';
};

export const getAccountBusinessSupportEmail = ( state: SettingsRootState ) => {
	return getSettings( state ).account_business_support_email || '';
};

export const getAccountBusinessSupportPhone = ( state: SettingsRootState ) => {
	return getSettings( state ).account_business_support_phone || '';
};

export const getAccountDomesticCurrency = ( state: SettingsRootState ) => {
	return getSettings( state ).account_domestic_currency || '';
};

export const getDepositScheduleInterval = ( state: SettingsRootState ) => {
	return getSettings( state ).deposit_schedule_interval || '';
};

export const getDepositScheduleWeeklyAnchor = ( state: SettingsRootState ) => {
	return getSettings( state ).deposit_schedule_weekly_anchor || '';
};

export const getDepositScheduleMonthlyAnchor = ( state: SettingsRootState ) => {
	return getSettings( state ).deposit_schedule_monthly_anchor || '';
};

export const getDepositDelayDays = ( state: SettingsRootState ) => {
	return getSettings( state ).deposit_delay_days || '7';
};

export const getCompletedWaitingPeriod = ( state: SettingsRootState ) => {
	return getSettings( state ).deposit_completed_waiting_period || false;
};

export const getDepositStatus = ( state: SettingsRootState ) => {
	return getSettings( state ).deposit_status || '';
};

export const getDepositRestrictions = ( state: SettingsRootState ) => {
	return getSettings( state ).deposit_restrictions || '';
};

export const getIsManualCaptureEnabled = ( state: SettingsRootState ) => {
	return getSettings( state ).is_manual_capture_enabled || false;
};

export const getIsTestModeEnabled = ( state: SettingsRootState ) => {
	return getSettings( state ).is_test_mode_enabled || false;
};

export const getIsTestModeOnboarding = ( state: SettingsRootState ) => {
	return getSettings( state ).is_test_mode_onboarding || false;
};

export const getIsDevModeEnabled = ( state: SettingsRootState ) => {
	return getSettings( state ).is_dev_mode_enabled || false;
};

export const getIsPaymentRequestEnabled = ( state: SettingsRootState ) => {
	return getSettings( state ).is_payment_request_enabled || false;
};

export const getIsExpressCheckoutInPaymentMethodsEnabled = (
	state: SettingsRootState
) => {
	return (
		getSettings( state ).is_express_checkout_in_payment_methods_enabled ||
		false
	);
};

export const getIsDebugLogEnabled = ( state: SettingsRootState ) => {
	return getSettings( state ).is_debug_log_enabled || false;
};

export const getIsMultiCurrencyEnabled = ( state: SettingsRootState ) => {
	return getSettings( state ).is_multi_currency_enabled || false;
};

export const getPaymentRequestButtonType = ( state: SettingsRootState ) => {
	return getSettings( state ).payment_request_button_type || '';
};

export const getPaymentRequestButtonSize = ( state: SettingsRootState ) => {
	return getSettings( state ).payment_request_button_size || '';
};

export const getPaymentRequestButtonTheme = ( state: SettingsRootState ) => {
	return getSettings( state ).payment_request_button_theme || '';
};

export const getPaymentRequestButtonBorderRadius = (
	state: SettingsRootState
) => {
	const radius = getSettings( state ).payment_request_button_border_radius;

	if ( radius === 0 || radius === '0' || radius ) {
		return radius;
	}

	return 4;
};

export const getIsSavedCardsEnabled = ( state: SettingsRootState ) => {
	return getSettings( state ).is_saved_cards_enabled || false;
};

export const getSavingError = ( state: SettingsRootState ) => {
	return getSettingsState( state ).savingError;
};

export const getIsCardPresentEligible = ( state: SettingsRootState ) => {
	return getSettings( state ).is_card_present_eligible || false;
};

export const getIsWCPaySubscriptionsEnabled = ( state: SettingsRootState ) => {
	return getSettings( state ).is_wcpay_subscriptions_enabled || false;
};

export const getIsWCPaySubscriptionsEligible = ( state: SettingsRootState ) => {
	return getSettings( state ).is_wcpay_subscriptions_eligible || false;
};

export const getIsSubscriptionsPluginActive = ( state: SettingsRootState ) => {
	return getSettings( state ).is_subscriptions_plugin_active || false;
};

export const getIsWooPayEnabled = ( state: SettingsRootState ) => {
	return getSettings( state ).is_woopay_enabled || false;
};

export const getIsWooPayGlobalThemeSupportEnabled = (
	state: SettingsRootState
) => {
	return getSettings( state ).is_woopay_global_theme_support_enabled || false;
};

export const getWooPayCustomMessage = ( state: SettingsRootState ) => {
	return getSettings( state ).woopay_custom_message || '';
};

export const getWooPayStoreLogo = ( state: SettingsRootState ) => {
	return getSettings( state ).woopay_store_logo || '';
};

export const getCurrentProtectionLevel = ( state: SettingsRootState ) => {
	return getSettings( state ).current_protection_level || 'basic';
};

export const getAdvancedFraudProtectionSettings = (
	state: SettingsRootState
) => {
	return getSettings( state ).advanced_fraud_protection_settings || EMPTY_ARR;
};

export const getShowWooPayIncompatibilityNotice = (
	state: SettingsRootState
) => {
	return getSettings( state ).show_woopay_incompatibility_notice || false;
};

export const getAccountCommunicationsEmail = ( state: SettingsRootState ) => {
	return getSettings( state ).account_communications_email || '';
};

export const getExpressCheckoutProductMethods = (
	state: SettingsRootState
) => {
	return getSettings( state ).express_checkout_product_methods || EMPTY_ARR;
};

export const getExpressCheckoutCartMethods = ( state: SettingsRootState ) => {
	return getSettings( state ).express_checkout_cart_methods || EMPTY_ARR;
};

export const getExpressCheckoutCheckoutMethods = (
	state: SettingsRootState
) => {
	return getSettings( state ).express_checkout_checkout_methods || EMPTY_ARR;
};
