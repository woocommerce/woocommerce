/**
 * External dependencies
 */
import { useCallback, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import { useDispatch } from '@wordpress/data';
import { store as noticesStore } from '@wordpress/notices';
import { store as coreStore } from '@wordpress/core-data';
import { parse, serialize } from '@wordpress/blocks';

/**
 * Shape of an entry on the `choices` array sent to /apply.
 */
export interface ApplyChoice {
	/** Path of a `copy_changes` entry from the change-summary. */
	path: Array< number | string >;
	/** `keep_yours` (default) or `use_core`. */
	decision: 'keep_yours' | 'use_core';
}

/**
 * Shape of the /apply response.
 */
interface ApplyResponse {
	merged_content: string;
	revision_id: string;
	version_to: string;
	status: 'applied';
	structural_skipped: boolean;
	aliases_migrated: string[];
}

/**
 * Shape of the /undo response.
 */
interface UndoResponse {
	restored_content: string;
	status: 'restored';
}

interface UseApplyUpdateResult {
	apply: ( choices: ApplyChoice[] ) => Promise< ApplyResponse | null >;
	isApplying: boolean;
}

/**
 * Drive the apply + undo flow for a `woo_email` post.
 *
 * On apply success: shows a snackbar with an Undo action wired to the
 * `/undo` endpoint, syncs the editor's in-memory entity to the merged
 * content (so the canvas reflects the apply without a page reload), and
 * surfaces the migrated alias list in the snackbar copy when applicable.
 *
 * Mirrors the post-write sync pattern from
 * `reset-notification-email-content.tsx` (parse → editEntityRecord →
 * saveEditedEntityRecord) so dirty tracking stays consistent with what
 * the server just persisted.
 *
 * @param postId The `woo_email` post ID.
 */
export function useApplyUpdate( postId: number | null ): UseApplyUpdateResult {
	const { createSuccessNotice, createErrorNotice } =
		useDispatch( noticesStore );
	const { editEntityRecord, saveEditedEntityRecord } =
		useDispatch( coreStore );
	const [ isApplying, setIsApplying ] = useState< boolean >( false );

	const syncEditorState = useCallback(
		async ( content: string ) => {
			if ( ! postId ) {
				return;
			}
			const blocks = parse( content || '' );
			await editEntityRecord( 'postType', 'woo_email', postId, {
				blocks,
				content: serialize( blocks ),
			} );
			await saveEditedEntityRecord( 'postType', 'woo_email', postId, {} );
		},
		[ postId, editEntityRecord, saveEditedEntityRecord ]
	);

	const undo = useCallback(
		async ( revisionId: string ) => {
			if ( ! postId ) {
				return;
			}
			try {
				const res = ( await apiFetch( {
					path: `/woocommerce-email-editor/v1/emails/${ postId }/undo`,
					method: 'POST',
					data: { revision_id: revisionId },
				} ) ) as UndoResponse;

				await syncEditorState( res.restored_content );

				createSuccessNotice( __( 'Update reverted.', 'woocommerce' ), {
					type: 'snackbar',
				} );
			} catch ( err: unknown ) {
				const message =
					err && typeof err === 'object' && 'message' in err
						? String( err.message )
						: __( 'Could not revert the update.', 'woocommerce' );
				createErrorNotice( message, { type: 'snackbar' } );
			}
		},
		[ postId, createSuccessNotice, createErrorNotice, syncEditorState ]
	);

	const apply = useCallback(
		async ( choices: ApplyChoice[] ): Promise< ApplyResponse | null > => {
			if ( ! postId ) {
				return null;
			}
			setIsApplying( true );
			try {
				const res = ( await apiFetch( {
					path: `/woocommerce-email-editor/v1/emails/${ postId }/apply`,
					method: 'POST',
					data: { choices },
				} ) ) as ApplyResponse;

				await syncEditorState( res.merged_content );

				createSuccessNotice( __( 'Update applied.', 'woocommerce' ), {
					type: 'snackbar',
					actions: [
						{
							label: __( 'Undo', 'woocommerce' ),
							onClick: () => {
								void undo( res.revision_id );
							},
						},
					],
				} );

				return res;
			} catch ( err: unknown ) {
				const message =
					err && typeof err === 'object' && 'message' in err
						? String( err.message )
						: __( 'Could not apply the update.', 'woocommerce' );
				createErrorNotice( message, { type: 'snackbar' } );
				return null;
			} finally {
				setIsApplying( false );
			}
		},
		[
			postId,
			createSuccessNotice,
			createErrorNotice,
			undo,
			syncEditorState,
		]
	);

	return { apply, isApplying };
}
