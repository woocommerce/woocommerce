/**
 * Internal dependencies
 */
import {
	avsSupportedCountries,
	CheckOperators,
	Checks,
	Outcomes,
	Rules,
} from './constants';
import {
	FraudPreventionSetting,
	FraudProtectionEnvironment,
	FraudProtectionReadEnvironment,
	FraudProtectionRule,
	FraudProtectionSettingsCheck,
	FraudProtectionSettingsSingleCheck,
	isFraudProtectionSettingsSingleCheck,
	isOrderItemsThresholdSetting,
	isPurchasePriceThresholdSetting,
	ProtectionSettingsUI,
} from './types';

const getDefaultEnableConfig = (
	environment: FraudProtectionReadEnvironment
): FraudPreventionSetting => ( {
	enabled: false,
	block: ! environment.isReviewFeatureActive,
} );

export const isSellingToAvsSupportedLocations = ( {
	allowedCountriesType,
	settingCountries,
}: Pick<
	FraudProtectionEnvironment,
	'allowedCountriesType' | 'settingCountries'
> ): boolean => {
	switch ( allowedCountriesType ) {
		case 'all':
			return true;
		case 'specific':
			return avsSupportedCountries.some( ( country ) =>
				settingCountries.includes( country )
			);
		case 'all_except':
			return ! avsSupportedCountries.every( ( country ) =>
				settingCountries.includes( country )
			);
		default:
			return true;
	}
};

const buildFormattedRulePrice = (
	price: string | number | null,
	currency: string
): string => {
	const priceFloat = parseFloat( `${ price ?? '' }` );

	if ( isNaN( priceFloat ) ) {
		return '';
	}

	return [ Math.trunc( priceFloat * 100 ), currency.toUpperCase() ].join(
		'|'
	);
};

const readFormattedRulePrice = ( value: unknown ) => {
	if ( ! value ) {
		return '';
	}

	const [ amount ] = `${ value }`.split( '|' );

	return Number( amount ) / 100;
};

const getRuleBase = (
	setting: string,
	block: boolean
): FraudProtectionRule => ( {
	key: setting,
	outcome: block ? Outcomes.BLOCK : Outcomes.REVIEW,
	check: null,
} );

const maybeParseInteger = ( value: string | number | null ) => {
	const parsed = parseInt( `${ value ?? '' }`, 10 );

	return isNaN( parsed ) ? null : parsed;
};

const maybeParseFloat = ( value: string | number | null ) => {
	const parsed = parseFloat( `${ value ?? '' }` );

	return isNaN( parsed ) ? null : parsed;
};

const buildRuleset = (
	ruleKey: string,
	ruleConfiguration: FraudPreventionSetting,
	environment: FraudProtectionEnvironment
): FraudProtectionRule => {
	const shouldBlock = environment.isReviewFeatureActive
		? ruleConfiguration.block
		: true;
	const ruleBase = getRuleBase( ruleKey, shouldBlock );

	switch ( ruleKey ) {
		case Rules.RULE_AVS_VERIFICATION:
			ruleBase.check = {
				key: Checks.CHECK_AVS_MISMATCH,
				operator: CheckOperators.OPERATOR_EQUALS,
				value: true,
			};
			break;
		case Rules.RULE_ADDRESS_MISMATCH:
			ruleBase.check = {
				key: Checks.CHECK_BILLING_SHIPPING_ADDRESS_SAME,
				operator: CheckOperators.OPERATOR_EQUALS,
				value: false,
			};
			break;
		case Rules.RULE_INTERNATIONAL_IP_ADDRESS:
			ruleBase.check = {
				key: Checks.CHECK_IP_COUNTRY,
				operator:
					environment.allowedCountriesType === 'specific'
						? CheckOperators.OPERATOR_NOT_IN
						: CheckOperators.OPERATOR_IN,
				value: environment.settingCountries.join( '|' ).toLowerCase(),
			};
			break;
		case Rules.RULE_IP_ADDRESS_MISMATCH:
			ruleBase.check = {
				key: Checks.CHECK_IP_BILLING_COUNTRY_SAME,
				operator: CheckOperators.OPERATOR_EQUALS,
				value: false,
			};
			break;
		case Rules.RULE_ORDER_ITEMS_THRESHOLD:
			if ( isOrderItemsThresholdSetting( ruleConfiguration ) ) {
				const minItems = maybeParseInteger(
					ruleConfiguration.min_items
				);
				const maxItems = maybeParseInteger(
					ruleConfiguration.max_items
				);

				if ( minItems && maxItems ) {
					ruleBase.check = {
						operator: CheckOperators.LIST_OPERATOR_OR,
						checks: [
							{
								key: Checks.CHECK_ITEM_COUNT,
								operator: CheckOperators.OPERATOR_LT,
								value: minItems,
							},
							{
								key: Checks.CHECK_ITEM_COUNT,
								operator: CheckOperators.OPERATOR_GT,
								value: maxItems,
							},
						],
					};
				} else if ( minItems || maxItems ) {
					ruleBase.check = minItems
						? {
								key: Checks.CHECK_ITEM_COUNT,
								operator: CheckOperators.OPERATOR_LT,
								value: minItems,
						  }
						: {
								key: Checks.CHECK_ITEM_COUNT,
								operator: CheckOperators.OPERATOR_GT,
								value: maxItems,
						  };
				}
			}
			break;
		case Rules.RULE_PURCHASE_PRICE_THRESHOLD:
			if ( isPurchasePriceThresholdSetting( ruleConfiguration ) ) {
				const minAmount = maybeParseFloat(
					ruleConfiguration.min_amount
				);
				const maxAmount = maybeParseFloat(
					ruleConfiguration.max_amount
				);

				if ( minAmount && maxAmount ) {
					ruleBase.check = {
						operator: CheckOperators.LIST_OPERATOR_OR,
						checks: [
							{
								key: Checks.CHECK_ORDER_TOTAL,
								operator: CheckOperators.OPERATOR_LT,
								value: buildFormattedRulePrice(
									minAmount,
									environment.storeCurrency
								),
							},
							{
								key: Checks.CHECK_ORDER_TOTAL,
								operator: CheckOperators.OPERATOR_GT,
								value: buildFormattedRulePrice(
									maxAmount,
									environment.storeCurrency
								),
							},
						],
					};
				} else if ( minAmount || maxAmount ) {
					ruleBase.check = minAmount
						? {
								key: Checks.CHECK_ORDER_TOTAL,
								operator: CheckOperators.OPERATOR_LT,
								value: buildFormattedRulePrice(
									minAmount,
									environment.storeCurrency
								),
						  }
						: {
								key: Checks.CHECK_ORDER_TOTAL,
								operator: CheckOperators.OPERATOR_GT,
								value: buildFormattedRulePrice(
									maxAmount,
									environment.storeCurrency
								),
						  };
				}
			}
			break;
	}

	return ruleBase;
};

const findCheck = (
	current: FraudProtectionSettingsCheck,
	checkKey: string,
	operator: string
): FraudProtectionSettingsSingleCheck | false => {
	if (
		isFraudProtectionSettingsSingleCheck( current ) &&
		checkKey === current.key &&
		operator === current.operator
	) {
		return current;
	}

	if ( current && ! isFraudProtectionSettingsSingleCheck( current ) ) {
		for ( const check of current.checks ) {
			const result = findCheck( check, checkKey, operator );

			if ( result !== false ) {
				return result;
			}
		}
	}

	return false;
};

const getThresholdCheckValue = (
	check: FraudProtectionSettingsSingleCheck | false
) => {
	if ( check === false ) {
		return '';
	}

	return typeof check.value === 'string' || typeof check.value === 'number'
		? check.value
		: '';
};

export const writeRuleset = (
	config: ProtectionSettingsUI,
	environment: FraudProtectionEnvironment
): FraudProtectionRule[] =>
	Object.entries( config )
		.filter( ( [ , setting ] ) => setting.enabled )
		.map( ( [ key, setting ] ) =>
			buildRuleset( key, setting, environment )
		);

const getRuleBlockStatus = (
	outcome: string,
	environment: FraudProtectionReadEnvironment
) => {
	if ( ! environment.isReviewFeatureActive ) {
		return true;
	}

	return outcome === Outcomes.BLOCK;
};

export const readRuleset = (
	rulesetConfig: FraudProtectionRule[] | string,
	environment: FraudProtectionReadEnvironment
): ProtectionSettingsUI => {
	const defaultEnableConfig = getDefaultEnableConfig( environment );
	const defaultUIConfig: ProtectionSettingsUI = {
		[ Rules.RULE_AVS_VERIFICATION ]: {
			enabled: environment.isAvsFailureDeclineEnabled,
			block: environment.isAvsFailureDeclineEnabled,
		},
		[ Rules.RULE_ADDRESS_MISMATCH ]: { ...defaultEnableConfig },
		[ Rules.RULE_INTERNATIONAL_IP_ADDRESS ]: {
			...defaultEnableConfig,
		},
		[ Rules.RULE_IP_ADDRESS_MISMATCH ]: { ...defaultEnableConfig },
		[ Rules.RULE_ORDER_ITEMS_THRESHOLD ]: {
			...defaultEnableConfig,
			min_items: null,
			max_items: null,
		},
		[ Rules.RULE_PURCHASE_PRICE_THRESHOLD ]: {
			...defaultEnableConfig,
			min_amount: null,
			max_amount: null,
		},
	};
	const parsedUIConfig: ProtectionSettingsUI = {};

	if ( typeof rulesetConfig !== 'string' && Array.isArray( rulesetConfig ) ) {
		for ( const rule of rulesetConfig ) {
			switch ( rule.key ) {
				case Rules.RULE_AVS_VERIFICATION:
				case Rules.RULE_ADDRESS_MISMATCH:
				case Rules.RULE_INTERNATIONAL_IP_ADDRESS:
				case Rules.RULE_IP_ADDRESS_MISMATCH:
					parsedUIConfig[ rule.key ] = {
						enabled: true,
						block: getRuleBlockStatus( rule.outcome, environment ),
					};
					break;
				case Rules.RULE_ORDER_ITEMS_THRESHOLD: {
					const minItemsCheck = findCheck(
						rule.check,
						Checks.CHECK_ITEM_COUNT,
						CheckOperators.OPERATOR_LT
					);
					const maxItemsCheck = findCheck(
						rule.check,
						Checks.CHECK_ITEM_COUNT,
						CheckOperators.OPERATOR_GT
					);
					parsedUIConfig[ rule.key ] = {
						enabled: true,
						block: getRuleBlockStatus( rule.outcome, environment ),
						min_items: getThresholdCheckValue( minItemsCheck ),
						max_items: getThresholdCheckValue( maxItemsCheck ),
					};
					break;
				}
				case Rules.RULE_PURCHASE_PRICE_THRESHOLD: {
					const minAmountCheck = findCheck(
						rule.check,
						Checks.CHECK_ORDER_TOTAL,
						CheckOperators.OPERATOR_LT
					);
					const maxAmountCheck = findCheck(
						rule.check,
						Checks.CHECK_ORDER_TOTAL,
						CheckOperators.OPERATOR_GT
					);
					parsedUIConfig[ rule.key ] = {
						enabled: true,
						block: getRuleBlockStatus( rule.outcome, environment ),
						min_amount: readFormattedRulePrice(
							getThresholdCheckValue( minAmountCheck )
						),
						max_amount: readFormattedRulePrice(
							getThresholdCheckValue( maxAmountCheck )
						),
					};
					break;
				}
			}
		}
	}

	return {
		...defaultUIConfig,
		...parsedUIConfig,
	};
};
