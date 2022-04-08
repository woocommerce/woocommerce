/**
 * External dependencies
 */
import PropTypes from 'prop-types';
import { createContext, useContext, useState } from '@wordpress/element';

/**
 * @typedef {import('@woocommerce/type-defs/contexts').NoticeContext} NoticeContext
 * @typedef {import('react')} React
 */

const StoreNoticesContext = createContext( {
	setIsSuppressed: ( val ) => void { val },
	isSuppressed: false,
} );

/**
 * Returns the notices context values.
 *
 * @return {NoticeContext} The notice context value from the notice context.
 */
export const useStoreNoticesContext = () => {
	return useContext( StoreNoticesContext );
};

/**
 * Provides an interface for blocks to add notices to the frontend UI.
 *
 * Statuses map to https://github.com/WordPress/gutenberg/tree/master/packages/components/src/notice
 *  - Default (no status)
 *  - Error
 *  - Warning
 *  - Info
 *  - Success
 *
 * @param {Object}      props          Incoming props for the component.
 * @param {JSX.Element} props.children The Elements wrapped by this component.
 */
export const StoreNoticesProvider = ( { children } ) => {
	const [ isSuppressed, setIsSuppressed ] = useState( false );

	const contextValue = {
		setIsSuppressed,
		isSuppressed,
	};

	return (
		<StoreNoticesContext.Provider value={ contextValue }>
			{ children }
		</StoreNoticesContext.Provider>
	);
};

StoreNoticesProvider.propTypes = {
	children: PropTypes.node,
};
