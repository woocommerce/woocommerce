/**
 * External dependencies
 */
import { useState, useEffect, useCallback } from '@wordpress/element';

/**
 * @typedef {import('../stripe-utils/type-defs').StripeElementOptions} StripeElementOptions
 */

/**
 * Returns the CSS value of the specified property in the document element.
 *
 * @param {string} property     Name of the property to retrieve the style
 *                              value from.
 * @param {string} defaultValue Fallback value if the value for the property
 *                              could not be retrieved.
 *
 * @return {string} The style value of that property in the document element.
 */
const getDocumentStyle = ( property, defaultValue ) => {
	let documentStyle = {};
	if (
		typeof window === 'object' &&
		typeof window.getComputedStyle === 'function'
	) {
		documentStyle = window.getComputedStyle( document.documentElement );
	}
	return documentStyle[ property ] || defaultValue;
};

/**
 * Default options for the stripe elements.
 */
const elementOptions = {
	style: {
		base: {
			iconColor: '#666EE8',
			color: '#31325F',
			fontSize: getDocumentStyle( 'fontSize', '16px' ),
			lineHeight: 1.375, // With a font-size of 16px, line-height will be 22px.
			'::placeholder': {
				color: '#fff',
			},
		},
	},
	classes: {
		focus: 'focused',
		empty: 'empty',
		invalid: 'has-error',
	},
};

/**
 * A custom hook handling options implemented on the stripe elements.
 *
 * @param {Object} [overloadedOptions] An array of extra options to merge with
 *                                     the options provided for the element.
 *
 * @return {StripeElementOptions}  The stripe element options interface
 */
export const useElementOptions = ( overloadedOptions ) => {
	const [ isActive, setIsActive ] = useState( false );
	const [ options, setOptions ] = useState( {
		...elementOptions,
		...overloadedOptions,
	} );
	const [ error, setError ] = useState( '' );

	useEffect( () => {
		const color = isActive ? '#CFD7E0' : '#fff';

		setOptions( ( prevOptions ) => {
			const showIcon =
				typeof prevOptions.showIcon !== 'undefined'
					? { showIcon: isActive }
					: {};
			return {
				...options,
				style: {
					...options.style,
					base: {
						...options.style.base,
						'::placeholder': {
							color,
						},
					},
				},
				...showIcon,
			};
		} );
	}, [ isActive ] );

	const onActive = useCallback(
		( isEmpty ) => {
			if ( ! isEmpty ) {
				setIsActive( true );
			} else {
				setIsActive( ( prevActive ) => ! prevActive );
			}
		},
		[ setIsActive ]
	);
	return { options, onActive, error, setError };
};
