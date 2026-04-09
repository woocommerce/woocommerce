/* eslint-disable @typescript-eslint/no-explicit-any -- some general types in this file need to use "any"  */
/* eslint-disable @typescript-eslint/no-unused-vars -- params in declare module type signatures are inherently unused  */

// there are no native types for @wordpress/keyboard-shortcuts yet
declare module '@wordpress/keyboard-shortcuts' {
	import { StoreDescriptor } from '@wordpress/data/build-types/types';

	export const store: { name: 'core/keyboard-shortcuts' } & StoreDescriptor< {
		reducer: () => unknown;
		selectors: {
			getShortcutRepresentation: (
				state: unknown,
				scope: string
			) => unknown;
		};
		actions: {
			registerShortcut: ( options: any ) => object;
		};
	} >;
	export const ShortcutProvider: any;
	export const useShortcut: any;
}

// there are no native types for @wordpress/preferences yet
declare module '@wordpress/preferences' {
	import { StoreDescriptor } from '@wordpress/data/build-types/types';

	export const store: { name: 'core/preferences' } & StoreDescriptor< {
		reducer: () => unknown;
		selectors: {
			get: < T >( state: unknown, scope: string, name: string ) => T;
		};
	} >;
	export const PreferenceToggleMenuItem: any;
}

// Augment @wordpress/notices with richer action/selector types
declare module '@wordpress/notices' {
	import { StoreDescriptor } from '@wordpress/data/build-types/types';
	import { NoticeProps } from '@wordpress/components/build-types/notice/types';
	import { WPNotice } from '@wordpress/notices/build-types/store/selectors';

	export * from '@wordpress/notices';

	type Notice = Omit< NoticeProps, 'children' > & {
		id: string;
		content: WPNotice[ 'content' ];
		type: WPNotice[ 'type' ];
	};

	export const store: { name: 'core/notices' } & StoreDescriptor< {
		reducer: () => unknown;
		actions: {
			createSuccessNotice: ( content: string, options?: unknown ) => void;
			createErrorNotice: ( content: string, options?: unknown ) => void;
			removeNotice: ( id: string, context?: string ) => void;
			createNotice: (
				status: 'error' | 'info' | 'success' | 'warning' | undefined,
				content: string,
				options?: unknown
			) => void;
		};
		selectors: {
			getNotices: ( state?: unknown, context?: string ) => Notice[];
			removeNotice: ( id: string, context?: string ) => void;
		};
	} >;
}
