/* eslint-disable @typescript-eslint/no-explicit-any -- some general types in this file need to use "any"  */
/* eslint-disable @typescript-eslint/no-duplicate-imports -- importing within multiple "declare module" blocks is OK  */
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

// Augment @wordpress/notices with Notice type used by email-editor components.
// The store itself is now properly typed via native types + internal-ts-config.
declare module '@wordpress/notices' {
	import { NoticeProps } from '@wordpress/components/build-types/notice/types';
	import { WPNotice } from '@wordpress/notices/build-types/store/selectors';

	export type Notice = Omit< NoticeProps, 'children' > & {
		id: string;
		content: WPNotice[ 'content' ];
		type: WPNotice[ 'type' ];
	};
}
