/**
 * External dependencies
 */
import { useLayoutEffect, useState } from 'react';
import { select, dispatch } from '@wordpress/data';
import { store as editorStore } from '@wordpress/editor';

/**
 * Internal dependencies
 */
import { SendPreview } from './components/preview';
import { StylesSidebar } from './components/styles-sidebar';
import { initBlocks } from './blocks';
import { createStore, storeName } from './store';

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

	return (
		<>
			<SendPreview />
			<StylesSidebar />
		</>
	);
};
