/**
 * External dependencies
 */
import PropTypes from 'prop-types';
import { createContext, useContext } from '@wordpress/element';
import classnames from 'classnames';

/**
 * This context is a configuration object used for connecting
 * all children blocks in a given tree contained in the context with information
 * about the parent block. Typically this is used for extensibility features.
 *
 * @member {Object} InnerBlockLayoutContext A react context object
 */
const InnerBlockLayoutContext = createContext( {
	parentName: '',
	parentClassName: '',
	isLoading: false,
} );

export const useInnerBlockLayoutContext = () =>
	useContext( InnerBlockLayoutContext );

export const InnerBlockLayoutContextProvider = ( {
	parentName = '',
	parentClassName = '',
	isLoading = false,
	children,
} ) => {
	const contextValue = {
		parentName,
		parentClassName,
		isLoading,
	};

	return (
		<InnerBlockLayoutContext.Provider value={ contextValue }>
			<div
				className={ classnames( 'wc-block-layout', {
					'wc-block-layout--is-loading': isLoading,
				} ) }
			>
				{ children }
			</div>
		</InnerBlockLayoutContext.Provider>
	);
};

InnerBlockLayoutContextProvider.propTypes = {
	children: PropTypes.node,
	parentName: PropTypes.string,
	parentClassName: PropTypes.string,
};
