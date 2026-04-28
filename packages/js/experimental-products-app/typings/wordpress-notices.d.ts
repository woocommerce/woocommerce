import type { store } from '@wordpress/notices';
/**
 * Fix WordPress notices store types.
 * createSuccessNotice and createErrorNotice return void, not Promise.
 */

interface NoticeOptions {
	id?: string;
	type?: 'default' | 'snackbar' | 'plain';
	isDismissible?: boolean;
	duration?: number;
	actions?: Array< {
		label: string | React.ReactElement;
		onClick?: () => void;
		url?: string;
	} >;
	icon?: React.ReactElement;
	explicitDismiss?: boolean;
	onDismiss?: () => void;
	speak?: boolean;
	__unstableHTML?: string;
}

declare module '@wordpress/data' {
	export function useDispatch( storeNameOrDescriptor: typeof store ): {
		createSuccessNotice: (
			content: string,
			options?: NoticeOptions
		) => void;
		createErrorNotice: ( content: string, options?: NoticeOptions ) => void;
		createWarningNotice: (
			content: string,
			options?: NoticeOptions
		) => void;
		createInfoNotice: ( content: string, options?: NoticeOptions ) => void;
		removeNotice: ( id: string, context?: string ) => void;
		removeAllNotices: ( status?: string, context?: string ) => void;
	};
}
