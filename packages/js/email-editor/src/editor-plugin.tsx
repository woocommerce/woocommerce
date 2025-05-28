/**
 * External dependencies
 */
import { registerPlugin } from '@wordpress/plugins';
import { store as editorStore } from '@wordpress/editor';
import { select, useDispatch, useSelect } from '@wordpress/data';
import { useEffect } from '@wordpress/element';
import { createStore } from './store';

/**
 * Internal dependencies
 */
import { useEmailCss } from './hooks';

const EmailEditorPlugin = () => {
	const { updateEditorSettings } = useDispatch( editorStore );
	const [ styles ] = useEmailCss();
	const { editedPostId } = useSelect( ( sel ) => {
		return {
			editedPostId: sel( editorStore ).getCurrentPostId(),
		};
	} );

	// Push email styles to editor settings.
	// Set styles directly to settings overwriting the automatically loaded theme styles
	useEffect( () => {
		if ( ! styles ) {
			return;
		}
		const editorSettings = select( editorStore ).getEditorSettings();
		updateEditorSettings( {
			...editorSettings,
			styles,
		} );
	}, [ styles, editedPostId, updateEditorSettings ] );

	return null;
};

export function initializeEmailEditorPlugin() {
	registerPlugin( 'email-editor', {
		render: EmailEditorPlugin,
	} );
	createStore();

}
