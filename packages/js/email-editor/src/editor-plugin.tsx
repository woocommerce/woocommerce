/**
 * External dependencies
 */
import { useEffect, useState } from 'react';

/**
 * Internal dependencies
 */
import { SendPreview } from './components/preview';
import { StylesSidebar } from './components/styles-sidebar';
import { createStore } from './store';

export const EditorPlugin = () => {
	const [ isInitialized, setIsInitialized ] = useState( false );
	useEffect( () => {
		createStore();
		setIsInitialized( true );
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
