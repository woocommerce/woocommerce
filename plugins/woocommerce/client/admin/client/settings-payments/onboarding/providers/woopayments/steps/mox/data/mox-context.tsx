/**
 * External dependencies
 */
import React, { createContext, useContext, useState } from 'react';
import { isNil, omitBy } from 'lodash';

/**
 * Internal dependencies
 */
import { OnboardingFields } from '../types';

const useMOXContextValue = ( initialState = {} as OnboardingFields ) => {
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

type MOXContextValue = ReturnType< typeof useMOXContextValue >;

const MOXContext = createContext< MOXContextValue | null >( null );

export const MOXContextProvider: React.FC< {
	initialData?: OnboardingFields;
	children: React.ReactNode;
} > = ( { children, initialData } ) => {
	return (
		<MOXContext.Provider value={ useMOXContextValue( initialData ) }>
			{ children }
		</MOXContext.Provider>
	);
};

export const useMOXContext = (): MOXContextValue => {
	const context = useContext( MOXContext );
	if ( ! context ) {
		throw new Error(
			'useMOXContext() must be used within <MOXContextProvider>'
		);
	}
	return context;
};
