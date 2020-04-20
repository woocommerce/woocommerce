/**
 * External dependencies
 */
import { useStoreNoticesContext } from '@woocommerce/base-context';
import { useMemo } from '@wordpress/element';

export const useStoreNotices = () => {
	const {
		notices,
		createNotice,
		removeNotice,
		createSnackbarNotice,
	} = useStoreNoticesContext();

	const noticesApi = useMemo(
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
			hasNoticesOfType: ( type ) => {
				return notices.some( ( notice ) => notice.type === type );
			},
			removeNotices: ( status = null ) => {
				notices.map( ( notice ) => {
					if ( status === null || notice.status === status ) {
						removeNotice( notice.id );
					}
					return true;
				} );
			},
			removeNotice,
			addSnackbarNotice: ( text, noticeProps = {} ) => {
				createSnackbarNotice( text, noticeProps );
			},
		} ),
		[ createNotice, createSnackbarNotice, notices ]
	);

	return {
		notices,
		...noticesApi,
	};
};
