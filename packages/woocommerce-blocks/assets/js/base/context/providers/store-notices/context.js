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

/**
 * Internal dependencies
 */
import { useStoreEvents } from '../../hooks/use-store-events';
import { useEditorContext } from '../editor-context';
import StoreNoticesContainer from './components/store-notices-container';

/**
 * @typedef {import('@woocommerce/type-defs/contexts').NoticeContext} NoticeContext
 * @typedef {import('react')} React
 */

const StoreNoticesContext = createContext( {
	notices: [],
	createNotice: ( status, text, props ) => void { status, text, props },
	removeNotice: ( id, ctxt ) => void { id, ctxt },
	setIsSuppressed: ( val ) => void { val },
	context: 'wc/core',
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
 * @param {Object} props Incoming props for the component.
 * @param {JSX.Element} props.children The Elements wrapped by this component.
 * @param {string} [props.className] CSS class used.
 * @param {boolean} [props.createNoticeContainer] Whether to create a notice container or not.
 * @param {string} [props.context] The notice context for notices being rendered.
 */
export const StoreNoticesProvider = ( {
	children,
	className = '',
	createNoticeContainer = true,
	context = 'wc/core',
} ) => {
	const { createNotice, removeNotice } = useDispatch( 'core/notices' );
	const [ isSuppressed, setIsSuppressed ] = useState( false );
	const { dispatchStoreEvent } = useStoreEvents();
	const { isEditor } = useEditorContext();

	const createNoticeWithContext = useCallback(
		( status = 'default', content = '', options = {} ) => {
			createNotice( status, content, {
				...options,
				context: options.context || context,
			} );
			dispatchStoreEvent( 'store-notice-create', {
				status,
				content,
				options,
			} );
		},
		[ createNotice, dispatchStoreEvent, context ]
	);

	const removeNoticeWithContext = useCallback(
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
		createNotice: createNoticeWithContext,
		removeNotice: removeNoticeWithContext,
		context,
		setIsSuppressed,
	};

	const noticeOutput = isSuppressed ? null : (
		<StoreNoticesContainer
			className={ className }
			notices={ contextValue.notices }
			removeNotice={ contextValue.removeNotice }
			isEditor={ isEditor }
		/>
	);

	return (
		<StoreNoticesContext.Provider value={ contextValue }>
			{ createNoticeContainer && noticeOutput }
			{ children }
		</StoreNoticesContext.Provider>
	);
};

StoreNoticesProvider.propTypes = {
	className: PropTypes.string,
	createNoticeContainer: PropTypes.bool,
	children: PropTypes.node,
	context: PropTypes.string,
};
