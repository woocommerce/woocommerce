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

/**
 * This context is a configuration object used for connecting
 * all children blocks in a given tree contained in the context with information
 * about the parent block. Typically this is used for extensibility features.
 *
 * @var {React.Context} InnerBlockConfigurationContext A react context object
 */
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
