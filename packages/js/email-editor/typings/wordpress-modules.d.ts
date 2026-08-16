/* eslint-disable @typescript-eslint/no-explicit-any -- some general types in this file need to use "any"  */
/* eslint-disable @typescript-eslint/no-duplicate-imports -- importing within multiple "declare module" blocks is OK  */
/* eslint-disable @typescript-eslint/no-unused-vars -- params in declare module type signatures are inherently unused  */

// `@wordpress/keyboard-shortcuts` and `@wordpress/preferences` ship no native
// types and are not in `@types/wordpress__*`. These minimal `declare module`
// blocks let the package source compile without leaking the augmentations
// into the emitted `build-types/` consumed by downstream packages — this file
// lives under `typings/` (compile-only, outside `rootDir`).

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

// wp-6.8's @types/wordpress__block-editor only declares
// `useSettings(...paths: string[]): any[]`. These overloads give email-editor
// call sites a properly typed return. The `EditorSettings.gradients`
// augmentation fills another wp-min gap.
declare module '@wordpress/block-editor' {
	import { FontSize } from '@wordpress/components/build-types/font-size-picker/types';
	import {
		Color,
		Gradient,
	} from '@wordpress/components/build-types/palette-edit/types';
	import { FontFamily } from '../src/types';

	export function useSettings( path: string ): unknown;
	export function useSettings(
		path1: 'typography.fontSizes',
		path2: 'typography.fontFamilies'
	): [ FontSize[], { default: FontFamily[] } ];
	export function useSettings(
		path1: 'color.palette',
		path2: 'color.gradients'
	): [ Color[], Gradient[] ];
	export function useSettings( path: 'typography.fontSizes' ): [ FontSize[] ];

	export interface EditorSettings {
		gradients: {
			name: string;
			slug: string;
			gradient: string;
		}[];
	}
}
