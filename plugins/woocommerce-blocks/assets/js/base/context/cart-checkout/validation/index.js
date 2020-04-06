/**
 * External dependencies
 */
import { createContext, useContext, useState } from '@wordpress/element';
import { omit, pickBy } from 'lodash';

/**
 * @typedef { import('@woocommerce/type-defs/contexts').ValidationContext } ValidationContext
 */

const ValidationContext = createContext( {
	getValidationError: () => '',
	setValidationErrors: ( errors ) => void errors,
	clearValidationError: ( property ) => void property,
	clearAllValidationErrors: () => void null,
	hideValidationError: () => void null,
	showValidationError: () => void null,
	showAllValidationErrors: () => void null,
	hasValidationErrors: false,
	getValidationErrorId: ( errorId ) => errorId,
} );

/**
 * @return {ValidationContext} The context values for the validation context.
 */
export const useValidationContext = () => {
	return useContext( ValidationContext );
};

/**
 * Validation context provider
 *
 * Any children of this context will be exposed to validation state and helpers
 * for tracking validation.
 */
export const ValidationContextProvider = ( { children } ) => {
	const [ validationErrors, updateValidationErrors ] = useState( {} );

	/**
	 * This retrieves any validation error message that exists in state for the
	 * given property name.
	 *
	 * @param {string} property The property the error message is for.
	 *
	 * @return {Object} The error object for the given property.
	 */
	const getValidationError = ( property ) => validationErrors[ property ];

	/**
	 * Clears any validation error that exists in state for the given property
	 * name.
	 *
	 * @param {string} property  The name of the property to clear if exists in
	 *                           validation error state.
	 */
	const clearValidationError = ( property ) => {
		if ( validationErrors[ property ] ) {
			updateValidationErrors( omit( validationErrors, [ property ] ) );
		}
	};

	/**
	 * Clears the entire validation error state.
	 */
	const clearAllValidationErrors = () => void updateValidationErrors( {} );

	/**
	 * Used to record new validation errors in the state.
	 *
	 * @param {Object} newErrors An object where keys are the property names the
	 *                           validation error is for and values are the
	 *                           validation error message displayed to the user.
	 */
	const setValidationErrors = ( newErrors ) => {
		if ( ! newErrors ) {
			return;
		}
		// all values must be a string.
		newErrors = pickBy(
			newErrors,
			( { message } ) => typeof message === 'string'
		);
		if ( Object.values( newErrors ).length > 0 ) {
			updateValidationErrors( ( prevErrors ) => ( {
				...prevErrors,
				...newErrors,
			} ) );
		}
	};

	const updateValidationError = ( property, newError ) => {
		updateValidationErrors( ( prevErrors ) => {
			if ( ! prevErrors.hasOwnProperty( property ) ) {
				return prevErrors;
			}
			return {
				...prevErrors,
				[ property ]: {
					...prevErrors[ property ],
					...newError,
				},
			};
		} );
	};

	/**
	 * Given a property name and if an associated error exists, it sets its
	 * `hidden` value to true.
	 *
	 * @param {string} property  The name of the property to set the `hidden`
	 *                           value to true.
	 */
	const hideValidationError = ( property ) => {
		updateValidationError( property, {
			hidden: true,
		} );
	};

	/**
	 * Given a property name and if an associated error exists, it sets its
	 * `hidden` value to false.
	 *
	 * @param {string} property  The name of the property to set the `hidden`
	 *                           value to false.
	 */
	const showValidationError = ( property ) => {
		updateValidationError( property, {
			hidden: false,
		} );
	};

	/**
	 * Sets the `hidden` value of all errors to `false`.
	 */
	const showAllValidationErrors = () => {
		updateValidationErrors( ( prevErrors ) => {
			const newErrors = {};
			Object.keys( prevErrors ).forEach( ( property ) => {
				newErrors[ property ] = {
					...prevErrors[ property ],
					hidden: false,
				};
			} );
			return newErrors;
		} );
	};

	/**
	 * Provides an id for the validation error that can be used to fill out
	 * aria-describedby attribute values.
	 *
	 * @param {string} errorId The input css id the validation error is related
	 *                         to.
	 * @return {string} The id to use for the validation error container.
	 */
	const getValidationErrorId = ( errorId ) => {
		const error = getValidationError( errorId );
		if ( ! error || error.hidden ) {
			return '';
		}
		return `validate-error-${ errorId }`;
	};

	const context = {
		getValidationError,
		setValidationErrors,
		clearValidationError,
		clearAllValidationErrors,
		hideValidationError,
		showValidationError,
		showAllValidationErrors,
		hasValidationErrors: Object.keys( validationErrors ).length > 0,
		getValidationErrorId,
	};
	return (
		<ValidationContext.Provider value={ context }>
			{ children }
		</ValidationContext.Provider>
	);
};
