/// <reference path="./selectors.d.ts" />

declare module '@wordpress/core-data/build-types/private-selectors' {
	/**
	 * Internal dependencies
	 */
	import { type State } from '@wordpress/core-data/build-types/selectors';
	type EntityRecordKey = string | number;
	/**
	 * Returns the previous edit from the current undo offset
	 * for the entity records edits history, if any.
	 *
	 * @param state State tree.
	 *
	 * @return The undo manager.
	 */
	export declare function getUndoManager(state: State): import("@wordpress/undo-manager").UndoManager;
	/**
	 * Retrieve the fallback Navigation.
	 *
	 * @param state Data state.
	 * @return The ID for the fallback Navigation post.
	 */
	export declare function getNavigationFallbackId(state: State): EntityRecordKey | undefined;
	export declare const getBlockPatternsForPostType: {
	    (state: any, postType: any): any;
	    isRegistrySelector?: boolean;
	    registry?: any;
	};
	/**
	 * Returns the entity records permissions for the given entity record ids.
	 */
	export declare const getEntityRecordsPermissions: {
	    (state: State, kind: string, name: string, ids: string | string[]): {
	        delete: any;
	        update: any;
	    }[];
	    isRegistrySelector?: boolean;
	    registry?: any;
	};
	/**
	 * Returns the entity record permissions for the given entity record id.
	 *
	 * @param state Data state.
	 * @param kind  Entity kind.
	 * @param name  Entity name.
	 * @param id    Entity record id.
	 *
	 * @return The entity record permissions.
	 */
	export declare function getEntityRecordPermissions(state: State, kind: string, name: string, id: string): {
	    delete: any;
	    update: any;
	};
	/**
	 * Returns the registered post meta fields for a given post type.
	 *
	 * @param state    Data state.
	 * @param postType Post type.
	 *
	 * @return Registered post meta fields.
	 */
	export declare function getRegisteredPostMeta(state: State, postType: string): Object;
	export declare const getHomePage: {
	    (): {
	        postType: string;
	        postId: any;
	    } | null;
	    isRegistrySelector?: boolean;
	    registry?: any;
	};
	export declare const getPostsPageId: {
	    (): string | null;
	    isRegistrySelector?: boolean;
	    registry?: any;
	};
	export declare const getTemplateId: {
	    (state: any, postType: any, postId: any): any;
	    isRegistrySelector?: boolean;
	    registry?: any;
	};
	export {};
}
