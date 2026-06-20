/**
 * External dependencies
 */
import { useDispatch, useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { STORE_NAME } from './store';

const makeSettingHook = ( selectorName: string, actionName: string ) => () => {
	const actions = useDispatch( STORE_NAME );
	const value = useSelect( ( select ) =>
		select( STORE_NAME )[ selectorName ]()
	);

	return [ value, actions[ actionName ] ];
};

export const useSavedCards = makeSettingHook(
	'getIsSavedCardsEnabled',
	'updateIsSavedCardsEnabled'
);
export const useCardPresentEligible = makeSettingHook(
	'getIsCardPresentEligible',
	'updateIsCardPresentEligible'
);
export const useEnabledPaymentMethodIds = makeSettingHook(
	'getEnabledPaymentMethodIds',
	'updateEnabledPaymentMethodIds'
);
export const useDebugLog = makeSettingHook(
	'getIsDebugLogEnabled',
	'updateIsDebugLogEnabled'
);
export const useTestMode = makeSettingHook(
	'getIsTestModeEnabled',
	'updateIsTestModeEnabled'
);
export const useMultiCurrency = makeSettingHook(
	'getIsMultiCurrencyEnabled',
	'updateIsMultiCurrencyEnabled'
);
export const useAccountStatementDescriptor = makeSettingHook(
	'getAccountStatementDescriptor',
	'updateAccountStatementDescriptor'
);
export const useAccountStatementDescriptorKanji = makeSettingHook(
	'getAccountStatementDescriptorKanji',
	'updateAccountStatementDescriptorKanji'
);
export const useAccountStatementDescriptorKana = makeSettingHook(
	'getAccountStatementDescriptorKana',
	'updateAccountStatementDescriptorKana'
);
export const useAccountBusinessSupportEmail = makeSettingHook(
	'getAccountBusinessSupportEmail',
	'updateAccountBusinessSupportEmail'
);
export const useAccountBusinessSupportPhone = makeSettingHook(
	'getAccountBusinessSupportPhone',
	'updateAccountBusinessSupportPhone'
);
export const useDepositScheduleInterval = makeSettingHook(
	'getDepositScheduleInterval',
	'updateDepositScheduleInterval'
);
export const useDepositScheduleWeeklyAnchor = makeSettingHook(
	'getDepositScheduleWeeklyAnchor',
	'updateDepositScheduleWeeklyAnchor'
);
export const useDepositScheduleMonthlyAnchor = makeSettingHook(
	'getDepositScheduleMonthlyAnchor',
	'updateDepositScheduleMonthlyAnchor'
);
export const useManualCapture = makeSettingHook(
	'getIsManualCaptureEnabled',
	'updateIsManualCaptureEnabled'
);
export const useIsWCPayEnabled = makeSettingHook(
	'getIsWCPayEnabled',
	'updateIsWCPayEnabled'
);
export const usePaymentRequestEnabledSettings = makeSettingHook(
	'getIsPaymentRequestEnabled',
	'updateIsPaymentRequestEnabled'
);
export const useExpressCheckoutInPaymentMethodsEnabledSettings =
	makeSettingHook(
		'getIsExpressCheckoutInPaymentMethodsEnabled',
		'updateIsExpressCheckoutInPaymentMethodsEnabled'
	);
export const usePaymentRequestButtonType = makeSettingHook(
	'getPaymentRequestButtonType',
	'updatePaymentRequestButtonType'
);
export const usePaymentRequestButtonSize = makeSettingHook(
	'getPaymentRequestButtonSize',
	'updatePaymentRequestButtonSize'
);
export const usePaymentRequestButtonTheme = makeSettingHook(
	'getPaymentRequestButtonTheme',
	'updatePaymentRequestButtonTheme'
);
export const usePaymentRequestButtonBorderRadius = makeSettingHook(
	'getPaymentRequestButtonBorderRadius',
	'updatePaymentRequestButtonBorderRadius'
);
export const useWooPayEnabledSettings = makeSettingHook(
	'getIsWooPayEnabled',
	'updateIsWooPayEnabled'
);
export const useWooPayGlobalThemeSupportEnabledSettings = makeSettingHook(
	'getIsWooPayGlobalThemeSupportEnabled',
	'updateIsWooPayGlobalThemeSupportEnabled'
);
export const useWooPayCustomMessage = makeSettingHook(
	'getWooPayCustomMessage',
	'updateWooPayCustomMessage'
);
export const useWooPayStoreLogo = makeSettingHook(
	'getWooPayStoreLogo',
	'updateWooPayStoreLogo'
);
export const useCurrentProtectionLevel = makeSettingHook(
	'getCurrentProtectionLevel',
	'updateProtectionLevel'
);
export const useAdvancedFraudProtectionSettings = makeSettingHook(
	'getAdvancedFraudProtectionSettings',
	'updateAdvancedFraudProtectionSettings'
);
export const useAccountCommunicationsEmail = makeSettingHook(
	'getAccountCommunicationsEmail',
	'updateAccountCommunicationsEmail'
);

export const useAccountDomesticCurrency = () =>
	useSelect( ( select ) =>
		select( STORE_NAME ).getAccountDomesticCurrency()
	);

export const useSelectedPaymentMethod = () => {
	const { updateSelectedPaymentMethod } = useDispatch( STORE_NAME );
	const enabledPaymentMethodIds = useSelect( ( select ) =>
		select( STORE_NAME ).getEnabledPaymentMethodIds()
	);

	return [ enabledPaymentMethodIds, updateSelectedPaymentMethod ];
};

export const useUnselectedPaymentMethod = () => {
	const { updateUnselectedPaymentMethod } = useDispatch( STORE_NAME );
	const enabledPaymentMethodIds = useSelect( ( select ) =>
		select( STORE_NAME ).getEnabledPaymentMethodIds()
	);

	return [ enabledPaymentMethodIds, updateUnselectedPaymentMethod ];
};

export const useTestModeOnboarding = () =>
	useSelect(
		( select ) => select( STORE_NAME ).getIsTestModeOnboarding(),
		[]
	);

export const useDevMode = () =>
	useSelect( ( select ) => select( STORE_NAME ).getIsDevModeEnabled(), [] );

export const useWCPaySubscriptions = () => {
	const { updateIsWCPaySubscriptionsEnabled } = useDispatch( STORE_NAME );
	const isWCPaySubscriptionsEnabled = useSelect( ( select ) =>
		select( STORE_NAME ).getIsWCPaySubscriptionsEnabled()
	);
	const isWCPaySubscriptionsEligible = useSelect( ( select ) =>
		select( STORE_NAME ).getIsWCPaySubscriptionsEligible()
	);

	return [
		isWCPaySubscriptionsEnabled,
		isWCPaySubscriptionsEligible,
		updateIsWCPaySubscriptionsEnabled,
	];
};

export const useDepositDelayDays = () =>
	useSelect( ( select ) => select( STORE_NAME ).getDepositDelayDays(), [] );

export const useCompletedWaitingPeriod = () =>
	useSelect( ( select ) => select( STORE_NAME ).getCompletedWaitingPeriod() );

export const useDepositStatus = () =>
	useSelect( ( select ) => select( STORE_NAME ).getDepositStatus(), [] );

export const useDepositRestrictions = () =>
	useSelect( ( select ) => select( STORE_NAME ).getDepositRestrictions() );

export const useGetAvailablePaymentMethodIds = () =>
	useSelect( ( select ) =>
		select( STORE_NAME ).getAvailablePaymentMethodIds()
	);

export const useGetPaymentMethodStatuses = () =>
	useSelect( ( select ) => select( STORE_NAME ).getPaymentMethodStatuses() );

export const useGetDuplicatedPaymentMethodIds = () =>
	useSelect( ( select ) =>
		select( STORE_NAME ).getDuplicatedPaymentMethodIds()
	);

export const useDismissedDuplicatePaymentMethodNotices = () => {
	const { updateDismissedDuplicatePaymentMethodNotices } =
		useDispatch( STORE_NAME );
	const dismissedNotices = useSelect( ( select ) =>
		select( STORE_NAME ).getDismissedDuplicatePaymentMethodNotices()
	);

	return [ dismissedNotices, updateDismissedDuplicatePaymentMethodNotices ];
};

export const useGetAccountFees = () =>
	useSelect( ( select ) => select( STORE_NAME ).getAccountFees() );

export const useGetSettings = () =>
	useSelect( ( select ) => select( STORE_NAME ).getSettings() );

export const useSettings = () => {
	const { saveSettings } = useDispatch( STORE_NAME );
	const isSaving = useSelect( ( select ) =>
		select( STORE_NAME ).isSavingSettings()
	);
	const isDirty = useSelect( ( select ) => select( STORE_NAME ).isDirty() );
	const isLoading = useSelect( ( select ) => {
		select( STORE_NAME ).getSettings();
		const isResolving = select( STORE_NAME ).isResolving( 'getSettings' );
		const hasFinishedResolving =
			select( STORE_NAME ).hasFinishedResolution( 'getSettings' );

		return isResolving || ! hasFinishedResolving;
	} );

	return {
		isLoading,
		saveSettings,
		isSaving,
		isDirty,
	};
};

const makeExpressCheckoutLocationHook = ( methodId: string ) => () => {
	type ExpressCheckoutLocation = 'product' | 'cart' | 'checkout';
	const {
		updateExpressCheckoutProductMethods,
		updateExpressCheckoutCartMethods,
		updateExpressCheckoutCheckoutMethods,
	} = useDispatch( STORE_NAME );

	const productMethods = useSelect( ( select ) =>
		select( STORE_NAME ).getExpressCheckoutProductMethods()
	);
	const cartMethods = useSelect( ( select ) =>
		select( STORE_NAME ).getExpressCheckoutCartMethods()
	);
	const checkoutMethods = useSelect( ( select ) =>
		select( STORE_NAME ).getExpressCheckoutCheckoutMethods()
	);

	const methodsListMap: Record< ExpressCheckoutLocation, string[] > = {
		product: productMethods,
		cart: cartMethods,
		checkout: checkoutMethods,
	};
	const methodsUpdatersMap: Record<
		ExpressCheckoutLocation,
		( methods: string[] ) => void
	> = {
		product: updateExpressCheckoutProductMethods,
		cart: updateExpressCheckoutCartMethods,
		checkout: updateExpressCheckoutCheckoutMethods,
	};
	const enabledLocations = [
		productMethods.includes( methodId ) && 'product',
		cartMethods.includes( methodId ) && 'cart',
		checkoutMethods.includes( methodId ) && 'checkout',
	].filter( Boolean );
	const locationUpdater = (
		location: ExpressCheckoutLocation,
		isChecked: boolean
	) => {
		methodsUpdatersMap[ location ](
			isChecked
				? [ ...methodsListMap[ location ], methodId ]
				: methodsListMap[ location ].filter(
						( method: string ) => method !== methodId
				  )
		);
	};

	return [ enabledLocations, locationUpdater ];
};

export const usePaymentRequestLocations =
	makeExpressCheckoutLocationHook( 'payment_request' );
export const useWooPayLocations = makeExpressCheckoutLocationHook( 'woopay' );
export const useAmazonPayLocations =
	makeExpressCheckoutLocationHook( 'amazon_pay' );

const usePaymentMethodEnabled = ( methodId: string ) => {
	const { updateEnabledPaymentMethodIds } = useDispatch( STORE_NAME );
	const enabledPaymentMethodIds = useSelect( ( select ) =>
		select( STORE_NAME ).getEnabledPaymentMethodIds()
	);
	const isEnabled = enabledPaymentMethodIds.includes( methodId );
	const updateIsEnabled = ( shouldEnable: boolean ) => {
		const enabledMethodIds = new Set( enabledPaymentMethodIds );

		if ( shouldEnable ) {
			enabledMethodIds.add( methodId );
		} else {
			enabledMethodIds.delete( methodId );
		}

		updateEnabledPaymentMethodIds( Array.from( enabledMethodIds ) );
	};

	return [ isEnabled, updateIsEnabled ];
};

export const useAmazonPayEnabledSettings = () =>
	usePaymentMethodEnabled( 'amazon_pay' );

export const useLinkEnabledSettings = ( isWooPayBlockingLink?: boolean ) => {
	const [ isLinkEnabled, updateIsLinkEnabled ] = usePaymentMethodEnabled(
		'link'
	) as [ boolean, ( isEnabled: boolean ) => void ];
	const [ isWooPayEnabled ] = useWooPayEnabledSettings() as [ boolean ];
	const shouldBlockLink = isWooPayBlockingLink ?? isWooPayEnabled;

	const updateStripeLinkCheckout = ( isEnabled: boolean ) => {
		if ( shouldBlockLink ) {
			return;
		}

		if ( isEnabled ) {
			updateIsLinkEnabled( true );
		} else {
			updateIsLinkEnabled( false );
		}
	};

	return [ isLinkEnabled, updateStripeLinkCheckout, shouldBlockLink ];
};

export const useWooPayShowIncompatibilityNotice = () =>
	useSelect( ( select ) =>
		select( STORE_NAME ).getShowWooPayIncompatibilityNotice()
	);

export const useGetSavingError = () =>
	useSelect( ( select ) => select( STORE_NAME ).getSavingError(), [] );
