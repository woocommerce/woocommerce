/**
 * Internal dependencies
 */
import { setTinyContent, getTinyContent } from '../utils/tiny-tools';

export const useTinyEditor = ( editorId?: string ) => {
	return {
		setContent: ( str: string ) => setTinyContent( str, editorId ),
		getContent: () => getTinyContent( editorId ),
	};
};
