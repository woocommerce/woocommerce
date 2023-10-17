/* eslint-disable @woocommerce/dependency-group */
/* eslint-disable @typescript-eslint/ban-ts-comment */

/**
 * External dependencies
 */
// @ts-ignore No types for this exist yet.
import { useIsSiteEditorLoading } from '@wordpress/edit-site/build-module/components/layout/hooks';
import { useEffect } from '@wordpress/element';

export const useEditorScroll = ( {
	editorSelector,
	scrollDirection = 'bottom',
}: {
	editorSelector: string;
	scrollDirection: 'top' | 'bottom';
} ) => {
	const isEditorLoading = useIsSiteEditorLoading();

	useEffect( () => {
		// Scroll to the bottom of the preview when the editor is done loading.
		if ( isEditorLoading ) {
			return;
		}

		const previewContainer =
			document.querySelector< HTMLIFrameElement >( editorSelector );
		if ( previewContainer ) {
			previewContainer.contentWindow?.scrollTo(
				0,
				scrollDirection === 'bottom'
					? previewContainer.contentDocument?.body.scrollHeight || 0
					: 0
			);
		}
	}, [ isEditorLoading, editorSelector, scrollDirection ] );
};
