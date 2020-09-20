/**
 * External dependencies
 */
import PropTypes from 'prop-types';
import { createContext, useContext } from '@wordpress/element';
import { useContainerQueries } from '@woocommerce/base-hooks';
import classNames from 'classnames';

/**
 * @typedef {import('@woocommerce/type-defs/contexts').ContainerWidthContext} ContainerWidthContext
 * @typedef {import('react')} React
 */

const ContainerWidthContext = createContext( {
	hasContainerWidth: false,
	containerClassName: '',
	isMobile: false,
	isSmall: false,
	isMedium: false,
	isLarge: false,
} );

/**
 * @return {ContainerWidthContext} Returns the container width context value
 */
export const useContainerWidthContext = () => {
	return useContext( ContainerWidthContext );
};

/**
 * Provides an interface to useContainerQueries so children can see what size is being used by the
 * container.
 *
 * @param {Object} props Incoming props for the component.
 * @param {React.ReactChildren} props.children React elements wrapped by the component.
 * @param {string} props.className CSS class in use.
 */
export const ContainerWidthContextProvider = ( {
	children,
	className = '',
} ) => {
	const [ resizeListener, containerClassName ] = useContainerQueries();

	const contextValue = {
		hasContainerWidth: containerClassName !== '',
		containerClassName,
		isMobile: containerClassName === 'is-mobile',
		isSmall: containerClassName === 'is-small',
		isMedium: containerClassName === 'is-medium',
		isLarge: containerClassName === 'is-large',
	};

	/**
	 * @type {ContainerWidthContext}
	 */
	return (
		<ContainerWidthContext.Provider value={ contextValue }>
			<div className={ classNames( className, containerClassName ) }>
				{ resizeListener }
				{ children }
			</div>
		</ContainerWidthContext.Provider>
	);
};

ContainerWidthContextProvider.propTypes = {
	children: PropTypes.node,
};
