/* eslint-disable @typescript-eslint/no-explicit-any */

declare module '@wordpress/keyboard-shortcuts' {
	import type { StoreDescriptor, ReduxStoreConfig } from '@wordpress/data/build-types/types';

	export const store: { name: 'core/keyboard-shortcuts' } & StoreDescriptor< ReduxStoreConfig<
		unknown,
		{
			registerShortcut: ( options: any ) => object;
		},
		{
			getShortcutRepresentation: (
				state: unknown,
				scope: string
			) => unknown;
		}
	> >;

	export const ShortcutProvider: any;

	export function useShortcut(
		name: string,
		callback: ( event: KeyboardEvent ) => void
	): void;
}

declare module '@wordpress/data/build-types/types' {
	interface StoreRegistry {
		'core/keyboard-shortcuts': typeof import('@wordpress/keyboard-shortcuts').store;
	}
}
