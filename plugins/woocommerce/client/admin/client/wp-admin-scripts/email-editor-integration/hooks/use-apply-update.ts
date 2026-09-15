/**
 * External dependencies
 */
import { useCallback, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import { select, useDispatch } from '@wordpress/data';
import { store as noticesStore } from '@wordpress/notices';
import { store as coreStore } from '@wordpress/core-data';

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

interface UseApplyUpdateOptions {
	/**
	 * When true, suppress the `createErrorNotice` snackbar fired on
	 * `/apply` failure. Use when the calling surface (e.g., RSM-141's
	 * editor banner) carries the error feedback itself and would
	 * otherwise produce a double-error UI.
	 *
	 * Default: false — existing behavior (error snackbar fires).
	 *
	 * @since RSM-141
	 */
	suppressSnackbarOnError?: boolean;
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
 * Sync uses `receiveEntityRecords` to push the server's freshly-saved
 * content straight into core-data's cache. The reducer auto-clears any
 * matching pending edits, so no follow-up `saveEditedEntityRecord`
 * round-trip is needed.
 *
 * @param postId  The `woo_email` post ID.
 * @param options Optional behavior flags (e.g., `suppressSnackbarOnError`).
 */
export function useApplyUpdate(
	postId: number | null,
	options: UseApplyUpdateOptions = {}
): UseApplyUpdateResult {
	const { createSuccessNotice, createErrorNotice } =
		useDispatch( noticesStore );
	const { receiveEntityRecords } = useDispatch( coreStore );
	const [ isApplying, setIsApplying ] = useState< boolean >( false );

	const syncEditorState = useCallback(
		( content: string ) => {
			if ( ! postId ) {
				return;
			}
			// Read the current canonical record so the patched record we
			// hand to `receiveEntityRecords` keeps every other field
			// (title, status, meta, …) intact. Only `content.raw` changes.
			const current = select( coreStore ).getEntityRecord(
				'postType',
				'woo_email',
				postId
			) as { content?: { raw?: string } } | undefined;
			if ( ! current ) {
				return;
			}
			void receiveEntityRecords(
				'postType',
				'woo_email',
				[
					{
						...current,
						content: { ...current.content, raw: content },
					},
				],
				undefined,
				false,
				undefined,
				undefined
			);
		},
		[ postId, receiveEntityRecords ]
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

				syncEditorState( res.restored_content );

				void createSuccessNotice(
					__( 'Update reverted.', 'woocommerce' ),
					{
						type: 'snackbar',
					}
				);
			} catch ( err: unknown ) {
				const message =
					err && typeof err === 'object' && 'message' in err
						? String( err.message )
						: __( 'Could not revert the update.', 'woocommerce' );
				void createErrorNotice( message, { type: 'snackbar' } );
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

				syncEditorState( res.merged_content );

				// Snackbar wording follows what the merchant actually chose.
				// Smart Apply (`choices: []`) defaults every conflict to
				// `keep_yours`, so customizations are preserved. If the
				// drawer-driven path picked `use_core` for everything, the
				// merchant explicitly chose to overwrite — say so plainly.
				const allUseCore =
					choices.length > 0 &&
					choices.every( ( c ) => c.decision === 'use_core' );
				const successMessage = allUseCore
					? __( 'Update applied', 'woocommerce' )
					: __(
							'Update applied · customizations preserved',
							'woocommerce'
					  );
				void createSuccessNotice( successMessage, {
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
				if ( ! options.suppressSnackbarOnError ) {
					void createErrorNotice( message, { type: 'snackbar' } );
				}
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
			options.suppressSnackbarOnError,
		]
	);

	return { apply, isApplying };
}
