/**
 * External dependencies
 */
import { set, toPairs } from 'lodash';

/**
 * Internal dependencies
 */
import { ListItem } from '../../../components/grouped-select-control';
import businessTypeDescriptionStrings from '../translations/descriptions';
import { Country, MccsDisplayTreeItem, OnboardingFields } from '../types';

export const fromDotNotation = (
	record: Record< string, unknown >
): Record< string, unknown > =>
	toPairs( record ).reduce( ( result, [ key, value ] ) => {
		return value !== null ? set( result, key, value ) : result;
	}, {} );

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

export const hasUndefinedValues = ( obj: Record< string, unknown > ): boolean =>
	Object.values( obj ).some( ( value ) => value === undefined );

/**
 * Get the MCC code for the selected industry.
 *
 * @return {string | undefined} The MCC code for the selected industry. Will return undefined if no industry is selected.
 */
export const getMccFromIndustry = (
	industryToMcc: string[]
): string | undefined => {
	const industry =
		window.wcSettings.admin?.onboarding?.profile?.industry?.[ 0 ];
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

export const isPreKycComplete = ( data: OnboardingFields ): boolean => {
	const requiredFields: ( keyof OnboardingFields )[] = [
		'business_type',
		'country',
		'mcc',
	];

	return requiredFields.every( ( field ) => Boolean( data[ field ] ) );
};

export const getComingSoonShareKey = () => {
	const {
		woocommerce_share_key: shareKey,
		woocommerce_coming_soon: comingSoon,
		woocommerce_private_link: privateLink,
	} = window.wcSettings?.admin?.siteVisibilitySettings || {};

	if ( comingSoon !== 'yes' || privateLink === 'no' ) {
		return '';
	}

	return shareKey ? '?woo-share=' + shareKey : '';
};
