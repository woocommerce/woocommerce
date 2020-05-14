/**
 * External dependencies
 */
import { useStoreNoticesContext } from '@woocommerce/base-context';
import { useMemo, useRef, useEffect } from '@wordpress/element';

export const useStoreNotices = () => {
	const {
		notices,
		createNotice,
		removeNotice,
		createSnackbarNotice,
		setIsSuppressed,
	} = useStoreNoticesContext();
	// Added to a ref so the surface for notices doesn't change frequently
	// and thus can be used as dependencies on effects.
	const currentNotices = useRef( notices );

	// Update notices ref whenever they change
	useEffect( () => {
		currentNotices.current = notices;
	}, [ notices ] );

	const noticesApi = useMemo(
		() => ( {
			hasNoticesOfType: ( type ) => {
				return currentNotices.current.some(
					( notice ) => notice.type === type
				);
			},
			removeNotices: ( status = null ) => {
				currentNotices.current.map( ( notice ) => {
					if ( status === null || notice.status === status ) {
						removeNotice( notice.id );
					}
					return true;
				} );
			},
			removeNotice,
		} ),
		[ removeNotice ]
	);

	const noticeCreators = useMemo(
		() => ( {
			addDefaultNotice: ( text, noticeProps = {} ) =>
				void createNotice( 'default', text, {
					...noticeProps,
				} ),
			addErrorNotice: ( text, noticeProps = {} ) =>
				void createNotice( 'error', text, {
					...noticeProps,
				} ),
			addWarningNotice: ( text, noticeProps = {} ) =>
				void createNotice( 'warning', text, {
					...noticeProps,
				} ),
			addInfoNotice: ( text, noticeProps = {} ) =>
				void createNotice( 'info', text, {
					...noticeProps,
				} ),
			addSuccessNotice: ( text, noticeProps = {} ) =>
				void createNotice( 'success', text, {
					...noticeProps,
				} ),
			addSnackbarNotice: ( text, noticeProps = {} ) => {
				createSnackbarNotice( text, noticeProps );
			},
		} ),
		[ createNotice, createSnackbarNotice ]
	);

	return {
		notices,
		...noticesApi,
		...noticeCreators,
		setIsSuppressed,
	};
};
