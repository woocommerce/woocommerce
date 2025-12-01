/**
 * External dependencies
 */
import {
	useEffect,
	createPortal,
	useCallback,
	useRef,
} from '@wordpress/element';
import { useSelect } from '@wordpress/data';
import { store as editorStore } from '@wordpress/editor';

/**
 * Internal dependencies
 */
import { SendButton } from '../components/header/send-button';

type NextButtonSlotPropType = {
	children: React.ReactNode;
};

function NextPublishSlot( { children }: NextButtonSlotPropType ) {
	const sendButtonPortalEl = useRef( document.createElement( 'div' ) );

	// Place element for rendering send button next to publish button
	useEffect( () => {
		const publishButton = document.getElementsByClassName(
			'editor-post-publish-button__button'
		)[ 0 ];
		if ( publishButton ) {
			publishButton.parentNode?.insertBefore(
				sendButtonPortalEl.current,
				publishButton.nextSibling
			);
		}
	}, [ sendButtonPortalEl ] );

	return createPortal( <>{ children }</>, sendButtonPortalEl.current );
}

export function PublishSave() {
	const observerRef = useRef< MutationObserver | null >( null );
	const { hasNonPostEntityChanges, isEditedPostDirty, isEditingTemplate } =
		useSelect(
			( select ) => ( {
				hasNonPostEntityChanges:
					// @ts-expect-error hasNonPostEntityChanges is not typed in @types/wordpress__editor
					select( editorStore ).hasNonPostEntityChanges(),
				isEditedPostDirty: select( editorStore ).isEditedPostDirty(),
				isEditingTemplate:
					select( editorStore ).getCurrentPostType() ===
					'wp_template',
			} ),
			[]
		);

	// Display the original publish button when:
	// - The user is editing a template (regardless of detected changes).
	// - There are changes outside of the post (e.g., in a linked template).
	// - There are changes in the post together with changes outside of it.
	//
	// Do not display the button when:
	// - There are only changes in the post content with no related entity changes.
	// Draft posts have their own "Save as draft" button.
	const displayOriginalPublishButton =
		isEditingTemplate ||
		hasNonPostEntityChanges ||
		( isEditedPostDirty && hasNonPostEntityChanges );

	const toggleElementVisible = useCallback(
		( element: Element, visible: boolean ) => {
			if ( visible && element.classList.contains( 'force-hidden' ) ) {
				element.classList.remove( 'force-hidden' );
			}
			if ( ! visible && ! element.classList.contains( 'force-hidden' ) ) {
				element.classList.add( 'force-hidden' );
			}
		},
		[]
	);

	useEffect( () => {
		const publishButton = document.getElementsByClassName(
			'editor-post-publish-button__button'
		)[ 0 ];
		toggleElementVisible( publishButton, displayOriginalPublishButton );

		if ( ! publishButton ) {
			return () => observerRef.current?.disconnect();
		}

		// It may get additionally re-rendered by the editor, so we need to check it again
		if ( observerRef.current ) {
			observerRef.current.disconnect();
		}
		observerRef.current = new MutationObserver( () => {
			toggleElementVisible( publishButton, displayOriginalPublishButton );
		} );
		observerRef.current.observe( publishButton, {
			attributes: true,
			childList: true,
			subtree: false,
		} );

		// Cleanup observer
		return () => observerRef.current?.disconnect();
	}, [ displayOriginalPublishButton, toggleElementVisible ] );

	return (
		<NextPublishSlot>
			{ ! displayOriginalPublishButton && <SendButton /> }
		</NextPublishSlot>
	);
}
