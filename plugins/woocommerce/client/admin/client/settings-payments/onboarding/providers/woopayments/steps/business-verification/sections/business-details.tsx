/**
 * External dependencies
 */
import React from 'react';
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */
import { useBusinessVerificationContext } from '../data/business-verification-context';
import { useOnboardingContext } from '../../../data/onboarding-context';
import { Item } from '../../../components/custom-select-control';
import { OnboardingFields, BusinessType, MccsDisplayTreeItem } from '../types';
import {
	OnboardingGroupedSelectField,
	OnboardingSelectField,
} from '../components/form';
import {
	getAvailableCountries,
	getBusinessTypes,
	getMccsFlatList,
} from '../utils';
import strings from '../strings';

/**
 * Contains business and store details KYC logic.
 */
const BusinessDetails: React.FC = () => {
	const { data, setData } = useBusinessVerificationContext();
	const { currentStep, sessionEntryPoint } = useOnboardingContext();
	const countries = getAvailableCountries(
		currentStep?.context?.fields?.available_countries || {}
	);
	const businessTypes = getBusinessTypes(
		currentStep?.context?.fields?.business_types || []
	);
	const mccsFlatList = getMccsFlatList(
		( currentStep?.context?.fields?.mccs_display_tree ??
			[] ) as MccsDisplayTreeItem[]
	);

	const selectedCountry = businessTypes.find( ( country ) => {
		// Special case for Puerto Rico as it's considered a separate country in Core, but the business country should be US.
		if ( data.country === 'PR' ) {
			return country.key === 'US';
		}

		return country.key === data.country;
	} );

	// Reorder the country business types so company is always first, if it exists.
	const reorderedBusinessTypes = selectedCountry?.types.sort( ( a, b ) =>
		// eslint-disable-next-line no-nested-ternary
		a.key === 'company' ? -1 : b.key === 'company' ? 1 : 0
	);

	const selectedBusinessType = reorderedBusinessTypes?.find(
		( type ) => type.key === data.business_type
	);

	const selectedBusinessStructure =
		selectedBusinessType?.structures.length === 0 ||
		selectedBusinessType?.structures.find(
			( structure ) => structure.key === data[ 'company.structure' ]
		);

	const updateBusinessVerificationData = (
		selfAssessmentData: OnboardingFields
	): Promise< void > => {
		// Update the local state with the new data.
		setData( selfAssessmentData );

		const saveUrl = currentStep?.actions?.save?.href;
		if ( saveUrl ) {
			// Persist the data on the backend.
			return apiFetch( {
				url: saveUrl,
				method: 'POST',
				data: {
					self_assessment: selfAssessmentData,
					source: sessionEntryPoint,
				},
			} );
		}

		// Return a resolved promise to maintain consistency with the API.
		return Promise.resolve();
	};

	const handleTiedChange = (
		name: keyof OnboardingFields,
		selectedItem?: Item | null
	): Promise< void > => {
		let newData: OnboardingFields = {
			[ name ]: selectedItem?.key,
		};
		if ( name === 'business_type' ) {
			newData = { ...newData, 'company.structure': undefined };
		} else if ( name === 'country' ) {
			newData = { ...newData, business_type: undefined };
		}

		return updateBusinessVerificationData( newData );
	};

	const updateDataOnChange = (
		name: keyof OnboardingFields,
		selectedItem?: Item | null
	): Promise< void > => {
		const newData: OnboardingFields = {
			...data,
			[ name ]: selectedItem?.key,
		};

		return updateBusinessVerificationData( newData );
	};

	return (
		<>
			<span data-testid={ 'country-select' }>
				<OnboardingSelectField
					name="country"
					options={ countries }
					onChange={ handleTiedChange }
				/>
			</span>
			{ selectedCountry && selectedCountry.types.length > 0 && (
				<span data-testid={ 'business-type-select' }>
					<OnboardingSelectField
						name="business_type"
						options={ selectedCountry.types }
						onChange={ handleTiedChange }
					>
						{ ( item: Item & BusinessType ) => (
							<div>
								<div>{ item.name }</div>
								<div className="complete-business-info-task__option-description">
									{ item.description }
								</div>
							</div>
						) }
					</OnboardingSelectField>
				</span>
			) }
			{ selectedBusinessType &&
				selectedBusinessType.structures.length > 0 && (
					<span data-testid={ 'business-structure-select' }>
						<OnboardingSelectField
							name="company.structure"
							options={ selectedBusinessType.structures }
							onChange={ handleTiedChange }
						/>
					</span>
				) }
			{ selectedCountry &&
				selectedBusinessType &&
				selectedBusinessStructure && (
					<>
						<span data-testid={ 'mcc-select' }>
							<OnboardingGroupedSelectField
								name="mcc"
								options={ mccsFlatList }
								onChange={ updateDataOnChange }
								searchable
							/>
						</span>

						<span className={ 'woopayments-onboarding__tos' }>
							{ strings.tos }
						</span>
					</>
				) }
		</>
	);
};

export default BusinessDetails;
