/**
 * External dependencies
 */
import { useState, useEffect, useCallback } from '@wordpress/element';

/**
 * @typedef {import('../stripe-utils/type-defs').StripeElementOptions} StripeElementOptions
 */

/**
 * Default options for the stripe elements.
 */
const elementOptions = {
	style: {
		base: {
			iconColor: '#666EE8',
			color: '#31325F',
			fontSize: '15px',
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
