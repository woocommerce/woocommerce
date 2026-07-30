/**
 * External dependencies
 */
import { Button } from '@wordpress/components';
import { useSelect, useDispatch, select as dataSelect } from '@wordpress/data';
import { addFilter } from '@wordpress/hooks';
import { __ } from '@wordpress/i18n';
import { store as editorStore } from '@wordpress/editor';
import { store as coreDataStore } from '@wordpress/core-data';

/**
 * Internal dependencies
 */
import { NAME_SPACE } from './constants';

/**
 * Replacement for the editor's Publish/Save button.
 *
 * With lazy post creation, opening an email creates a draft that stays
 * invisible to rendering. Every explicit save must make the content live —
 * matching how saving an email always behaved — so the first save publishes
 * the post in the background instead of surfacing WordPress's draft/publish
 * flow.
 */
export function SaveButton() {
	const { isSaving, postStatus, isDirty } = useSelect(
		( select ) => ( {
			isSaving: select( editorStore ).isSavingPost(),
			postStatus:
				select( editorStore ).getEditedPostAttribute( 'status' ),
			isDirty:
				select( editorStore ).isEditedPostDirty() ||
				select( editorStore ).hasNonPostEntityChanges(),
		} ),
		[]
	);
	const { editPost, savePost } = useDispatch( editorStore );
	const { saveEditedEntityRecord } = useDispatch( coreDataStore );

	// An unpublished post must stay savable even without edits so the user can
	// accept the file template defaults as-is.
	const isDisabled = isSaving || ( postStatus === 'publish' && ! isDirty );

	const onClick = () => {
		const currentPostId = dataSelect( editorStore ).getCurrentPostId();
		const currentPostType = dataSelect( editorStore ).getCurrentPostType();
		const dirtyRecords = dataSelect(
			coreDataStore
		).__experimentalGetDirtyEntityRecords() as {
			kind: string;
			name: string;
			key: string | number;
		}[];

		if ( postStatus !== 'publish' ) {
			void editPost( { status: 'publish' } );
		}
		void savePost();

		// Persist other dirty entities (e.g. global styles) the way the
		// entities-saved-states panel would; the post itself is handled by
		// savePost() above.
		dirtyRecords
			.filter(
				( record ) =>
					! (
						record.kind === 'postType' &&
						record.name === currentPostType &&
						record.key === currentPostId
					)
			)
			.forEach( ( record ) => {
				void saveEditedEntityRecord(
					record.kind,
					record.name,
					record.key,
					{}
				);
			} );
	};

	return (
		<Button
			variant="primary"
			// The core classes are load-bearing: the email editor package's
			// DOM tracking records `header_save_button_clicked` for clicks on
			// `.editor-post-publish-button` (see the package's
			// events/dom-tracking.ts and the editor-tracking-selectors e2e
			// canary), and its guard also requires the `aria-disabled`
			// attribute below.
			className="editor-post-publish-button editor-post-publish-button__button"
			onClick={ onClick }
			isBusy={ isSaving }
			disabled={ isDisabled }
			aria-disabled={ isDisabled }
		>
			{ __( 'Save', 'woocommerce' ) }
		</Button>
	);
}

/**
 * Injects the save button into the email editor via the wrap-editor filter.
 * Must run before `initializeEditor()`.
 *
 * The custom button is only used while the email post is unpublished — its
 * sole job is to publish the lazily created scratchpad in the background on
 * the first save. Once the post is published, core's stock save flow takes
 * over again, restoring the multi-entity save panel ("Are you ready to
 * save?") when e.g. template changes are pending alongside content changes.
 */
export function registerWooEmailSaveButton() {
	addFilter(
		'woocommerce_email_editor_wrap_editor_component',
		`${ NAME_SPACE }/save-button`,
		( EditorComponent: React.ComponentType< Record< string, unknown > > ) =>
			function EditorWithWooSaveButton(
				props: Record< string, unknown >
			) {
				const postStatus = useSelect(
					( select ) =>
						(
							select( coreDataStore ).getEntityRecord(
								'postType',
								props.postType as string,
								props.postId as string | number
							) as { status?: string } | undefined
						 )?.status,
					[ props.postType, props.postId ]
				);
				const isUnpublished = !! postStatus && postStatus !== 'publish';

				return (
					<EditorComponent
						{ ...props }
						customSaveButton={
							isUnpublished ? <SaveButton /> : undefined
						}
					/>
				);
			}
	);
}
