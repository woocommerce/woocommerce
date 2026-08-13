/**
 * Type declarations to extend WordPress core-data types.
 *
 * These augmentations add DataViews-related settings that are returned for
 * post types in the Next Admin API responses.
 */

import type {
	CurriedSelectorsOf,
	DataRegistry,
	DispatchReturn,
	PromisifyActionCreator,
	UseDispatchReturn,
} from '@wordpress/data';
import type {
	finishResolution,
	invalidateResolution,
	invalidateResolutionForStore,
	invalidateResolutionForStoreSelector,
	startResolution,
} from '@wordpress/data/build-types/redux-store/metadata/actions';
import type { isResolving as metadataIsResolving } from '@wordpress/data/build-types/redux-store/metadata/selectors';
import type { Context, store } from '@wordpress/core-data';
import type { SupportedLayouts, View, Form } from '@wordpress/dataviews';

interface ViewPreset {
	slug: string;
	label: string;
	view: View;
}

interface PostTypeVisibility {
	/**
	 * Whether to generate a default UI for managing this post type.
	 */
	show_ui: boolean;
}

type PostTypeDataViewsFields< C extends Context = 'edit' > = {
	/**
	 * DataViews configuration for the post type. Contains default view settings
	 * like layout, filters, sorting, etc.
	 */
	default_view?: View;

	/**
	 * Default layouts for the post type.
	 */
	default_layouts?: SupportedLayouts;

	/**
	 * An array of views that are alternative presets to the default_view.
	 */
	views?: ViewPreset[];

	/**
	 * Default form for the quick edit form.
	 */
	quick_edit_form?: Form;

	/**
	 * The visibility settings for the post type.
	 */
	visibility?: PostTypeVisibility;

	/**
	 * Whether the post type is hierarchical.
	 */
	hierarchical?: boolean;
};

type PostAugmentation< C extends Context = 'edit' > = {
	/**
	 * The number of comments on the post.
	 */
	comment_count: number | undefined;
};

type UserAugmentation< C extends Context = 'edit' > = {
	/**
	 * The number of posts by the user.
	 */
	post_count?: number;

	/**
	 * Whether syntax highlighting is enabled for the user.
	 */
	syntax_highlighting?: boolean;
};

type PluginAugmentation< C extends Context = 'edit' > = {
	/**
	 * Plugin tags.
	 */
	tags: string[];
	/**
	 * Plugin rating.
	 */
	rating: number;
	/**
	 * The date/time when the plugin was last updated.
	 * Format is a date-time string as returned by the REST API.
	 */
	last_updated: string;
	/**
	 * Whether the plugin has been recently active.
	 */
	recently_active?: boolean;
	/**
	 * Whether the plugin is set to auto-update.
	 */
	auto_update: boolean;
	/**
	 * Whether an update is available for the plugin.
	 */
	update_available: boolean;
};

type ThemeAutoUpdates = {
	/**
	 * Whether the theme is set to auto-update.
	 */
	enabled: boolean;

	/**
	 * Whether the theme is forced to auto-update.
	 */
	forced: boolean | null;

	/**
	 * Whether the theme supports auto-updates.
	 */
	supported: boolean;
};

type ThemeUpdateAvailable = {
	/**
	 * Indicates that there is an update available for the theme.
	 */
	has_update: true;

	/**
	 * The current version of the theme.
	 */
	current_version: string;

	/**
	 * The new version of the theme.
	 */
	new_version: string;

	/**
	 * The URL to the update.
	 */
	update_url: string;

	/**
	 * The package URL for the update.
	 */
	package: string;
};

type ThemeUpdateNotAvailable = {
	/**
	 * Indicates that there is no update available for the theme.
	 */
	has_update: false;

	/**
	 * The current version of the theme.
	 */
	current_version: string;

	/**
	 * The new version of the theme, if available.
	 */
	new_version: null;

	/**
	 * The URL to the update, if available.
	 */
	update_url: null;

	/**
	 * The package URL for the update, if available.
	 */
	package: null;
};

type ThemeAugmentation< C extends Context = 'edit' > = {
	/**
	 * Auto-update settings for the theme.
	 */
	auto_updates: ThemeAutoUpdates;

	/**
	 * Version update information for the theme.
	 */
	update_info: ThemeUpdateAvailable | ThemeUpdateNotAvailable;

	/**
	 * The permissions for the theme.
	 */
	permissions: {
		update?: boolean;
	};
};

declare module '@wordpress/core-data' {
	export namespace BaseEntityRecords {
		export interface Type< C extends Context = 'edit' >
			extends PostTypeDataViewsFields< C > {}

		export interface Post< C extends Context = 'edit' >
			extends PostAugmentation< C > {}

		export interface User< C extends Context = 'edit' >
			extends UserAugmentation< C > {}

		export interface Plugin< C extends Context = 'edit' >
			extends PluginAugmentation< C > {}

		export interface Theme< C extends Context = 'edit' >
			extends ThemeAugmentation< C > {}
	}

	export interface TypeVisibility extends PostTypeVisibility {}
}

/**
 * Ensure the type extensions are visible when the underlying BaseEntityRecords
 * namespace is imported directly inside @wordpress/core-data's build types.
 */
declare module '@wordpress/core-data/build-types/entity-types/base-entity-records' {
	export namespace BaseEntityRecords {
		export interface Type< C extends Context = 'edit' >
			extends PostTypeDataViewsFields< C > {}

		export interface Post< C extends Context = 'edit' >
			extends PostAugmentation< C > {}

		export interface User< C extends Context = 'edit' >
			extends UserAugmentation< C > {}

		export interface Plugin< C extends Context = 'edit' >
			extends PluginAugmentation< C > {}

		export interface Theme< C extends Context = 'edit' >
			extends ThemeAugmentation< C > {}
	}
}

type IsResolvingArgs =
	| Parameters< typeof metadataIsResolving >[ 2 ]
	| ReadonlyArray< unknown >;

type MetadataSelectors = {
	isResolving: (
		selectorName: Parameters< typeof metadataIsResolving >[ 1 ],
		args?: IsResolvingArgs
	) => ReturnType< typeof metadataIsResolving >;
};

type CoreDataSelectFunction = {
	(
		storeNameOrDescriptor: typeof store
	): CurriedSelectorsOf< typeof store > & MetadataSelectors;
	< S >( storeNameOrDescriptor: S ): CurriedSelectorsOf< S >;
};

type MetadataActions = {
	startResolution: typeof startResolution;
	finishResolution: typeof finishResolution;
	invalidateResolution: typeof invalidateResolution;
	invalidateResolutionForStore: typeof invalidateResolutionForStore;
	invalidateResolutionForStoreSelector: typeof invalidateResolutionForStoreSelector;
};

export type WPDataActions = {
	[ Action in keyof MetadataActions ]: PromisifyActionCreator<
		MetadataActions[ Action ]
	>;
};

declare module '@wordpress/data' {
	/**
	 * Add resolver metadata helpers to the dispatch/useDispatch return type for the core-data store.
	 */
	export function dispatch(
		storeNameOrDescriptor: typeof store
	): DispatchReturn< typeof store > & WPDataActions;
	export function useDispatch(
		storeNameOrDescriptor: typeof store
	): UseDispatchReturn< typeof store > & WPDataActions;
	export function select(
		storeNameOrDescriptor: typeof store
	): CurriedSelectorsOf< typeof store > & MetadataSelectors;
	export function useSelect< TResult >(
		mapSelect: (
			select: CoreDataSelectFunction,
			registry?: DataRegistry
		) => TResult,
		deps?: unknown[]
	): TResult;
	export function useSelect(
		storeNameOrDescriptor: typeof store
	): CurriedSelectorsOf< typeof store > & MetadataSelectors;
}
