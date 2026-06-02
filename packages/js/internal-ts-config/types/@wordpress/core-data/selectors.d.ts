/// <reference path="./entity-types/index.d.ts" />

declare module '@wordpress/core-data/build-types/selectors' {
	import type * as ET from '@wordpress/core-data/build-types/entity-types';
	import type { UndoManager } from '@wordpress/undo-manager';
	export interface State {
	    autosaves: Record<string | number, Array<unknown>>;
	    blockPatterns: Array<unknown>;
	    blockPatternCategories: Array<unknown>;
	    currentGlobalStylesId: string;
	    currentTheme: string;
	    currentUser: ET.User<'view'>;
	    embedPreviews: Record<string, {
	        html: string;
	    }>;
	    entities: EntitiesState;
	    themeBaseGlobalStyles: Record<string, Object>;
	    themeGlobalStyleVariations: Record<string, string>;
	    themeGlobalStyleRevisions: Record<number, Object>;
	    undoManager: UndoManager;
	    userPermissions: Record<string, boolean>;
	    users: UserState;
	    navigationFallbackId: EntityRecordKey;
	    userPatternCategories: Array<UserPatternCategory>;
	    defaultTemplates: Record<string, string>;
	    registeredPostMeta: Record<string, Object>;
	}
	type EntityRecordKey = string | number;
	interface EntitiesState {
	    config: EntityConfig[];
	    records: Record<string, Record<string, EntityState<ET.EntityRecord>>>;
	}
	interface QueriedData {
	    items: Record<ET.Context, Record<number, ET.EntityRecord>>;
	    itemIsComplete: Record<ET.Context, Record<number, boolean>>;
	    queries: Record<ET.Context, Record<string, Array<number>>>;
	}
	type RevisionRecord = Record<ET.Context, Record<number, ET.PostRevision>> | Record<ET.Context, Record<number, ET.GlobalStylesRevision>>;
	interface RevisionsQueriedData {
	    items: RevisionRecord;
	    itemIsComplete: Record<ET.Context, Record<number, boolean>>;
	    queries: Record<ET.Context, Record<string, Array<number>>>;
	}
	interface EntityState<EntityRecord extends ET.EntityRecord> {
	    edits: Record<string, Partial<EntityRecord>>;
	    saving: Record<string, Partial<{
	        pending: boolean;
	        isAutosave: boolean;
	        error: Error;
	    }>>;
	    deleting: Record<string, Partial<{
	        pending: boolean;
	        error: Error;
	    }>>;
	    queriedData: QueriedData;
	    revisions?: RevisionsQueriedData;
	}
	interface EntityConfig {
	    name: string;
	    kind: string;
	}
	interface UserState {
	    queries: Record<string, EntityRecordKey[]>;
	    byId: Record<EntityRecordKey, ET.User<'edit'>>;
	}
	type TemplateQuery = {
	    slug?: string;
	    is_custom?: boolean;
	    ignore_empty?: boolean;
	};
	export interface UserPatternCategory {
	    id: number;
	    name: string;
	    label: string;
	    slug: string;
	    description: string;
	}
	type Optional<T> = T | undefined;
	/**
	 * HTTP Query parameters sent with the API request to fetch the entity records.
	 */
	export type GetRecordsHttpQuery = Record<string, any>;
	/**
	 * Arguments for EntityRecord selectors.
	 */
	type EntityRecordArgs = [string, string, EntityRecordKey] | [string, string, EntityRecordKey, GetRecordsHttpQuery];
	type EntityResource = {
	    kind: string;
	    name: string;
	    id?: EntityRecordKey;
	};
	/**
	 * Returns true if a request is in progress for embed preview data, or false
	 * otherwise.
	 *
	 * @param state Data state.
	 * @param url   URL the preview would be for.
	 *
	 * @return Whether a request is in progress for an embed preview.
	 */
	export declare const isRequestingEmbedPreview: {
	    (state: State, url: string): boolean;
	    isRegistrySelector?: boolean;
	    registry?: any;
	};
	/**
	 * Returns all available authors.
	 *
	 * @deprecated since 11.3. Callers should use `select( 'core' ).getUsers({ who: 'authors' })` instead.
	 *
	 * @param      state Data state.
	 * @param      query Optional object of query parameters to
	 *                   include with request. For valid query parameters see the [Users page](https://developer.wordpress.org/rest-api/reference/users/) in the REST API Handbook and see the arguments for [List Users](https://developer.wordpress.org/rest-api/reference/users/#list-users) and [Retrieve a User](https://developer.wordpress.org/rest-api/reference/users/#retrieve-a-user).
	 * @return Authors list.
	 */
	export declare function getAuthors(state: State, query?: GetRecordsHttpQuery): ET.User[];
	/**
	 * Returns the current user.
	 *
	 * @param state Data state.
	 *
	 * @return Current user object.
	 */
	export declare function getCurrentUser(state: State): ET.User<'view'>;
	/**
	 * Returns all the users returned by a query ID.
	 *
	 * @param state   Data state.
	 * @param queryID Query ID.
	 *
	 * @return Users list.
	 */
	export declare const getUserQueryResults: ((state: State, queryID: string) => ET.User<"edit">[]) & import("rememo").EnhancedSelector;
	/**
	 * Returns the loaded entities for the given kind.
	 *
	 * @deprecated since WordPress 6.0. Use getEntitiesConfig instead
	 * @param      state Data state.
	 * @param      kind  Entity kind.
	 *
	 * @return Array of entities with config matching kind.
	 */
	export declare function getEntitiesByKind(state: State, kind: string): Array<any>;
	/**
	 * Returns the loaded entities for the given kind.
	 *
	 * @param state Data state.
	 * @param kind  Entity kind.
	 *
	 * @return Array of entities with config matching kind.
	 */
	export declare const getEntitiesConfig: ((state: State, kind: string) => Array<any>) & import("rememo").EnhancedSelector;
	/**
	 * Returns the entity config given its kind and name.
	 *
	 * @deprecated since WordPress 6.0. Use getEntityConfig instead
	 * @param      state Data state.
	 * @param      kind  Entity kind.
	 * @param      name  Entity name.
	 *
	 * @return Entity config
	 */
	export declare function getEntity(state: State, kind: string, name: string): any;
	/**
	 * Returns the entity config given its kind and name.
	 *
	 * @param state Data state.
	 * @param kind  Entity kind.
	 * @param name  Entity name.
	 *
	 * @return Entity config
	 */
	export declare function getEntityConfig(state: State, kind: string, name: string): any;
	/**
	 * GetEntityRecord is declared as a *callable interface* with
	 * two signatures to work around the fact that TypeScript doesn't
	 * allow currying generic functions:
	 *
	 * ```ts
	 * 		type CurriedState = F extends ( state: any, ...args: infer P ) => infer R
	 * 			? ( ...args: P ) => R
	 * 			: F;
	 * 		type Selector = <K extends string | number>(
	 *         state: any,
	 *         kind: K,
	 *         key: K extends string ? 'string value' : false
	 *    ) => K;
	 * 		type BadlyInferredSignature = CurriedState< Selector >
	 *    // BadlyInferredSignature evaluates to:
	 *    // (kind: string number, key: false | "string value") => string number
	 * ```
	 *
	 * The signature without the state parameter shipped as CurriedSignature
	 * is used in the return value of `select( coreStore )`.
	 *
	 * See https://github.com/WordPress/gutenberg/pull/41578 for more details.
	 */
	export interface GetEntityRecord {
	    <EntityRecord extends ET.EntityRecord<any> | Partial<ET.EntityRecord<any>>>(state: State, kind: string, name: string, key?: EntityRecordKey, query?: GetRecordsHttpQuery): EntityRecord | undefined;
	    CurriedSignature: <EntityRecord extends ET.EntityRecord<any> | Partial<ET.EntityRecord<any>>>(kind: string, name: string, key?: EntityRecordKey, query?: GetRecordsHttpQuery) => EntityRecord | undefined;
	    __unstableNormalizeArgs?: (args: EntityRecordArgs) => EntityRecordArgs;
	}
	/**
	 * Returns the Entity's record object by key. Returns `null` if the value is not
	 * yet received, undefined if the value entity is known to not exist, or the
	 * entity object if it exists and is received.
	 *
	 * @param state State tree
	 * @param kind  Entity kind.
	 * @param name  Entity name.
	 * @param key   Optional record's key. If requesting a global record (e.g. site settings), the key can be omitted. If requesting a specific item, the key must always be included.
	 * @param query Optional query. If requesting specific
	 *              fields, fields must always include the ID. For valid query parameters see the [Reference](https://developer.wordpress.org/rest-api/reference/) in the REST API Handbook and select the entity kind. Then see the arguments available "Retrieve a [Entity kind]".
	 *
	 * @return Record.
	 */
	export declare const getEntityRecord: GetEntityRecord;
	/**
	 * Returns true if a record has been received for the given set of parameters, or false otherwise.
	 *
	 * Note: This action does not trigger a request for the entity record from the API
	 * if it's not available in the local state.
	 *
	 * @param state State tree
	 * @param kind  Entity kind.
	 * @param name  Entity name.
	 * @param key   Record's key.
	 * @param query Optional query.
	 *
	 * @return Whether an entity record has been received.
	 */
	export declare function hasEntityRecord(state: State, kind: string, name: string, key?: EntityRecordKey, query?: GetRecordsHttpQuery): boolean;
	/**
	 * Returns the Entity's record object by key. Doesn't trigger a resolver nor requests the entity records from the API if the entity record isn't available in the local state.
	 *
	 * @param state State tree
	 * @param kind  Entity kind.
	 * @param name  Entity name.
	 * @param key   Record's key
	 *
	 * @return Record.
	 */
	export declare function __experimentalGetEntityRecordNoResolver<EntityRecord extends ET.EntityRecord<any>>(state: State, kind: string, name: string, key: EntityRecordKey): EntityRecord | undefined;
	/**
	 * Returns the entity's record object by key,
	 * with its attributes mapped to their raw values.
	 *
	 * @param state State tree.
	 * @param kind  Entity kind.
	 * @param name  Entity name.
	 * @param key   Record's key.
	 *
	 * @return Object with the entity's raw attributes.
	 */
	export declare const getRawEntityRecord: (<EntityRecord extends ET.EntityRecord<any>>(state: State, kind: string, name: string, key: EntityRecordKey) => EntityRecord | undefined) & import("rememo").EnhancedSelector;
	/**
	 * Returns true if records have been received for the given set of parameters,
	 * or false otherwise.
	 *
	 * @param state State tree
	 * @param kind  Entity kind.
	 * @param name  Entity name.
	 * @param query Optional terms query. For valid query parameters see the [Reference](https://developer.wordpress.org/rest-api/reference/) in the REST API Handbook and select the entity kind. Then see the arguments available for "List [Entity kind]s".
	 *
	 * @return  Whether entity records have been received.
	 */
	export declare function hasEntityRecords(state: State, kind: string, name: string, query?: GetRecordsHttpQuery): boolean;
	/**
	 * GetEntityRecord is declared as a *callable interface* with
	 * two signatures to work around the fact that TypeScript doesn't
	 * allow currying generic functions.
	 *
	 * @see GetEntityRecord
	 * @see https://github.com/WordPress/gutenberg/pull/41578
	 */
	export interface GetEntityRecords {
	    <EntityRecord extends ET.EntityRecord<any> | Partial<ET.EntityRecord<any>>>(state: State, kind: string, name: string, query?: GetRecordsHttpQuery): EntityRecord[] | null;
	    CurriedSignature: <EntityRecord extends ET.EntityRecord<any> | Partial<ET.EntityRecord<any>>>(kind: string, name: string, query?: GetRecordsHttpQuery) => EntityRecord[] | null;
	}
	/**
	 * Returns the Entity's records.
	 *
	 * @param state State tree
	 * @param kind  Entity kind.
	 * @param name  Entity name.
	 * @param query Optional terms query. If requesting specific
	 *              fields, fields must always include the ID. For valid query parameters see the [Reference](https://developer.wordpress.org/rest-api/reference/) in the REST API Handbook and select the entity kind. Then see the arguments available for "List [Entity kind]s".
	 *
	 * @return Records.
	 */
	export declare const getEntityRecords: GetEntityRecords;
	/**
	 * Returns the Entity's total available records for a given query (ignoring pagination).
	 *
	 * @param state State tree
	 * @param kind  Entity kind.
	 * @param name  Entity name.
	 * @param query Optional terms query. If requesting specific
	 *              fields, fields must always include the ID. For valid query parameters see the [Reference](https://developer.wordpress.org/rest-api/reference/) in the REST API Handbook and select the entity kind. Then see the arguments available for "List [Entity kind]s".
	 *
	 * @return number | null.
	 */
	export declare const getEntityRecordsTotalItems: (state: State, kind: string, name: string, query: GetRecordsHttpQuery) => number | null;
	/**
	 * Returns the number of available pages for the given query.
	 *
	 * @param state State tree
	 * @param kind  Entity kind.
	 * @param name  Entity name.
	 * @param query Optional terms query. If requesting specific
	 *              fields, fields must always include the ID. For valid query parameters see the [Reference](https://developer.wordpress.org/rest-api/reference/) in the REST API Handbook and select the entity kind. Then see the arguments available for "List [Entity kind]s".
	 *
	 * @return number | null.
	 */
	export declare const getEntityRecordsTotalPages: (state: State, kind: string, name: string, query: GetRecordsHttpQuery) => number | null;
	type DirtyEntityRecord = {
	    title: string;
	    key: EntityRecordKey;
	    name: string;
	    kind: string;
	};
	/**
	 * Returns the list of dirty entity records.
	 *
	 * @param state State tree.
	 *
	 * @return The list of updated records
	 */
	export declare const __experimentalGetDirtyEntityRecords: ((state: State) => Array<DirtyEntityRecord>) & import("rememo").EnhancedSelector;
	/**
	 * Returns the list of entities currently being saved.
	 *
	 * @param state State tree.
	 *
	 * @return The list of records being saved.
	 */
	export declare const __experimentalGetEntitiesBeingSaved: ((state: State) => Array<DirtyEntityRecord>) & import("rememo").EnhancedSelector;
	/**
	 * Returns the specified entity record's edits.
	 *
	 * @param state    State tree.
	 * @param kind     Entity kind.
	 * @param name     Entity name.
	 * @param recordId Record ID.
	 *
	 * @return The entity record's edits.
	 */
	export declare function getEntityRecordEdits(state: State, kind: string, name: string, recordId: EntityRecordKey): Optional<any>;
	/**
	 * Returns the specified entity record's non transient edits.
	 *
	 * Transient edits don't create an undo level, and
	 * are not considered for change detection.
	 * They are defined in the entity's config.
	 *
	 * @param state    State tree.
	 * @param kind     Entity kind.
	 * @param name     Entity name.
	 * @param recordId Record ID.
	 *
	 * @return The entity record's non transient edits.
	 */
	export declare const getEntityRecordNonTransientEdits: ((state: State, kind: string, name: string, recordId: EntityRecordKey) => Optional<any>) & import("rememo").EnhancedSelector;
	/**
	 * Returns true if the specified entity record has edits,
	 * and false otherwise.
	 *
	 * @param state    State tree.
	 * @param kind     Entity kind.
	 * @param name     Entity name.
	 * @param recordId Record ID.
	 *
	 * @return Whether the entity record has edits or not.
	 */
	export declare function hasEditsForEntityRecord(state: State, kind: string, name: string, recordId: EntityRecordKey): boolean;
	/**
	 * Returns the specified entity record, merged with its edits.
	 *
	 * @param state    State tree.
	 * @param kind     Entity kind.
	 * @param name     Entity name.
	 * @param recordId Record ID.
	 *
	 * @return The entity record, merged with its edits.
	 */
	export declare const getEditedEntityRecord: (<EntityRecord extends ET.EntityRecord<any>>(state: State, kind: string, name: string, recordId: EntityRecordKey) => ET.Updatable<EntityRecord> | false) & import("rememo").EnhancedSelector;
	/**
	 * Returns true if the specified entity record is autosaving, and false otherwise.
	 *
	 * @param state    State tree.
	 * @param kind     Entity kind.
	 * @param name     Entity name.
	 * @param recordId Record ID.
	 *
	 * @return Whether the entity record is autosaving or not.
	 */
	export declare function isAutosavingEntityRecord(state: State, kind: string, name: string, recordId: EntityRecordKey): boolean;
	/**
	 * Returns true if the specified entity record is saving, and false otherwise.
	 *
	 * @param state    State tree.
	 * @param kind     Entity kind.
	 * @param name     Entity name.
	 * @param recordId Record ID.
	 *
	 * @return Whether the entity record is saving or not.
	 */
	export declare function isSavingEntityRecord(state: State, kind: string, name: string, recordId: EntityRecordKey): boolean;
	/**
	 * Returns true if the specified entity record is deleting, and false otherwise.
	 *
	 * @param state    State tree.
	 * @param kind     Entity kind.
	 * @param name     Entity name.
	 * @param recordId Record ID.
	 *
	 * @return Whether the entity record is deleting or not.
	 */
	export declare function isDeletingEntityRecord(state: State, kind: string, name: string, recordId: EntityRecordKey): boolean;
	/**
	 * Returns the specified entity record's last save error.
	 *
	 * @param state    State tree.
	 * @param kind     Entity kind.
	 * @param name     Entity name.
	 * @param recordId Record ID.
	 *
	 * @return The entity record's save error.
	 */
	export declare function getLastEntitySaveError(state: State, kind: string, name: string, recordId: EntityRecordKey): any;
	/**
	 * Returns the specified entity record's last delete error.
	 *
	 * @param state    State tree.
	 * @param kind     Entity kind.
	 * @param name     Entity name.
	 * @param recordId Record ID.
	 *
	 * @return The entity record's save error.
	 */
	export declare function getLastEntityDeleteError(state: State, kind: string, name: string, recordId: EntityRecordKey): any;
	/**
	 * Returns the previous edit from the current undo offset
	 * for the entity records edits history, if any.
	 *
	 * @deprecated since 6.3
	 *
	 * @param      state State tree.
	 *
	 * @return The edit.
	 */
	export declare function getUndoEdit(state: State): Optional<any>;
	/**
	 * Returns the next edit from the current undo offset
	 * for the entity records edits history, if any.
	 *
	 * @deprecated since 6.3
	 *
	 * @param      state State tree.
	 *
	 * @return The edit.
	 */
	export declare function getRedoEdit(state: State): Optional<any>;
	/**
	 * Returns true if there is a previous edit from the current undo offset
	 * for the entity records edits history, and false otherwise.
	 *
	 * @param state State tree.
	 *
	 * @return Whether there is a previous edit or not.
	 */
	export declare function hasUndo(state: State): boolean;
	/**
	 * Returns true if there is a next edit from the current undo offset
	 * for the entity records edits history, and false otherwise.
	 *
	 * @param state State tree.
	 *
	 * @return Whether there is a next edit or not.
	 */
	export declare function hasRedo(state: State): boolean;
	/**
	 * Return the current theme.
	 *
	 * @param state Data state.
	 *
	 * @return The current theme.
	 */
	export declare function getCurrentTheme(state: State): any;
	/**
	 * Return the ID of the current global styles object.
	 *
	 * @param state Data state.
	 *
	 * @return The current global styles ID.
	 */
	export declare function __experimentalGetCurrentGlobalStylesId(state: State): string;
	/**
	 * Return theme supports data in the index.
	 *
	 * @param state Data state.
	 *
	 * @return Index data.
	 */
	export declare function getThemeSupports(state: State): any;
	/**
	 * Returns the embed preview for the given URL.
	 *
	 * @param state Data state.
	 * @param url   Embedded URL.
	 *
	 * @return Undefined if the preview has not been fetched, otherwise, the preview fetched from the embed preview API.
	 */
	export declare function getEmbedPreview(state: State, url: string): any;
	/**
	 * Determines if the returned preview is an oEmbed link fallback.
	 *
	 * WordPress can be configured to return a simple link to a URL if it is not embeddable.
	 * We need to be able to determine if a URL is embeddable or not, based on what we
	 * get back from the oEmbed preview API.
	 *
	 * @param state Data state.
	 * @param url   Embedded URL.
	 *
	 * @return Is the preview for the URL an oEmbed link fallback.
	 */
	export declare function isPreviewEmbedFallback(state: State, url: string): boolean;
	/**
	 * Returns whether the current user can perform the given action on the given
	 * REST resource.
	 *
	 * Calling this may trigger an OPTIONS request to the REST API via the
	 * `canUser()` resolver.
	 *
	 * https://developer.wordpress.org/rest-api/reference/
	 *
	 * @param state    Data state.
	 * @param action   Action to check. One of: 'create', 'read', 'update', 'delete'.
	 * @param resource Entity resource to check. Accepts entity object `{ kind: 'postType', name: 'attachment', id: 1 }`
	 *                 or REST base as a string - `media`.
	 * @param id       Optional ID of the rest resource to check.
	 *
	 * @return Whether or not the user can perform the action,
	 *                             or `undefined` if the OPTIONS request is still being made.
	 */
	export declare function canUser(state: State, action: string, resource: string | EntityResource, id?: EntityRecordKey): boolean | undefined;
	/**
	 * Returns whether the current user can edit the given entity.
	 *
	 * Calling this may trigger an OPTIONS request to the REST API via the
	 * `canUser()` resolver.
	 *
	 * https://developer.wordpress.org/rest-api/reference/
	 *
	 * @param state    Data state.
	 * @param kind     Entity kind.
	 * @param name     Entity name.
	 * @param recordId Record's id.
	 * @return Whether or not the user can edit,
	 * or `undefined` if the OPTIONS request is still being made.
	 */
	export declare function canUserEditEntityRecord(state: State, kind: string, name: string, recordId: EntityRecordKey): boolean | undefined;
	/**
	 * Returns the latest autosaves for the post.
	 *
	 * May return multiple autosaves since the backend stores one autosave per
	 * author for each post.
	 *
	 * @param state    State tree.
	 * @param postType The type of the parent post.
	 * @param postId   The id of the parent post.
	 *
	 * @return An array of autosaves for the post, or undefined if there is none.
	 */
	export declare function getAutosaves(state: State, postType: string, postId: EntityRecordKey): Array<any> | undefined;
	/**
	 * Returns the autosave for the post and author.
	 *
	 * @param state    State tree.
	 * @param postType The type of the parent post.
	 * @param postId   The id of the parent post.
	 * @param authorId The id of the author.
	 *
	 * @return The autosave for the post and author.
	 */
	export declare function getAutosave<EntityRecord extends ET.EntityRecord<any>>(state: State, postType: string, postId: EntityRecordKey, authorId: EntityRecordKey): EntityRecord | undefined;
	/**
	 * Returns true if the REST request for autosaves has completed.
	 *
	 * @param state    State tree.
	 * @param postType The type of the parent post.
	 * @param postId   The id of the parent post.
	 *
	 * @return True if the REST request was completed. False otherwise.
	 */
	export declare const hasFetchedAutosaves: {
	    (state: State, postType: string, postId: EntityRecordKey): boolean;
	    isRegistrySelector?: boolean;
	    registry?: any;
	};
	/**
	 * Returns a new reference when edited values have changed. This is useful in
	 * inferring where an edit has been made between states by comparison of the
	 * return values using strict equality.
	 *
	 * @example
	 *
	 * ```
	 * const hasEditOccurred = (
	 *    getReferenceByDistinctEdits( beforeState ) !==
	 *    getReferenceByDistinctEdits( afterState )
	 * );
	 * ```
	 *
	 * @param state Editor state.
	 *
	 * @return A value whose reference will change only when an edit occurs.
	 */
	export declare function getReferenceByDistinctEdits(state: any): any;
	/**
	 * Retrieve the current theme's base global styles
	 *
	 * @param state Editor state.
	 *
	 * @return The Global Styles object.
	 */
	export declare function __experimentalGetCurrentThemeBaseGlobalStyles(state: State): any;
	/**
	 * Return the ID of the current global styles object.
	 *
	 * @param state Data state.
	 *
	 * @return The current global styles ID.
	 */
	export declare function __experimentalGetCurrentThemeGlobalStylesVariations(state: State): string | null;
	/**
	 * Retrieve the list of registered block patterns.
	 *
	 * @param state Data state.
	 *
	 * @return Block pattern list.
	 */
	export declare function getBlockPatterns(state: State): Array<any>;
	/**
	 * Retrieve the list of registered block pattern categories.
	 *
	 * @param state Data state.
	 *
	 * @return Block pattern category list.
	 */
	export declare function getBlockPatternCategories(state: State): Array<any>;
	/**
	 * Retrieve the registered user pattern categories.
	 *
	 * @param state Data state.
	 *
	 * @return User patterns category array.
	 */
	export declare function getUserPatternCategories(state: State): Array<UserPatternCategory>;
	/**
	 * Returns the revisions of the current global styles theme.
	 *
	 * @deprecated since WordPress 6.5.0. Callers should use `select( 'core' ).getRevisions( 'root', 'globalStyles', ${ recordKey } )` instead, where `recordKey` is the id of the global styles parent post.
	 *
	 * @param      state Data state.
	 *
	 * @return The current global styles.
	 */
	export declare function getCurrentThemeGlobalStylesRevisions(state: State): Array<object> | null;
	/**
	 * Returns the default template use to render a given query.
	 *
	 * @param state Data state.
	 * @param query Query.
	 *
	 * @return The default template id for the given query.
	 */
	export declare function getDefaultTemplateId(state: State, query: TemplateQuery): string;
	/**
	 * Returns an entity's revisions.
	 *
	 * @param state     State tree
	 * @param kind      Entity kind.
	 * @param name      Entity name.
	 * @param recordKey The key of the entity record whose revisions you want to fetch.
	 * @param query     Optional query. If requesting specific
	 *                  fields, fields must always include the ID. For valid query parameters see revisions schema in [the REST API Handbook](https://developer.wordpress.org/rest-api/reference/). Then see the arguments available "Retrieve a [Entity kind]".
	 *
	 * @return Record.
	 */
	export declare const getRevisions: (state: State, kind: string, name: string, recordKey: EntityRecordKey, query?: GetRecordsHttpQuery) => RevisionRecord[] | null;
	/**
	 * Returns a single, specific revision of a parent entity.
	 *
	 * @param state       State tree
	 * @param kind        Entity kind.
	 * @param name        Entity name.
	 * @param recordKey   The key of the entity record whose revisions you want to fetch.
	 * @param revisionKey The revision's key.
	 * @param query       Optional query. If requesting specific
	 *                    fields, fields must always include the ID. For valid query parameters see revisions schema in [the REST API Handbook](https://developer.wordpress.org/rest-api/reference/). Then see the arguments available "Retrieve a [entity kind]".
	 *
	 * @return Record.
	 */
	export declare const getRevision: ((state: State, kind: string, name: string, recordKey: EntityRecordKey, revisionKey: EntityRecordKey, query?: GetRecordsHttpQuery) => RevisionRecord | Record<PropertyKey, never> | undefined) & import("rememo").EnhancedSelector;
	export {};
}
