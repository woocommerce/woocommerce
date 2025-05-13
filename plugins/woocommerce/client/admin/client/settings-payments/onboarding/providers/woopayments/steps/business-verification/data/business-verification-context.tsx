/**
 * External dependencies
 */
import React, { createContext, useContext, useState } from 'react';
import { isNil, omitBy } from 'lodash';

/**
 * Internal dependencies
 */
import { OnboardingFields } from '../types';

const useBusinessVerificationContextValue = (
	initialState = {} as OnboardingFields
) => {
	const [ data, setData ] = useState( initialState );
	const [ errors, setErrors ] = useState( {} as OnboardingFields );
	const [ touched, setTouched ] = useState( {} as OnboardingFields );

	return {
		data,
		setData: ( value: Record< string, string | undefined > ) =>
			setData( ( prev ) => ( { ...prev, ...value } ) ),
		errors,
		setErrors: ( value: Record< string, string | undefined > ) =>
			setErrors( ( prev ) => omitBy( { ...prev, ...value }, isNil ) ),
		touched,
		setTouched: ( value: Record< string, boolean > ) =>
			setTouched( ( prev ) => ( { ...prev, ...value } ) ),
	};
};

type BusinessVerificationContextValue = ReturnType<
	typeof useBusinessVerificationContextValue
>;

const BusinessVerificationContext =
	createContext< BusinessVerificationContextValue | null >( null );

export const BusinessVerificationContextProvider: React.FC< {
	initialData?: OnboardingFields;
	children: React.ReactNode;
} > = ( { children, initialData } ) => {
	return (
		<BusinessVerificationContext.Provider
			value={ useBusinessVerificationContextValue( initialData ) }
		>
			{ children }
		</BusinessVerificationContext.Provider>
	);
};

export const useBusinessVerificationContext =
	(): BusinessVerificationContextValue => {
		const context = useContext( BusinessVerificationContext );
		if ( ! context ) {
			throw new Error(
				'useBusinessVerificationContext() must be used within <BusinessVerificationContextProvider>'
			);
		}
		return context;
	};
