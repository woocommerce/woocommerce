declare module '@wordpress/keyboard-shortcuts' {
	import type { StoreDescriptor, ReduxStoreConfig } from '@wordpress/data/build-types/types';

	type KeyboardShortcutsConfig = ReduxStoreConfig<
		unknown,
		{
			registerShortcut: ( options: {
				name: string;
				category: string;
				description: string;
				keyCombination: {
					modifier?: string;
					character: string;
				};
				aliases?: Array< {
					modifier?: string;
					character: string;
				} >;
			} ) => object;
			unregisterShortcut: ( name: string ) => object;
		},
		{
			getShortcutKeyCombination: (
				state: unknown,
				name: string
			) => { modifier?: string; character: string } | null;
			getShortcutRepresentation: (
				state: unknown,
				name: string,
				representation?: 'display' | 'raw' | 'ariaLabel'
			) => string | null;
			getShortcutDescription: ( state: unknown, name: string ) => string | null;
			getShortcutAliases: (
				state: unknown,
				name: string
			) => Array< { modifier?: string; character: string } >;
		}
	>;

	type KeyboardShortcutsStoreDescriptor = StoreDescriptor< KeyboardShortcutsConfig > & {
		name: 'core/keyboard-shortcuts';
	};

	export const store: KeyboardShortcutsStoreDescriptor;
	export const ShortcutProvider: any;
	export const useShortcut: (
		name: string,
		callback: ( event: KeyboardEvent ) => void,
		options?: { isDisabled?: boolean }
	) => void;
}

declare module '@wordpress/data' {
	import type { StoreDescriptor, ReduxStoreConfig } from '@wordpress/data/build-types/types';

	type KeyboardShortcutsConfig = ReduxStoreConfig<
		unknown,
		{
			registerShortcut: ( options: {
				name: string;
				category: string;
				description: string;
				keyCombination: {
					modifier?: string;
					character: string;
				};
				aliases?: Array< {
					modifier?: string;
					character: string;
				} >;
			} ) => object;
			unregisterShortcut: ( name: string ) => object;
		},
		{
			getShortcutKeyCombination: (
				state: unknown,
				name: string
			) => { modifier?: string; character: string } | null;
			getShortcutRepresentation: (
				state: unknown,
				name: string,
				representation?: 'display' | 'raw' | 'ariaLabel'
			) => string | null;
			getShortcutDescription: ( state: unknown, name: string ) => string | null;
			getShortcutAliases: (
				state: unknown,
				name: string
			) => Array< { modifier?: string; character: string } >;
		}
	>;

	type KeyboardShortcutsStoreDescriptor = StoreDescriptor< KeyboardShortcutsConfig > & {
		name: 'core/keyboard-shortcuts';
	};

	interface StoreRegistry {
		'core/keyboard-shortcuts': KeyboardShortcutsStoreDescriptor;
	}
}
