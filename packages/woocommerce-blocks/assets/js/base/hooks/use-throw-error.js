/**
 * External dependencies
 */
import { useState } from '@wordpress/element';

/**
 * Helper method for throwing an error in a React Hook.
 *
 * @see https://github.com/facebook/react/issues/14981
 *
 * @return {function(Object)} A function receiving the error that will be thrown.
 */
export const useThrowError = () => {
	const [ , setState ] = useState();
	return ( error ) =>
		setState( () => {
			throw error;
		} );
};
