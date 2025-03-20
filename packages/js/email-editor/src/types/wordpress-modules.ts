/* eslint-disable @typescript-eslint/no-explicit-any -- some general types in this file need to use "any"  */
/* eslint-disable import/no-duplicates -- importing within multiple "declare module" blocks is OK  */
/* eslint-disable @typescript-eslint/no-duplicate-imports -- importing within multiple "declare module" blocks is OK  */

declare module '@wordpress/block-editor' {
	import * as blockEditorActions from '@wordpress/block-editor/store/actions';
	import * as blockEditorSelectors from '@wordpress/block-editor/store/selectors';
	import { StoreDescriptor as GenericStoreDescriptor } from '@wordpress/data/build-types/types';

	export * from '@wordpress/block-editor/index';

	export const store: {
		name: 'core/block-editor';
	} & GenericStoreDescriptor< {
		reducer: () => unknown;
		actions: typeof blockEditorActions;
		selectors: typeof blockEditorSelectors;
	} >;
}

declare module '@wordpress/editor' {
	import { ComponentType } from 'react';
	import * as editorActions from '@wordpress/editor/store/actions';
	import * as editorSelectors from '@wordpress/editor/store/selectors';
	import { StoreDescriptor as GenericStoreDescriptor } from '@wordpress/data/build-types/types';
	import { PostPreviewButton as WPPostPreviewButton } from '@wordpress/editor/components';

	export * from '@wordpress/editor/index';

	// eslint-disable-next-line @typescript-eslint/ban-ts-comment
	// @ts-ignore - disable redeclaration error because it's a module declaration
	export const store: { name: 'core/editor' } & GenericStoreDescriptor< {
		reducer: () => unknown;
		actions: typeof editorActions;
		selectors: typeof editorSelectors;
	} >;

	export const PostPreviewButton: ComponentType<
		WPPostPreviewButton.Props & {
			className?: string;
			role?: string;
			textContent: JSX.Element;
			onPreview: () => void;
		}
	>;
}

// there are no @types/wordpress__interface yet
declare module '@wordpress/interface' {
	import { StoreDescriptor } from '@wordpress/data/build-types/types';
	import * as interfaceActions from '@wordpress/interface/src/store/actions';

	export const store: { name: 'core/interface' } & StoreDescriptor< {
		reducer: () => unknown;
		actions: typeof interfaceActions;
		selectors: {
			getActiveComplementaryArea: (
				state: unknown,
				scope: string
			) => string | undefined | null;
		};
	} >;
	export const ComplementaryArea: any;
	export const FullscreenMode: any;
	export const InterfaceSkeleton: any;
	export const PinnedItems: any;
}

// there are no @types/wordpress__keyboard-shortcuts yet
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

// there are no @types/wordpress__preferences yet
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

// Types in @types/wordpress__notices are outdated and build on top of @types/wordpress__data
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

	// We don't want to use the types from @types/wordpress__notices but the package
	// is installed anyway as a subdependency of @types/wordpress__components
	// The ignore comment is needed to allow overriding the store
	// eslint-disable-next-line @typescript-eslint/ban-ts-comment
	// @ts-ignore
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
			getNotices: ( state: unknown, context?: string ) => Notice[];
			removeNotice: ( id: string, context?: string ) => void;
		};
	} >;
}

declare module '@wordpress/core-data' {
	import { BlockInstance } from '@wordpress/blocks/index';

	export function useEntityBlockEditor(
		kind: string,
		name: string,
		{
			// eslint-disable-next-line @typescript-eslint/ban-ts-comment
			// @ts-ignore
			id: _id,
		}?: {
			id?: string | undefined;
		}
	): [
		WPBlock[],
		( blocks: BlockInstance[] ) => void,
		( blocks: BlockInstance[] ) => void
	];
	export type WPBlock = any;

	export * from '@wordpress/core-data/build-types';
}
