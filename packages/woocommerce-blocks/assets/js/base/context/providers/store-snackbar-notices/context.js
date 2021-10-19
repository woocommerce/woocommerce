/**
 * External dependencies
 */
import PropTypes from 'prop-types';
import {
	createContext,
	useContext,
	useCallback,
	useState,
} from '@wordpress/element';
import { useSelect, useDispatch } from '@wordpress/data';
import SnackbarNoticesContainer from '@woocommerce/base-context/providers/store-snackbar-notices/components/snackbar-notices-container';

/**
 * Internal dependencies
 */
import { useStoreEvents } from '../../hooks/use-store-events';
import { useEditorContext } from '../editor-context';

/**
 * @typedef {import('@woocommerce/type-defs/contexts').NoticeContext} NoticeContext
 * @typedef {import('react')} React
 */

const StoreSnackbarNoticesContext = createContext( {
	notices: [],
	createSnackbarNotice: ( content, options ) => void { content, options },
	removeSnackbarNotice: ( id, ctxt ) => void { id, ctxt },
	setIsSuppressed: ( val ) => void { val },
	context: 'wc/core',
} );

/**
 * Returns the notices context values.
 *
 * @return {NoticeContext} The notice context value from the notice context.
 */
export const useStoreSnackbarNoticesContext = () => {
	return useContext( StoreSnackbarNoticesContext );
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
 * @param {Object} props Incoming props for the component.
 * @param {React.ReactChildren} props.children The Elements wrapped by this component.
 * @param {string} props.context The notice context for notices being rendered.
 */
export const StoreSnackbarNoticesProvider = ( {
	children,
	context = 'wc/core',
} ) => {
	const { createNotice, removeNotice } = useDispatch( 'core/notices' );
	const [ isSuppressed, setIsSuppressed ] = useState( false );
	const { dispatchStoreEvent } = useStoreEvents();
	const { isEditor } = useEditorContext();

	const createSnackbarNotice = useCallback(
		( content = '', options = {} ) => {
			createNotice( 'default', content, {
				...options,
				type: 'snackbar',
				context: options.context || context,
			} );
			dispatchStoreEvent( 'store-notice-create', {
				status: 'default',
				content,
				options,
			} );
		},
		[ createNotice, dispatchStoreEvent, context ]
	);

	const removeSnackbarNotice = useCallback(
		( id, ctxt = context ) => {
			removeNotice( id, ctxt );
		},
		[ removeNotice, context ]
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
		createSnackbarNotice,
		removeSnackbarNotice,
		context,
		setIsSuppressed,
	};

	const snackbarNoticeOutput = isSuppressed ? null : (
		<SnackbarNoticesContainer
			notices={ contextValue.notices }
			removeNotice={ contextValue.removeSnackbarNotice }
			isEditor={ isEditor }
		/>
	);

	return (
		<StoreSnackbarNoticesContext.Provider value={ contextValue }>
			{ children }
			{ snackbarNoticeOutput }
		</StoreSnackbarNoticesContext.Provider>
	);
};

StoreSnackbarNoticesProvider.propTypes = {
	className: PropTypes.string,
	children: PropTypes.node,
	context: PropTypes.string,
};
