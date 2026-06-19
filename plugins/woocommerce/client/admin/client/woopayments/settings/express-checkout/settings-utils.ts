export type SettingsRecord = Record< string, unknown >;

export const asSettingsRecord = ( value: unknown ): SettingsRecord =>
	value && typeof value === 'object' ? ( value as SettingsRecord ) : {};

export const asString = ( value: unknown, fallback = '' ) =>
	typeof value === 'string' ? value : fallback;

export const isExpressCheckoutInPaymentMethodsListSupported = (
	settings: SettingsRecord
) => settings.is_express_checkout_in_payment_methods_list_supported !== false;
