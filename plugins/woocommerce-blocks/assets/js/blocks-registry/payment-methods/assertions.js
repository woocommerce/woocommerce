/**
 * External dependencies
 */
import { isValidElement } from '@wordpress/element';

export const assertValidPaymentMethodComponent = (
	component,
	componentName
) => {
	if ( typeof component !== 'function' ) {
		throw new TypeError(
			`The ${ componentName } property for the payment method must be a functional component`
		);
	}
};

export const assertValidElement = ( element, elementName ) => {
	if ( element !== null && ! isValidElement( element ) ) {
		throw new TypeError(
			`The ${ elementName } property for the payment method must be a React element or null.`
		);
	}
};

export const assertValidElementOrString = ( element, elementName ) => {
	if (
		element !== null &&
		! isValidElement( element ) &&
		typeof element !== 'string'
	) {
		throw new TypeError(
			`The ${ elementName } property for the payment method must be a React element, a string, or null.`
		);
	}
};

export const assertConfigHasProperties = (
	config,
	expectedProperties = []
) => {
	const missingProperties = expectedProperties.reduce( ( acc, property ) => {
		if ( ! config.hasOwnProperty( property ) ) {
			acc.push( property );
		}
		return acc;
	}, [] );
	if ( missingProperties.length > 0 ) {
		const message =
			'The payment method configuration object is missing the following properties:';
		throw new TypeError( message + missingProperties.join( ', ' ) );
	}
};

export const assertValidPaymentMethodCreator = ( creator, configName ) => {
	if ( typeof creator !== 'function' ) {
		throw new TypeError(
			`A payment method must be registered with a function that creates and returns a ${ configName } instance`
		);
	}
};
