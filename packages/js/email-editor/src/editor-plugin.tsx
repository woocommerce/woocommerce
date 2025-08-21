/**
 * External dependencies
 */
import { useLayoutEffect, useEffect, useState } from 'react';
import { select, dispatch, useDispatch } from '@wordpress/data';
import { store as editorStore } from '@wordpress/editor';

/**
 * Internal dependencies
 */
import { SendPreview } from './components/preview';
import { StylesSidebar } from './components/styles-sidebar';
import { initBlocks } from './blocks';
import { createStore, storeName } from './store';
import { useEmailCss } from './hooks';

const Editor = () => {
	// Push email styles to editor settings.// Push email styles to editor settings.
	// Set styles directly to settings overwriting the automatically loaded theme styles
	const [ styles ] = useEmailCss();
	const { updateEditorSettings } = useDispatch( editorStore );
	useEffect( () => {
		console.log( 'UPDATING Styles', styles );
		if ( ! styles ) {
			return;
		}
		const editorSettings = select( editorStore ).getEditorSettings();
		updateEditorSettings( {
			...editorSettings,
			styles,
		} );
	}, [ styles ] );

	return (
		<>
			<SendPreview />
			<StylesSidebar />
		</>
	);
};

export const EditorPlugin = () => {
	const [ isInitialized, setIsInitialized ] = useState( false );

	useLayoutEffect( () => {
		const cleanups = [];
		createStore();
		cleanups.push( initBlocks() );
		// Handle editor settings - backup original settings and set initial email editor settings
		const initialEmailEditorSettings =
			select( storeName ).getInitialEditorSettings();
		const backupEditorSettings = select( editorStore ).getEditorSettings();
		console.log(
			'emailEditorSettings',
			initialEmailEditorSettings,
			backupEditorSettings
		);
		dispatch( editorStore ).updateEditorSettings(
			initialEmailEditorSettings
		);
		setIsInitialized( true );
		return () => {
			console.log( 'Unmounting editor plugin' );
			cleanups.forEach( ( cleanup ) => cleanup() );
			dispatch( editorStore ).updateEditorSettings(
				backupEditorSettings
			);
		};
	}, [] );

	if ( ! isInitialized ) {
		return null;
	}

	return <Editor />;
};
