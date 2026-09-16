/**
 * External dependencies
 */
import { Button } from '@wordpress/components';
import { useSelect, useDispatch, select as dataSelect } from '@wordpress/data';
import { useEffect, useRef } from '@wordpress/element';
import { addFilter } from '@wordpress/hooks';
import { __ } from '@wordpress/i18n';
import { store as editorStore } from '@wordpress/editor';
import { store as coreDataStore } from '@wordpress/core-data';
import {
	useShortcut,
	store as keyboardShortcutsStore,
	type ShortcutKeyCombination,
} from '@wordpress/keyboard-shortcuts';

/**
 * Internal dependencies
 */
import { NAME_SPACE } from './constants';

const SAVE_SHORTCUT_NAME = 'woocommerce/email-editor/save';

/**
 * Take over the editor's save shortcut (Cmd/Ctrl+S) while the custom Save
 * button is mounted, so the shortcut publishes exactly like the button.
 *
 * Core's handler saves a draft without publishing — with the publish step
 * hidden behind the custom button, that leaves content invisible to sending.
 * Every handler registered for a shortcut fires on a key match, so adding a
 * second one would double-save; instead `core/editor/save` is unregistered
 * (its handler stops matching) and the key combination is re-registered
 * under an own name. Unmounting restores core's registration for the
 * published-post phase, where the stock save flow takes over again.
 *
 * @param onSave     Save handler shared with the button.
 * @param isDisabled Whether saving is currently disabled.
 */
function useSaveShortcutTakeover( onSave: () => void, isDisabled: boolean ) {
	const coreShortcut = useSelect(
		( select ) => ( {
			keyCombination: select(
				keyboardShortcutsStore
			).getShortcutKeyCombination(
				'core/editor/save'
			) as ShortcutKeyCombination | null,
			description: select(
				keyboardShortcutsStore
			).getShortcutDescription( 'core/editor/save' ) as
				| string
				| undefined,
		} ),
		[]
	);
	const { registerShortcut, unregisterShortcut } = useDispatch(
		keyboardShortcutsStore
	);
	const stashedShortcut = useRef< {
		keyCombination: ShortcutKeyCombination;
		description: string | undefined;
	} | null >( null );

	// Driven by the store, not mount order: EditorKeyboardShortcutsRegister
	// registers `core/editor/save` in its own mount effect, so a plain mount
	// effect here could run earlier and unregister nothing. Re-runs whenever
	// core's registration reappears while the button stays mounted, so the
	// takeover cannot be undone by a re-registration.
	useEffect( () => {
		if ( ! coreShortcut.keyCombination ) {
			return;
		}
		if ( ! stashedShortcut.current ) {
			stashedShortcut.current = {
				keyCombination: coreShortcut.keyCombination,
				description: coreShortcut.description,
			};
		}
		void unregisterShortcut( 'core/editor/save' );
		void registerShortcut( {
			name: SAVE_SHORTCUT_NAME,
			category: 'global',
			description: __( 'Save your changes.', 'woocommerce' ),
			keyCombination: coreShortcut.keyCombination,
		} );
	}, [ coreShortcut, registerShortcut, unregisterShortcut ] );

	useEffect( () => {
		return () => {
			if ( ! stashedShortcut.current ) {
				return;
			}
			void unregisterShortcut( SAVE_SHORTCUT_NAME );
			void registerShortcut( {
				name: 'core/editor/save',
				category: 'global',
				description: stashedShortcut.current.description,
				keyCombination: stashedShortcut.current.keyCombination,
			} );
			stashedShortcut.current = null;
		};
	}, [ registerShortcut, unregisterShortcut ] );

	useShortcut(
		SAVE_SHORTCUT_NAME,
		( event: { preventDefault: () => void } ) => {
			event.preventDefault();
			if ( isDisabled ) {
				return;
			}
			onSave();
		}
	);
}

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

	useSaveShortcutTakeover( onClick, isDisabled );

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
