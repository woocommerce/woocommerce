export type SettingsRecord = Record< string, unknown >;
export type ExpressCheckoutFeatureFlagName =
	| 'woopay'
	| 'woopayExpressCheckout'
	| 'isDynamicCheckoutPlaceOrderButtonEnabled'
	| 'amazonPay';

export type ExpressCheckoutFeatureFlags = Record<
	ExpressCheckoutFeatureFlagName,
	boolean
>;

const DEFAULT_EXPRESS_CHECKOUT_FEATURE_FLAGS: ExpressCheckoutFeatureFlags = {
	woopay: true,
	woopayExpressCheckout: true,
	isDynamicCheckoutPlaceOrderButtonEnabled: true,
	amazonPay: true,
};

export const asSettingsRecord = ( value: unknown ): SettingsRecord =>
	value && typeof value === 'object' ? ( value as SettingsRecord ) : {};

export const asString = ( value: unknown, fallback = '' ) =>
	typeof value === 'string' ? value : fallback;

export const asStringArray = ( value: unknown ) =>
	Array.isArray( value )
		? value.filter( ( item ): item is string => typeof item === 'string' )
		: [];

const asBoolean = ( value: unknown, fallback: boolean ) =>
	typeof value === 'boolean' ? value : fallback;

export const getExpressCheckoutFeatureFlags = (
	settings: SettingsRecord
): ExpressCheckoutFeatureFlags => {
	const flags = asSettingsRecord( settings.feature_flags );

	return {
		woopay: asBoolean(
			flags.woopay,
			DEFAULT_EXPRESS_CHECKOUT_FEATURE_FLAGS.woopay
		),
		woopayExpressCheckout: asBoolean(
			flags.woopayExpressCheckout,
			DEFAULT_EXPRESS_CHECKOUT_FEATURE_FLAGS.woopayExpressCheckout
		),
		isDynamicCheckoutPlaceOrderButtonEnabled: asBoolean(
			flags.isDynamicCheckoutPlaceOrderButtonEnabled,
			DEFAULT_EXPRESS_CHECKOUT_FEATURE_FLAGS.isDynamicCheckoutPlaceOrderButtonEnabled
		),
		amazonPay: asBoolean(
			flags.amazonPay,
			DEFAULT_EXPRESS_CHECKOUT_FEATURE_FLAGS.amazonPay
		),
	};
};

export const isExpressFeatureEnabled = (
	settings: SettingsRecord,
	flagName: ExpressCheckoutFeatureFlagName,
	fallback = DEFAULT_EXPRESS_CHECKOUT_FEATURE_FLAGS[ flagName ]
) => {
	const flags = asSettingsRecord( settings.feature_flags );

	return asBoolean( flags[ flagName ], fallback );
};

export const isWooPayExpressCheckoutAvailable = (
	settings: SettingsRecord
) => {
	const flags = getExpressCheckoutFeatureFlags( settings );

	return flags.woopay && flags.woopayExpressCheckout;
};

export const isAmazonPayExpressCheckoutAvailable = (
	settings: SettingsRecord
) => {
	const flags = getExpressCheckoutFeatureFlags( settings );
	const availablePaymentMethodIds = asStringArray(
		settings.available_payment_method_ids
	);

	return (
		flags.amazonPay && availablePaymentMethodIds.includes( 'amazon_pay' )
	);
};

export const isExpressCheckoutInPaymentMethodsListSupported = (
	settings: SettingsRecord
) =>
	settings.is_express_checkout_in_payment_methods_list_supported !== false &&
	isExpressFeatureEnabled(
		settings,
		'isDynamicCheckoutPlaceOrderButtonEnabled'
	);
