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
 * @member {Object} InnerBlockConfigurationContext A react context object
 */
const InnerBlockConfigurationContext = createContext( {
	parentName: '',
	layoutStyleClassPrefix: '',
} );

export const useInnerBlockConfigurationContext = () =>
	useContext( InnerBlockConfigurationContext );

export const InnerBlockConfigurationProvider = ( {
	parentName = '',
	layoutStyleClassPrefix = '',
	children,
} ) => {
	const contextValue = {
		parentName,
		layoutStyleClassPrefix,
	};

	return (
		<InnerBlockConfigurationContext.Provider value={ contextValue }>
			{ children }
		</InnerBlockConfigurationContext.Provider>
	);
};

InnerBlockConfigurationProvider.propTypes = {
	children: PropTypes.node,
	parentName: PropTypes.string,
	layoutStyleClassPrefix: PropTypes.string,
};
