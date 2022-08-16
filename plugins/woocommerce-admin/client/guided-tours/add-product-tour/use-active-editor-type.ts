/**
 * External dependencies
 */
import { useEffect, useRef, useState } from '@wordpress/element';

/* TinyMCE or HTML (textarea) editor  */
export type EditorType = 'tmce' | 'html';

export const useActiveEditorType = ( {
	editorWrapSelector,
}: {
	editorWrapSelector: string;
} ) => {
	const editor = useRef( document.querySelector( editorWrapSelector ) );
	if ( ! editor ) {
		// eslint-disable-next-line no-console
		console.warn( `Editor Wrap ${ editorWrapSelector } not found` );
	}

	const [ activeEditor, setActiveEditor ] = useState< EditorType >(
		editor.current && editor.current.classList.contains( 'html-active' )
			? 'html'
			: 'tmce' // set to "tmce" if it's active or editor is not found
	);

	useEffect( () => {
		const onClickEditorTab = ( e: MouseEvent ) => {
			if ( e.target ) {
				setActiveEditor(
					( e.target as HTMLElement ).classList.contains(
						'switch-html'
					)
						? 'html'
						: 'tmce'
				);
			}
		};
		const tmceTab = document.querySelector< HTMLButtonElement >(
			`${ editorWrapSelector } .switch-tmce`
		);
		tmceTab?.addEventListener( 'click', onClickEditorTab );

		const htmlTab = document.querySelector< HTMLButtonElement >(
			`${ editorWrapSelector } .switch-html`
		);
		htmlTab?.addEventListener( 'click', onClickEditorTab );

		return () => {
			tmceTab?.removeEventListener( 'click', onClickEditorTab );
			htmlTab?.removeEventListener( 'click', onClickEditorTab );
		};
	}, [ editorWrapSelector ] );

	return {
		activeEditor,
		isTmce: activeEditor === 'tmce',
		isHtml: activeEditor === 'html',
	};
};
