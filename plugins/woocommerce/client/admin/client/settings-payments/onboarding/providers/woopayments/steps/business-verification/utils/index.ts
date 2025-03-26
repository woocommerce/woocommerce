/**
 * External dependencies
 */
import { set, toPairs } from 'lodash';
import apiFetch from '@wordpress/api-fetch';
import { WC_ADMIN_NAMESPACE } from '@woocommerce/data';

/**
 * Internal dependencies
 */
import { ListItem } from '../../../components/grouped-select-control';
import businessTypeDescriptionStrings from '../translations/descriptions';
import {
	Country,
	MccsDisplayTreeItem,
	OnboardingFields,
	PoEligibleData,
	PoEligibleResponse,
	FinalizeOnboardingResponse,
} from '../types';

export const fromDotNotation = (
	record: Record< string, unknown >
): Record< string, unknown > =>
	toPairs( record ).reduce( ( result, [ key, value ] ) => {
		return value !== null ? set( result, key, value ) : result;
	}, {} );

const hasUndefinedValues = ( obj: Record< string, unknown > ): boolean =>
	Object.values( obj ).some( ( value ) => value === undefined );

export const getAvailableCountries = (
	countries: Record< string, string >
): Country[] =>
	Object.entries( countries || [] )
		.map( ( [ key, name ] ) => ( { key, name, types: [] } ) )
		.sort( ( a, b ) => a.name.localeCompare( b.name ) );

export const getBusinessTypes = ( data: Country[] ): Country[] => {
	return (
		( data || [] )
			.map( ( country ) => ( {
				...country,
				types: country.types.map( ( type ) => ( {
					...type,
					description: businessTypeDescriptionStrings[ country.key ]
						? businessTypeDescriptionStrings[ country.key ][
								type.key
						  ]
						: businessTypeDescriptionStrings.generic[ type.key ],
				} ) ),
			} ) )
			.sort( ( a, b ) => a.name.localeCompare( b.name ) ) || []
	);
};

/**
 * Make an API request to finalize the onboarding process.
 *
 * @param urlSource The source URL.
 */
export const finalizeOnboarding = async ( urlSource: string ) => {
	return await apiFetch< FinalizeOnboardingResponse >( {
		path: `${ WC_ADMIN_NAMESPACE }/settings/payments/woopayments/onboarding/step/business_verification/session/finish`,
		method: 'POST',
		data: {
			source: urlSource,
		},
	} );
};

/**
 * Make an API request to determine if the user is eligible for a PO account.
 *
 * @param onboardingFields The form data, used to determine eligibility.
 */
export const isPoEligible = async (
	onboardingFields: OnboardingFields
): Promise< boolean > => {
	// Check if any required property is undefined
	if (
		hasUndefinedValues( {
			country: onboardingFields.country,
			business_type: onboardingFields.business_type,
			mcc: onboardingFields.mcc,
			annual_revenue: onboardingFields.annual_revenue,
			go_live_timeframe: onboardingFields.go_live_timeframe,
		} )
	) {
		return false;
	}

	const eligibilityData: PoEligibleData = {
		location: onboardingFields.country as string,
		self_assessment: {
			country: onboardingFields.country as string,
			type: onboardingFields.business_type as string,
			mcc: onboardingFields.mcc as string,
			annual_revenue: onboardingFields.annual_revenue as string,
			go_live_timeframe: onboardingFields.go_live_timeframe as string,
		},
	};

	const response: PoEligibleResponse = await apiFetch( {
		path: `${ WC_ADMIN_NAMESPACE }/settings/payments/woopayments/onboarding/step/business_verification/check/po_eligible`,
		method: 'POST',
		data: eligibilityData,
	} );

	return response.result === 'eligible';
};

/**
 * Get the MCC code for the selected industry.
 *
 * @return {string | undefined} The MCC code for the selected industry. Will return undefined if no industry is selected.
 */
export const getMccFromIndustry = (
	industryToMcc: string[]
): string | undefined => {
	const industry = window.wcSettings.admin?.onboarding?.profile?.industry?.[ 0 ];
	if ( ! industry ) {
		return undefined;
	}

	return industryToMcc[ industry ];
};

export const getMccsFlatList = (
	industryToMcc: MccsDisplayTreeItem[]
): ListItem[] => {
	const data = industryToMcc;

	// Right now we support only two levels (top-level groups and items in those groups).
	// For safety, we will discard anything else like top-level items or sub-groups.
	const normalizedData = ( data || [] ).filter( ( group ) => {
		if ( ! group?.items ) {
			return false;
		}

		const groupItems =
			group.items?.filter( ( item ) => ! item?.items ) || [];

		return groupItems.length;
	} );

	return normalizedData.reduce( ( acc, group ): ListItem[] => {
		const groupItems =
			group.items?.map( ( item ): ListItem => {
				return {
					key: item.id,
					name: item.title,
					group: group.id,
					context: item?.keywords ? item.keywords.join( ' ' ) : '',
				};
			} ) || [];

		return [
			...acc,
			{
				key: group.id,
				name: group.title,
				items: groupItems.map( ( item ) => item.key ),
			},
			...groupItems,
		];
	}, [] as ListItem[] );
};
