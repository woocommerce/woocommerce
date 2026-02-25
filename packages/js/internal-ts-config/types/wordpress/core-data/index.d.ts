// Full module replacement for @wordpress/core-data
// This is a script file (no `export {}`) so each `declare module` block
// fully replaces the module rather than augmenting it. This ensures
// TypeScript emits portable `import("@wordpress/core-data/...")` paths
// in downstream declaration files instead of resolving through
// non-portable pnpm virtual store paths.

/// <reference path="./entity-types/helpers.d.ts" />
/// <reference path="./entity-types/index.d.ts" />
/// <reference path="./selectors.d.ts" />

// --- Main module ---
declare module '@wordpress/core-data' {
	import type { Context } from '@wordpress/core-data/build-types/entity-types/helpers';
	import type {
		CoreDataSelectors,
		CoreDataActions,
	} from '@wordpress/core-data/build-types/selectors';

	export type {
		User,
		Post,
		Settings,
		Page,
		WpTemplate,
		Attachment,
		Comment,
		GlobalStylesRevision,
		MenuLocation,
		NavMenu,
		NavMenuItem,
		Plugin,
		PostRevision,
		PostStatusObject,
		Sidebar,
		Taxonomy,
		Term,
		Theme,
		Type,
		Widget,
		WidgetType,
		WpTemplatePart,
		Base,
		EntityRecord,
		PerPackageEntityRecords,
	} from '@wordpress/core-data/build-types/entity-types';

	export type { Context } from '@wordpress/core-data/build-types/entity-types/helpers';

	export type {
		GetEntityRecord,
		GetEntityRecords,
		GetRecordsHttpQuery,
		EntityRecordKey,
	} from '@wordpress/core-data/build-types/selectors';

	export const store: import( '@wordpress/data' ).StoreDescriptor<
		import( '@wordpress/data' ).ReduxStoreConfig<
			any,
			CoreDataActions,
			CoreDataSelectors
		>
	>;

	// Hooks
	interface EntityRecordResolution< RecordType > {
		record: RecordType | null;
		editedRecord: Partial< RecordType >;
		edits: Partial< RecordType >;
		edit: ( diff: Partial< RecordType > ) => void;
		save: () => Promise< void >;
		isResolving: boolean;
		hasEdits: boolean;
		hasResolved: boolean;
		status: string;
	}

	interface EntityRecordsResolution< RecordType > {
		records: RecordType[] | null;
		isResolving: boolean;
		hasResolved: boolean;
		status: string;
		totalItems: number | null;
		totalPages: number | null;
	}

	interface EntityHookOptions {
		enabled?: boolean;
	}

	export function useEntityRecord< RecordType >(
		kind: string,
		name: string,
		recordId: string | number,
		options?: EntityHookOptions
	): EntityRecordResolution< RecordType >;

	export function useEntityRecords< RecordType >(
		kind: string,
		name: string,
		queryArgs?: Record< string, unknown >,
		options?: EntityHookOptions
	): EntityRecordsResolution< RecordType >;

	export function useEntityProp(
		kind: string,
		name: string,
		prop: string,
		_id?: number | string
	): [ any, Function, any ];

	export function useEntityId( kind: string, name: string ): any;

	export function EntityProvider( props: {
		kind: string;
		type: string;
		id: number;
		children: any;
	} ): any;
}

// --- StoreRegistry registration ---
declare module '@wordpress/data' {
	interface StoreRegistry {
		core: typeof import( '@wordpress/core-data' ).store;
	}
}
