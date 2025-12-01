/**
 * External dependencies
 */
import { useEffect } from 'react';

/**
 * Internal dependencies
 */
import strings from '../strings';
import { useBusinessVerificationContext } from '../data/business-verification-context';
import { OnboardingFields } from '../types';

const isValid = ( name: keyof OnboardingFields, value?: string ): boolean => {
	if ( ! value ) return false;

	switch ( name ) {
		default:
			return true;
	}
};

// TS is smart enough to infer the return type here.
// eslint-disable-next-line @typescript-eslint/explicit-module-boundary-types
export const useValidation = ( name: keyof OnboardingFields ) => {
	const { data, errors, setErrors, touched, setTouched } =
		useBusinessVerificationContext();

	const validate = ( value: string | undefined = data[ name ] ) => {
		if ( ! touched[ name ] ) setTouched( { [ name ]: true } );

		const error = isValid( name, value )
			? undefined
			: ( strings.errors as Record< string, string > )[ name ] ||
			  strings.errors.generic;

		setErrors( { [ name ]: error } );
	};

	useEffect( () => {
		// Validate on mount.
		validate();

		// Set touched to false if the field is empty.
		if ( ! data[ name ] ) setTouched( { [ name ]: false } );

		// Clean up the error when the field is unmounted.
		return () => setErrors( { [ name ]: undefined } );

		// We only want to run this once, so we disable the exhaustive deps rule.
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [] );

	return {
		validate,
		error: () => ( touched[ name ] ? errors[ name ] : undefined ),
	};
};
