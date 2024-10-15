/**
 * External dependencies
 */
import { useEffect } from '@wordpress/element';

type addClassToIframeWhenChildFocusProps = {
	iframeSelector: string;
	childSelector: string;
	className: string;
};

const addClassToIframeWhenChildFocus = ( {
	iframeSelector,
	childSelector,
	className,
}: addClassToIframeWhenChildFocusProps ) => {
	const iframe =
		document.querySelector< HTMLIFrameElement >( iframeSelector );
	const innerDoc =
		iframe?.contentDocument ||
		( iframe?.contentWindow && iframe?.contentWindow.document );

	if ( innerDoc ) {
		const onFocus = () => iframe?.classList.add( className );
		const onBlur = () => iframe?.classList.remove( className );

		const child = innerDoc.querySelector< HTMLElement >( childSelector );
		child?.addEventListener( 'focus', onFocus );
		child?.addEventListener( 'blur', onBlur );

		return () => {
			child?.removeEventListener( 'focus', onFocus );
			child?.removeEventListener( 'blur', onBlur );
		};
	}
	return () => ( {} );
};

export const useTmceIframeFocusStyle = ( {
	iframeSelector,
	isActive,
}: {
	iframeSelector: string;
	isActive: boolean;
} ) => {
	// Add a focus class to tmce iframe when editor is focused.
	useEffect( () => {
		if ( ! isActive ) {
			return;
		}
		const clearIFrameEvent = addClassToIframeWhenChildFocus( {
			iframeSelector: `${ iframeSelector }`,
			childSelector: '#tinymce',
			className: 'focus-within',
		} );
		return () => {
			clearIFrameEvent();
		};
	}, [ isActive, iframeSelector ] );

	return {
		style: isActive
			? `
				${ iframeSelector }.focus-within {
					border: 1.5px solid #007CBA;
				}
				`
			: null,
	};
};
