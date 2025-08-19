/**
 * External dependencies
 */
import { useEffect, useState } from 'react';

/**
 * Internal dependencies
 */
import { SendPreview } from './components/preview';
import { StylesSidebar } from './components/styles-sidebar';
import { initBlocks } from './blocks';
import { createStore } from './store';

export const EditorPlugin = () => {
	const [ isInitialized, setIsInitialized ] = useState( false );
	useEffect( () => {
		const cleanups = [];
		createStore();
		cleanups.push( initBlocks() );
		setIsInitialized( true );
		return () => {
			console.log( 'Unmounting editor plugin' );
			cleanups.forEach( ( cleanup ) => cleanup() );
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
