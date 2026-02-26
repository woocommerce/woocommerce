/* eslint-disable @typescript-eslint/no-explicit-any -- some general types in this file need to use "any"  */
/* eslint-disable import/no-duplicates -- importing within multiple "declare module" blocks is OK  */
/* eslint-disable @typescript-eslint/no-duplicate-imports -- importing within multiple "declare module" blocks is OK  */

declare module '@wordpress/block-editor' {
	import { FontSize } from '@wordpress/components/build-types/font-size-picker/types';
	import {
		Color,
		Gradient,
	} from '@wordpress/components/build-types/palette-edit/types';

	// FontFamily defined inline (can't use relative imports in ambient modules)
	type FontFamily = {
		name: string;
		slug: string;
		fontFamily: string;
	};

	export const __experimentalLibrary: any;
	export const __experimentalListView: any;

	// types for 'useSettings' are missing in @types/wordpress__block-editor
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

	// types for 'gradients' are missing in @types/wordpress__block-editor
	export interface EditorSettings {
		gradients: {
			name: string;
			slug: string;
			gradient: string;
		}[];
	}
}

declare module '@wordpress/editor' {
	import { ComponentType } from 'react';
	import { PostPreviewButton as WPPostPreviewButton } from '@wordpress/editor/components';

	export const PostPreviewButton: ComponentType<
		WPPostPreviewButton.Props & {
			className?: string;
			role?: string;
			textContent: JSX.Element;
			onPreview: () => void;
		}
	>;
}

declare module '@wordpress/core-data' {
	import { BlockInstance } from '@wordpress/blocks/index';

	export * from '@wordpress/core-data/build-types';

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
}

// Re-declare NoticeList and SnackbarList with looser types.
// The @wordpress/notices WPNotice type has `status: string` but
// @wordpress/components expects `status: 'info' | 'warning' | 'success' | 'error'`.
// This is a type incompatibility in WordPress packages that we work around here.
declare module '@wordpress/components' {
	import type { ReactNode } from 'react';

	export * from '@wordpress/components/build-types';

	// Looser notice type that accepts WPNotice from @wordpress/notices
	type LooseNotice = {
		id: string;
		content: string;
		status?: string;
		isDismissible?: boolean;
		actions?: any[];
		[ key: string ]: any;
	};

	export function NoticeList( props: {
		notices: LooseNotice[];
		onRemove?: ( id: string ) => void;
		className?: string;
		children?: ReactNode;
	} ): JSX.Element;

	export function SnackbarList( props: {
		notices: LooseNotice[];
		onRemove?: ( id: string ) => void;
		className?: string;
		children?: ReactNode;
	} ): JSX.Element;
}
