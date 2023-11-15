/**
 * External dependencies
 */
import { useState, useEffect } from 'react';

/**
 * Internal dependencies
 */
import {
	getTinyContentObject,
	setTinyContent,
	getTinyContent,
	TinyContent,
} from '../utils/tiny-tools';

export const useTinyEditor = ( editorId?: string ) => {
	const [ editor, setEditor ] = useState< TinyContent | null >( null );

	useEffect( () => {
		const fetchEditor = () => {
			const editorInstance = getTinyContentObject( editorId );
			if ( editorInstance ) {
				setEditor( editorInstance );
			}
		};

		window.addEventListener( 'load', fetchEditor );

		return () => {
			document.removeEventListener( 'load', fetchEditor );
		};
	}, [ editorId ] );

	return {
		setContent: ( str: string ) => setTinyContent( str, editorId ),
		getContent: () => getTinyContent( editorId ),
		getEditorObject: () => editor,
	};
};
