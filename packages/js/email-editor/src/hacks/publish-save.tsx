/**
 * External dependencies
 */
import {
	useEffect,
	useState,
	createPortal,
	useCallback,
	useRef,
} from '@wordpress/element';
import { useSelect } from '@wordpress/data';
import {
	store as editorStore,
	useEntitiesSavedStatesIsDirty,
} from '@wordpress/editor';

/**
 * Internal dependencies
 */
import { SendButton } from '../components/header/send-button';
import { useContentValidation } from '../hooks';

import { editorCurrentPostType } from '../store';

type NextButtonSlotPropType = {
	children: React.ReactNode;
};

function NextPublishSlot( { children }: NextButtonSlotPropType ) {
	const [ sendButtonPortalEl ] = useState( document.createElement( 'div' ) );

	// Place element for rendering send button next to publish button
	useEffect( () => {
		const publishButton = document.getElementsByClassName(
			'editor-post-publish-button__button'
		)[ 0 ];
		if ( publishButton ) {
			publishButton.parentNode?.insertBefore(
				sendButtonPortalEl,
				publishButton.nextSibling
			);
		}
	}, [ sendButtonPortalEl ] );

	return createPortal( <>{ children }</>, sendButtonPortalEl );
}

export function PublishSave() {
	const { validateContent, isInvalid } = useContentValidation();
	const observerRef = useRef< MutationObserver | null >( null );
	const { dirtyEntityRecords } = useEntitiesSavedStatesIsDirty();
	const { status } = useSelect(
		( select ) => ( {
			status: select( editorStore ).getEditedPostAttribute( 'status' ),
		} ),
		[]
	);

	// Display original button when there are changes to save except for draft
	// For draft, there is an extra save button to save as draft
	const displayOriginalPublishButton = dirtyEntityRecords.some(
		( entity ) =>
			entity.name !== editorCurrentPostType || status !== 'draft'
	);

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
			{ ! displayOriginalPublishButton && (
				<SendButton
					validateContent={ validateContent }
					isContentInvalid={ isInvalid }
				/>
			) }
		</NextPublishSlot>
	);
}
