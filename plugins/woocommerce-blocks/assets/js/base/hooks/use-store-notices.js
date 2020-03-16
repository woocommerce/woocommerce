/**
 * External dependencies
 */
import { useStoreNoticesContext } from '@woocommerce/base-context';

export const useStoreNotices = () => {
	const {
		notices,
		createNotice,
		removeNotice,
		createSnackbarNotice,
	} = useStoreNoticesContext();

	const noticesApi = {
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
		removeNotices: ( type = null ) => {
			notices.map( ( notice ) => {
				if ( type === null || notice.status === type ) {
					removeNotice( notice.id );
				}
				return true;
			} );
		},
		removeNotice,
		addSnackbarNotice: ( text, noticeProps = {} ) => {
			createSnackbarNotice( text, noticeProps );
		},
	};

	return {
		notices,
		...noticesApi,
	};
};
