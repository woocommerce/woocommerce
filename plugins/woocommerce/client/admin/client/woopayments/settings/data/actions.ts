/**
 * External dependencies
 */
import directApiFetch from '@wordpress/api-fetch';
import { dispatch, select } from '@wordpress/data';
import { apiFetch } from '@wordpress/data-controls';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import ACTION_TYPES from './action-types';
import { NAMESPACE } from './constants';
import { STORE_NAME } from './store-name';

type SettingsValues = Record< string, unknown >;
type SettingsSaveError = {
	server_error?: string;
	data?: {
		details?: unknown;
	};
};
type SettingsSaveResponse = {
	data?: SettingsValues & {
		payment_method_statuses?: unknown;
	};
	payment_method_statuses?: unknown;
};

function hasFieldLevelErrorDetails( error: SettingsSaveError ) {
	const details = error.data?.details;

	return (
		!! details &&
		typeof details === 'object' &&
		! Array.isArray( details ) &&
		Object.keys( details ).length > 0
	);
}

function updateSettingsValues( payload: SettingsValues ) {
	return {
		type: ACTION_TYPES.SET_SETTINGS_VALUES,
		payload,
	};
}

export function updateIsSavedCardsEnabled( isEnabled: boolean ) {
	return updateSettingsValues( { is_saved_cards_enabled: isEnabled } );
}

export function updateIsCardPresentEligible( isEnabled: boolean ) {
	return updateSettingsValues( { is_card_present_eligible: isEnabled } );
}

export function updatePaymentRequestButtonType( type: string ) {
	return updateSettingsValues( { payment_request_button_type: type } );
}

export function updatePaymentRequestButtonSize( size: string ) {
	return updateSettingsValues( { payment_request_button_size: size } );
}

export function updatePaymentRequestButtonTheme( theme: string ) {
	return updateSettingsValues( { payment_request_button_theme: theme } );
}

export function updatePaymentRequestButtonBorderRadius( radius: number ) {
	return updateSettingsValues( {
		payment_request_button_border_radius: radius,
	} );
}

export function updateSettings( data: SettingsValues ) {
	return {
		type: ACTION_TYPES.SET_SETTINGS,
		data,
	};
}

export function updateIsWCPayEnabled( isEnabled: boolean ) {
	return updateSettingsValues( { is_wcpay_enabled: isEnabled } );
}

export function updateIsPaymentRequestEnabled( isEnabled: boolean ) {
	return updateSettingsValues( { is_payment_request_enabled: isEnabled } );
}

export function updateIsExpressCheckoutInPaymentMethodsEnabled(
	isEnabled: boolean
) {
	return updateSettingsValues( {
		is_express_checkout_in_payment_methods_enabled: isEnabled,
	} );
}

export function updateEnabledPaymentMethodIds( methodIds: string[] ) {
	return updateSettingsValues( {
		enabled_payment_method_ids: [ ...methodIds ],
	} );
}

export function updateDismissedDuplicatePaymentMethodNotices(
	notices: Record< string, string[] >
) {
	return updateSettingsValues( {
		dismissed_duplicate_payment_method_notices: { ...notices },
	} );
}

export function updateIsSavingSettings( isSaving: boolean, error?: unknown ) {
	return {
		type: ACTION_TYPES.SET_IS_SAVING_SETTINGS,
		isSaving,
		error,
	};
}

export function updateSelectedPaymentMethod( id: string ) {
	return {
		type: ACTION_TYPES.SET_SELECTED_PAYMENT_METHOD,
		id,
	};
}

export function updateUnselectedPaymentMethod( id: string ) {
	return {
		type: ACTION_TYPES.SET_UNSELECTED_PAYMENT_METHOD,
		id,
	};
}

export function updateIsManualCaptureEnabled( isEnabled: boolean ) {
	return updateSettingsValues( { is_manual_capture_enabled: isEnabled } );
}

export function updateIsTestModeEnabled( isEnabled: boolean ) {
	return updateSettingsValues( { is_test_mode_enabled: isEnabled } );
}

export function updateIsDebugLogEnabled( isEnabled: boolean ) {
	return updateSettingsValues( { is_debug_log_enabled: isEnabled } );
}

export function updateIsMultiCurrencyEnabled( isEnabled: boolean ) {
	return updateSettingsValues( { is_multi_currency_enabled: isEnabled } );
}

export function updateIsWCPaySubscriptionsEnabled( isEnabled: boolean ) {
	return updateSettingsValues( {
		is_wcpay_subscriptions_enabled: isEnabled,
	} );
}

export function updateAccountStatementDescriptor( value: string ) {
	return updateSettingsValues( { account_statement_descriptor: value } );
}

export function updateAccountStatementDescriptorKanji( value: string ) {
	return updateSettingsValues( {
		account_statement_descriptor_kanji: value,
	} );
}

export function updateAccountStatementDescriptorKana( value: string ) {
	return updateSettingsValues( { account_statement_descriptor_kana: value } );
}

export function updateAccountBusinessSupportEmail( value: string ) {
	return updateSettingsValues( { account_business_support_email: value } );
}

export function updateAccountBusinessSupportPhone( value: string ) {
	return updateSettingsValues( { account_business_support_phone: value } );
}

export function updateDepositScheduleInterval( value: string ) {
	return updateSettingsValues( { deposit_schedule_interval: value } );
}

export function updateDepositScheduleWeeklyAnchor( value: string ) {
	return updateSettingsValues( { deposit_schedule_weekly_anchor: value } );
}

export function updateDepositScheduleMonthlyAnchor( value: string ) {
	return updateSettingsValues( {
		deposit_schedule_monthly_anchor:
			value === '' ? null : parseInt( value, 10 ),
	} );
}

export function* saveSettings(): Generator<
	unknown,
	boolean,
	SettingsSaveResponse
> {
	let error: SettingsSaveError | null = null;

	try {
		const settings = select( STORE_NAME ).getSettings();

		yield updateIsSavingSettings( true, null );

		const response = yield apiFetch( {
			path: `${ NAMESPACE }/settings`,
			method: 'post',
			data: settings,
		} );
		const responseData = response.data ?? response;

		yield updateSettings( {
			...settings,
			...responseData,
			payment_method_statuses:
				responseData.payment_method_statuses ??
				settings.payment_method_statuses,
		} );

		yield dispatch( 'core/notices' ).createSuccessNotice(
			__( 'Settings saved.', 'woocommerce' )
		);
	} catch ( e ) {
		error = e as SettingsSaveError;

		yield dispatch( 'core/notices' ).createErrorNotice(
			__( 'Error saving settings.', 'woocommerce' )
		);

		if ( error?.server_error && ! hasFieldLevelErrorDetails( error ) ) {
			yield dispatch( 'core/notices' ).createErrorNotice(
				error.server_error
			);
		}
	} finally {
		yield updateIsSavingSettings( false, error );
	}

	return error === null;
}

export function updateIsWooPayEnabled( isEnabled: boolean ) {
	return updateSettingsValues( { is_woopay_enabled: isEnabled } );
}

export function updateIsWooPayGlobalThemeSupportEnabled( isEnabled: boolean ) {
	return updateSettingsValues( {
		is_woopay_global_theme_support_enabled: isEnabled,
	} );
}

export function updateWooPayCustomMessage( message: string ) {
	return updateSettingsValues( { woopay_custom_message: message } );
}

export function updateWooPayStoreLogo( storeLogo: string ) {
	return updateSettingsValues( { woopay_store_logo: storeLogo } );
}

export function updateProtectionLevel( level: string ) {
	return updateSettingsValues( { current_protection_level: level } );
}

export function updateAdvancedFraudProtectionSettings( settings: unknown[] ) {
	return updateSettingsValues( {
		advanced_fraud_protection_settings: settings,
	} );
}

export function updateAccountCommunicationsEmail( email: string ) {
	return updateSettingsValues( { account_communications_email: email } );
}

export function updateExpressCheckoutProductMethods( methods: string[] ) {
	return updateSettingsValues( {
		express_checkout_product_methods: [ ...methods ],
	} );
}

export function updateExpressCheckoutCartMethods( methods: string[] ) {
	return updateSettingsValues( {
		express_checkout_cart_methods: [ ...methods ],
	} );
}

export function updateExpressCheckoutCheckoutMethods( methods: string[] ) {
	return updateSettingsValues( {
		express_checkout_checkout_methods: [ ...methods ],
	} );
}

export function saveOption( optionName: string, value: unknown ) {
	return directApiFetch( {
		path: `${ NAMESPACE }/settings/${ optionName }`,
		method: 'post',
		data: { value },
	} ).catch( () => {
		dispatch( 'core/notices' ).createErrorNotice(
			__( 'Error saving option', 'woocommerce' )
		);
	} );
}
