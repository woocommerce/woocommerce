export type FraudPreventionSetting = {
	block: boolean;
	enabled: boolean;
};

export type OrderItemsThresholdSetting = {
	min_items: string | number | null;
	max_items: string | number | null;
};

export type PurchasePriceThresholdSetting = {
	min_amount: string | number | null;
	max_amount: string | number | null;
};

export type FraudPreventionOrderItemsThresholdSetting = FraudPreventionSetting &
	OrderItemsThresholdSetting;

export type FraudPreventionPurchasePriceThresholdSetting =
	FraudPreventionSetting & PurchasePriceThresholdSetting;

export type FraudPreventionSettings =
	| FraudPreventionSetting
	| FraudPreventionOrderItemsThresholdSetting
	| FraudPreventionPurchasePriceThresholdSetting;

export type ProtectionSettingsUI = Record< string, FraudPreventionSettings >;

export type FraudProtectionSettingsSingleCheck = {
	key: string;
	value: string | number | boolean | null;
	operator: string;
};

export type FraudProtectionSettingsMultipleChecks = {
	operator: string;
	checks: FraudProtectionSettingsSingleCheck[];
};

export type FraudProtectionSettingsCheck =
	| FraudProtectionSettingsSingleCheck
	| FraudProtectionSettingsMultipleChecks
	| null;

export type FraudProtectionRule = {
	key: string;
	outcome: string;
	check: FraudProtectionSettingsCheck;
};

export type FraudProtectionEnvironment = {
	storeCurrency: string;
	isReviewFeatureActive: boolean;
	allowedCountriesType: string;
	settingCountries: string[];
};

export type FraudProtectionReadEnvironment = {
	isReviewFeatureActive: boolean;
	isAvsFailureDeclineEnabled: boolean;
};

export const isFraudProtectionSettingsSingleCheck = (
	check: FraudProtectionSettingsCheck
): check is FraudProtectionSettingsSingleCheck =>
	Boolean( check && ( check as FraudProtectionSettingsSingleCheck ).key );

export const isOrderItemsThresholdSetting = (
	setting: FraudPreventionSettings
): setting is FraudPreventionOrderItemsThresholdSetting =>
	( setting as FraudPreventionOrderItemsThresholdSetting ).min_items !==
	undefined;

export const isPurchasePriceThresholdSetting = (
	setting: FraudPreventionSettings
): setting is FraudPreventionPurchasePriceThresholdSetting =>
	( setting as FraudPreventionPurchasePriceThresholdSetting ).min_amount !==
	undefined;
