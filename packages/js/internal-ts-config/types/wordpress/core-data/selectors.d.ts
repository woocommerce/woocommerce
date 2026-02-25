// Selector and action types for @wordpress/core-data
// This is a script file (no `export {}`) — the declare module block
// fully replaces the module.

declare module '@wordpress/core-data/build-types/selectors' {
	import type * as ET from '@wordpress/core-data/build-types/entity-types';
	import type { Updatable } from '@wordpress/core-data/build-types/entity-types/helpers';

	export type EntityRecordKey = string | number;
	export type GetRecordsHttpQuery = Record< string, any >;

	export interface UserPatternCategory {
		id: number;
		name: string;
		label: string;
		slug: string;
		description: string;
	}

	export interface State {
		[ key: string ]: any;
	}

	export interface GetEntityRecord {
		< EntityRecord extends ET.EntityRecord< any > | Partial< ET.EntityRecord< any > > >(
			state: State,
			kind: string,
			name: string,
			key: EntityRecordKey,
			query?: GetRecordsHttpQuery
		): EntityRecord | undefined;
		CurriedSignature: <
			EntityRecord extends
				| ET.EntityRecord< any >
				| Partial< ET.EntityRecord< any > >
		>(
			kind: string,
			name: string,
			key: EntityRecordKey,
			query?: GetRecordsHttpQuery
		) => EntityRecord | undefined;
		PromiseCurriedSignature: <
			EntityRecord extends
				| ET.EntityRecord< any >
				| Partial< ET.EntityRecord< any > >
		>(
			kind: string,
			name: string,
			key?: EntityRecordKey,
			query?: GetRecordsHttpQuery
		) => Promise< EntityRecord | undefined >;
	}

	export interface GetEntityRecords {
		< EntityRecord extends ET.EntityRecord< any > | Partial< ET.EntityRecord< any > > >(
			state: State,
			kind: string,
			name: string,
			query?: GetRecordsHttpQuery
		): EntityRecord[] | null;
		CurriedSignature: <
			EntityRecord extends
				| ET.EntityRecord< any >
				| Partial< ET.EntityRecord< any > >
		>(
			kind: string,
			name: string,
			query?: GetRecordsHttpQuery
		) => EntityRecord[] | null;
		PromiseCurriedSignature: <
			EntityRecord extends
				| ET.EntityRecord< any >
				| Partial< ET.EntityRecord< any > >
		>(
			kind: string,
			name: string,
			query?: GetRecordsHttpQuery
		) => Promise< EntityRecord[] | null >;
	}

	// ---- Selectors ----
	export interface CoreDataSelectors {
		// Entity record selectors
		getEntityRecord: GetEntityRecord;
		getEntityRecords: GetEntityRecords;
		getEntityRecordEdits(
			state: State,
			kind: string,
			name: string,
			recordId: EntityRecordKey
		): Record< string, any > | undefined;
		getEntityRecordNonTransientEdits(
			state: State,
			kind: string,
			name: string,
			recordId: EntityRecordKey
		): Record< string, any > | undefined;
		hasEntityRecords(
			state: State,
			kind: string,
			name: string,
			query?: GetRecordsHttpQuery
		): boolean;
		getEditedEntityRecord(
			state: State,
			kind: string,
			name: string,
			key: EntityRecordKey
		): Updatable< ET.EntityRecord > | false;
		getRawEntityRecord(
			state: State,
			kind: string,
			name: string,
			key: EntityRecordKey
		): ET.EntityRecord | undefined;
		__experimentalGetEntityRecordNoResolver< EntityRecord extends ET.EntityRecord< any > >(
			state: State,
			kind: string,
			name: string,
			key: EntityRecordKey
		): EntityRecord | undefined;
		getEntityRecordsTotalItems(
			state: State,
			kind: string,
			name: string,
			query: GetRecordsHttpQuery
		): number | null;
		getEntityRecordsTotalPages(
			state: State,
			kind: string,
			name: string,
			query: GetRecordsHttpQuery
		): number | null;
		hasEditsForEntityRecord(
			state: State,
			kind: string,
			name: string,
			recordId: EntityRecordKey
		): boolean;

		// Entity record state selectors
		isSavingEntityRecord(
			state: State,
			kind: string,
			name: string,
			recordId: EntityRecordKey
		): boolean;
		isAutosavingEntityRecord(
			state: State,
			kind: string,
			name: string,
			recordId: EntityRecordKey
		): boolean;
		isDeletingEntityRecord(
			state: State,
			kind: string,
			name: string,
			recordId: EntityRecordKey
		): boolean;
		getLastEntitySaveError(
			state: State,
			kind: string,
			name: string,
			recordId: EntityRecordKey
		): any;
		getLastEntityDeleteError(
			state: State,
			kind: string,
			name: string,
			recordId: EntityRecordKey
		): any;

		// Dirty entity record selectors
		__experimentalGetDirtyEntityRecords( state: State ): Array< {
			title: string;
			key: EntityRecordKey;
			name: string;
			kind: string;
		} >;
		__experimentalGetEntitiesBeingSaved( state: State ): Array< {
			title: string;
			key: EntityRecordKey;
			name: string;
			kind: string;
		} >;

		// Entity config selectors
		getEntity( state: State, kind: string, name: string ): any;
		getEntityConfig( state: State, kind: string, name: string ): any;
		getEntitiesByKind( state: State, kind: string ): Array< any >;
		getEntitiesConfig( state: State, kind: string ): Array< any >;

		// User selectors
		getCurrentUser( state: State ): ET.User< 'edit' >;
		getAuthors(
			state: State,
			query?: GetRecordsHttpQuery
		): ET.User[];
		getUserQueryResults(
			state: State,
			queryID: string
		): ET.User< 'edit' >[];

		// Permission selectors
		canUser(
			state: State,
			action: string,
			resource: string,
			id?: EntityRecordKey
		): boolean | undefined;
		canUserEditEntityRecord(
			state: State,
			kind: string,
			name: string,
			recordId: EntityRecordKey
		): boolean | undefined;

		// Theme selectors
		getCurrentTheme( state: State ): any;
		getThemeSupports( state: State ): any;
		__experimentalGetCurrentGlobalStylesId( state: State ): string;
		__experimentalGetCurrentThemeBaseGlobalStyles( state: State ): any;
		__experimentalGetCurrentThemeGlobalStylesVariations( state: State ): string | null;
		getCurrentThemeGlobalStylesRevisions( state: State ): Array< object > | null;

		// Template selectors
		__experimentalGetTemplateForLink( state: State, link: string ): Updatable< ET.WpTemplate > | null | false | undefined;
		getDefaultTemplateId( state: State, query: {
			slug?: string;
			is_custom?: boolean;
			ignore_empty?: boolean;
		} ): string;

		// Embed selectors
		isRequestingEmbedPreview: Function;
		getEmbedPreview( state: State, url: string ): any;
		isPreviewEmbedFallback( state: State, url: string ): boolean;

		// Autosave selectors
		getAutosaves(
			state: State,
			postType: string,
			postId: EntityRecordKey
		): Array< any > | undefined;
		getAutosave< EntityRecord extends ET.EntityRecord< any > >(
			state: State,
			postType: string,
			postId: EntityRecordKey,
			authorId: EntityRecordKey
		): EntityRecord | undefined;
		hasFetchedAutosaves: Function;

		// Undo/redo selectors
		getUndoEdit( state: State ): any;
		getRedoEdit( state: State ): any;
		hasUndo( state: State ): boolean;
		hasRedo( state: State ): boolean;

		// Revision selectors
		getRevisions(
			state: State,
			kind: string,
			name: string,
			recordKey: EntityRecordKey,
			query?: GetRecordsHttpQuery
		): any[] | null;
		getRevision(
			state: State,
			kind: string,
			name: string,
			recordKey: EntityRecordKey,
			revisionKey: EntityRecordKey,
			query?: GetRecordsHttpQuery
		): any | undefined;

		// Pattern selectors
		getBlockPatterns( state: State ): Array< any >;
		getBlockPatternCategories( state: State ): Array< any >;
		getUserPatternCategories( state: State ): Array< UserPatternCategory >;

		// Misc selectors
		getReferenceByDistinctEdits( state: any ): any;
	}

	// ---- Actions ----
	export interface CoreDataActions {
		[ name: string ]: ( ...args: any[] ) => any;

		// Entity record actions
		saveEntityRecord(
			kind: string,
			name: string,
			record: any,
			options?: {
				isAutosave?: boolean;
				__unstableFetch?: Function;
				throwOnError?: boolean;
			}
		): any;
		saveEditedEntityRecord(
			kind: string,
			name: string,
			recordId: any,
			options?: any
		): any;
		editEntityRecord(
			kind: string,
			name: string,
			recordId: EntityRecordKey,
			edits: any,
			options?: { undoIgnore?: boolean }
		): any;
		deleteEntityRecord(
			kind: string,
			name: string,
			recordId: EntityRecordKey,
			query?: any,
			options?: {
				__unstableFetch?: Function;
				throwOnError?: boolean;
			}
		): any;
		__experimentalSaveSpecifiedEntityEdits(
			kind: string,
			name: string,
			recordId: any,
			itemsToSave: any[],
			options?: any
		): any;
		__experimentalBatch( requests: any[] ): any;

		// Entity config actions
		addEntities( entities: any[] ): any;

		// Receive actions
		receiveEntityRecords(
			kind: string,
			name: string,
			records: any[] | any,
			query?: any,
			invalidateCache?: boolean,
			edits?: any,
			meta?: any
		): any;
		receiveCurrentUser( currentUser: any ): any;
		receiveUserQuery( queryID: string, users: any[] | any ): any;
		receiveCurrentTheme( currentTheme: any ): any;
		receiveThemeSupports(): any;
		receiveThemeGlobalStyleRevisions(
			currentId: number,
			revisions: any[]
		): any;
		receiveEmbedPreview( url: string, preview: any ): any;
		receiveUserPermission( key: string, isAllowed: boolean ): any;
		receiveUploadPermissions( hasUploadPermissions: boolean ): any;
		receiveAutosaves(
			postId: number,
			autosaves: any[] | any
		): any;
		receiveNavigationFallbackId( fallbackId: number ): any;
		receiveDefaultTemplateId( query: any, templateId: string ): any;
		receiveRevisions(
			kind: string,
			name: string,
			recordKey: EntityRecordKey,
			records: any[] | any,
			query?: any,
			invalidateCache?: boolean,
			meta?: any
		): any;
		__experimentalReceiveCurrentGlobalStylesId(
			currentGlobalStylesId: string
		): any;
		__experimentalReceiveThemeBaseGlobalStyles(
			stylesheet: string,
			globalStyles: any
		): any;
		__experimentalReceiveThemeGlobalStyleVariations(
			stylesheet: string,
			variations: any[]
		): any;

		// Undo/redo actions
		undo(): any;
		redo(): any;
		__unstableCreateUndoLevel(): any;
	}
}
