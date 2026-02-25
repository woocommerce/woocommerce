declare module '@wordpress/notices' {
	import type {
		StoreDescriptor,
		ReduxStoreConfig,
	} from '@wordpress/data/build-types/types';

	type NoticeStatus = 'info' | 'success' | 'warning' | 'error';

	interface NoticeAction {
		label: string;
		url?: string;
		onClick?: () => void;
	}

	interface Notice {
		id: string;
		status: NoticeStatus;
		content: string;
		isDismissible: boolean;
		actions: NoticeAction[];
		type: 'default' | 'snackbar';
	}

	type NoticesConfig = ReduxStoreConfig<
		unknown,
		{
			createNotice: (
				status: NoticeStatus,
				content: string,
				options?: {
					context?: string;
					id?: string;
					isDismissible?: boolean;
					type?: 'default' | 'snackbar';
					actions?: NoticeAction[];
					icon?: any;
					explicitDismiss?: boolean;
					onDismiss?: () => void;
				}
			) => object;
			createSuccessNotice: (
				content: string,
				options?: Record< string, unknown >
			) => object;
			createInfoNotice: (
				content: string,
				options?: Record< string, unknown >
			) => object;
			createErrorNotice: (
				content: string,
				options?: Record< string, unknown >
			) => object;
			createWarningNotice: (
				content: string,
				options?: Record< string, unknown >
			) => object;
			removeNotice: (
				id: string,
				context?: string
			) => object;
			removeNotices: (
				ids: string[],
				context?: string
			) => object;
			removeAllNotices: ( context?: string ) => object;
		},
		{
			getNotices: (
				state: unknown,
				context?: string
			) => Notice[];
		}
	>;

	type NoticesStoreDescriptor = StoreDescriptor< NoticesConfig > & {
		name: 'core/notices';
	};

	export const store: NoticesStoreDescriptor;
}

declare module '@wordpress/data' {
	import type {
		StoreDescriptor,
		ReduxStoreConfig,
	} from '@wordpress/data/build-types/types';

	type NoticeStatus = 'info' | 'success' | 'warning' | 'error';

	interface NoticeAction {
		label: string;
		url?: string;
		onClick?: () => void;
	}

	interface Notice {
		id: string;
		status: NoticeStatus;
		content: string;
		isDismissible: boolean;
		actions: NoticeAction[];
		type: 'default' | 'snackbar';
	}

	type NoticesConfig = ReduxStoreConfig<
		unknown,
		{
			createNotice: (
				status: NoticeStatus,
				content: string,
				options?: {
					context?: string;
					id?: string;
					isDismissible?: boolean;
					type?: 'default' | 'snackbar';
					actions?: NoticeAction[];
					icon?: any;
					explicitDismiss?: boolean;
					onDismiss?: () => void;
				}
			) => object;
			createSuccessNotice: (
				content: string,
				options?: Record< string, unknown >
			) => object;
			createInfoNotice: (
				content: string,
				options?: Record< string, unknown >
			) => object;
			createErrorNotice: (
				content: string,
				options?: Record< string, unknown >
			) => object;
			createWarningNotice: (
				content: string,
				options?: Record< string, unknown >
			) => object;
			removeNotice: (
				id: string,
				context?: string
			) => object;
			removeNotices: (
				ids: string[],
				context?: string
			) => object;
			removeAllNotices: ( context?: string ) => object;
		},
		{
			getNotices: (
				state: unknown,
				context?: string
			) => Notice[];
		}
	>;

	type NoticesStoreDescriptor = StoreDescriptor< NoticesConfig > & {
		name: 'core/notices';
	};

	interface StoreRegistry {
		'core/notices': NoticesStoreDescriptor;
	}
}
