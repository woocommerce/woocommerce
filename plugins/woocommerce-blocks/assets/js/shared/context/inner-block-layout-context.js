/**
 * External dependencies
 */
import PropTypes from 'prop-types';
import { createContext, useContext } from '@wordpress/element';

/**
 * This context is a configuration object used for connecting
 * all children blocks in a given tree contained in the context with information
 * about the parent block. Typically this is used for extensibility features.
 *
 * @member {Object} InnerBlockLayoutContext A react context object
 */
const InnerBlockLayoutContext = createContext( {
	parentName: '',
	layoutStyleClassPrefix: '',
} );

export const useInnerBlockLayoutContext = () =>
	useContext( InnerBlockLayoutContext );

export const InnerBlockLayoutContextProvider = ( {
	parentName = '',
	layoutStyleClassPrefix = '',
	children,
} ) => {
	const contextValue = {
		parentName,
		layoutStyleClassPrefix,
	};

	return (
		<InnerBlockLayoutContext.Provider value={ contextValue }>
			{ children }
		</InnerBlockLayoutContext.Provider>
	);
};

InnerBlockLayoutContextProvider.propTypes = {
	children: PropTypes.node,
	parentName: PropTypes.string,
	layoutStyleClassPrefix: PropTypes.string,
};
