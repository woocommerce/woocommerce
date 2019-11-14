/**
 * External dependencies
 */
import { createContext, useContext, useEffect } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { assertValidContextValue } from './utils';

const validationMap = {
	parentName: {
		required: true,
		type: 'string',
	},
};

const InnerBlockConfigurationContext = createContext( { parentName: null } );

export const useInnerBlockConfigurationContext = () =>
	useContext( InnerBlockConfigurationContext );
export const InnerBlockConfigurationProvider = ( { value, children } ) => {
	useEffect( () => {
		assertValidContextValue(
			'InnerBlockConfigurationProvider',
			validationMap,
			value
		);
	}, [ value ] );
	return (
		<InnerBlockConfigurationContext.Provider value={ value }>
			{ children }
		</InnerBlockConfigurationContext.Provider>
	);
};
