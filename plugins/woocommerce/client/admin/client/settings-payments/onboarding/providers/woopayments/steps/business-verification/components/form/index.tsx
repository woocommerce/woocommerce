/**
 * External dependencies
 */
import React, { useState } from 'react';
import { Button } from '@wordpress/components';
import { isEmpty, mapValues } from 'lodash';

/**
 * Internal dependencies
 */
import { useOnboardingContext } from '../../../../data/onboarding-context';
import { useStepperContext } from '../stepper';
import { Item as SelectItem } from '../../../../components/custom-select-control';
import { ListItem as GroupedSelectItem } from '../../../../components/grouped-select-control';
import {
	GroupedSelectField,
	GroupedSelectFieldProps,
	SelectField,
	SelectFieldProps,
	TextField,
	TextFieldProps,
} from './fields';
import { useBusinessVerificationContext } from '../../data/business-verification-context';
import { OnboardingFields } from '../../types';
import { isPreKycComplete } from '../../utils';
import { completeSubStep } from '../../utils/actions';
import { useValidation } from '../../utils/validation';
import strings from '../../strings';
import './style.scss';
import { recordPaymentsOnboardingEvent } from '~/settings-payments/utils';

type OnboardingFormProps = {
	children: React.ReactNode;
};

export const OnboardingForm: React.FC< OnboardingFormProps > = ( {
	children,
} ) => {
	const { data, errors, touched, setTouched } =
		useBusinessVerificationContext();
	const { currentStep, sessionEntryPoint } = useOnboardingContext();
	const { nextStep } = useStepperContext();
	const [ isContinueButtonLoading, setIsContinueButtonLoading ] =
		useState( false );

	const handleContinue = (): Promise< void > => {
		if ( isEmpty( errors ) && isPreKycComplete( data ) ) {
			setIsContinueButtonLoading( true );

			// Complete the business sub-step.
			return completeSubStep(
				'business',
				currentStep?.actions?.save?.href ?? undefined,
				currentStep?.context?.sub_steps ?? {}
			)
				.then( () => {
					recordPaymentsOnboardingEvent(
						'woopayments_onboarding_modal_kyc_sub_step_completed',
						{
							sub_step_id: 'business',
							country: data.country || 'unknown',
							business_type: data.business_type || 'unknown',
							mcc: data.mcc || 'unknown',
							source: sessionEntryPoint,
						}
					);

					setIsContinueButtonLoading( false );

					return nextStep();
				} )
				.catch( () => {
					// Handle any errors that occur during the process.
					setIsContinueButtonLoading( false );
					// Error tracking is handled on the backend, so we don't need to do anything here.
				} );
		}

		// If there are validation errors, set all fields as touched to show validation errors.
		setTouched( mapValues( touched, () => true ) );

		// Return a resolved promise when there are errors.
		return Promise.resolve();
	};

	return (
		<form
			onSubmit={ async ( event ) => {
				event.preventDefault();
				await handleContinue();
			} }
		>
			{ children }
			<Button
				variant={ 'primary' }
				type="submit"
				className="stepper__cta"
				onClick={ () => {
					recordPaymentsOnboardingEvent(
						'woopayments_onboarding_modal_click',
						{
							step: currentStep?.id ?? 'unknown',
							sub_step_id: 'business',
							action: 'business_form_continue',
							source: sessionEntryPoint,
						}
					);
				} }
				isBusy={ isContinueButtonLoading }
				disabled={ isContinueButtonLoading }
			>
				{ strings.continue }
			</Button>
		</form>
	);
};

interface OnboardingTextFieldProps extends Partial< TextFieldProps > {
	name: keyof OnboardingFields;
}

export const OnboardingTextField: React.FC< OnboardingTextFieldProps > = (
	props
) => {
	const { name } = props;
	const { data, setData, touched } = useBusinessVerificationContext();
	const { validate, error } = useValidation( name );
	const inputRef = React.useRef< HTMLInputElement >( null );

	return (
		<TextField
			ref={ inputRef as React.RefObject< HTMLInputElement > }
			label={ strings.fields[ name ] }
			value={ data[ name ] || '' }
			onChange={ ( value: string ) => {
				setData( { [ name ]: value } );
				if (
					touched[ name ] ||
					inputRef.current !==
						inputRef.current?.ownerDocument.activeElement
				)
					validate( value );
			} }
			onBlur={ () => validate() }
			onKeyDown={ ( event: React.KeyboardEvent< HTMLInputElement > ) => {
				if ( event.key === 'Enter' ) validate();
			} }
			error={ error() }
			{ ...props }
		/>
	);
};

interface OnboardingSelectFieldProps< ItemType >
	extends Partial< Omit< SelectFieldProps< ItemType >, 'onChange' > > {
	name: keyof OnboardingFields;
	onChange?: ( name: keyof OnboardingFields, item?: ItemType | null ) => void;
}

export const OnboardingSelectField = < ItemType extends SelectItem >( {
	onChange,
	...rest
}: OnboardingSelectFieldProps< ItemType > ): JSX.Element => {
	const { name } = rest;
	const { data, setData } = useBusinessVerificationContext();
	const { validate, error } = useValidation( name );

	return (
		<SelectField
			label={ strings.fields[ name ] }
			value={ rest.options?.find(
				( item ) => item.key === data[ name ]
			) }
			placeholder={
				( strings.placeholders as Record< string, string > )[ name ] ??
				strings.placeholders.generic
			}
			onChange={ ( { selectedItem } ) => {
				if ( onChange ) {
					onChange?.( name, selectedItem );
				} else {
					setData( { [ name ]: selectedItem?.key } );
				}
				validate( selectedItem?.key );
			} }
			options={ [] }
			error={ error() }
			{ ...rest }
		/>
	);
};

interface OnboardingGroupedSelectFieldProps< ItemType >
	extends Partial< Omit< GroupedSelectFieldProps< ItemType >, 'onChange' > > {
	name: keyof OnboardingFields;
	onChange?: ( name: keyof OnboardingFields, item?: ItemType | null ) => void;
}

export const OnboardingGroupedSelectField = <
	ListItemType extends GroupedSelectItem
>( {
	onChange,
	...rest
}: OnboardingGroupedSelectFieldProps< ListItemType > ): JSX.Element => {
	const { name } = rest;
	const { data, setData } = useBusinessVerificationContext();
	const { validate, error } = useValidation( name );

	return (
		<GroupedSelectField
			label={ strings.fields[ name ] }
			value={ rest.options?.find(
				( item ) => item.key === data[ name ]
			) }
			placeholder={
				( strings.placeholders as Record< string, string > )[ name ] ??
				strings.placeholders.generic
			}
			onChange={ ( { selectedItem } ) => {
				if ( onChange ) {
					onChange?.( name, selectedItem );
				} else {
					setData( { [ name ]: selectedItem?.key } );
				}
				validate( selectedItem?.key );
			} }
			options={ [] }
			error={ error() }
			{ ...rest }
		/>
	);
};
