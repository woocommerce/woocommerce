/**
 * External dependencies
 */
import { FontSize } from '@wordpress/components/build-types/font-size-picker/types';
import { store as blockEditorStore } from '@wordpress/block-editor';
import { store as keyboardShortutsStore } from '@wordpress/keyboard-shortcuts';
import { store as interfaceStore } from '@wordpress/interface';
import { store as preferencesStore } from '@wordpress/preferences';
import { store as noticesStore } from '@wordpress/notices';
import {
	ActionCreatorsOf,
	ConfigOf,
	CurriedSelectorsOf,
	DataRegistry,
	StoreDescriptor as GenericStoreDescriptor,
	UseSelectReturn,
} from '@wordpress/data/build-types/types';
import {
	Color,
	Gradient,
} from '@wordpress/components/build-types/palette-edit/types';

/**
 * Internal dependencies
 */
import './wordpress-modules';

/* eslint-disable @typescript-eslint/no-explicit-any -- some general types in this file need to use "any"  */
/* eslint-disable @typescript-eslint/naming-convention -- we have no control over 3rd-party naming conventions */
/* eslint-disable no-underscore-dangle -- we have no control over 3rd-party naming conventions */

export type FontFamily = {
	name: string;
	slug: string;
	fontFamily: string;
};

// fix and improve some @wordpress/data types
declare module '@wordpress/data' {
	// Derive typings for select(), dispatch(), useSelect(), and useDispatch()calls
	// by store name. The StoreMap interface can be augmented to add custom stores.
	interface StoreMap {
		[ key: string ]: StoreDescriptor;
	}

	type TKey = keyof StoreMap;
	type TStore< T > = T extends keyof StoreMap ? StoreMap[ T ] : never;
	type TSelectors< T > = CurriedSelectorsOf< TStore< T > >;
	type TActions< T > = ActionCreatorsOf< ConfigOf< TStore< T > > >;
	type TSelectFunction = < T extends TKey | StoreDescriptor >(
		store: T
	) => T extends TKey ? TSelectors< T > : CurriedSelectorsOf< T >;
	type TMapSelect = (
		select: TSelectFunction,
		registry: DataRegistry
	) => any;

	// select('store-name')
	function select< T extends string >( store: T ): TSelectors< T >;

	// fix return type for select(storeDescriptor)
	export function select< T extends GenericStoreDescriptor< any > >(
		store: T
	): CurriedSelectorsOf< T >;

	// dispatch('store-name')
	function dispatch< T extends string >( store: T ): TActions< T >;

	// fix return type for dispatch(storeDescriptor)
	export function dispatch< T extends GenericStoreDescriptor< any > >(
		store: T
	): ActionCreatorsOf< ConfigOf< T > >;

	// function "batch" is missing in data registry
	export function useRegistry(): {
		batch: ( callback: () => void ) => void;
	};

	// useSelect((select) => select('store-name') => ...)
	// useSelect((select) => select(storeDescriptor) => ...)
	export function useSelect< T extends TMapSelect >(
		mapSelect: T,
		deps?: unknown[]
	): ReturnType< T >;

	// useSelect(storeDescriptor)
	export function useSelect< T extends StoreDescriptor >(
		store: T,
		deps?: unknown[]
	): UseSelectReturn< T >;

	// useSelect('store-name')
	export function useSelect< T extends string >(
		store: T,
		deps?: unknown[]
	): UseSelectReturn< TStore< T > >;

	// useDispatch('store-name')
	export function useDispatch< T extends string >( store: T ): TActions< T >;

	// types for "createRegistrySelector" are not correct
	export function createRegistrySelector<
		S extends typeof select,
		T extends ( state: any, ...args: any ) => any
	>( registrySelector: ( select: S ) => T ): T;

	interface StoreMap {
		[ blockEditorStore.name ]: typeof blockEditorStore;
		[ keyboardShortutsStore.name ]: typeof keyboardShortutsStore;
		[ interfaceStore.name ]: typeof interfaceStore;
		[ preferencesStore.name ]: typeof preferencesStore;
		[ noticesStore.name ]: typeof noticesStore;
	}
}

declare module '@wordpress/block-editor' {
	export const __experimentalLibrary: any;
	export const __experimentalListView: any;

	// types for 'useSetting' are missing in @types/wordpress__block-editor
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
