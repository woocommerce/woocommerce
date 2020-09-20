/**
 * External dependencies
 */
import {
	createContext,
	useCallback,
	useContext,
	useState,
} from '@wordpress/element';
import { omit, pickBy } from 'lodash';
import isShallowEqual from '@wordpress/is-shallow-equal';

/**
 * @typedef { import('@woocommerce/type-defs/contexts').ValidationContext } ValidationContext
 * @typedef {import('react')} React
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
 *
 * @param {Object} props Incoming props for the component.
 * @param {React.ReactChildren} props.children What react elements are wrapped by this component.
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
	const getValidationError = useCallback(
		( property ) => validationErrors[ property ],
		[ validationErrors ]
	);

	/**
	 * Provides an id for the validation error that can be used to fill out
	 * aria-describedby attribute values.
	 *
	 * @param {string} errorId The input css id the validation error is related
	 *                         to.
	 * @return {string} The id to use for the validation error container.
	 */
	const getValidationErrorId = useCallback(
		( errorId ) => {
			const error = validationErrors[ errorId ];
			if ( ! error || error.hidden ) {
				return '';
			}
			return `validate-error-${ errorId }`;
		},
		[ validationErrors ]
	);

	/**
	 * Clears any validation error that exists in state for the given property
	 * name.
	 *
	 * @param {string} property  The name of the property to clear if exists in
	 *                           validation error state.
	 */
	const clearValidationError = useCallback( ( property ) => {
		updateValidationErrors( ( prevErrors ) => {
			if ( ! prevErrors[ property ] ) {
				return prevErrors;
			}
			return omit( prevErrors, [ property ] );
		} );
	}, [] );

	/**
	 * Clears the entire validation error state.
	 */
	const clearAllValidationErrors = useCallback(
		() => void updateValidationErrors( {} ),
		[]
	);

	/**
	 * Used to record new validation errors in the state.
	 *
	 * @param {Object} newErrors An object where keys are the property names the
	 *                           validation error is for and values are the
	 *                           validation error message displayed to the user.
	 */
	const setValidationErrors = useCallback( ( newErrors ) => {
		if ( ! newErrors ) {
			return;
		}
		updateValidationErrors( ( prevErrors ) => {
			newErrors = pickBy( newErrors, ( error, property ) => {
				if ( typeof error.message !== 'string' ) {
					return false;
				}
				if ( prevErrors.hasOwnProperty( property ) ) {
					return ! isShallowEqual( prevErrors[ property ], error );
				}
				return true;
			} );
			if ( Object.values( newErrors ).length === 0 ) {
				return prevErrors;
			}
			return {
				...prevErrors,
				...newErrors,
			};
		} );
	}, [] );

	/**
	 * Used to update a validation error.
	 *
	 * @param {string} property The name of the property to update.
	 * @param {Object} newError New validation error object.
	 */
	const updateValidationError = useCallback( ( property, newError ) => {
		updateValidationErrors( ( prevErrors ) => {
			if ( ! prevErrors.hasOwnProperty( property ) ) {
				return prevErrors;
			}
			const updatedError = {
				...prevErrors[ property ],
				...newError,
			};
			return isShallowEqual( prevErrors[ property ], updatedError )
				? prevErrors
				: {
						...prevErrors,
						[ property ]: updatedError,
				  };
		} );
	}, [] );

	/**
	 * Given a property name and if an associated error exists, it sets its
	 * `hidden` value to true.
	 *
	 * @param {string} property  The name of the property to set the `hidden`
	 *                           value to true.
	 */
	const hideValidationError = useCallback(
		( property ) =>
			void updateValidationError( property, {
				hidden: true,
			} ),
		[ updateValidationError ]
	);

	/**
	 * Given a property name and if an associated error exists, it sets its
	 * `hidden` value to false.
	 *
	 * @param {string} property  The name of the property to set the `hidden`
	 *                           value to false.
	 */
	const showValidationError = useCallback(
		( property ) =>
			void updateValidationError( property, {
				hidden: false,
			} ),
		[ updateValidationError ]
	);

	/**
	 * Sets the `hidden` value of all errors to `false`.
	 */
	const showAllValidationErrors = useCallback(
		() =>
			void updateValidationErrors( ( prevErrors ) => {
				const updatedErrors = {};

				Object.keys( prevErrors ).forEach( ( property ) => {
					if ( prevErrors[ property ].hidden ) {
						updatedErrors[ property ] = {
							...prevErrors[ property ],
							hidden: false,
						};
					}
				} );

				if ( Object.values( updatedErrors ).length === 0 ) {
					return prevErrors;
				}

				return {
					...prevErrors,
					...updatedErrors,
				};
			} ),
		[]
	);

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
