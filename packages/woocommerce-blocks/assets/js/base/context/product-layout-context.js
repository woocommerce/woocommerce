/**
 * External dependencies
 */
import { createContext, useContext, useEffect } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { assertValidContextValue } from './utils';

const validationMap = {
	layoutStyleClassPrefix: {
		required: true,
		type: 'string',
	},
};

/**
 * ProductLayoutContext is an configuration object for layout options shared
 * among all components in a tree.
 *
 * @var {React.Context} ProductLayoutContext A react context object
 */
const ProductLayoutContext = createContext( {
	layoutStyleClassPrefix: '',
} );

export const useProductLayoutContext = () => useContext( ProductLayoutContext );
export const ProductLayoutContextProvider = ( { value, children } ) => {
	useEffect( () => {
		assertValidContextValue(
			'ProductLayoutContextProvider',
			validationMap,
			value
		);
	}, [ value ] );
	return (
		<ProductLayoutContext.Provider value={ value }>
			{ children }
		</ProductLayoutContext.Provider>
	);
};
