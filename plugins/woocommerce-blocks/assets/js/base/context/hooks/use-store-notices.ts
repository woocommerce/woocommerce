/**
 * External dependencies
 */
import { useMemo, useRef, useEffect } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { useStoreNoticesContext } from '../providers/store-notices/context';

type WPNoticeAction = {
	label: string;
	url?: string;
	onClick?: () => void;
};

type WPNotice = {
	id: string;
	status: 'success' | 'info' | 'error' | 'warning';
	content: string;
	spokenMessage: string;
	// eslint-disable-next-line @typescript-eslint/naming-convention
	__unstableHTML: string;
	isDismissible: boolean;
	type: 'default' | 'snackbar';
	speak: boolean;
	actions: WPNoticeAction[];
};

type NoticeOptions = {
	id: string;
	type: string;
	isDismissible: boolean;
};

type NoticeCreator = ( text: string, noticeProps: NoticeOptions ) => void;

export const useStoreNotices = (): {
	notices: WPNotice[];
	hasNoticesOfType: ( type: string ) => boolean;
	removeNotices: ( status: string | null ) => void;
	removeNotice: ( id: string, context: string ) => void;
	addDefaultNotice: NoticeCreator;
	addErrorNotice: NoticeCreator;
	addWarningNotice: NoticeCreator;
	addInfoNotice: NoticeCreator;
	addSuccessNotice: NoticeCreator;
	setIsSuppressed: ( isSuppressed: boolean ) => void;
} => {
	const {
		notices,
		createNotice,
		removeNotice,
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
			hasNoticesOfType: ( type: 'default' | 'snackbar' ): boolean => {
				return currentNotices.current.some(
					( notice ) => notice.type === type
				);
			},
			removeNotices: ( status = null ) => {
				currentNotices.current.forEach( ( notice ) => {
					if ( status === null || notice.status === status ) {
						removeNotice( notice.id );
					}
				} );
			},
			removeNotice,
		} ),
		[ removeNotice ]
	);

	const noticeCreators = useMemo(
		() => ( {
			addDefaultNotice: ( text: string, noticeProps = {} ) =>
				void createNotice( 'default', text, {
					...noticeProps,
				} ),
			addErrorNotice: ( text: string, noticeProps = {} ) =>
				void createNotice( 'error', text, {
					...noticeProps,
				} ),
			addWarningNotice: ( text: string, noticeProps = {} ) =>
				void createNotice( 'warning', text, {
					...noticeProps,
				} ),
			addInfoNotice: ( text: string, noticeProps = {} ) =>
				void createNotice( 'info', text, {
					...noticeProps,
				} ),
			addSuccessNotice: ( text: string, noticeProps = {} ) =>
				void createNotice( 'success', text, {
					...noticeProps,
				} ),
		} ),
		[ createNotice ]
	);

	return {
		notices,
		...noticesApi,
		...noticeCreators,
		setIsSuppressed,
	};
};
