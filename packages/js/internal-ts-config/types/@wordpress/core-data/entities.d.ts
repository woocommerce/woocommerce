declare module '@wordpress/core-data/build-types/entities' {
	export const DEFAULT_ENTITY_KEY: "id";
	export const rootEntitiesConfig: ({
	    label: import("@wordpress/i18n").TranslatableText<"Base">;
	    kind: string;
	    key: boolean;
	    name: string;
	    baseURL: string;
	    baseURLParams: {
	        _fields: string;
	        context?: undefined;
	    };
	    plural: string;
	    rawAttributes?: undefined;
	    supportsPagination?: undefined;
	    transientEdits?: undefined;
	    getTitle?: undefined;
	    getRevisionsUrl?: undefined;
	} | {
	    label: import("@wordpress/i18n").TranslatableText<"Post Type">;
	    name: string;
	    kind: string;
	    key: string;
	    baseURL: string;
	    baseURLParams: {
	        context: string;
	        _fields?: undefined;
	    };
	    plural: string;
	    rawAttributes?: undefined;
	    supportsPagination?: undefined;
	    transientEdits?: undefined;
	    getTitle?: undefined;
	    getRevisionsUrl?: undefined;
	} | {
	    name: string;
	    kind: string;
	    baseURL: string;
	    baseURLParams: {
	        context: string;
	        _fields?: undefined;
	    };
	    plural: string;
	    label: import("@wordpress/i18n").TranslatableText<"Media">;
	    rawAttributes: string[];
	    supportsPagination: boolean;
	    key?: undefined;
	    transientEdits?: undefined;
	    getTitle?: undefined;
	    getRevisionsUrl?: undefined;
	} | {
	    name: string;
	    kind: string;
	    key: string;
	    baseURL: string;
	    baseURLParams: {
	        context: string;
	        _fields?: undefined;
	    };
	    plural: string;
	    label: import("@wordpress/i18n").TranslatableText<"Taxonomy">;
	    rawAttributes?: undefined;
	    supportsPagination?: undefined;
	    transientEdits?: undefined;
	    getTitle?: undefined;
	    getRevisionsUrl?: undefined;
	} | {
	    name: string;
	    kind: string;
	    baseURL: string;
	    baseURLParams: {
	        context: string;
	        _fields?: undefined;
	    };
	    plural: string;
	    transientEdits: {
	        blocks: boolean;
	    };
	    label: import("@wordpress/i18n").TranslatableText<"Widget areas">;
	    key?: undefined;
	    rawAttributes?: undefined;
	    supportsPagination?: undefined;
	    getTitle?: undefined;
	    getRevisionsUrl?: undefined;
	} | {
	    name: string;
	    kind: string;
	    baseURL: string;
	    baseURLParams: {
	        context: string;
	        _fields?: undefined;
	    };
	    plural: string;
	    transientEdits: {
	        blocks: boolean;
	    };
	    label: import("@wordpress/i18n").TranslatableText<"Widgets">;
	    key?: undefined;
	    rawAttributes?: undefined;
	    supportsPagination?: undefined;
	    getTitle?: undefined;
	    getRevisionsUrl?: undefined;
	} | {
	    name: string;
	    kind: string;
	    baseURL: string;
	    baseURLParams: {
	        context: string;
	        _fields?: undefined;
	    };
	    plural: string;
	    label: import("@wordpress/i18n").TranslatableText<"Widget types">;
	    key?: undefined;
	    rawAttributes?: undefined;
	    supportsPagination?: undefined;
	    transientEdits?: undefined;
	    getTitle?: undefined;
	    getRevisionsUrl?: undefined;
	} | {
	    label: import("@wordpress/i18n").TranslatableText<"User">;
	    name: string;
	    kind: string;
	    baseURL: string;
	    getTitle: (record: any) => any;
	    baseURLParams: {
	        context: string;
	        _fields?: undefined;
	    };
	    plural: string;
	    supportsPagination: boolean;
	    key?: undefined;
	    rawAttributes?: undefined;
	    transientEdits?: undefined;
	    getRevisionsUrl?: undefined;
	} | {
	    name: string;
	    kind: string;
	    baseURL: string;
	    baseURLParams: {
	        context: string;
	        _fields?: undefined;
	    };
	    plural: string;
	    label: import("@wordpress/i18n").TranslatableText<"Comment">;
	    supportsPagination: boolean;
	    key?: undefined;
	    rawAttributes?: undefined;
	    transientEdits?: undefined;
	    getTitle?: undefined;
	    getRevisionsUrl?: undefined;
	} | {
	    name: string;
	    kind: string;
	    baseURL: string;
	    baseURLParams: {
	        context: string;
	        _fields?: undefined;
	    };
	    plural: string;
	    label: import("@wordpress/i18n").TranslatableText<"Menu">;
	    supportsPagination: boolean;
	    key?: undefined;
	    rawAttributes?: undefined;
	    transientEdits?: undefined;
	    getTitle?: undefined;
	    getRevisionsUrl?: undefined;
	} | {
	    name: string;
	    kind: string;
	    baseURL: string;
	    baseURLParams: {
	        context: string;
	        _fields?: undefined;
	    };
	    plural: string;
	    label: import("@wordpress/i18n").TranslatableText<"Menu Item">;
	    rawAttributes: string[];
	    supportsPagination: boolean;
	    key?: undefined;
	    transientEdits?: undefined;
	    getTitle?: undefined;
	    getRevisionsUrl?: undefined;
	} | {
	    name: string;
	    kind: string;
	    baseURL: string;
	    baseURLParams: {
	        context: string;
	        _fields?: undefined;
	    };
	    plural: string;
	    label: import("@wordpress/i18n").TranslatableText<"Menu Location">;
	    key: string;
	    rawAttributes?: undefined;
	    supportsPagination?: undefined;
	    transientEdits?: undefined;
	    getTitle?: undefined;
	    getRevisionsUrl?: undefined;
	} | {
	    label: import("@wordpress/i18n").TranslatableText<"Global Styles">;
	    name: string;
	    kind: string;
	    baseURL: string;
	    baseURLParams: {
	        context: string;
	        _fields?: undefined;
	    };
	    plural: string;
	    getTitle: () => import("@wordpress/i18n").TranslatableText<"Custom Styles">;
	    getRevisionsUrl: (parentId: any, revisionId: any) => string;
	    supportsPagination: boolean;
	    key?: undefined;
	    rawAttributes?: undefined;
	    transientEdits?: undefined;
	} | {
	    label: import("@wordpress/i18n").TranslatableText<"Themes">;
	    name: string;
	    kind: string;
	    baseURL: string;
	    baseURLParams: {
	        context: string;
	        _fields?: undefined;
	    };
	    plural: string;
	    key: string;
	    rawAttributes?: undefined;
	    supportsPagination?: undefined;
	    transientEdits?: undefined;
	    getTitle?: undefined;
	    getRevisionsUrl?: undefined;
	} | {
	    label: import("@wordpress/i18n").TranslatableText<"Plugins">;
	    name: string;
	    kind: string;
	    baseURL: string;
	    baseURLParams: {
	        context: string;
	        _fields?: undefined;
	    };
	    plural: string;
	    key: string;
	    rawAttributes?: undefined;
	    supportsPagination?: undefined;
	    transientEdits?: undefined;
	    getTitle?: undefined;
	    getRevisionsUrl?: undefined;
	} | {
	    label: import("@wordpress/i18n").TranslatableText<"Status">;
	    name: string;
	    kind: string;
	    baseURL: string;
	    baseURLParams: {
	        context: string;
	        _fields?: undefined;
	    };
	    plural: string;
	    key: string;
	    rawAttributes?: undefined;
	    supportsPagination?: undefined;
	    transientEdits?: undefined;
	    getTitle?: undefined;
	    getRevisionsUrl?: undefined;
	})[];
	export namespace deprecatedEntities {
	    namespace root {
	        namespace media {
	            let since: string;
	            namespace alternative {
	                let kind: string;
	                let name: string;
	            }
	        }
	    }
	}
	export const additionalEntityConfigLoaders: ({
	    kind: string;
	    loadEntities: typeof loadPostTypeEntities;
	    name?: undefined;
	    plural?: undefined;
	} | {
	    kind: string;
	    name: string;
	    plural: string;
	    loadEntities: typeof loadSiteEntity;
	})[];
	export function prePersistPostType(persistedRecord: any, edits: any): any;
	export function getMethodName(kind: string, name: string, prefix?: string): string;
	/**
	 * Returns the list of post type entities.
	 *
	 * @return {Promise} Entities promise
	 */
	declare function loadPostTypeEntities(): Promise<any>;
	/**
	 * Returns the Site entity.
	 *
	 * @return {Promise} Entity promise
	 */
	declare function loadSiteEntity(): Promise<any>;
	export {};
}
