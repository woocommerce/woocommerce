/**
 * External dependencies
 */
import PropTypes from 'prop-types';
import { createContext, useContext } from '@wordpress/element';

/**
 * This context is used to pass product data down to all children blocks in a given tree.
 *
 * @member {Object} ProductDataContext A react context object
 */
const ProductDataContext = createContext( {
	product: null,
} );

export const useProductDataContext = () => useContext( ProductDataContext );

export const ProductDataContextProvider = ( { product = null, children } ) => {
	const contextValue = {
		product,
	};

	return (
		<ProductDataContext.Provider value={ contextValue }>
			{ children }
		</ProductDataContext.Provider>
	);
};

ProductDataContextProvider.propTypes = {
	children: PropTypes.node,
	product: PropTypes.object,
};
