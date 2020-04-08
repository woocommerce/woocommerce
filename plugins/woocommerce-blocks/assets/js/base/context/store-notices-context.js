/**
 * External dependencies
 */
import PropTypes from 'prop-types';
import { createContext, useContext, useCallback } from '@wordpress/element';
import { useSelect, useDispatch } from '@wordpress/data';
import {
	StoreNoticesContainer,
	SnackbarNoticesContainer,
} from '@woocommerce/base-components/store-notices-container';

const StoreNoticesContext = createContext( {
	notices: [],
	createNotice: ( status, text, props ) => void { status, text, props },
	createSnackBarNotice: () => void null,
	removeNotice: ( id ) => void id,
	context: 'wc/core',
} );

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
 */
export const StoreNoticesProvider = ( {
	children,
	className = '',
	createNoticeContainer = true,
	context = 'wc/core',
} ) => {
	const { createNotice, removeNotice } = useDispatch( 'core/notices' );

	const createNoticeWithContext = useCallback(
		( status = 'default', content = '', options = {} ) => {
			createNotice( status, content, {
				...options,
				context: options.context || context,
			} );
		},
		[ createNotice, context ]
	);

	const removeNoticeWithContext = useCallback(
		( id ) => {
			removeNotice( id, context );
		},
		[ createNotice, context ]
	);

	const createSnackbarNotice = useCallback(
		( content = '', options = {} ) => {
			createNoticeWithContext( 'default', content, {
				...options,
				type: 'snackbar',
			} );
		},
		[ createNoticeWithContext ]
	);

	const { notices } = useSelect(
		( select ) => {
			return {
				notices: select( 'core/notices' ).getNotices( context ),
			};
		},
		[ context ]
	);

	const contextValue = {
		notices,
		createNotice: createNoticeWithContext,
		createSnackbarNotice,
		removeNotice: removeNoticeWithContext,
		context,
	};

	return (
		<StoreNoticesContext.Provider value={ contextValue }>
			{ createNoticeContainer && (
				<StoreNoticesContainer
					className={ className }
					notices={ contextValue.notices }
				/>
			) }
			{ children }
			<SnackbarNoticesContainer />
		</StoreNoticesContext.Provider>
	);
};

StoreNoticesProvider.propTypes = {
	className: PropTypes.string,
	createNoticeContainer: PropTypes.bool,
	children: PropTypes.node,
	context: PropTypes.string,
};
