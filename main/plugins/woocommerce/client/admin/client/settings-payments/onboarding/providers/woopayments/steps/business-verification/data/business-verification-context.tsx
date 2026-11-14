/**
 * External dependencies
 */
import React, { createContext, useCallback, useContext, useState } from 'react';
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

	const updateData = useCallback(
		( value: Record< string, string | undefined > ) => {
			setData( ( prev ) => ( { ...prev, ...value } ) );
		},
		[]
	);
	const updateErrors = useCallback(
		( value: Record< string, string | undefined > ) => {
			setErrors( ( prev ) => omitBy( { ...prev, ...value }, isNil ) );
		},
		[]
	);
	const updateTouched = useCallback( ( value: Record< string, boolean > ) => {
		setTouched( ( prev ) => ( { ...prev, ...value } ) );
	}, [] );

	return {
		data,
		setData: updateData,
		errors,
		setErrors: updateErrors,
		touched,
		setTouched: updateTouched,
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
