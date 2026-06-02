/// <reference path="./constants.d.ts" />
/// <reference path="./use-entity-record.d.ts" />

declare module '@wordpress/core-data/build-types/hooks/use-entity-records' {
	import type { Options } from '@wordpress/core-data/build-types/hooks/use-entity-record';
	import type { Status } from '@wordpress/core-data/build-types/hooks/constants';
	interface EntityRecordsResolution<RecordType> {
	    /** The requested entity records */
	    records: RecordType[] | null;
	    /**
	     * Is the record still being resolved?
	     */
	    isResolving: boolean;
	    /**
	     * Is the record resolved by now?
	     */
	    hasResolved: boolean;
	    /** Resolution status */
	    status: Status;
	    /**
	     * The total number of available items (if not paginated).
	     */
	    totalItems: number | null;
	    /**
	     * The total number of pages.
	     */
	    totalPages: number | null;
	}
	export type WithPermissions<RecordType> = RecordType & {
	    permissions: {
	        delete: boolean;
	        update: boolean;
	    };
	};
	interface EntityRecordsWithPermissionsResolution<RecordType> extends Omit<EntityRecordsResolution<RecordType>, 'records'> {
	    /** The requested entity records with permissions */
	    records: WithPermissions<RecordType>[] | null;
	}
	/**
	 * Resolves the specified entity records.
	 *
	 * @since 6.1.0 Introduced in WordPress core.
	 *
	 * @param    kind      Kind of the entity, e.g. `root` or a `postType`. See rootEntitiesConfig in ../entities.ts for a list of available kinds.
	 * @param    name      Name of the entity, e.g. `plugin` or a `post`. See rootEntitiesConfig in ../entities.ts for a list of available names.
	 * @param    queryArgs Optional HTTP query description for how to fetch the data, passed to the requested API endpoint.
	 * @param    options   Optional hook options.
	 * @example
	 * ```js
	 * import { useEntityRecords } from '@wordpress/core-data';
	 *
	 * function PageTitlesList() {
	 *   const { records, isResolving } = useEntityRecords( 'postType', 'page' );
	 *
	 *   if ( isResolving ) {
	 *     return 'Loading...';
	 *   }
	 *
	 *   return (
	 *     <ul>
	 *       {records.map(( page ) => (
	 *         <li>{ page.title }</li>
	 *       ))}
	 *     </ul>
	 *   );
	 * }
	 *
	 * // Rendered in the application:
	 * // <PageTitlesList />
	 * ```
	 *
	 * In the above example, when `PageTitlesList` is rendered into an
	 * application, the list of records and the resolution details will be retrieved from
	 * the store state using `getEntityRecords()`, or resolved if missing.
	 *
	 * @return Entity records data.
	 * @template RecordType
	 */
	export default function useEntityRecords<RecordType>(kind: string, name: string, queryArgs?: Record<string, unknown>, options?: Options): EntityRecordsResolution<RecordType>;
	export declare function __experimentalUseEntityRecords(kind: string, name: string, queryArgs: any, options: any): EntityRecordsResolution<unknown>;
	export declare function useEntityRecordsWithPermissions<RecordType>(kind: string, name: string, queryArgs?: Record<string, unknown>, options?: Options): EntityRecordsWithPermissionsResolution<RecordType>;
	export {};
}
