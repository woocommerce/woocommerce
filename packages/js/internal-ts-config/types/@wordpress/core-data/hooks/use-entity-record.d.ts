/// <reference path="./constants.d.ts" />

declare module '@wordpress/core-data/build-types/hooks/use-entity-record' {
	import type { Status } from '@wordpress/core-data/build-types/hooks/constants';
	export interface EntityRecordResolution<RecordType> {
	    /** The requested entity record */
	    record: RecordType | null;
	    /** The edited entity record */
	    editedRecord: Partial<RecordType>;
	    /** The edits to the edited entity record */
	    edits: Partial<RecordType>;
	    /** Apply local (in-browser) edits to the edited entity record */
	    edit: (diff: Partial<RecordType>) => void;
	    /** Persist the edits to the server */
	    save: () => Promise<void>;
	    /**
	     * Is the record still being resolved?
	     */
	    isResolving: boolean;
	    /**
	     * Does the record have any local edits?
	     */
	    hasEdits: boolean;
	    /**
	     * Is the record resolved by now?
	     */
	    hasResolved: boolean;
	    /** Resolution status */
	    status: Status;
	}
	export interface Options {
	    /**
	     * Whether to run the query or short-circuit and return null.
	     *
	     * @default true
	     */
	    enabled: boolean;
	}
	/**
	 * Resolves the specified entity record.
	 *
	 * @since 6.1.0 Introduced in WordPress core.
	 *
	 * @param    kind     Kind of the entity, e.g. `root` or a `postType`. See rootEntitiesConfig in ../entities.ts for a list of available kinds.
	 * @param    name     Name of the entity, e.g. `plugin` or a `post`. See rootEntitiesConfig in ../entities.ts for a list of available names.
	 * @param    recordId ID of the requested entity record.
	 * @param    options  Optional hook options.
	 * @example
	 * ```js
	 * import { useEntityRecord } from '@wordpress/core-data';
	 *
	 * function PageTitleDisplay( { id } ) {
	 *   const { record, isResolving } = useEntityRecord( 'postType', 'page', id );
	 *
	 *   if ( isResolving ) {
	 *     return 'Loading...';
	 *   }
	 *
	 *   return record.title;
	 * }
	 *
	 * // Rendered in the application:
	 * // <PageTitleDisplay id={ 1 } />
	 * ```
	 *
	 * In the above example, when `PageTitleDisplay` is rendered into an
	 * application, the page and the resolution details will be retrieved from
	 * the store state using `getEntityRecord()`, or resolved if missing.
	 *
	 * @example
	 * ```js
	 * import { useCallback } from 'react';
	 * import { useDispatch } from '@wordpress/data';
	 * import { __ } from '@wordpress/i18n';
	 * import { TextControl } from '@wordpress/components';
	 * import { store as noticeStore } from '@wordpress/notices';
	 * import { useEntityRecord } from '@wordpress/core-data';
	 *
	 * function PageRenameForm( { id } ) {
	 * 	const page = useEntityRecord( 'postType', 'page', id );
	 * 	const { createSuccessNotice, createErrorNotice } =
	 * 		useDispatch( noticeStore );
	 *
	 * 	const setTitle = useCallback( ( title ) => {
	 * 		page.edit( { title } );
	 * 	}, [ page.edit ] );
	 *
	 * 	if ( page.isResolving ) {
	 * 		return 'Loading...';
	 * 	}
	 *
	 * 	async function onRename( event ) {
	 * 		event.preventDefault();
	 * 		try {
	 * 			await page.save();
	 * 			createSuccessNotice( __( 'Page renamed.' ), {
	 * 				type: 'snackbar',
	 * 			} );
	 * 		} catch ( error ) {
	 * 			createErrorNotice( error.message, { type: 'snackbar' } );
	 * 		}
	 * 	}
	 *
	 * 	return (
	 * 		<form onSubmit={ onRename }>
	 * 			<TextControl
	 *				__nextHasNoMarginBottom
	 *				__next40pxDefaultSize
	 * 				label={ __( 'Name' ) }
	 * 				value={ page.editedRecord.title }
	 * 				onChange={ setTitle }
	 * 			/>
	 * 			<button type="submit">{ __( 'Save' ) }</button>
	 * 		</form>
	 * 	);
	 * }
	 *
	 * // Rendered in the application:
	 * // <PageRenameForm id={ 1 } />
	 * ```
	 *
	 * In the above example, updating and saving the page title is handled
	 * via the `edit()` and `save()` mutation helpers provided by
	 * `useEntityRecord()`;
	 *
	 * @return Entity record data.
	 * @template RecordType
	 */
	export default function useEntityRecord<RecordType>(kind: string, name: string, recordId: string | number, options?: Options): EntityRecordResolution<RecordType>;
	export declare function __experimentalUseEntityRecord(kind: string, name: string, recordId: any, options: any): EntityRecordResolution<unknown>;
}
