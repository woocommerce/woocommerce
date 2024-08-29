/* eslint-disable @woocommerce/dependency-group */
/* eslint-disable @typescript-eslint/ban-ts-comment */

/**
 * External dependencies
 */
// @ts-ignore No types for this exist yet.
import { useIsSiteEditorLoading } from '@wordpress/edit-site/build-module/components/layout/hooks';
import { useCallback, useEffect } from '@wordpress/element';

export const useEditorScroll = ( {
	editorSelector,
	scrollDirection = 'bottom',
}: {
	editorSelector: string;
	scrollDirection: 'top' | 'bottom';
} ) => {
	const isEditorLoading = useIsSiteEditorLoading();

	const scroll = useCallback( () => {
		const previewContainer =
			document.querySelector< HTMLIFrameElement >( editorSelector );
		if ( previewContainer ) {
			previewContainer.contentWindow?.scrollTo( {
				left: 0,
				top:
					scrollDirection === 'bottom'
						? previewContainer.contentDocument?.body.scrollHeight ||
						  0
						: 0,
			} );
		}
	}, [ scrollDirection, editorSelector ] );

	useEffect( () => {
		// Scroll to the bottom of the preview when the editor is done loading.
		if ( ! isEditorLoading ) {
			scroll();
		}
	}, [ isEditorLoading, scroll ] );

	return {
		scroll,
	};
};
